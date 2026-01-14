<?php
// --------------------------------------------------------------------------
// AUTH.PHP: KİMLİK DOĞRULAMA VE OTURUM YÖNETİMİ
// Amacı: Giriş, çıkış, kayıt olma ve sayfa güvenliğini (yetki kontrolü) sağlamak.
// --------------------------------------------------------------------------

// 1. ADIM: Oturumu Başlat
// Sunucuda bu kullanıcı için bir hafıza alanı (Session) oluşturur.
// Bu sayede sayfalar arasında gezerken "Bu kimdi?" diye unutmaz.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Ayar dosyasından auth (kimlik) ayarlarını çeken yardımcı fonksiyon
function auth_config(): array
{
    $config = require __DIR__ . '/config.php';
    return $config['auth'];
}

// Şu an giriş yapmış olan kullanıcının bilgilerini getirir.
// Giriş yoksa null (boş) döner.
function current_user(): ?array
{
    $auth = auth_config();
    // $_SESSION dizisinde kullanıcının bilgisi var mı diye bakar.
    return $_SESSION[$auth['session_key']] ?? null;
}

// --------------------------------------------------------------------------
// GÜVENLİK KAPISI: Giriş yapmamış kullanıcıları engeller.
// --------------------------------------------------------------------------
function require_login(): void
{
    // *** YENİ EKLENEN KISIM (CACHE SORUNU ÇÖZÜMÜ) ***
    // Tarayıcıya "Bu sayfayı hafızanda tutma" emri veriyoruz.
    // Böylece logout yaptıktan sonra "Geri" tuşuna basılsa bile
    // tarayıcı eski sayfayı göstermez, sunucuya tekrar sorar ve giriş sayfasına atar.
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (!current_user()) {
        // Eğer oturum yoksa, kullanıcıyı zorla giriş sayfasına at.
        header('Location: login.php'); 
        exit; // Kodun devamının çalışmasını kesinlikle durdur.
    }
}

// ÇIKIŞ İŞLEMİ
function logout(): void
{
    $auth = auth_config();
    // 1. Session değişkenini sil
    unset($_SESSION[$auth['session_key']]);
    
    // 2. Sunucudaki oturum dosyasını tamamen yok et
    session_destroy();
    
    // 3. Karşılama sayfasına yönlendir
    header('Location: index.php');
    exit;
}

// --------------------------------------------------------------------------
// GİRİŞ FONKSİYONU (LOGIN)
// --------------------------------------------------------------------------
function login(string $username, string $password): bool
{
    $pdo = get_db_connection();
    
    // GÜVENLİK NOTU: SQL Injection'ı önlemek için 'prepare' kullanıyoruz.
    // Kullanıcı adını doğrudan sorguya yazmıyoruz, :kadi yer tutucusu kullanıyoruz.
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = :kadi AND aktif_mi = 1");
    $stmt->execute([':kadi' => $username]);
    $user = $stmt->fetch();

    // Kullanıcı bulunduysa VE şifre doğruysa
    // password_verify: Girilen "123456" ile veritabanındaki karmaşık hash'i karşılaştırır.
    if ($user && password_verify($password, $user['sifre_hash'])) {
        $auth = auth_config();
        
        // Session'a en kritik bilgileri yüklüyoruz.
        // Özellikle 'role' bilgisi, admin paneli erişimi için çok önemli.
        $_SESSION[$auth['session_key']] = [
            'id'       => $user['kullanici_id'],
            'username' => $user['kullanici_adi'],
            'email'    => $user['email'],
            'role'     => $user['rol'], // Örn: 'admin' veya 'personel'
        ];
        return true;
    }

    return false;
}

// --------------------------------------------------------------------------
// KAYIT FONKSİYONU (REGISTER)
// --------------------------------------------------------------------------
function register(string $username, string $email, string $password): array
{
    $pdo = get_db_connection();

    // 1. Boş alan kontrolü
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Kullanıcı adı ve şifre zorunludur.'];
    }

    // 2. Bu kullanıcı adı zaten var mı?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_adi = :kadi");
    $stmt->execute([':kadi' => $username]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Bu kullanıcı adı zaten alınmış.'];
    }

    // 3. ŞİFRE HASHLEME (Kritik Güvenlik)
    // Şifreyi veritabanına "123456" diye kaydetmeyiz. Okunamaz hale getiririz.
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Yeni kayıt olanlar yetkisiz (standart) kullanıcı 'personel' oluyor.
        // E-posta alanı da kaydediliyor.
        $insert = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre_hash, rol) VALUES (:kadi, :email, :hash, 'personel')");
        $insert->execute([
            ':kadi' => $username, 
            ':email' => $email,
            ':hash' => $hash
        ]);

        return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
}