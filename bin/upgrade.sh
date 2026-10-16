#!/bin/bash

# Upgrade the Recency Web install in place.
#
# Run it straight from the repo:
#
#   sudo bash -c "$(curl -fsSL "https://raw.githubusercontent.com/deforay/recency-web/master/bin/upgrade.sh?v=$(date +%s)")"
#
# Or from an install that already has it:
#
#   sudo /var/www/recency-web/bin/upgrade.sh
#
# Pass the script as an argument, as above. Piping it in with `curl ... | sudo bash`
# breaks the prompts: where sudoers sets use_pty (the default on Ubuntu 22.04 and
# sudo 1.9.14+), the answer goes to the curl pipe and never reaches the script.
#
# What it does, in order:
#   1. Backs up the database, config/autoload and the code.
#   2. Refreshes a shallow source mirror of master and rsyncs it over the install.
#      .git never reaches the install. VERSION.txt records the deployed commit.
#   3. Keeps every existing file in config/autoload, and config/acl.config.php.
#      This install's settings live there, including in tracked files such as
#      global.php and custom.global.php, and the Roles page saves permissions to
#      acl.config.php. The deploy only adds files that are new and reports
#      tracked files that differ from the repo.
#   4. Runs composer install, then bin/migrate, then checks bin/migrate --status.
#   5. Sets ownership, creates the runtime folders, adds the crunz cron line when
#      the code has cron.sh, and reloads Apache and php-fpm.
#
# It does no system-level work: PHP, MySQL and Apache belong to the server setup.
#
# Restore a database backup with:
#   zstd -dc /var/recency-web-backup/db/NAME.sql.zst | mysql   (or gunzip -c for .sql.gz)
#
# Usage:
#   sudo bin/upgrade.sh [-p PATH] [-b] [-y]
#
# Options:
#   -p PATH  the install to upgrade (default /var/www/recency-web)
#   -b       skip the database and code backups (config/autoload is always saved)
#   -y       take the default for every prompt. The defaults are the safe answers,
#            so a failed backup stops the upgrade.
#
# Env:
#   RECENCY_SRC_DIR   source mirror location (default /usr/local/lib/recency-web/src)

set -o pipefail

if [ "$EUID" -ne 0 ]; then
    echo "Need admin privileges for this script. Run it with sudo."
    exit 1
fi

stamp="$(date +'%Y%m%d-%H%M%S')"
log_file="/tmp/recency-web-upgrade-${stamp}.log"

REPO_GIT_URL="https://github.com/deforay/recency-web.git"
REPO_TARBALL_URL="https://codeload.github.com/deforay/recency-web/tar.gz/refs/heads/master"
REPO_SCRIPT_URL="https://raw.githubusercontent.com/deforay/recency-web/master/bin/upgrade.sh"
SRC_DIR="${RECENCY_SRC_DIR:-/usr/local/lib/recency-web/src}"
BACKUP_ROOT="/var/recency-web-backup"

# Folders the app writes at runtime. The deploy never touches them.
RUNTIME_DIRS=(data/cache data/logs public/uploads public/temporary backup)

# Tracked files the app rewrites. The Roles page saves role permissions to
# config/acl.config.php, so the deploy keeps the install's copy, as it does for
# config/autoload.
KEPT_FILES=(config/acl.config.php)

# ---------------------------------------------------------------- helpers ---

print() {
    case "$1" in
        error)   printf "\033[1;91mError:\033[0m %s\n" "$2" ;;
        success) printf "\033[1;92mDone:\033[0m %s\n" "$2" ;;
        warning) printf "\033[1;93mWarning:\033[0m %s\n" "$2" ;;
        info)    printf "\033[1;96mInfo:\033[0m %s\n" "$2" ;;
        header)  printf "\n\033[1;96m===== %s =====\033[0m\n\n" "$2" ;;
        *)       printf "%s\n" "$2" ;;
    esac
}

log_action() {
    echo "$(date +'%Y-%m-%d %H:%M:%S') - $1" >>"$log_file"
}

say() {
    print "$1" "$2"
    log_action "$2"
}

die() {
    say error "$1"
    exit 1
}

# A terminal that takes the prompt but never returns an answer. See ask_yes_no.
prompt_is_deaf=false

# Prompts read from the terminal, not stdin, because the script may arrive on
# stdin. Without a terminal at all (cron, CI) the default wins.
ask_yes_no() {
    local question="$1" default="${2:-no}" answer

    if [ "$assume_defaults" = true ] || [ "$prompt_is_deaf" = true ]; then
        print info "${question}? [auto: ${default}]"
        [ "$default" = "yes" ]
        return
    fi

    # Real stdin first. Open /dev/tty rather than test for it: it exists under
    # cron and in containers but cannot be opened there.
    if [ -t 0 ]; then
        exec 3<&0
    elif ! { exec 3<>/dev/tty; } 2>/dev/null; then
        print info "${question}? [auto: ${default}]"
        [ "$default" = "yes" ]
        return
    fi

    printf "%s? [default: %s, auto in 60s] " "$question" "$default" >&3
    if ! read -r -t 60 answer <&3; then
        printf '\n' >&3
        # Under `curl | sudo bash` with use_pty the prompt shows and the typing
        # echoes, but the answer never arrives. Stop waiting a minute per question.
        prompt_is_deaf=true
        say warning "No answer reached this script in 60s. Taking the default for this and every later question."
        say warning "If an answer was typed, the pipe swallowed it. Run it as: sudo bash -c \"\$(curl -fsSL ${REPO_SCRIPT_URL})\""
        answer="$default"
    fi
    exec 3>&-

    answer="$(printf '%s' "${answer:-$default}" | tr '[:upper:]' '[:lower:]')"
    [ "$answer" = "y" ] || [ "$answer" = "yes" ]
}

as_web() {
    sudo -u "$web_user" "$@"
}

# safe.directory stops git refusing a mirror it does not own. The low-speed
# limit stops a dead link from hanging the upgrade.
run_git() {
    git -c safe.directory='*' -c http.lowSpeedLimit=1000 -c http.lowSpeedTime=60 "$@"
}

# A Recency Web tree, as opposed to the other Laminas apps on the same server
# (odkdash shares recrbc). bin/migrate is not required: installs older than it
# are the ones that need this script.
is_recency_path() {
    [ -f "$1/composer.json" ] &&
    grep -q '"deforay/recency-web"' "$1/composer.json" 2>/dev/null &&
    [ -f "$1/public/index.php" ]
}

why_not_recency() {
    if [ ! -f "$1/composer.json" ]; then
        echo "no composer.json there"
    elif ! grep -q '"deforay/recency-web"' "$1/composer.json" 2>/dev/null; then
        echo "composer.json is not deforay/recency-web"
    else
        echo "no public/index.php"
    fi
}

fetch_url() {
    local url="$1" dest="$2"
    if command -v curl &>/dev/null; then
        curl -fsSL --retry 3 -o "$dest" "$url" 2>>"$log_file"
    elif command -v wget &>/dev/null; then
        wget -q -O "$dest" "$url" 2>>"$log_file"
    else
        return 1
    fi
}

# The merged config decides the database and its credentials: the DSN is in
# global.php and the user and password in local.php. This reads the files in the
# app's own glob order. Prints name, user, password, host and port, one per line.
resolve_db_config() {
    (cd "$app_path" && as_web php -r '
        $files = glob("config/autoload/{{,*.}global,{,*.}local}.php", GLOB_BRACE) ?: [];
        $db = [];
        foreach ($files as $file) {
            $conf = include $file;
            if (is_array($conf) && isset($conf["db"]) && is_array($conf["db"])) {
                $db = array_merge($db, $conf["db"]);
            }
        }
        $name = $host = $port = "";
        if (!empty($db["dsn"])) {
            foreach (["dbname" => "name", "host" => "host", "port" => "port"] as $key => $var) {
                if (preg_match("/" . $key . "=([^;]+)/", $db["dsn"], $m)) {
                    $$var = trim($m[1]);
                }
            }
        }
        foreach ([$name, $db["username"] ?? "", $db["password"] ?? "", $host, $port] as $value) {
            echo str_replace(["\r", "\n"], "", (string) $value), "\n";
        }
    ' 2>/dev/null)
}

# The password goes in a 0600 option file, never on the command line where ps
# shows it. Values are quoted because a password may contain # or ;.
write_defaults_file() {
    local user="$1" pass="$2" host="$3" port="$4" escaped

    defaults_file="$(mktemp)"
    chmod 600 "$defaults_file"
    {
        printf '[client]\n'
        escaped="${user//\\/\\\\}"; printf 'user="%s"\n' "${escaped//\"/\\\"}"
        if [ -n "$pass" ]; then
            escaped="${pass//\\/\\\\}"; printf 'password="%s"\n' "${escaped//\"/\\\"}"
        fi
        # localhost means the socket to mysqldump, as it does to PDO
        [ -n "$host" ] && [ "$host" != "localhost" ] && printf 'host="%s"\n' "$host"
        [ -n "$port" ] && printf 'port=%s\n' "$port"
    } >"$defaults_file"
}

compress() {
    if command -v zstd &>/dev/null; then zstd -q -T0 -3; else gzip -c; fi
}

# Dumps as root over the unix socket first, which needs no password where root
# uses auth_socket. Otherwise as the app's own user, which may lack PROCESS and
# the rights to read routines and triggers, hence the narrowing retries.
dump_database() {
    local ext rc=1
    local -a attempt
    command -v zstd &>/dev/null && ext="sql.zst" || ext="sql.gz"
    db_backup="${BACKUP_ROOT}/db/${db_name}-${stamp}.${ext}"

    # Root over the socket reaches only this server's MySQL, so try it only when
    # the app's database is here too. Otherwise a local database with the same
    # name would be dumped in place of the one bin/migrate changes.
    # --no-defaults so no option file (root's .my.cnf) can point it elsewhere.
    if [[ "${db_host:-localhost}" =~ ^(localhost|127\.0\.0\.1|::1)$ ]] && [[ "${db_port:-3306}" == "3306" ]] &&
        mysqldump --no-defaults --user=root --protocol=socket \
            --single-transaction --routines --triggers --databases "$db_name" 2>>"$log_file" |
        compress >"$db_backup"; then
        return 0
    fi

    [ -n "$db_user" ] || { rm -f "$db_backup"; return 1; }
    say info "Dumping as ${db_user}, the user in config/autoload."
    write_defaults_file "$db_user" "$db_pass" "$db_host" "$db_port"

    attempt=(--routines --triggers --no-tablespaces)
    while :; do
        if mysqldump --defaults-file="$defaults_file" --single-transaction "${attempt[@]}" \
            --databases "$db_name" 2>>"$log_file" | compress >"$db_backup"; then
            [ ${#attempt[@]} -eq 1 ] &&
                say warning "Dumped without routines or triggers: ${db_user} cannot read them."
            rc=0
            break
        fi
        [ ${#attempt[@]} -gt 1 ] || break
        attempt=(--no-tablespaces)
    done

    # The option file holds the password in clear.
    rm -f "$defaults_file"
    defaults_file=""
    [ $rc -eq 0 ] || rm -f "$db_backup"
    return $rc
}

installed_ref() {
    if [ -f "$app_path/VERSION.txt" ]; then
        head -1 "$app_path/VERSION.txt"
    else
        echo "unknown"
    fi
}

trap 'rc=$?; [ -n "${defaults_file:-}" ] && rm -f "$defaults_file"; [ $rc -ne 0 ] && print info "Full log: '"$log_file"'"; exit $rc' EXIT

# ----------------------------------------------------------------- source ---

src_dir=""
temp_dir=""

# Marks a folder as this script's own mirror. The script deletes and re-clones
# only a folder that carries it.
MIRROR_MARKER=".recency-upgrade-mirror"

# Refuse a mirror path that is, holds, or sits inside something that matters
check_mirror_path() {
    local dir
    dir="$(realpath -m "$SRC_DIR")"
    case "$dir" in
        / | /usr | /usr/local | /usr/local/lib | /var | /var/www | /var/www/* | /home | /root | /tmp)
            die "RECENCY_SRC_DIR=${SRC_DIR} is not a safe place for the source mirror." ;;
    esac
    for protected in "$app_path" "$BACKUP_ROOT"; do
        case "$dir/" in "$protected"/* | "$protected/") die "RECENCY_SRC_DIR=${SRC_DIR} overlaps ${protected}." ;; esac
        case "$protected/" in "$dir"/*) die "RECENCY_SRC_DIR=${SRC_DIR} contains ${protected}." ;; esac
    done
    if [ -d "$dir" ] && [ -n "$(ls -A "$dir" 2>/dev/null)" ] && [ ! -f "$dir/$MIRROR_MARKER" ]; then
        die "${dir} exists and is not this script's source mirror (no ${MIRROR_MARKER}). Move it or set RECENCY_SRC_DIR."
    fi
    SRC_DIR="$dir"
}

# A persistent shallow mirror, so each run after the first fetches only what
# changed. The tarball is the fallback for a server without git.
acquire_source() {
    print header "Downloading Recency Web"
    temp_dir="$(mktemp -d)"

    if command -v git &>/dev/null && [ -d "$SRC_DIR/.git" ]; then
        print info "Updating the source mirror..."
        if run_git -C "$SRC_DIR" fetch --depth 1 origin master >>"$log_file" 2>&1 &&
            run_git -C "$SRC_DIR" reset --hard FETCH_HEAD >>"$log_file" 2>&1 &&
            run_git -C "$SRC_DIR" clean -fd -e "$MIRROR_MARKER" >>"$log_file" 2>&1; then
            run_git -C "$SRC_DIR" gc --prune=now --quiet >>"$log_file" 2>&1 || true
            src_dir="$SRC_DIR"
            say success "Source mirror updated."
        else
            say warning "Fetch failed. Cloning the mirror again."
            rm -rf "$SRC_DIR"
        fi
    fi

    if [ -z "$src_dir" ] && command -v git &>/dev/null; then
        print info "Cloning master into the source mirror..."
        mkdir -p "$(dirname "$SRC_DIR")"
        rm -rf "$SRC_DIR"
        if run_git clone --depth 1 --single-branch --branch master \
            "$REPO_GIT_URL" "$SRC_DIR" >>"$log_file" 2>&1; then
            touch "$SRC_DIR/$MIRROR_MARKER"
            src_dir="$SRC_DIR"
            say success "Source cloned."
        else
            say warning "git clone failed. See ${log_file}."
        fi
    fi

    if [ -z "$src_dir" ]; then
        print info "Falling back to the tarball..."
        if fetch_url "$REPO_TARBALL_URL" "$temp_dir/master.tar.gz" &&
            gzip -t "$temp_dir/master.tar.gz" 2>>"$log_file" &&
            tar -xzf "$temp_dir/master.tar.gz" -C "$temp_dir" 2>>"$log_file" &&
            [ -d "$temp_dir/recency-web-master" ]; then
            src_dir="$temp_dir/recency-web-master"
            say success "Source downloaded as a tarball."
        fi
    fi

    [ -n "$src_dir" ] || return 1

    # .git never reaches the install, so VERSION.txt is how it knows what it runs
    if [ -d "$src_dir/.git" ]; then
        run_git -C "$src_dir" rev-parse --short HEAD >"$src_dir/VERSION.txt" 2>/dev/null || true
    else
        echo "tarball-${stamp}" >"$src_dir/VERSION.txt"
    fi
}

# ------------------------------------------------------------------ flags ---

app_path=""
skip_backups=false
assume_defaults=false
defaults_file=""

while getopts ":p:by" opt; do
    case $opt in
        p) app_path="$OPTARG" ;;
        b) skip_backups=true ;;
        y) assume_defaults=true ;;
        *) die "Unknown option. Usage: sudo bin/upgrade.sh [-p PATH] [-b] [-y]" ;;
    esac
done

for cmd in php composer rsync mysqldump; do
    command -v "$cmd" &>/dev/null || die "${cmd} is not installed."
done

app_path="${app_path:-/var/www/recency-web}"
app_path="$(cd "$app_path" 2>/dev/null && pwd)" || die "Path not found: ${app_path}"
is_recency_path "$app_path" ||
    die "${app_path} does not look like a Recency Web install ($(why_not_recency "$app_path"))."

web_user="www-data"
id "$web_user" &>/dev/null || web_user="$(stat -c '%U' "$app_path/public/index.php")"

export COMPOSER_HOME="${COMPOSER_HOME:-/var/www/.composer}"
mkdir -p "$COMPOSER_HOME"
chown -R "$web_user":"$web_user" "$COMPOSER_HOME"

print header "Recency Web upgrade"
say info "Install: ${app_path}"
say info "Currently at: $(installed_ref)"
say info "Web user: ${web_user}"
say info "Log: ${log_file}"

# ---------------------------------------------------------------- backups ---

print header "Backups"
mkdir -p "$BACKUP_ROOT/db" "$BACKUP_ROOT/config" "$BACKUP_ROOT/code"
chmod 700 "$BACKUP_ROOT"

config_backup="${BACKUP_ROOT}/config/autoload-${stamp}.tar.gz"
tar -czf "$config_backup" -C "$app_path" config/autoload ||
    die "Could not archive config/autoload."
say success "Configuration backed up to ${config_backup}"

{ read -r db_name; read -r db_user; read -r db_pass; read -r db_host; read -r db_port; } < <(resolve_db_config)

if [ "$skip_backups" = true ]; then
    say info "Skipping the database and code backups (-b)."
else
    if [ -z "$db_name" ]; then
        say warning "Could not read the database name from config/autoload."
        ask_yes_no "Continue without a database backup" "no" || die "Stopped: no database backup."
    else
        print info "Dumping database ${db_name}..."
        if dump_database; then
            say success "Database backed up to ${db_backup} ($(du -h "$db_backup" | cut -f1))"
        else
            say warning "Database dump failed. See ${log_file}."
            ask_yes_no "Continue without a database backup" "no" || die "Stopped: no database backup."
        fi
    fi

    # The code is small without vendor/ and the runtime folders, and it is the
    # only way back to the old version: the install has no git history.
    code_backup="${BACKUP_ROOT}/code/recency-web-${stamp}.tar.gz"
    excludes=(--exclude=./vendor --exclude=./.git)
    for dir in "${RUNTIME_DIRS[@]}"; do
        excludes+=("--exclude=./${dir}")
    done
    if tar -czf "$code_backup" "${excludes[@]}" -C "$app_path" . 2>>"$log_file"; then
        say success "Code backed up to ${code_backup} ($(du -h "$code_backup" | cut -f1))"
    else
        say warning "Code backup failed. See ${log_file}."
        ask_yes_no "Continue without a code backup" "no" || die "Stopped: no code backup."
    fi
fi

# ----------------------------------------------------------------- deploy ---

ref_before="$(installed_ref)"

check_mirror_path
acquire_source || die "Could not get the source (mirror, clone and tarball all failed)."

print header "Deploying"
# No --delete: files the install has and the repo does not are left alone.
# -K keeps a symlinked folder (uploads on another disk) a symlink.
rsync_excludes=(--exclude='.git' --exclude="$MIRROR_MARKER" --exclude='config/autoload/' --exclude='vendor/')
for dir in "${RUNTIME_DIRS[@]}"; do
    rsync_excludes+=("--exclude=${dir}/")
done
for file in "${KEPT_FILES[@]}"; do
    rsync_excludes+=("--exclude=/${file}")
done
rsync -a -K "${rsync_excludes[@]}" "$src_dir/" "$app_path/" 2>>"$log_file" ||
    die "rsync deploy failed. See ${log_file}."

# config/autoload: add files that are new, never overwrite one that exists
rsync -a --ignore-existing "$src_dir/config/autoload/" "$app_path/config/autoload/" 2>>"$log_file" ||
    die "Could not add new files to config/autoload."
for file in "$src_dir"/config/autoload/*.php; do
    name="$(basename "$file")"
    if ! cmp -s "$file" "$app_path/config/autoload/$name"; then
        say warning "config/autoload/${name} differs from the repo and was kept. Compare with: diff ${file} ${app_path}/config/autoload/${name}"
    fi
done

for file in "${KEPT_FILES[@]}"; do
    if [ ! -e "$app_path/$file" ]; then
        cp -a "$src_dir/$file" "$app_path/$file" || die "Could not add ${file}."
    elif ! cmp -s "$src_dir/$file" "$app_path/$file"; then
        say warning "${file} differs from the repo and was kept (the app saves role permissions there). Compare with: diff ${src_dir}/${file} ${app_path}/${file}"
    fi
done

ref_after="$(installed_ref)"
if [ "$ref_before" = "$ref_after" ]; then
    say info "Already at ${ref_after}."
else
    say success "Deployed ${ref_before} -> ${ref_after}"
fi

for dir in "${RUNTIME_DIRS[@]}"; do
    mkdir -p "$app_path/$dir"
done

# Before composer, which runs as the web user
chown -R "$web_user":"$web_user" "$app_path"

# ----------------------------------------------------------- dependencies ---

print header "Dependencies"
cd "$app_path" || die "Could not enter ${app_path}."

# Every run, not only when the source changed: after a failed install the new
# composer.lock is already on disk, so a changed-lock check would skip the retry.
# With nothing to change, composer install only checks and rebuilds the autoloader.
as_web composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader 2>&1 |
    tee -a "$log_file" || die "composer install failed. Run this script again to retry."
say success "Dependencies installed."

# ------------------------------------------------------------- migrations ---

print header "Database migrations"
chmod +x bin/migrate bin/console bin/upgrade.sh 2>/dev/null
as_web php bin/migrate 2>&1 | tee -a "$log_file" ||
    die "Migrations failed. The database backup is ${db_backup:-not taken}. See ${log_file}."

# --status confirms the schema reached this code's version
status_output="$(as_web php bin/migrate --status 2>&1)"
status_rc=$?
printf '%s\n' "$status_output" | tee -a "$log_file"
if [ $status_rc -ne 0 ]; then
    die "Could not check the schema version (bin/migrate --status failed). See ${log_file}."
fi
if ! printf '%s' "$status_output" | grep -qF "database is up to date"; then
    die "Migrations are still pending. Run again: (cd ${app_path} && sudo -u ${web_user} php bin/migrate)"
fi
say success "The schema is at the version this code expects."

# ------------------------------------------------------------ permissions ---

chown -R "$web_user":"$web_user" "$app_path"
for dir in "${RUNTIME_DIRS[@]}"; do
    chmod -R u+rwX,g+rwX "$app_path/$dir"
done
say success "Ownership set to ${web_user}. Runtime folders are writable."

# ------------------------------------------------------------------- cron ---

# Only when the code has a crunz schedule. cron.sh changes to the app folder
# itself, so the crontab line needs no cd.
if [ -f "$app_path/cron.sh" ]; then
    chmod +x "$app_path/cron.sh"
    cron_line="* * * * * ${app_path}/cron.sh >> ${app_path}/data/logs/crunz.log 2>&1"
    current_crontab="$(crontab -u "$web_user" -l 2>/dev/null || true)"
    if printf '%s\n' "$current_crontab" | grep -Fq "${app_path}/cron.sh"; then
        say info "The crunz cron line is already in the ${web_user} crontab."
    else
        {
            if [ -z "$current_crontab" ]; then
                printf 'MAILTO=""\n'
                printf 'PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\n\n'
            else
                printf '%s\n' "$current_crontab"
            fi
            printf '%s\n' "$cron_line"
        } | crontab -u "$web_user" -
        say success "Added the crunz cron line to the ${web_user} crontab."
    fi
fi

[ -n "$temp_dir" ] && rm -rf "$temp_dir"

# --------------------------------------------------------------- web tier ---

print header "Reloading the web server"

# A graceful reload clears mod_php's OPcache, so the new code is served at once
if command -v apache2ctl &>/dev/null; then
    if apache2ctl -t 2>>"$log_file"; then
        if apache2ctl -k graceful 2>>"$log_file" || systemctl reload apache2 2>>"$log_file"; then
            say success "Apache reloaded."
        else
            say warning "Could not reload Apache. Reload it by hand."
        fi
    else
        say warning "The Apache config test failed, so Apache was not reloaded. Fix it, then reload."
    fi
fi

# php-fpm keeps its own OPcache
for unit in $(systemctl list-units --type=service --state=running --no-legend 'php*-fpm.service' 2>/dev/null | awk '{print $1}'); do
    systemctl reload "$unit" 2>>"$log_file" && say success "Reloaded ${unit}."
done

print header "Upgrade complete"
say success "${app_path} is at $(installed_ref)."
print info "Backups: ${BACKUP_ROOT}"
print info "Log: ${log_file}"
