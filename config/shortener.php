<?php

declare(strict_types=1);

return [

    // Length of generated codes. 7 base62 characters give ~3.5 trillion combinations.
    'code_length' => (int) env('SHORTENER_CODE_LENGTH', 7),

    // How many times the generator may draw a new code after a collision before giving up.
    'code_max_attempts' => 5,

    // Longest original URL we accept. Browsers stop around 2000 characters anyway.
    'max_url_length' => 2048,

    // Seconds a resolved link stays in Redis. Invalidated early on any change.
    'cache_ttl' => (int) env('SHORTENER_CACHE_TTL', 3600),

    // Click rows older than this are removed by the purge command.
    'click_retention_days' => (int) env('SHORTENER_CLICK_RETENTION_DAYS', 365),

    // Paths that must never be handed out as a short code because they are (or may become) routes.
    'reserved_aliases' => [
        'api', 'admin', 'login', 'logout', 'register', 'me', 'up', 'health',
        'docs', 'links', 'stats', 'qr', 'www', 'static', 'assets', 'storage',
    ],

    // "none" accepts every URL. Swap in a real driver to check against a blocklist service.
    'blocklist_driver' => env('SHORTENER_BLOCKLIST_DRIVER', 'none'),

];
