<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CV Owner
    |--------------------------------------------------------------------------
    |
    | Shown on the PIN entry page. The contact link is offered to visitors
    | who don't have an access code yet; leave it empty to hide it.
    |
    */

    'owner_name' => env('CV_OWNER_NAME', 'Your Name'),

    'contact_url' => env('CV_CONTACT_URL'),

    'contact_label' => env('CV_CONTACT_LABEL', 'LinkedIn'),

];
