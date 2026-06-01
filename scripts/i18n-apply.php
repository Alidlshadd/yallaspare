<?php
// Apply a batch of AR/KU translation fixes to lang/ar.json and lang/ku.json.
// Input: a JSON file shaped as:
//   { "english key": { "ar": "...", "ku": "..." }, ... }
// Updates files in place, preserving original key order, pretty-printed, UTF-8 unescaped.

if ($argc < 2) {
    echo "Usage: php scripts/i18n-apply.php <batch-file.json>\n";
    exit(1);
}

$batchPath = $argv[1];
if (!file_exists($batchPath)) {
    fwrite(STDERR, "Batch file not found: $batchPath\n");
    exit(1);
}

$batch = json_decode(file_get_contents($batchPath), true);
if (!is_array($batch)) {
    fwrite(STDERR, "Invalid batch JSON\n");
    exit(1);
}

$arPath = __DIR__ . '/../lang/ar.json';
$kuPath = __DIR__ . '/../lang/ku.json';
$enPath = __DIR__ . '/../lang/en.json';

$ar = json_decode(file_get_contents($arPath), true);
$ku = json_decode(file_get_contents($kuPath), true);
$en = json_decode(file_get_contents($enPath), true);

$applied = 0;
$skipped = 0;
$missing = [];

foreach ($batch as $key => $vals) {
    if (!isset($en[$key])) {
        $missing[] = $key;
        $skipped++;
        continue;
    }
    if (!empty($vals['ar'])) {
        $ar[$key] = $vals['ar'];
    }
    if (!empty($vals['ku'])) {
        $ku[$key] = $vals['ku'];
    }
    $applied++;
}

// Preserve key order from en.json (canonical)
$arOrdered = [];
$kuOrdered = [];
foreach ($en as $k => $_) {
    $arOrdered[$k] = $ar[$k] ?? $k;
    $kuOrdered[$k] = $ku[$k] ?? $k;
}

$opts = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
file_put_contents($arPath, json_encode($arOrdered, $opts) . "\n");
file_put_contents($kuPath, json_encode($kuOrdered, $opts) . "\n");

echo "Applied: $applied | Skipped: $skipped\n";
if ($missing) {
    echo "Keys not found in en.json (skipped):\n";
    foreach (array_slice($missing, 0, 5) as $k) echo "  - $k\n";
}

// Re-check broken count for progress reporting
$brokenAr = 0;
foreach ($arOrdered as $v) { if (is_string($v) && str_contains($v, 'عنصر')) $brokenAr++; }
$brokenKu = 0;
foreach ($kuOrdered as $v) { if (is_string($v) && str_contains($v, 'بڕگە')) $brokenKu++; }
echo "Remaining broken — AR: $brokenAr | KU: $brokenKu\n";
