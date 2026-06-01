<?php
// Find translations that contain the placeholder words "عنصر" (Arabic for "item")
// and "بڕگە" (Kurdish for "item/piece"), which appear to be leftover from a broken
// auto-translation pass that replaced unknown words with these placeholders.

$en = json_decode(file_get_contents(__DIR__ . '/../lang/en.json'), true);
$ar = json_decode(file_get_contents(__DIR__ . '/../lang/ar.json'), true);
$ku = json_decode(file_get_contents(__DIR__ . '/../lang/ku.json'), true);

function countMarker(string $s, string $marker): int {
    return substr_count($s, $marker);
}

$brokenAr = [];
foreach ($ar as $k => $v) {
    if (!is_string($v)) continue;
    $count = countMarker($v, 'عنصر');
    if ($count > 0) {
        $brokenAr[] = ['key' => $k, 'value' => $v, 'count' => $count, 'wordCount' => str_word_count(preg_replace('/\s+/', ' ', $v))];
    }
}

$brokenKu = [];
foreach ($ku as $k => $v) {
    if (!is_string($v)) continue;
    $count = countMarker($v, 'بڕگە');
    if ($count > 0) {
        $brokenKu[] = ['key' => $k, 'value' => $v, 'count' => $count];
    }
}

usort($brokenAr, fn ($a, $b) => $b['count'] <=> $a['count']);
usort($brokenKu, fn ($a, $b) => $b['count'] <=> $a['count']);

echo "=== AR: translations containing placeholder 'عنصر' (item) ===\n";
echo "Total broken AR strings: " . count($brokenAr) . " / " . count($ar) . " (" . round(count($brokenAr) / count($ar) * 100, 1) . "%)\n\n";

echo "=== KU: translations containing placeholder 'بڕگە' (item/piece) ===\n";
echo "Total broken KU strings: " . count($brokenKu) . " / " . count($ku) . " (" . round(count($brokenKu) / count($ku) * 100, 1) . "%)\n\n";

echo "=== Top 15 WORST AR strings (most placeholders) ===\n";
foreach (array_slice($brokenAr, 0, 15) as $h) {
    echo "[{$h['count']}x] {$h['key']}\n";
    echo "      AR: {$h['value']}\n\n";
}

echo "=== Top 10 WORST KU strings ===\n";
foreach (array_slice($brokenKu, 0, 10) as $h) {
    echo "[{$h['count']}x] {$h['key']}\n";
    echo "      KU: {$h['value']}\n\n";
}

// Save full lists to JSON for fix step
file_put_contents(__DIR__ . '/i18n-broken-ar.json', json_encode($brokenAr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/i18n-broken-ku.json', json_encode($brokenKu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "\n(Full lists saved to scripts/i18n-broken-ar.json and scripts/i18n-broken-ku.json)\n";
