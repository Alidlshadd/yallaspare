<?php
$keys = $argv[1] ?? '';
$keys = $keys ? explode('|', $keys) : ['Payment', 'Paid', 'Failed', 'Refunded', 'Pending'];
foreach (['en', 'ar', 'ku'] as $l) {
    $j = json_decode(file_get_contents(__DIR__ . "/../lang/{$l}.json"), true);
    foreach ($keys as $k) {
        $v = $j[$k] ?? 'MISSING';
        echo "$l | $k => $v\n";
    }
    echo "---\n";
}
