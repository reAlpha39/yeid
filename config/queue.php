<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Options
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of the queue worker globally,
    | affecting all queue connections unless overridden in specific connections.
    |
    */
    'worker_timeout' => env('QUEUE_TIMEOUT', 7200),
    'retry_after' => env('QUEUE_RETRY_AFTER', 7300),
    'max_jobs_before_stopping' => env('QUEUE_MAX_JOBS', 500),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION', null),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => env('DB_QUEUE_RETRY_AFTER', 7300),
            'after_commit' => true, // Changed to true for better data consistency
            'maintenance_mode_queue' => 'maintenance',
            // Added options for better queue management
            'queue_priority' => true,
            'block_for' => null,
            'max_tries' => env('QUEUE_MAX_TRIES', 3),
            'backoff' => [300, 600, 1200], // Progressive delays between retries (5, 10, 20 minutes)
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
        // Added timeout for failed job pruning
        'prune_after' => env('QUEUE_FAILED_PRUNE_AFTER', 7), // Days to keep failed jobs
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Monitor Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used by the queue health monitoring system
    |
    */
    'monitor' => [
        'enabled' => env('QUEUE_MONITOR_ENABLED', true),
        'timeout' => env('QUEUE_MONITOR_TIMEOUT', 300), // 5 minutes
        'max_jobs_in_queue' => env('QUEUE_MONITOR_MAX_JOBS', 1000),
        'alert_threshold' => env('QUEUE_MONITOR_ALERT_THRESHOLD', 500),
    ],
];
