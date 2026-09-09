<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram notifications
    |--------------------------------------------------------------------------
    |
    | Order and payment events are pushed to a Telegram group. Leave the token
    | or chat id empty and the notifier stays silent, so a deployment without
    | credentials behaves exactly like one with notifications switched off.
    |
    */

    'enabled' => (bool) env('TELEGRAM_ENABLED', false),

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'chat_id' => env('TELEGRAM_CHAT_ID'),

    'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org'),

];
