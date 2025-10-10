<?php

// config for Hwkdo/AzureStorageLaravel
return [
    'default' => env('AZURE_STORAGE_LARAVEL_DEFAULT', 'azure'),

    'connections' => [
        'azure' => [
            'tenant_id' => env('AZURE_STORAGE_TENANT_ID'),
            'client_id' => env('AZURE_STORAGE_CLIENT_ID'),
            'client_secret' => env('AZURE_STORAGE_CLIENT_SECRET'),
            'account_name' => env('AZURE_STORAGE_ACCOUNT_NAME'),
            'container' => env('AZURE_STORAGE_CONTAINER'),
        ],
    ],

    // Azure AI Search configuration
    'ai_search' => [
        'service_name' => env('AZURE_SEARCH_SERVICE_NAME'),
        'admin_api_key' => env('AZURE_SEARCH_ADMIN_API_KEY'),
        'api_version' => env('AZURE_SEARCH_API_VERSION', '2024-07-01'),
        'index_name' => env('AZURE_SEARCH_INDEX_NAME'),
    ],
];
