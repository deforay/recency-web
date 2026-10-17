#!/bin/bash

# Runs the crunz schedule. bin/upgrade.sh adds this line to the www-data crontab:
#
#   * * * * * /var/www/recency-web/cron.sh >> /var/www/recency-web/data/logs/crunz.log 2>&1
#
# The jobs are in tasks/RecencyTasks.php. This script finds vendor/bin/crunz from
# its own folder, so the crontab line stays the same if the app moves.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# Cap every log in data/logs. A job that fails every minute, or an error loop,
# must not fill the disk: a log on this server once grew to 3 TB. Any .log file
# over 10 MB is cut to its newest 5 MB, written back into the same file. The
# file stays the same one, so a writer that has it open, such as the crontab
# redirect to crunz.log during this run, keeps writing to it. Every writer opens
# its log in append mode, so its next line goes to the new end. With 30 daily
# app logs and the job logs, the folder stays under about 370 MB.
LOG_MAX_BYTES=$((10 * 1024 * 1024))
LOG_KEEP_BYTES=$((5 * 1024 * 1024))
for log in "$SCRIPT_DIR"/data/logs/*.log; do
    [ -f "$log" ] && [ ! -L "$log" ] || continue
    if [ "$(wc -c <"$log")" -gt "$LOG_MAX_BYTES" ]; then
        tail -c "$LOG_KEEP_BYTES" "$log" >"$log.trim" && cat "$log.trim" >"$log"
        rm -f "$log.trim"
    fi
done

"$SCRIPT_DIR/vendor/bin/crunz" schedule:run
