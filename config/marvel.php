<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Contact Details
    |--------------------------------------------------------------------------
    |
    | Shown in the site footer and on the contact page. Contact form
    | submissions are emailed to the support address.
    |
    */

    'contact' => [
        'email' => env('MARVEL_SUPPORT_EMAIL', 'support@marvelacademy.co.uk'),
        'phone' => env('MARVEL_SUPPORT_PHONE', '020 3966 4120'),
        'address' => env('MARVEL_ADDRESS', '71–75 Shelton Street, Covent Garden, London WC2H 9JQ'),
        'hours' => 'Monday to Friday, 9am–6pm · Saturday, 10am–2pm',
    ],

    'social' => [
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'youtube' => 'https://www.youtube.com/',
        'x' => 'https://x.com/',
    ],

];
