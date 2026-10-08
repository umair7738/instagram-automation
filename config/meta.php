<?php

return [
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    'instagram_app_id' => env('META_INSTAGRAM_APP_ID'),
    'instagram_app_secret' => env('META_INSTAGRAM_APP_SECRET'),
    'verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
    'graph_version' => env('META_GRAPH_VERSION', 'v26.0'),
    'api_base_url' => env('META_GRAPH_API_URL', 'https://graph.facebook.com'),
    'oauth_redirect' => env('META_OAUTH_REDIRECT_URI'),
    'instagram_graph_version' => env('META_INSTAGRAM_GRAPH_VERSION', env('META_GRAPH_VERSION', 'v26.0')),
    'instagram_api_base_url' => env('META_INSTAGRAM_API_URL', 'https://graph.instagram.com'),
    'instagram_oauth_base_url' => env('META_INSTAGRAM_OAUTH_URL', 'https://www.instagram.com'),
    'instagram_token_base_url' => env('META_INSTAGRAM_TOKEN_URL', 'https://api.instagram.com'),
    'instagram_oauth_redirect' => env(
        'META_INSTAGRAM_OAUTH_REDIRECT_URI',
        rtrim((string) env('META_PUBLIC_BASE_URL', ''), '/').'/meta/callback/instagram',
    ),
    // Webhooks can arrive from either the legacy Facebook Login app or the
    // Instagram Login app while accounts are migrated.
    'webhook_secrets' => array_values(array_filter([
        env('META_APP_SECRET'),
        env('META_INSTAGRAM_APP_SECRET'),
    ])),
];
