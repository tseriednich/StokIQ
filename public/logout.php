<?php
// --------------------------------------------------------------------------
// logout.php: ÇIKIŞ İŞLEMİ
// Amacı: Oturumu güvenli bir şekilde kapatmak ve temizlemek.
// --------------------------------------------------------------------------

// auth.php dosyasını çağır (Logout fonksiyonu orada tanımlı)
require_once __DIR__ . '/../app/auth.php';

// auth.php içindeki logout() fonksiyonunu çalıştır.
// Bu fonksiyon session'ı silecek ve index.php'ye yönlendirecek.
logout();