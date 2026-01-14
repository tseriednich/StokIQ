<?php
// stok hareketleri sayfasi: urunlerin stok giris, cikis, iade ve sayim islemlerini kaydetmek icin kullanilir.
// sayfa iki bolumden olusur: sol tarafta yeni hareket ekleme formu, sag tarafta son 20 hareketin listesi.
// hareket tipine gore urun stok miktari otomatik olarak guncellenir (giris artirir, cikis azaltir, sayim degeri set eder).
// tum hareketler stok_hareketleri tablosuna kaydedilir ve urunler tablosundaki stok_miktar alani guncellenir.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

// stok hareketleri sayfasina erisim icin giris zorunlulugu uygula
require_login();
// veritabani baglantisini al
$pdo = get_db_connection();

// formda kullanilacak aktif urun listesini getir
$products = $pdo->query("SELECT urun_id, urun_adi FROM urunler WHERE aktif_mi = 1 ORDER BY urun_adi")->fetchAll();

// olasi hata mesajlari icin degisken
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urun_id = (int)($_POST['urun_id'] ?? 0);
    $tip = $_POST['tip'] ?? '';
    $miktar = (int)($_POST['miktar'] ?? 0);
    $aciklama = trim($_POST['aciklama'] ?? '');

    // zorunlu alanlar ve tip icin temel dogrulama yap
    if ($urun_id <= 0 || !in_array($tip, ['GIRIS','CIKIS','IADE','SAYIM'], true) || $miktar <= 0) {
        $error = 'Alanları kontrol edin.';
    } else {
        // stok hareketini veritabanina ekle
        $stmt = $pdo->prepare("INSERT INTO stok_hareketleri (urun_id, tip, miktar, aciklama) VALUES (:uid, :tip, :miktar, :aciklama)");
        $stmt->execute([
            ':uid' => $urun_id,
            ':tip' => $tip,
            ':miktar' => $miktar,
            ':aciklama' => $aciklama ?: null,
        ]);

        // stok miktarini hareket tipine gore guncelle
        $op = ($tip === 'CIKIS') ? -1 : 1;
        if ($tip === 'SAYIM') {
            $pdo->prepare("UPDATE urunler SET stok_miktar = :m WHERE urun_id = :id")->execute([':m' => $miktar, ':id' => $urun_id]);
        } else {
            $pdo->prepare("UPDATE urunler SET stok_miktar = stok_miktar + :delta WHERE urun_id = :id")->execute([':delta' => $op * $miktar, ':id' => $urun_id]);
        }

        header('Location: stock_movements.php');
        exit;
    }
}

$rows = $pdo->query("
    SELECT h.*, u.urun_adi
    FROM stok_hareketleri h
    JOIN urunler u ON u.urun_id = h.urun_id
    ORDER BY h.tarih DESC
    LIMIT 20
")->fetchAll();

render_layout_start('Stok Hareketleri', 'Giriş / çıkış / iade hareketlerini kaydet', 'products');
?>

<div class="grid two">
    <div class="card">
        <div class="card-header">
            <h3>Yeni Hareket</h3>
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

            <label>Tip *</label>
            <select name="tip" required>
                <option value="GIRIS">Giriş</option>
                <option value="CIKIS">Çıkış</option>
                <option value="IADE">İade</option>
                <option value="SAYIM">Sayım</option>
            </select>

            <label>Miktar *</label>
            <input type="number" name="miktar" min="1" required>

            <label>Açıklama</label>
            <textarea name="aciklama" rows="3"></textarea>

            <button class="btn primary" type="submit">Kaydet</button>
        </form>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Son Hareketler</h3>
        </div>
        <div class="table">
            <div class="table-head">
                <div>Ürün</div>
                <div>Tip</div>
                <div>Miktar</div>
                <div>Tarih</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <div class="table-row">
                    <div><?php echo htmlspecialchars($r['urun_adi']); ?></div>
                    <div><span class="pill <?php echo $r['tip'] === 'CIKIS' ? 'danger' : 'success'; ?>"><?php echo $r['tip']; ?></span></div>
                    <div><?php echo (int)$r['miktar']; ?></div>
                    <div><?php echo $r['tarih']; ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <div class="table-row empty">Kayıt yok.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
render_layout_end();

