<?php
// --------------------------------------------------------------------------
// AYARLAR SAYFASI (settings.php)
// Amacı: Sistemin teknik parametrelerini yönetmek ve bakım işlemleri yapmak.
// --------------------------------------------------------------------------

// 1. ADIM: Standart dosya çağırma işlemleri
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

// Güvenlik: Sadece giriş yapmış kullanıcılar görebilir.
require_login();
$pdo = get_db_connection();

// Kullanıcıya gösterilecek mesaj kutuları için değişkenler
$msg = '';
$msgType = '';

/* ---------------------------------------------------------
 * FORM İŞLEMLERİ (Kullanıcı Kaydet veya Sıfırla'ya bastığında)
 * ---------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // SENARYO 1: VERİTABANI SIFIRLAMA (Tehlikeli İşlem)
    // Eğer formdan 'reset_db' isimli veri geldiyse bu blok çalışır.
    if (isset($_POST['reset_db'])) {
        
        // SORU: Neden DELETE değil de TRUNCATE kullandın?
        // CEVAP: TRUNCATE tabloyu tamamen boşaltır ve ID sayacını 1'e sıfırlar.
        // DELETE ise satırları siler ama sayaç kaldığı yerden devam eder.
        $pdo->exec("TRUNCATE TABLE stok_hareketleri");
        
        // Ürünleri tamamen silmiyoruz (DELETE yok), sadece stoklarını sıfırlıyoruz.
        // Böylece ürün kataloğu duruyor, sadece depo boşalmış oluyor.
        $pdo->exec("UPDATE urunler SET stok_miktar = 0");
        
        // Aktif kampanyaları pasif duruma getiriyoruz.
        $pdo->exec("UPDATE promosyonlar SET aktif_mi = 0");
        
        $msg = 'Sistem başarıyla sıfırlandı. Tüm stoklar 0 oldu.';
        $msgType = 'success';
    }
    
    // SENARYO 2: AYAR GÜNCELLEME
    // SKT Uyarı Eşiği değiştirildiyse burası çalışır.
    if (isset($_POST['skt_esik'])) {
        $yeniEsik = (int)$_POST['skt_esik']; // Güvenlik için tamsayıya çevir
        
        // NOT: Normal projelerde bu ayar veritabanına kaydedilir.
        // Ancak okul projesinde veritabanı tablosuyla uğraşmamak için
        // bu ayarı geçici olarak SESSION (Oturum) hafızasında tutuyoruz.
        $_SESSION['demo_skt_esik'] = $yeniEsik;
        
        $msg = 'Ayarlar güncellendi (Oturum süresince geçerli).';
        $msgType = 'success';
    }
}

// Mevcut eşik değerini belirle.
// Eğer session'da ayar varsa onu al, yoksa varsayılan olarak 7 günü kullan.
// '??' operatörü (Null Coalescing) "yoksa bunu kullan" demektir.
$currentEsik = $_SESSION['demo_skt_esik'] ?? 7;

render_layout_start('Ayarlar', 'Sistem yapılandırması', 'settings');
?>

<div class="grid two">
    
    <div class="card">
        <div class="card-header">
            <h3>Genel Ayarlar</h3>
        </div>
        
        <?php if ($msg && $msgType === 'success'): ?>
            <div class="alert success" style="background:#e7fff1; color:#138a49; border:1px solid #b9f1cf; padding:10px; border-radius:10px; margin-bottom:10px;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="form">
            <label>SKT Uyarı Eşiği (Gün)</label>
            <p class="muted" style="font-size:12px; margin-top:-4px; margin-bottom:8px;">Kaç gün kala uyarı verilsin?</p>
            <input type="number" name="skt_esik" value="<?php echo $currentEsik; ?>" min="1" max="365">
            <button type="submit" class="btn primary full" style="margin-top:10px;">Kaydet</button>
        </form>
    </div>

    <div class="card" style="border-color: #f9c3bd;">
        <div class="card-header">
            <h3 class="danger">Tehlikeli Bölge</h3>
        </div>
        <div class="alert error">
            <i class="fa fa-triangle-exclamation"></i>
            Bu işlem tüm stok giriş/çıkış hareketlerini silecek ve ürün stoklarını sıfırlayacaktır.
        </div>
        
        <form method="post" onsubmit="return confirm('DİKKAT! Tüm veriler silinecek. Emin misiniz?');">
            <input type="hidden" name="reset_db" value="1">
            <button type="submit" class="btn danger full">Veritabanını Sıfırla</button>
        </form>
    </div>
</div>

<?php render_layout_end(); ?>