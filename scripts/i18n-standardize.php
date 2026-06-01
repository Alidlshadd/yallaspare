<?php
// Standardize chosen canonical terms across ar.json / ku.json.
// Each rule: only replace if the English key contains the trigger word (case-insensitive),
// to avoid replacing the same Arabic/Kurdish word in unrelated contexts.

$enPath = __DIR__ . '/../lang/en.json';
$arPath = __DIR__ . '/../lang/ar.json';
$kuPath = __DIR__ . '/../lang/ku.json';

$en = json_decode(file_get_contents($enPath), true);
$ar = json_decode(file_get_contents($arPath), true);
$ku = json_decode(file_get_contents($kuPath), true);

$rules = [
    // [lang, English trigger (lowercase substring), from, to]
    ['ar', 'deliver', 'التسليم', 'التوصيل'],
    ['ar', 'deliver', 'تسليم',   'توصيل'],
    ['ar', 'coupon',  'القسيمة', 'الكوبون'],
    ['ar', 'coupon',  'قسيمة',   'كوبون'],
    ['ku', 'product', 'بەرهەم',  'کاڵا'],
    ['ku', 'shipping', 'ناردن',   'گەیاندن'],
    ['ku', 'support',  'یارمەتی', 'پشتگیری'],
];

$changes = ['ar' => 0, 'ku' => 0];
$samples = [];

foreach ($en as $k => $_) {
    if (!is_string($k)) continue;
    $lower = mb_strtolower($k);
    foreach ($rules as [$lang, $trigger, $from, $to]) {
        if (!str_contains($lower, $trigger)) continue;
        $store = $lang === 'ar' ? $ar : $ku;
        if (!isset($store[$k]) || !is_string($store[$k])) continue;
        if (!str_contains($store[$k], $from)) continue;
        $new = str_replace($from, $to, $store[$k]);
        if ($new !== $store[$k]) {
            if (count($samples) < 12) {
                $samples[] = [$lang, $k, $store[$k], $new];
            }
            if ($lang === 'ar') $ar[$k] = $new;
            else $ku[$k] = $new;
            $changes[$lang]++;
        }
    }
}

// Preserve order from en.json
$opts = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
$arOrdered = [];
$kuOrdered = [];
foreach ($en as $k => $_) {
    $arOrdered[$k] = $ar[$k] ?? $k;
    $kuOrdered[$k] = $ku[$k] ?? $k;
}
file_put_contents($arPath, json_encode($arOrdered, $opts) . "\n");
file_put_contents($kuPath, json_encode($kuOrdered, $opts) . "\n");

echo "Changes — AR: {$changes['ar']} | KU: {$changes['ku']}\n\n";
echo "Sample changes:\n";
foreach ($samples as [$lang, $k, $old, $new]) {
    echo "[$lang] $k\n  - $old\n  + $new\n\n";
}
