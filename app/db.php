<?php

// veritabani baglantisini yoneten yardimci fonksiyon
function get_db_connection(): PDO
{
    // ayni istek icinde tek bir pdo nesnesi kullanmak icin statik degisken
    static $pdo = null;

    // eger pdo nesnesi daha once olustuysa dogrudan aynisini dondur
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // config dosyasindan veritabani ayarlarini oku
    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    // mysql icin dsn metnini hazirla
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        // pdo nesnesini olustur ve hata modunu exception olacak sekilde ayarla
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // baglanti hatasi olursa guvenli bir sekilde mesaji gostererek uygulamayi sonlandir
        die('veritabani baglanti hatasi: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}


