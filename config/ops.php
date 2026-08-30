<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database backups
    |--------------------------------------------------------------------------
    |
    | A backup nobody takes is the one failure with no way back. Everything
    | else in this application can be fixed by deploying again.
    |
    | Backups land inside storage/, which is already kept out of git and out
    | of the web root. On a real server they should also be copied somewhere
    | else — a disk that dies takes the database and the backups with it.
    |
    */

    'backup' => [

        'directory' => env('DB_BACKUP_DIRECTORY', storage_path('backups/db')),

        /*
        | Older dumps are deleted after this many days. Keep enough that a
        | problem noticed late can still be undone.
        */
        'keep_days' => (int) env('DB_BACKUP_KEEP_DAYS', 14),

        /*
        | Left empty, mysqldump is looked up on PATH, which is what a Linux
        | server wants. Set it when the binary is somewhere unusual.
        */
        'mysqldump' => env('DB_BACKUP_MYSQLDUMP', ''),

        /*
        | Seconds before a dump is abandoned. A dump that hangs must not hold
        | the scheduler open until the next one starts.
        */
        'timeout' => (int) env('DB_BACKUP_TIMEOUT', 900),

    ],

    /*
    |--------------------------------------------------------------------------
    | Operational alerts
    |--------------------------------------------------------------------------
    |
    | Queued work fails quietly: an order confirmation that never leaves looks
    | exactly like one that did. These say so out loud.
    |
    | With no address configured, alerts go to the verified super admins.
    |
    */

    'alerts' => [

        'email' => env('OPS_ALERT_EMAIL', ''),

        /*
        | Failures in the last hour before anyone is told. One is worth
        | knowing about; a shop whose mailer is misconfigured produces
        | hundreds, and they are all the same news.
        */
        'failed_job_threshold' => (int) env('OPS_FAILED_JOB_THRESHOLD', 1),

    ],

];
