<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abandoned cart reminders
    |--------------------------------------------------------------------------
    |
    | A customer who filled a cart and stopped is the closest thing the shop
    | has to a sale it has not made yet. One reminder brings some of them
    | back; more than two mostly buys unsubscribes.
    |
    | Only customers who gave marketing consent are ever written to, so this
    | can be left on: with no consent on file it simply finds nobody.
    |
    */

    'enabled' => env('CART_REMINDERS_ENABLED', true),

    /*
    | Hours after the cart was last touched. One entry per reminder, in order,
    | and the length of this list is how many a customer can receive.
    */
    'stages' => [
        (float) env('CART_REMINDER_FIRST_HOURS', 4),
        (float) env('CART_REMINDER_SECOND_HOURS', 24),
    ],

    /*
    | A cart older than this is left alone. Without it, the first run after
    | deployment writes to everyone who ever abandoned anything — months of
    | dead carts arriving as one blast, which is how a sending domain gets
    | marked as spam.
    */
    'max_age_days' => (int) env('CART_REMINDER_MAX_AGE_DAYS', 7),

    /*
    | Ceiling on one run, for the same reason. The rest wait for the next
    | hourly pass rather than leaving at once.
    */
    'max_per_run' => (int) env('CART_REMINDER_MAX_PER_RUN', 200),

];
