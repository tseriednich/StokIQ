<?php
// promosyonlar sayfasi: urun promosyonlarini yonetmek ve otomatik promosyon onerileri gormek icin kullanilir.
// sayfa uc bolumden olusur: skt esigine gore otomatik oneriler, yeni promosyon ekleme formu ve aktif promosyon listesi.
// promosyon onerileri skt yaklasan urunler veya stok seviyesi dusuk urunler icin otomatik olarak hesaplanir.
// kullanici promosyon ekleyebilir, aktif promosyonlari goruntuleyebilir ve promosyonlari pasif edebilir.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

// promosyon sayfasina erisim icin giris zorunlulugu uygula
require_login();
// veritabani baglantisini al
$pdo = get_db_connection();

// uygulama konfig dosyasindan skt esigi degerini oku
$config = require __DIR__ . '/../app/config.php';
$sktEsikGun = (int)($config['skt_esik_gun'] ?? 7);

// aktif urunleri promosyon formu icin liste olarak cek
$products = $pdo->query("SELECT urun_id, urun_adi FROM urunler WHERE aktif_mi = 1 ORDER BY urun_adi")->fetchAll();

// form hata mesajlari icin degisken
$error = '';

// aktif promosyonu pasif duruma getiren basit guncelleme islemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deaktif_id'])) {
    $deaktifId = (int)$_POST['deaktif_id'];
    if ($deaktifId > 0) {
        $stmt = $pdo->prepare("UPDATE promosyonlar SET aktif_mi = 0 WHERE promosyon_id = :id");
        $stmt->execute([':id' => $deaktifId]);
        header('Location: promotions.php');
        exit;
    }
}

// yeni promosyon kaydi olusturma islemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urun_id']) && !isset($_POST['deaktif_id'])) {
    $urun_id = (int)($_POST['urun_id'] ?? 0);
    $indirim_yuzde = $_POST['indirim_yuzde'] !== '' ? (float)$_POST['indirim_yuzde'] : null;
    $indirim_tutar = $_POST['indirim_tutar'] !== '' ? (float)$_POST['indirim_tutar'] : null;
    $baslangic = $_POST['baslangic_tarih'] ?? '';
    $bitis = $_POST['bitis_tarih'] ?? '';
    $neden = $_POST['neden'] ?? 'MANUEL';
    $aktif = isset($_POST['aktif_mi']) ? 1 : 0;

    if ($urun_id <= 0 || $baslangic === '' || $bitis === '') {
        $error = 'Zorunlu alanları doldurun.';
    } elseif ($indirim_yuzde !== null && ($indirim_yuzde < 1 || $indirim_yuzde > 90)) {
        $error = 'İndirim yüzdesi 1-90 arası olmalı.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO promosyonlar (urun_id, indirim_yuzde, indirim_tutar, baslangic_tarih, bitis_tarih, neden, aktif_mi)
                               VALUES (:uid, :yuzde, :tutar, :bas, :bitis, :neden, :aktif)");
        $stmt->execute([
            ':uid' => $urun_id,
            ':yuzde' => $indirim_yuzde,
            ':tutar' => $indirim_tutar,
            ':bas' => $baslangic,
            ':bitis' => $bitis,
            ':neden' => $neden,
            ':aktif' => $aktif,
        ]);
        header('Location: promotions.php');
        exit;
    }
}

$activePromos = $pdo->query("
    SELECT p.*, u.urun_adi
    FROM promosyonlar p
    JOIN urunler u ON u.urun_id = p.urun_id
    WHERE p.aktif_mi = 1
    ORDER BY p.baslangic_tarih DESC
")->fetchAll();

// Basit öneri: stok düşük veya SKT yakın (config'teki eşiğe göre)
$suggestStmt = $pdo->prepare("
    SELECT urun_id, urun_adi, stok_miktar, stok_alt_limit, skt
    FROM urunler
    WHERE aktif_mi = 1
      AND (
            (skt IS NOT NULL AND DATEDIFF(skt, CURDATE()) BETWEEN 0 AND :esik)
         OR (stok_miktar <= stok_alt_limit AND stok_miktar > 0)
      )
    ORDER BY skt ASC
    LIMIT 3
");
$suggestStmt->execute([':esik' => $sktEsikGun]);
$suggestions = $suggestStmt->fetchAll();

render_layout_start('Promosyon Yönetimi', 'Önerileri incele ve promosyon ekle', 'promos');
?>

<div class="grid two">
    <div class="card">
        <div class="card-header">
            <h3>Önerilenler</h3>
        </div>
        <div class="grid promos">
            <?php foreach ($suggestions as $s): ?>
                <div class="promo-card">
                    <h4><?php echo htmlspecialchars($s['urun_adi']); ?></h4>
                    <p class="muted">Stok: <?php echo (int)$s['stok_miktar']; ?> | Alt limit: <?php echo (int)$s['stok_alt_limit']; ?></p>
                    <p class="muted">SKT: <?php echo $s['skt'] ?: 'Yok'; ?></p>
                    <div class="promo-actions">
                        <button class="btn primary small">Uygula</button>
                        <button class="btn ghost small">Red</button>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($suggestions)): ?>
                <p class="muted">Öneri bulunamadı.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header between">
            <h3>Yeni Promosyon</h3>
        </div>
        <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" class="form">
            <label>Ürün *</label>
            <select name="urun_id" required>
                <option value="">Seçiniz</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['urun_id']; ?>"><?php echo htmlspecialchars($p['urun_adi']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>İndirim (%)</label>
            <input type="number" step="0.01" name="indirim_yuzde" placeholder="1-90">

            <label>İndirim Tutarı (₺)</label>
            <input type="number" step="0.01" name="indirim_tutar" placeholder="Opsiyonel">

            <div class="grid two">
                <div>
                    <label>Başlangıç *</label>
                    <input type="date" name="baslangic_tarih" required>
                </div>
                <div>
                    <label>Bitiş *</label>
                    <input type="date" name="bitis_tarih" required>
                </div>
            </div>

            <label>Neden</label>
            <select name="neden">
                <option value="SKT_YAKLASIYOR">SKT Yaklaşıyor</option>
                <option value="STOK_FAZLA">Stok Fazla</option>
                <option value="MANUEL" selected>Manuel</option>
            </select>

            <label><input type="checkbox" name="aktif_mi" checked> Aktif</label>

            <button class="btn primary" type="submit">Kaydet</button>
        </form>
    </div>
</div>

<div class="card">
        <div class="card-header">
            <h3>Aktif Promosyonlar</h3>
        </div>
    <div class="table">
        <div class="table-head">
            <div>Ürün</div>
            <div>İndirim</div>
            <div>Başlangıç</div>
            <div>Bitiş</div>
            <div>Durum</div>
        </div>
        <?php foreach ($activePromos as $p): ?>
            <div class="table-row">
                <div><?php echo htmlspecialchars($p['urun_adi']); ?></div>
                <div>
                    <?php
                    if ($p['indirim_yuzde']) {
                        echo '%' . $p['indirim_yuzde'];
                    } elseif ($p['indirim_tutar']) {
                        echo '₺' . $p['indirim_tutar'];
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div><?php echo $p['baslangic_tarih']; ?></div>
                <div><?php echo $p['bitis_tarih']; ?></div>
                <div>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Bu promosyonu pasif etmek istediğinize emin misiniz?');">
                        <input type="hidden" name="deaktif_id" value="<?php echo (int)$p['promosyon_id']; ?>">
                        <button class="btn small ghost" type="submit">Pasif Et</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($activePromos)): ?>
            <div class="table-row empty">Aktif promosyon yok.</div>
        <?php endif; ?>
    </div>
</div>

<?php
render_layout_end();

