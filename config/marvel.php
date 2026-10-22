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
        'email' => env('MARVEL_SUPPORT_EMAIL', 'info@marvelacademy.co.uk'),
        'phone' => env('MARVEL_SUPPORT_PHONE', '+44 7448 949999'),
        'address' => env('MARVEL_ADDRESS', '167–169 Great Portland Street, 5th Floor, London W1W 5PF'),
        'company_number' => env('MARVEL_COMPANY_NUMBER', '14864414'),
        'hours' => 'Live classes on alternate Saturdays · Enquiries Monday to Saturday',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Profiles
    |--------------------------------------------------------------------------
    |
    | Only networks with a URL configured are shown in the footer.
    |
    */

    'social' => array_filter([
        'facebook' => env('MARVEL_FACEBOOK_URL'),
        'instagram' => env('MARVEL_INSTAGRAM_URL'),
        'youtube' => env('MARVEL_YOUTUBE_URL'),
        'x' => env('MARVEL_X_URL'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Demo Data
    |--------------------------------------------------------------------------
    |
    | Whether the catalogue seeder uploads generated PDF materials to storage.
    |
    */

    'seed_demo_material_files' => (bool) env('SEED_DEMO_MATERIAL_FILES', true),

];
