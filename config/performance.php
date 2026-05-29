<?php

return [
    'dashboard_cache_ttl' => (int) env('DASHBOARD_CACHE_TTL', 300),
    'notification_cache_ttl' => (int) env('NOTIFICATION_CACHE_TTL', 300),
    'products_meta_cache_ttl' => (int) env('PRODUCTS_META_CACHE_TTL', 300),

    // Cache key bucket in minutes for dashboard / notifications panels.
    // The bucket is floored to the nearest multiple, so 5 means buckets at
    // :00, :05, :10, ... — every admin gets one cache miss per window per
    // unique filter set, rather than one per minute as before.
    'cache_bucket_minutes' => (int) env('ADMIN_CACHE_BUCKET_MINUTES', 5),
];

