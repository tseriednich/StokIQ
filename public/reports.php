<?php
// --------------------------------------------------------------------------
// RAPORLAR SAYFASI (reports.php)
// Amacı: İşletme için risk oluşturan ürünleri (stok bitmesi veya bozulma) listelemek.
// --------------------------------------------------------------------------

// 1. ADIM: Gerekli dosyaları çağır
require_once __DIR__ . '/../app/auth.php'; // Giriş kontrolü için
require_once __DIR__ . '/../app/db.php';   // Veritabanı bağlantısı için
require_once __DIR__ . '/layout.php';       // Sayfa iskeleti (Header/Footer) için

// 2. ADIM: Güvenlik ve Bağlantı
require_login();            // Giriş yapmamışsa login sayfasına at
$pdo = get_db_connection(); // Veritabanı köprüsünü kur

// 3. ADIM: Ayarları Oku
// config.php dosyasından "Kaç gün kala uyarı verelim?" bilgisini çeker.
// Eğer dosya okunamazsa varsayılan olarak '7' gün kabul eder.
$config = require __DIR__ . '/../app/config.php';
$sktEsik = (int)($config['skt_esik_gun'] ?? 7);

/* ---------------------------------------------------------
 * VERİTABANI SORGULARI (DATA FETCHING)
 * ---------------------------------------------------------
 */

// Soru 1: Kritik Stoklar Nasıl Bulunuyor?
// Mantık: Stok miktarı, ürüne özel belirlenen alt limitten az veya eşitse getir.
// Örn: Stok: 3, Limit: 5 -> Listeye girer.
$criticalProducts = $pdo->query("
    SELECT * FROM urunler 
    WHERE aktif_mi = 1 AND stok_miktar <= stok_alt_limit
    ORDER BY stok_miktar ASC
")->fetchAll();

// Soru 2: SKT (Bozulma Riski) Nasıl Hesaplanıyor?
// DATEDIFF(tarih1, tarih2): İki tarih arasındaki gün farkını veren SQL fonksiyonudur.
// CURDATE(): Bugünün tarihini verir.
// Mantık: (Ürünün Tarihi - Bugün) işlemi $sktEsik (7) günden azsa listele.
$sktProducts = $pdo->query("
    SELECT *, DATEDIFF(skt, CURDATE()) as kalan_gun 
    FROM urunler 
    WHERE aktif_mi = 1 AND skt IS NOT NULL 
    AND DATEDIFF(skt, CURDATE()) <= $sktEsik
    ORDER BY skt ASC
")->fetchAll();

// Sayfa başlığını ve menüyü oluştur
render_layout_start('Raporlar', 'Kritik durumdaki ürünler', 'reports');
?>

<div class="grid two">
    
    <div class="card">
        <div class="card-header between">
            <h3 class="danger">Kritik Stok Raporu</h3>
            <button class="btn icon" onclick="window.print()"><i class="fa fa-print"></i></button>
        </div>
        <div class="table">
            <div class="table-head">
                <div>Ürün</div>
                <div>Stok</div>
                <div>Limit</div>
            </div>
            
            <?php foreach ($criticalProducts as $p): ?>
            <div class="table-row">
                <div><?php echo htmlspecialchars($p['urun_adi']); ?></div>
                <div class="danger" style="font-weight:bold;"><?php echo $p['stok_miktar']; ?></div>
                <div><?php echo $p['stok_alt_limit']; ?></div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($criticalProducts)): ?>
                <div class="table-row empty">Kritik seviyede ürün yok.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header between">
            <h3 class="warning">SKT Raporu (Yaklaşanlar)</h3>
        </div>
        <div class="table">
            <div class="table-head">
                <div>Ürün</div>
                <div>SKT</div>
                <div>Durum</div>
            </div>
            
            <?php foreach ($sktProducts as $p): 
                // SQL'den gelen gün farkını tam sayıya çevir
                $kalan = (int)$p['kalan_gun'];
                
                // MANTIK: Negatif ise tarih geçmiştir, Pozitif ise daha zaman vardır.
                // Ternary Operatörü (Kısa if-else) kullanımı:
                $msg = $kalan < 0 ? abs($kalan) . " gün geçti" : $kalan . " gün kaldı";
                
                // Tarih geçtiyse Kırmızı (danger), geçmediyse Sarı (warning) yap
                $class = $kalan < 0 ? 'danger' : 'warning';
            ?>
            <div class="table-row">
                <div><?php echo htmlspecialchars($p['urun_adi']); ?></div>
                <div><?php echo $p['skt']; ?></div>
                <div><span class="pill <?php echo $class; ?>"><?php echo $msg; ?></span></div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($sktProducts)): ?>
                <div class="table-row empty">SKT sorunu yok.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php render_layout_end(); ?>