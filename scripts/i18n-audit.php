<?php
$locales = ['en', 'ar', 'ku'];
$base = __DIR__ . '/../lang';

function flatten(array $a, string $prefix = ''): array {
    $out = [];
    foreach ($a as $k => $v) {
        $key = $prefix === '' ? (string)$k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out += flatten($v, $key);
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
}

echo "=== JSON files (used by __('Some literal string')) ===\n";
$jsons = [];
foreach ($locales as $l) {
    $jsons[$l] = json_decode(file_get_contents("$base/$l.json"), true) ?? [];
    echo "$l.json: " . count($jsons[$l]) . " keys\n";
}

$enKeys = array_keys($jsons['en']);
echo "\n--- Keys present in en.json but MISSING in ar.json ---\n";
$missAr = array_diff($enKeys, array_keys($jsons['ar']));
echo "count: " . count($missAr) . "\n";
foreach (array_slice($missAr, 0, 30) as $k) echo "  - $k\n";
if (count($missAr) > 30) echo "  ... (+" . (count($missAr) - 30) . " more)\n";

echo "\n--- Keys present in en.json but MISSING in ku.json ---\n";
$missKu = array_diff($enKeys, array_keys($jsons['ku']));
echo "count: " . count($missKu) . "\n";
foreach (array_slice($missKu, 0, 30) as $k) echo "  - $k\n";
if (count($missKu) > 30) echo "  ... (+" . (count($missKu) - 30) . " more)\n";

echo "\n--- Keys in ar.json with value identical to English key (probably untranslated) ---\n";
$identAr = [];
foreach ($jsons['ar'] as $k => $v) {
    if ($k === $v && preg_match('/[A-Za-z]/', $k)) $identAr[] = $k;
}
echo "count: " . count($identAr) . "\n";
foreach (array_slice($identAr, 0, 30) as $k) echo "  - $k\n";
if (count($identAr) > 30) echo "  ... (+" . (count($identAr) - 30) . " more)\n";

echo "\n--- Keys in ku.json with value identical to English key (probably untranslated) ---\n";
$identKu = [];
foreach ($jsons['ku'] as $k => $v) {
    if ($k === $v && preg_match('/[A-Za-z]/', $k)) $identKu[] = $k;
}
echo "count: " . count($identKu) . "\n";
foreach (array_slice($identKu, 0, 30) as $k) echo "  - $k\n";
if (count($identKu) > 30) echo "  ... (+" . (count($identKu) - 30) . " more)\n";

echo "\n=== PHP language files ===\n";
foreach (['auth', 'validation', 'errors', 'invoice', 'user'] as $f) {
    $sets = [];
    foreach ($locales as $l) {
        $arr = require "$base/$l/$f.php";
        $sets[$l] = flatten($arr);
    }
    $enK = array_keys($sets['en']);
    $miAr = array_diff($enK, array_keys($sets['ar']));
    $miKu = array_diff($enK, array_keys($sets['ku']));
    $extraAr = array_diff(array_keys($sets['ar']), $enK);
    $extraKu = array_diff(array_keys($sets['ku']), $enK);
    echo "\n$f.php: en=" . count($sets['en']) . " ar=" . count($sets['ar']) . " ku=" . count($sets['ku']) . "\n";
    if ($miAr) { echo "  missing in ar (" . count($miAr) . "): " . implode(', ', array_slice($miAr, 0, 10)) . (count($miAr)>10?'...':'') . "\n"; }
    if ($miKu) { echo "  missing in ku (" . count($miKu) . "): " . implode(', ', array_slice($miKu, 0, 10)) . (count($miKu)>10?'...':'') . "\n"; }
    if ($extraAr) { echo "  extra in ar (" . count($extraAr) . "): " . implode(', ', array_slice($extraAr, 0, 10)) . "\n"; }
    if ($extraKu) { echo "  extra in ku (" . count($extraKu) . "): " . implode(', ', array_slice($extraKu, 0, 10)) . "\n"; }

    // Untranslated: value identical across locales (and contains latin letters)
    $untrAr = [];
    foreach ($sets['ar'] as $k => $v) {
        if (is_string($v) && isset($sets['en'][$k]) && $v === $sets['en'][$k] && preg_match('/[A-Za-z]/', $v) && !preg_match('/^:[a-z_]+$/i', $v)) {
            $untrAr[] = "$k => $v";
        }
    }
    $untrKu = [];
    foreach ($sets['ku'] as $k => $v) {
        if (is_string($v) && isset($sets['en'][$k]) && $v === $sets['en'][$k] && preg_match('/[A-Za-z]/', $v) && !preg_match('/^:[a-z_]+$/i', $v)) {
            $untrKu[] = "$k => $v";
        }
    }
    if ($untrAr) { echo "  untranslated in ar (" . count($untrAr) . "): " . implode(' | ', array_slice($untrAr, 0, 5)) . "\n"; }
    if ($untrKu) { echo "  untranslated in ku (" . count($untrKu) . "): " . implode(' | ', array_slice($untrKu, 0, 5)) . "\n"; }
}
