<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Donation Payment Options
    |--------------------------------------------------------------------------
    |
    | Configure your donation payment links. Leave empty to hide the option.
    | For CashApp, Venmo, and PayPal, just provide the username.
    | For Zelle, provide the email or phone number.
    | For Credit Card, provide the full PayPal.me URL.
    |
    */

    'cashapp' => env('DONATE_CASHAPP'),
    'venmo' => env('DONATE_VENMO'),
    'paypal' => env('DONATE_PAYPAL'),
    'zelle' => env('DONATE_ZELLE'),
    'credit_card' => env('DONATE_CREDIT_CARD'),
];
