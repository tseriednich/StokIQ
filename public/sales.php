<?php
// --------------------------------------------------------------------------
// SATIŞ ANALİZİ SAYFASI (sales.php)
// Amacı: İşletmenin finansal durumunu özetlemek ve nakit akışını göstermek.
// --------------------------------------------------------------------------

// 1. ADIM: Gerekli kütüphaneleri dahil et
require_once __DIR__ . '/../app/auth.php'; // Güvenlik kontrolü
require_once __DIR__ . '/../app/db.php';   // Veritabanı bağlantısı
require_once __DIR__ . '/layout.php';       // Sayfa tasarımı (Header/Footer)

// 2. ADIM: Oturum Kontrolü
// Kullanıcı giriş yapmamışsa, sayfayı görmesini engelle ve login'e at.
require_login();
$pdo = get_db_connection();

/* ---------------------------------------------------------
 * 1. İSTATİSTİK HESAPLAMALARI (Veritabanı Analizi)
 * ---------------------------------------------------------
 */

// SORU: Ciro (Toplam Gelir) Nasıl Hesaplanıyor?
// CEVAP: Stok hareketleri tablosunda fiyat yazmaz, sadece ürün ID yazar.
// Bu yüzden 'JOIN' kullanarak Ürünler tablosuna bağlanıyoruz ve
// (Satılan Adet * Güncel Satış Fiyatı) işlemini SQL içinde yapıyoruz.
// 'SUM' fonksiyonu ile tüm satırları topluyoruz.
$ciroQuery = "
    SELECT SUM(h.miktar * u.satis_fiyat) 
    FROM stok_hareketleri h
    JOIN urunler u ON u.urun_id = h.urun_id
    WHERE h.tip = 'CIKIS'
";
// fetchColumn(): Tek bir sayısal değer (Toplam Ciro) döndürür.
$totalRevenue = (float)$pdo->query($ciroQuery)->fetchColumn();

// Toplam Satılan Ürün Adedi
// Sadece 'CIKIS' işlemi olan hareketlerin miktarını toplar.
$totalSold = (int)$pdo->query("SELECT SUM(miktar) FROM stok_hareketleri WHERE tip = 'CIKIS'")->fetchColumn();

/* ---------------------------------------------------------
 * 2. VERİ LİSTELEME (Tablo Dökümü)
 * ---------------------------------------------------------
 */

// Son Satış Hareketlerini Listeleme
// Yine JOIN kullanıyoruz çünkü tabloda "iPhone" yazmaz, "ID: 5" yazar.
// Ürün adını alabilmek için 'urunler' tablosuyla birleştiriyoruz.
$lastSales = $pdo->query("
    SELECT h.*, u.urun_adi, u.satis_fiyat, (h.miktar * u.satis_fiyat) as toplam_tutar
    FROM stok_hareketleri h
    JOIN urunler u ON u.urun_id = h.urun_id
    WHERE h.tip = 'CIKIS'    -- Sadece satışları getir
    ORDER BY h.tarih DESC    -- En yeniden eskiye doğru sırala
    LIMIT 50                 -- Sayfa şişmesin diye son 50 işlemi getir
")->fetchAll();

// Sayfa başlığını ve menüyü oluştur
render_layout_start('Satış Analizi', 'Ciro ve satış hareketleri', 'sales');
?>

<div class="grid two">
    
    <div class="card stat">
        <div>
            <p class="label">Toplam Ciro (Tahmini)</p>
            <h2>₺<?php echo number_format($totalRevenue, 2, ',', '.'); ?></h2>
            <span class="pill success">Tüm zamanlar</span>
        </div>
    </div>
    
    <div class="card stat">
        <div>
            <p class="label">Toplam Satış Adedi</p>
            <h2><?php echo $totalSold; ?></h2>
            <span class="pill info">Adet ürün çıkışı</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Son Satış İşlemleri</h3>
        <button class="btn ghost small" onclick="window.print()"><i class="fa fa-print"></i> Yazdır</button>
    </div>
    <div class="table">
        <div class="table-head">
            <div>Ürün</div>
            <div>Miktar</div>
            <div>Birim Fiyat</div>
            <div>Toplam</div>
            <div>Tarih</div>
        </div>
        
        <?php foreach ($lastSales as $row): ?>
        <div class="table-row">
            <div><?php echo htmlspecialchars($row['urun_adi']); ?></div>
            
            <div><?php echo $row['miktar']; ?></div>
            
            <div>₺<?php echo number_format((float)$row['satis_fiyat'], 2, ',', '.'); ?></div>
            
            <div style="font-weight:bold;">₺<?php echo number_format((float)$row['toplam_tutar'], 2, ',', '.'); ?></div>
            
            <div><?php echo $row['tarih']; ?></div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($lastSales)): ?>
            <div class="table-row empty">Henüz satış kaydı yok.</div>
        <?php endif; ?>
    </div>
</div>

<?php 
render_layout_end(); 
?>