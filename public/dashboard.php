<?php
// dashboard sayfasi: Genel durum özeti
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

require_login();
$pdo = get_db_connection();

$config = require __DIR__ . '/../app/config.php';
$sktEsikGun = (int)($config['skt_esik_gun'] ?? 7);

// İstatistikler
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM urunler WHERE aktif_mi = 1")->fetchColumn();
$criticalStock = (int)$pdo->query("SELECT COUNT(*) FROM urunler WHERE aktif_mi = 1 AND stok_miktar <= stok_alt_limit")->fetchColumn();
$activePromos = (int)$pdo->query("SELECT COUNT(*) FROM promosyonlar WHERE aktif_mi = 1")->fetchColumn();

// Stok Durumu Özeti
$summaryStmt = $pdo->query("
    SELECT
      SUM(CASE WHEN stok_miktar > stok_alt_limit THEN 1 ELSE 0 END) AS stokta,
      SUM(CASE WHEN stok_miktar <= stok_alt_limit AND stok_miktar > 0 THEN 1 ELSE 0 END) AS dusuk,
      SUM(CASE WHEN stok_miktar = 0 THEN 1 ELSE 0 END) AS tukendi
    FROM urunler WHERE aktif_mi = 1
");
$summary = $summaryStmt->fetch() ?: ['stokta' => 0, 'dusuk' => 0, 'tukendi' => 0];

// Promosyon Önerileri
$promoStmt = $pdo->prepare("
    SELECT urun_id, urun_adi, stok_miktar, stok_alt_limit, skt, kategori_id
    FROM urunler
    WHERE aktif_mi = 1
      AND (
            (skt IS NOT NULL AND DATEDIFF(skt, CURDATE()) BETWEEN 0 AND :esik)
         OR (stok_miktar <= stok_alt_limit AND stok_miktar > 0)
      )
    ORDER BY skt ASC
    LIMIT 3
");
$promoStmt->execute([':esik' => $sktEsikGun]);
$promoSuggestions = $promoStmt->fetchAll();

// Grafik Verileri (Son 30 gün)
$salesStmt = $pdo->query("
    SELECT DATE(tarih) AS gun, SUM(miktar) AS toplam
    FROM stok_hareketleri
    WHERE tip = 'CIKIS' AND tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(tarih)
    ORDER BY gun ASC
");
$salesRows = $salesStmt->fetchAll();

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
                $val = (float)$pdo->query("SELECT SUM(stok_miktar * satis_fiyat) FROM urunler WHERE aktif_mi = 1")->fetchColumn();
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

    const labels = <?php echo json_encode($salesLabels, JSON_UNESCAPED_UNICODE); ?>;
    const data = <?php echo json_encode($salesData, JSON_UNESCAPED_UNICODE); ?>;

    if (!labels.length) {
        const msg = document.createElement('p');
        msg.className = 'muted';
        msg.textContent = 'Son 30 günde satış hareketi yok.';
        canvas.replaceWith(msg);
        return;
    }

    const max = Math.max(...data);
    const svgNS = 'http://www.w3.org/2000/svg';
    const wrapper = document.createElement('div');
    wrapper.className = 'mini-chart';
    const svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 100 40');

    const pts = data.map((v, i) => {
        const x = (i / Math.max(data.length - 1, 1)) * 100;
        const y = 40 - (v / (max || 1)) * 30;
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

<?php
/*  verilerin geldiği tablolar

1. Üst Bilgi Kartları (Özet Paneli):
Bu alandaki veriler doğrudan `urunler` ve `promosyonlar` tablolarından basit SQL toplama fonksiyonları ile çekilmektedir. 'Toplam Ürün' kartı veritabanındaki aktif ürün sayısını `COUNT` ile alırken, 'Kritik Stok' kartı stok miktarı belirlediğimiz alt limitin altına düşen ürünleri filtreler. 'Toplam Değer' kartı ise `urunler` tablosundaki her ürünün stok adedi ile satış fiyatını çarparak (`SUM` işlemi) deponun o anki toplam mali değerini hesaplar.

2. Satış Grafiği (Sol Grafik):
Bu grafik, `stok_hareketleri` tablosunu temel alarak son 30 günlük performansı görselleştirir. Veritabanından işlem tipi sadece 'ÇIKIŞ' (Satış) olan kayıtlar çekilir ve bu kayıtlar `GROUP BY` komutu kullanılarak günlere göre gruplandırılır. Her günün toplam cirosu toplanarak grafiğin Y eksenine, tarihler ise X eksenine yerleştirilir.

3. Stok Durumu Özeti (Sağ Yuvarlak Grafik):
Donut grafiğin veri kaynağı `urunler` tablosudur ancak hesaplama işlemi PHP tarafında mantıksal sınamalarla yapılır. Veritabanından çekilen tüm ürünler bir döngüye sokulur ve stok miktarları kontrol edilir; stoğu 0 olanlar 'Tükendi', stok alt limitinden az olanlar 'Kritik' ve diğerleri 'Normal' olarak sınıflandırılarak renkli dilimler oluşturulur.

4. Promosyon Önerileri (Alt Bölüm):
Bu bölümdeki veriler `urunler` tablosundan çekilmektedir. Sistem, son kullanma tarihi (SKT) tehlikeli derecede yaklaşan veya stoğu çok uzun süredir erimeyen ürünleri özel bir SQL sorgusu ile tespit eder ve kullanıcıya bu ürünler için indirim yapması gerektiğini 'Öneri Kartları' şeklinde sunar.
*/