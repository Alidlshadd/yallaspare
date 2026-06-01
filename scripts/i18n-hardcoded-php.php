<?php
// Find user-facing strings in PHP files that aren't wrapped in __()
// Focuses on common user-output patterns: ->with('success'/'error'), abort(...), validation messages, etc.

$root = __DIR__ . '/../app';

function findPhpFiles(string $dir): array {
    $out = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            $out[] = $file->getPathname();
        }
    }
    return $out;
}

$patterns = [
    'flash_msg'    => '/->with\s*\(\s*[\'"](?:success|error|warning|info|status)[\'"]\s*,\s*([\'"])([A-Z][^\'"\n]{4,200})\\1/',
    'abort_with'   => '/abort\s*\(\s*\d+\s*,\s*([\'"])([A-Z][^\'"\n]{4,200})\\1/',
    'session_flash' => '/->flash\s*\(\s*[\'"](?:success|error|warning|info|status|message)[\'"]\s*,\s*([\'"])([A-Z][^\'"\n]{4,200})\\1/',
    'throw_msg'    => '/throw\s+new\s+\w+(?:Exception|Error)\s*\(\s*([\'"])([A-Z][^\'"\n]{8,200})\\1/',
    'json_message' => '/[\'"]message[\'"]\s*=>\s*([\'"])([A-Z][^\'"\n]{4,200})\\1(?!.*__\()/',
];

$hits = [];

foreach (findPhpFiles($root) as $file) {
    $rel = str_replace([$root . '/', $root . '\\'], '', str_replace('\\', '/', $file));
    $src = file_get_contents($file);
    $lines = explode("\n", $src);

    foreach ($lines as $i => $line) {
        // skip lines that already use __() somewhere
        // (some lines mix translated & non-translated; check the matched string itself instead)
        foreach ($patterns as $kind => $pat) {
            if (preg_match_all($pat, $line, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[2] as $match) {
                    $text = $match[0];
                    // skip if this looks like a class name, key, slug, etc.
                    if (!str_contains($text, ' ')) continue; // single words usually = keys/codes
                    // skip if line already wraps this in __()
                    if (preg_match('/__\\(\\s*[\'"]' . preg_quote($text, '/') . '/', $line)) continue;
                    $hits[] = ['file' => $rel, 'line' => $i + 1, 'kind' => $kind, 'text' => $text];
                }
            }
        }
    }
}

echo "Total hardcoded user-facing PHP strings: " . count($hits) . "\n";
echo "(showing only English-looking phrases not wrapped in __())\n\n";

$byFile = [];
foreach ($hits as $h) $byFile[$h['file']][] = $h;
uksort($byFile, fn ($a, $b) => count($byFile[$b]) <=> count($byFile[$a]));

foreach ($byFile as $f => $list) {
    echo "$f (" . count($list) . ")\n";
    foreach (array_slice($list, 0, 8) as $h) {
        echo "  L{$h['line']} [{$h['kind']}]  \"{$h['text']}\"\n";
    }
    if (count($list) > 8) echo "  ... +" . (count($list) - 8) . " more\n";
}
