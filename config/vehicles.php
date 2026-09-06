<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand aliases
    |--------------------------------------------------------------------------
    |
    | The other names a marque is sold under. SsangYong became KGM and shoppers
    | type both, so a search for one has to find cars recorded under the other.
    |
    | Every name in a group matches every other. Spacing and punctuation are
    | ignored when matching, so "Ssang Yong" needs no separate entry — it is
    | listed anyway because it is also what a shopper might see suggested back.
    |
    | Nothing here rewrites a product's actual brand. It only widens what a
    | typed word will match. A group with fewer than two names is ignored.
    |
    */

    'brand_aliases' => [
        ['SsangYong', 'Ssang Yong', 'KGM'],
    ],

];
