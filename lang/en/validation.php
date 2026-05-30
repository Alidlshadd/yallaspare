<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| Partial overrides that merge with Laravel's bundled validation messages
| via the FileLoader's array_replace_recursive multi-path merge. Only the
| password sub-keys are defined here; every other validation message keeps
| coming from the framework defaults.
|
*/

return [
    'phone' => 'The :attribute must be a valid phone number (8–15 digits).',

    'password' => [
        'letters'       => 'The :attribute must contain at least one letter.',
        'mixed'         => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers'       => 'The :attribute must contain at least one number.',
        'symbols'       => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
];
