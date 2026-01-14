<?php
// Bu dosyayı config.php olarak kopyalayıp kendi ortamına göre düzenleyebilirsin.

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'stokiq_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'auth' => [
        'session_key' => 'stokiq_user',
    ],
    'skt_esik_gun' => 7,
];