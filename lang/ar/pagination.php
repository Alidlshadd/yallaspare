<?php

/*
 * The paginator asks for these by a dotted key, so they cannot live in the
 * JSON files with the rest of the interface. Without this file a locale
 * falls back to English and the buttons stop being translated.
 *
 * The guillemets are mirrored by the bidi algorithm, so the arrow points
 * back in Arabic and Kurdish without needing a second pair here.
 */

return [
    'previous' => '&laquo; السابق',
    'next' => 'التالي &raquo;',
];
