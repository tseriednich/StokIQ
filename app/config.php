<?php
// Ana konfigürasyon dosyası
// Gerekirse veritabanı bilgilerini kendi XAMPP/WAMP ayarlarına göre güncelle.
//
// NOT: config.example.php sadece örnek şablondur; uygulama tarafından
// kullanılan asıl dosya bu config.php'dir.

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
    // SKT uyarı eşiği (gün)
    'skt_esik_gun' => 7,
];

