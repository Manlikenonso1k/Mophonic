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

    /*
    | Deployments that predate ADMIN_SUPER_EMAIL already carry ADMIN_EMAIL —
    | the address the seeder creates the first account with — so it stands in
    | when the newer variable has not been added to that server's .env yet.
    */

    'fallback_admin_email' => env('ADMIN_EMAIL'),

];
