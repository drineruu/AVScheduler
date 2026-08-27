<?php

return [

    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),

    /*
    | Full service account JSON string, or a path to the JSON key file.
    */
    'service_account' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),

    'cache' => [
        'enabled' => (bool) env('GOOGLE_SHEETS_CACHE_ENABLED', true),
        'ttl_seconds' => (int) env('GOOGLE_SHEETS_CACHE_TTL', 60),
    ],

    'default_settings' => [
        'congregation' => '',
        'address' => '',
        'title' => 'AUDIO/VIDEO SCHEDULE',
        'include_preparation' => true,
    ],

    'sheets' => [
        'settings' => [
            'name' => 'Settings',
            'headers' => ['congregation', 'address', 'title', 'include_preparation'],
        ],
        'brothers' => [
            'name' => 'Brothers',
            'headers' => ['id', 'name', 'is_ms', 'can_audio', 'can_video', 'can_mic', 'can_stage', 'can_prep', 'training_role'],
        ],
        'meetings' => [
            'name' => 'Meetings',
            'headers' => ['date', 'skip', 'allow_trainee', 'busy_brothers'],
        ],
        'schedule' => [
            'name' => 'Schedule',
            'headers' => ['date', 'audio', 'video', 'mics', 'stage', 'preparation'],
        ],
    ],

];
