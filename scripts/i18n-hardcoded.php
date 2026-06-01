<?php
// Find Blade files with likely-hardcoded English strings outside __()/@lang()/etc.
// Heuristic: look for English words (3+ letters) inside HTML tags or common attributes,
// while ignoring text that's already inside __() or @lang() calls.

$root = __DIR__ . '/../resources/views';

function findBlades(string $dir): array {
    $out = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $out[] = $file->getPathname();
        }
    }
    return $out;
}

function maskTranslated(string $src): string {
    // Replace already-translated text with placeholder so we don't flag it
    $src = preg_replace("/\\{\\{\\s*__\\([^)]*\\)\\s*\\}\\}/", '__TRANS__', $src);
    $src = preg_replace("/__\\((['\"])[^'\"]*\\1[^)]*\\)/", '__TRANS__', $src);
    $src = preg_replace("/@lang\\([^)]+\\)/", '__TRANS__', $src);
    // Mask PHP blocks
    $src = preg_replace("/<\\?php.*?\\?>/s", '__PHP__', $src);
    $src = preg_replace("/\\{\\{.*?\\}\\}/s", '__EXPR__', $src);
    $src = preg_replace("/\\{\\{!!.*?!!\\}\\}/s", '__EXPR__', $src);
    // Mask blade comments
    $src = preg_replace("/\\{\\{--.*?--\\}\\}/s", '__COMMENT__', $src);
    // Mask HTML comments
    $src = preg_replace("/<!--.*?-->/s", '__HTMLCOMMENT__', $src);
    // Mask class / style attributes (often have English-looking utility names)
    $src = preg_replace("/\\bclass\\s*=\\s*\"[^\"]*\"/", 'class=__CLS__', $src);
    $src = preg_replace("/\\bstyle\\s*=\\s*\"[^\"]*\"/", 'style=__STY__', $src);
    $src = preg_replace("/\\bx-[a-z-]+\\s*=\\s*\"[^\"]*\"/", 'xattr=__XAT__', $src);
    $src = preg_replace("/\\b(wire|@click|@submit|v-|:|\\$)[a-zA-Z-]*\\s*=\\s*\"[^\"]*\"/", 'react=__REACT__', $src);
    // Mask script blocks
    $src = preg_replace("/<script\\b[^>]*>.*?<\\/script>/si", '__SCRIPT__', $src);
    return $src;
}

$hits = [];

foreach (findBlades($root) as $file) {
    $rel = str_replace($root . '/', '', str_replace('\\', '/', $file));
    $orig = file_get_contents($file);
    $masked = maskTranslated($orig);
    $lines = explode("\n", $masked);
    $origLines = explode("\n", $orig);

    foreach ($lines as $i => $line) {
        // Hardcoded text inside tags: e.g., <h1>Foo Bar</h1>, <span>Hello</span>
        if (preg_match_all('/>([A-Za-z][A-Za-z0-9 ,\.\'\-:?!&]{4,80})</', $line, $m)) {
            foreach ($m[1] as $text) {
                $t = trim($text);
                // skip if it's just placeholder we masked
                if (str_contains($t, '__TRANS__') || str_contains($t, '__EXPR__') || str_contains($t, '__PHP__')) continue;
                // skip empty, numbers only, or single letter
                if ($t === '' || strlen($t) < 5) continue;
                // skip CSS-like content
                if (preg_match('/^[a-z\-]+:\s*[a-z0-9\-]+;?$/i', $t)) continue;
                // must contain at least one English word
                if (preg_match('/\b[a-z]{4,}\b/i', $t)) {
                    $hits[] = ['file' => $rel, 'line' => $i + 1, 'text' => $t, 'kind' => 'tag-content'];
                }
            }
        }
        // Common attributes with English: placeholder, title, alt, aria-label
        foreach (['placeholder', 'title', 'alt', 'aria-label'] as $attr) {
            if (preg_match_all('/\\b' . $attr . '\\s*=\\s*"([^"]{4,120})"/i', $line, $m)) {
                foreach ($m[1] as $text) {
                    $t = trim($text);
                    if (str_contains($t, '__TRANS__') || str_contains($t, '__EXPR__') || str_contains($t, '{{')) continue;
                    if (preg_match('/\b[a-z]{4,}\b/i', $t) && !preg_match('/^[#.\-\/0-9]+$/', $t)) {
                        $hits[] = ['file' => $rel, 'line' => $i + 1, 'text' => "$attr=\"$t\"", 'kind' => 'attribute'];
                    }
                }
            }
        }
    }
}

// Group by file
$byFile = [];
foreach ($hits as $h) {
    $byFile[$h['file']][] = $h;
}
uksort($byFile, function ($a, $b) use ($byFile) { return count($byFile[$b]) <=> count($byFile[$a]); });

echo "Total hardcoded-English suspects: " . count($hits) . " across " . count($byFile) . " files\n\n";
echo "=== Top 20 files with most suspects ===\n";
$i = 0;
foreach ($byFile as $f => $list) {
    if (++$i > 20) break;
    echo sprintf("%4d  %s\n", count($list), $f);
}

echo "\n=== Sample suspects (first 40) ===\n";
foreach (array_slice($hits, 0, 40) as $h) {
    echo sprintf("%s:%d  [%s]  %s\n", $h['file'], $h['line'], $h['kind'], $h['text']);
}
