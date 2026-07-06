<?php

/*
 * Profanity word lists used by App\Support\ProfanityFilter.
 *
 * Matching is case-insensitive and bounded by non-letter characters, so
 * entries only match whole words ("sik" matches "sik" but not "siyasi").
 * Multi-word phrases are allowed. Extend each list freely — redeploy or
 * `php artisan config:clear` after editing.
 */
return [

    'words' => [

        'tr' => [
            'amk', 'aq', 'amına', 'amcık', 'ananı', 'orospu', 'oç', 'piç',
            'sik', 'sikik', 'siktir', 'sikerim', 'yarrak', 'yarak',
            'göt', 'götveren', 'ibne', 'ipne', 'pezevenk', 'kahpe',
            'sürtük', 'şerefsiz', 'pislik herif', 'gavat',
        ],

        'en' => [
            'fuck', 'fucking', 'fucker', 'motherfucker', 'shit', 'bullshit',
            'bitch', 'asshole', 'bastard', 'dick', 'dickhead', 'cunt',
            'whore', 'slut', 'faggot', 'nigger', 'nigga', 'pussy', 'wanker',
        ],

        'ar' => [
            'شرموطة', 'شرموطه', 'عاهرة', 'عاهره', 'قحبة', 'قحبه',
            'كس', 'زب', 'طيز', 'خول', 'منيوك', 'ابن الكلب', 'ابن الحرام',
            'يلعن', 'خرا', 'زبالة',
        ],

        'ku' => [
            'قەحپە', 'سەگباب', 'کوس', 'قوون', 'گوو', 'زڕە',
            'کەرباب', 'حەرامزادە',
        ],

    ],

];
