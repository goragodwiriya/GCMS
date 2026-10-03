<?php
/* settings/database.php */

return [
    'mysql' => [
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'gcms',
        'prefix' => 'gcms'
    ],
    'tables' => [
        'category' => 'category',
        'event' => 'eventcalendar',
        'language' => 'language',
        'logs' => 'logs',
        'user' => 'user',
        'user_meta' => 'user_meta'
    ]
];
