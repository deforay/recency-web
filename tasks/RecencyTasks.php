<?php

/**
 * Recency's scheduled jobs. cron.sh runs crunz every minute, and crunz runs the
 * jobs below that are due. Each job is a bin/console command.
 *
 * A job whose settings are missing is not scheduled at all, so it never fails
 * every tick against empty settings:
 *   - send-mail needs email.host and email.config.username.
 *   - The three InteLIS jobs need vlsm.sync set to true and vlsm.domain set.
 *     They are off by default: most installs enter VL results by hand.
 */

use Crunz\Schedule;

$schedule = new Schedule();

$projectRoot = dirname(__DIR__);

// The app loads custom.global.php, then custom.local.php over it. Read them the
// same way, so a job sees the settings the app sees.
$customConfig = [];
foreach (['custom.global.php', 'custom.local.php'] as $file) {
    $path = "$projectRoot/config/autoload/$file";
    $values = is_file($path) ? require $path : [];
    if (is_array($values)) {
        $customConfig = array_replace_recursive($customConfig, $values);
    }
}

// crunz.yml keeps crunz itself on UTC. Jobs at a set hour run on the server's
// local clock, or on custom config 'timezone' when set.
$timezone = $customConfig['timezone'] ?? (date_default_timezone_get() ?: 'UTC');

$console = PHP_BINARY . " $projectRoot/bin/console";
$logDir = "$projectRoot/data/logs";

// Queued emails, such as result notifications, from the temp_mail table
if (!empty($customConfig['email']['host']) && !empty($customConfig['email']['config']['username'])) {
    $schedule->run("$console send-mail")
        ->everyFiveMinutes()
        ->timezone($timezone)
        ->preventOverlapping()
        ->description('Send queued emails')
        ->appendOutputTo("$logDir/cron-send-mail.log");
}

// Adds rows to system_alerts for VL results and recency tests pending over 30
// days. The Monitoring page shows them. It sends nothing.
$schedule->run("$console system-alerts")
    ->daily()
    ->at('01:30')
    ->timezone($timezone)
    ->preventOverlapping()
    ->description('Record alerts for results pending over 30 days')
    ->appendOutputTo("$logDir/cron-system-alerts.log");

if (($customConfig['vlsm']['sync'] ?? false) === true && !empty($customConfig['vlsm']['domain'])) {
    $schedule->run("$console vlsm-send-requests")
        ->everyTenMinutes()
        ->timezone($timezone)
        ->preventOverlapping()
        ->description('Send VL test requests to InteLIS')
        ->appendOutputTo("$logDir/cron-vlsm-send-requests.log");

    $schedule->run("$console vlsm-receive-results")
        ->everyThirtyMinutes()
        ->timezone($timezone)
        ->preventOverlapping()
        ->description('Fetch VL results from InteLIS')
        ->appendOutputTo("$logDir/cron-vlsm-receive-results.log");

    $schedule->run("$console vlsm-sample-status")
        ->everyThirtyMinutes()
        ->timezone($timezone)
        ->preventOverlapping()
        ->description('Fetch VL sample status from InteLIS')
        ->appendOutputTo("$logDir/cron-vlsm-sample-status.log");
}

return $schedule;
