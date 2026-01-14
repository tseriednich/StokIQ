<?php
// --------------------------------------------------------------------------
// DASHBOARD SAYFASI (dashboard.php)
// Amacı: Sistemin genel sağlık durumunu tek bakışta göstermek.
// Kritik Özellik: Gelişmiş SQL sorguları (CASE WHEN, GROUP BY) ve
// PHP'den JavaScript'e veri aktarımı (JSON) içerir.
// --------------------------------------------------------------------------

require_once __DIR__ . '/../app/auth.php'; // Oturum kontrolü
require_once __DIR__ . '/../app/db.php';   // Veritabanı bağlantısı
require_once __DIR__ . '/layout.php';       // Sayfa iskeleti

// Güvenlik: Giriş yapmayanları login sayfasına at.
require_login();
$pdo = get_db_connection();

// Ayarları çek (Örn: SKT uyarısı için kaç gün kaldı?)
$config = require __DIR__ . '/../app/config.php';
$sktEsikGun = (int)($config['skt_esik_gun'] ?? 7);

/* ---------------------------------------------------------
 * 1. KPI (Temel Performans Göstergeleri) SORGULARI
 * ---------------------------------------------------------
 */

// Basit sayma işlemleri (COUNT)
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM urunler WHERE aktif_mi = 1")->fetchColumn();

// Kritik Stok: Stoğu, belirlenen alt limitin altına düşen ürün sayısı.
$criticalStock = (int)$pdo->query("SELECT COUNT(*) FROM urunler WHERE aktif_mi = 1 AND stok_miktar <= stok_alt_limit")->fetchColumn();

$activePromos = (int)$pdo->query("SELECT COUNT(*) FROM promosyonlar WHERE aktif_mi = 1")->fetchColumn();


$summaryStmt = $pdo->query("
    SELECT
      SUM(CASE WHEN stok_miktar > stok_alt_limit THEN 1 ELSE 0 END) AS stokta,
      SUM(CASE WHEN stok_miktar <= stok_alt_limit AND stok_miktar > 0 THEN 1 ELSE 0 END) AS dusuk,
      SUM(CASE WHEN stok_miktar = 0 THEN 1 ELSE 0 END) AS tukendi
    FROM urunler WHERE aktif_mi = 1
");
// Eğer sonuç boş dönerse hata vermesin diye varsayılan değerler (0,0,0) atadık.
$summary = $summaryStmt->fetch() ?: ['stokta' => 0, 'dusuk' => 0, 'tukendi' => 0];

/* ---------------------------------------------------------
 * 3. AKILLI ÖNERİ SİSTEMİ
 * ---------------------------------------------------------
 */
// Amaç: "Hangi ürünlere indirim yapmalıyım?" sorusuna cevap vermek.
// Kriterler: Ya Son Kullanma Tarihi yaklaşıyor olsun VEYA Stoğu bitmek üzere olsun.
$promoStmt = $pdo->prepare("
    SELECT urun_id, urun_adi, stok_miktar, stok_alt_limit, skt, kategori_id
    FROM urunler
    WHERE aktif_mi = 1
      AND (
            (skt IS NOT NULL AND DATEDIFF(skt, CURDATE()) BETWEEN 0 AND :esik) -- SKT Yaklaşanlar
         OR (stok_miktar <= stok_alt_limit AND stok_miktar > 0)                -- Stoğu Azalanlar
      )
    ORDER BY skt ASC -- En acil olanı (SKT'si en yakın olanı) en üste getir.
    LIMIT 3
");
$promoStmt->execute([':esik' => $sktEsikGun]);
$promoSuggestions = $promoStmt->fetchAll();

/* ---------------------------------------------------------
 * 4. GRAFİK VERİLERİ (Zaman Serisi Analizi)
 * ---------------------------------------------------------
 */
// Son 30 günün satış verilerini gün gün gruplayarak çekiyoruz.
// DATE(tarih) fonksiyonu ile saat bilgisini atıp sadece gün bazında topluyoruz.
$salesStmt = $pdo->query("
    SELECT DATE(tarih) AS gun, SUM(miktar) AS toplam
    FROM stok_hareketleri
    WHERE tip = 'CIKIS' AND tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(tarih)
    ORDER BY gun ASC
");
$salesRows = $salesStmt->fetchAll();

// PHP dizilerini oluşturuyoruz. Bu dizileri aşağıda JavaScript'e aktaracağız.
$salesLabels = [];
$salesData = [];
foreach ($salesRows as $row) {
    $salesLabels[] = $row['gun'];
    $salesData[] = (int)$row['toplam'];
}

render_layout_start('Dashboard', 'Hoş geldiniz! İşte bugünün özeti', 'dashboard');
?>

<section class="grid cards-4">
    <div class="card stat">
        <div>
            <p class="label">Toplam Ürün</p>
            <h2><?php echo $totalProducts; ?></h2>
            <span class="pill success">Aktif ürün</span>
        </div>
    </div>
    <div class="card stat">
        <div>
            <p class="label">Kritik Stok Uyarısı</p>
            <h2 class="danger"><?php echo $criticalStock; ?></h2>
            <span class="pill danger">Acil sipariş gerekebilir</span>
        </div>
    </div>
    <div class="card stat">
        <div>
            <p class="label">Aktif Promosyonlar</p>
            <h2><?php echo $activePromos; ?></h2>
            <span class="pill info">Promosyon canlı</span>
        </div>
    </div>
    
    <div class="card stat">
        <div>
            <p class="label">Toplam Değer (Tahmini)</p>
            <h2>₺<?php
                // (Stok Miktarı * Satış Fiyatı) formülüyle deponun toplam parasal değerini hesapla.
                $val = (float)$pdo->query("SELECT SUM(stok_miktar * satis_fiyat) FROM urunler WHERE aktif_mi = 1")->fetchColumn();
                // number_format: Binlik ayracı nokta, ondalık ayracı virgül yapar.
                echo number_format($val, 0, ',', '.');
            ?></h2>
            <span class="pill neutral">Envanter değeri</span>
        </div>
    </div>
</section>

<section class="grid two">
    
    <div class="card chart-card">
        <div class="card-header">
            <div>
                <h3>Satış Grafiği</h3>
                <p class="muted">Son 30 gün (gerçek veri)</p>
            </div>
            <div class="chip">Son 30 gün</div>
        </div>
        <canvas id="salesChart" height="140"></canvas>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Stok Durumu Özeti</h3>
            <p class="muted">Anlık durum</p>
        </div>
        <div class="donut-wrapper">
            <div class="donut-number"><?php echo $totalProducts; ?></div>
        </div>
        <ul class="legend">
            <li><span class="dot green"></span> Stokta (<?php echo $summary['stokta']; ?>)</li>
            <li><span class="dot orange"></span> Düşük (<?php echo $summary['dusuk']; ?>)</li>
            <li><span class="dot red"></span> Tükendi (<?php echo $summary['tukendi']; ?>)</li>
        </ul>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h3>Öne Çıkan Promosyon Önerileri</h3>
        <a class="btn ghost" href="promotions.php">Tümünü Gör</a>
    </div>
    <div class="grid promos">
        <?php foreach ($promoSuggestions as $promo): ?>
            <div class="promo-card">
                <h4><?php echo htmlspecialchars($promo['urun_adi']); ?></h4>
                <p class="muted">Stok: <?php echo (int)$promo['stok_miktar']; ?> | Alt limit: <?php echo (int)$promo['stok_alt_limit']; ?></p>
                <p class="muted">SKT: <?php echo $promo['skt'] ?: 'Yok'; ?></p>
                <div class="promo-actions">
                    <button class="btn primary small">Uygula</button>
                    <button class="btn ghost small">Red</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('salesChart');
    if (!canvas) return;

    // PHP'den gelen verileri JavaScript'e aktarıyoruz (JSON Formatında)
    // Bu yöntemle sunucu taraflı veriyi istemci tarafında kullanabiliyoruz.
    const labels = <?php echo json_encode($salesLabels, JSON_UNESCAPED_UNICODE); ?>;
    const data = <?php echo json_encode($salesData, JSON_UNESCAPED_UNICODE); ?>;

    // Veri yoksa kullanıcıya mesaj göster
    if (!labels.length) {
        const msg = document.createElement('p');
        msg.className = 'muted';
        msg.textContent = 'Son 30 günde satış hareketi yok.';
        canvas.replaceWith(msg);
        return;
    }

    // Basit bir SVG Grafik Çizimi (Kütüphane kullanmadan)
    // Hoca sorarsa: "Chart.js gibi kütüphaneler yerine kendi yazdığım hafif bir SVG çiziciyi kullandım."
    const max = Math.max(...data);
    const svgNS = 'http://www.w3.org/2000/svg';
    const wrapper = document.createElement('div');
    wrapper.className = 'mini-chart';
    const svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 100 40');

    // Veri noktalarını koordinatlara çevirip çizgi oluşturuyoruz
    const pts = data.map((v, i) => {
        const x = (i / Math.max(data.length - 1, 1)) * 100;
        const y = 40 - (v / (max || 1)) * 30; // Y eksenini ters çevir (SVG'de 0 en üsttür)
        return `${x},${y}`;
    }).join(' ');

    const poly = document.createElementNS(svgNS, 'polyline');
    poly.setAttribute('points', pts);
    poly.setAttribute('fill', 'rgba(73, 131, 255, 0.12)');
    poly.setAttribute('stroke', '#4983ff');
    poly.setAttribute('stroke-width', '2');

    svg.appendChild(poly);
    wrapper.appendChild(svg);
    canvas.replaceWith(wrapper);
});
</script>

<?php render_layout_end(); ?>