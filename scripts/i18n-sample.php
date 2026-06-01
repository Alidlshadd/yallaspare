<?php
// Sample a variety of AR/KU translations for user quality review.
// Focus on longer, content-bearing strings (not short labels) where mistakes are more likely.

$en = json_decode(file_get_contents(__DIR__ . '/../lang/en.json'), true);
$ar = json_decode(file_get_contents(__DIR__ . '/../lang/ar.json'), true);
$ku = json_decode(file_get_contents(__DIR__ . '/../lang/ku.json'), true);

// Pick keys that are medium-length sentences (likely product copy / messages)
$keys = array_filter(array_keys($en), function ($k) use ($en) {
    return strlen($k) >= 30 && strlen($k) <= 140 && str_contains($k, ' ');
});

shuffle($keys);
$sample = array_slice($keys, 0, 20);

echo "=== AR/KU TRANSLATION SAMPLE (20 random sentences) ===\n";
echo "Review each: is the Arabic / Kurdish translation accurate?\n\n";

$i = 0;
foreach ($sample as $k) {
    $i++;
    echo "[$i] EN: $k\n";
    echo "    AR: " . ($ar[$k] ?? '(MISSING)') . "\n";
    echo "    KU: " . ($ku[$k] ?? '(MISSING)') . "\n\n";
}
