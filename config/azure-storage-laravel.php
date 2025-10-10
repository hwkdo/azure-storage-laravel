<?php

// config for Hwkdo/AzureStorageLaravel
return [
    'default' => env('AZURE_STORAGE_LARAVEL_DEFAULT', 'azure'),

    'connections' => [
        'azure' => [
            'account_name' => env('AZURE_STORAGE_LARAVEL_ACCOUNT_NAME', ''),
            'account_key' => env('AZURE_STORAGE_LARAVEL_ACCOUNT_KEY', ''),
            'endpoint_suffix' => env('AZURE_STORAGE_LARAVEL_ENDPOINT_SUFFIX', 'core.windows.net'),
            'container' => env('AZURE_STORAGE_LARAVEL_CONTAINER', ''),
            'url_expiration_interval' => env('AZURE_STORAGE_LARAVEL_URL_EXPIRATION_INTERVAL', '+1 hour'), // see https://www.php.net/manual/en/datetime.formats.php
        ],
    ],
];
