<?php

return [
    'account' => env('SNOWFLAKE_ACCOUNT', ''),
    'region' => env('SNOWFLAKE_REGION', 'us-east-1'),
    'domain' => env('SNOWFLAKE_DOMAIN', 'snowflakecomputing.com'),
    'api_path' => env('SNOWFLAKE_API_PATH', '/api/v2/statements'),
    'user' => env('SNOWFLAKE_USER', ''),
    'role' => env('SNOWFLAKE_ROLE', ''),
    'warehouse' => env('SNOWFLAKE_WAREHOUSE', ''),
    'database' => env('SNOWFLAKE_DATABASE', ''),
    'schema' => env('SNOWFLAKE_SCHEMA', ''),
    'private_key' => env('SNOWFLAKE_PRIVATE_KEY', ''),
    'public_key' => env('SNOWFLAKE_PUBLIC_KEY', ''),
    'public_fingerprint' => env('SNOWFLAKE_PUBLIC_FINGERPRINT', ''),
];
