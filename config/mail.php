<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'gmail' => [
            'transport' => 'smtp',
            'host' => env('MAIL_GMAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_GMAIL_PORT', 587),
            'encryption' => env('MAIL_GMAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_GMAIL_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_GMAIL_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'hostinger' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOSTINGER_HOST', 'smtp.hostinger.com'),
            'port' => env('MAIL_HOSTINGER_PORT', 465),
            'encryption' => env('MAIL_HOSTINGER_ENCRYPTION', 'ssl'),
            'username' => env('MAIL_HOSTINGER_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_HOSTINGER_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'mailtrap' => [
            'transport' => 'smtp',
            'host' => env('MAILTRAP_HOST', 'sandbox.smtp.mailtrap.io'),
            'port' => env('MAILTRAP_PORT', 2525),
            'encryption' => env('MAILTRAP_ENCRYPTION', 'tls'),
            'username' => env('MAILTRAP_USERNAME'),
            'password' => env('MAILTRAP_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => null,
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'mailgun' => [
            'transport' => 'mailgun',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('MAIL_FAILOVER_MAILERS', 'smtp,log'))
            ))),
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Inbox
    |--------------------------------------------------------------------------
    |
    | Contact form submissions are routed here. By default this is the same
    | Google Workspace mailbox used as the sender for YallaSpare mail.
    |
    */

    'support' => [
        'address' => env('MAIL_SUPPORT_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@yallaspare.com')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
