<?php

return [
    'dashboard_cache_ttl' => (int) env('DASHBOARD_CACHE_TTL', 60),
    'notification_cache_ttl' => (int) env('NOTIFICATION_CACHE_TTL', 30),
    'products_meta_cache_ttl' => (int) env('PRODUCTS_META_CACHE_TTL', 300),
];

