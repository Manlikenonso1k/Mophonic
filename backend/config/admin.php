<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super admin bootstrapping
    |--------------------------------------------------------------------------
    |
    | The seeder grants the super_admin role to this address. It lives in the
    | environment so no address is baked into the codebase, and so each
    | deployment can own a different one.
    |
    */

    'super_admin_email' => env('ADMIN_SUPER_EMAIL'),

];
