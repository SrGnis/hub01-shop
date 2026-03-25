<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Download stats bot/UA filtering
    |--------------------------------------------------------------------------
    |
    | bad_user_agent_patterns can be set in tests to bypass file loading.
    | When null, patterns are loaded from bad_user_agent_list_path.
    |
    */
    'bad_user_agent_patterns' => null,
    'bad_user_agent_list_path' => base_path('config/bad-user-agent.list'),
];

