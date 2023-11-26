<?php

return [
    'plugin' => [
        'name' => 'MailRelay',
        'description' => 'Provides MailRelay integration services'
    ],

    'permissions' => [
        'configure' => 'Configure MailRelay API access',
    ],

    'settings' => [
        'description' => 'Configure MailRelay API access.',
        'account_name' => 'MailRelay account name',
        'api_key' => 'MailRelay API key',
        'api_key_comment' => 'Get an API Key from https://your-account-name.ipzmarketing.com/admin/api_keys/',
    ]
];