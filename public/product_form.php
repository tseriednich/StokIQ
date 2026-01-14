<?php
// urun formu sayfasi: yeni urun ekleme veya mevcut urun duzenleme islemlerini gerceklestirir.
// sayfa url parametresindeki id degerine gore ekleme veya duzenleme modunda calisir.
// form urun adi, barkod, kategori, fiyatlar, stok miktari, stok alt limiti, skt ve birim gibi alanlari icerir.
// form gonderildiginde veriler dogrulanir ve urunler tablosuna kaydedilir veya guncellenir.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

// form sayfasina erisim icin giris kontrolu yap
require_login();
// veritabani baglantisini al
$pdo = get_db_connection();

// id parametresi gelirse mevcut urunu duzenleme moduna gec
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

// formda kullanilacak kategori listesini cek
$cats = $pdo->query("SELECT kategori_id, kategori_adi FROM kategoriler ORDER BY kategori_adi")->fetchAll();

// yeni urun icin varsayilan alan degerlerini hazirla
$product = [
    'barkod' => '',
    'urun_adi' => '',
    'kategori_id' => $cats[0]['kategori_id'] ?? null,
    'alis_fiyat' => '',
    'satis_fiyat' => '',
    'stok_miktar' => 0,
    'stok_alt_limit' => 0,
    'skt' => '',
    'birim' => 'adet',
    'aktif_mi' => 1,
];

// duzenleme modunda ise ilgili urunu veritabanindan getir
if ($editing) {
    $stmt = $pdo->prepare("SELECT * FROM urunler WHERE urun_id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
    if (!$product) {
        // gonderilen id ye ait urun yoksa basit bir hata mesaji goster
        die('Ürün bulunamadı');
    }
}

// form hatalarini gostermek icin degisken
$error = '';

// form post edildiginde gelen verileri isle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // post verilerini tek bir dizi icinde topla
    $data = [
        'barkod' => trim($_POST['barkod'] ?? ''),
        'urun_adi' => trim($_POST['urun_adi'] ?? ''),
        'kategori_id' => (int)($_POST['kategori_id'] ?? 0),
        'alis_fiyat' => $_POST['alis_fiyat'] !== '' ? (float)$_POST['alis_fiyat'] : null,
        'satis_fiyat' => (float)($_POST['satis_fiyat'] ?? 0),
        'stok_miktar' => (int)($_POST['stok_miktar'] ?? 0),
        'stok_alt_limit' => (int)($_POST['stok_alt_limit'] ?? 0),
        'skt' => $_POST['skt'] !== '' ? $_POST['skt'] : null,
        'birim' => $_POST['birim'] ?? 'adet',
        'aktif_mi' => isset($_POST['aktif_mi']) ? 1 : 0,
    ];

    // temel dogrulama: zorunlu alanlar ve sayisal kontroller
    if ($data['urun_adi'] === '' || $data['kategori_id'] === 0 || $data['satis_fiyat'] <= 0 || $data['stok_miktar'] < 0) {
        $error = 'Lütfen zorunlu alanları doldurun ve geçerli değerler girin.';
    } else {
        // durum duzenleme ise update, degilse insert sorgusu olustur
        if ($editing) {
            $sql = "UPDATE urunler SET barkod=:barkod, urun_adi=:urun_adi, kategori_id=:kategori_id,
                    alis_fiyat=:alis_fiyat, satis_fiyat=:satis_fiyat, stok_miktar=:stok_miktar,
                    stok_alt_limit=:stok_alt_limit, skt=:skt, birim=:birim, aktif_mi=:aktif_mi
                    WHERE urun_id=:id";
            $data[':id'] = $id;
        } else {
            $sql = "INSERT INTO urunler (barkod, urun_adi, kategori_id, alis_fiyat, satis_fiyat, stok_miktar, stok_alt_limit, skt, birim, aktif_mi)
                    VALUES (:barkod, :urun_adi, :kategori_id, :alis_fiyat, :satis_fiyat, :stok_miktar, :stok_alt_limit, :skt, :birim, :aktif_mi)";
        }

        // sql sorgusunda kullanilacak parametreleri hazirla
        $params = [
            ':barkod' => $data['barkod'] ?: null,
            ':urun_adi' => $data['urun_adi'],
            ':kategori_id' => $data['kategori_id'],
            ':alis_fiyat' => $data['alis_fiyat'],
            ':satis_fiyat' => $data['satis_fiyat'],
            ':stok_miktar' => $data['stok_miktar'],
            ':stok_alt_limit' => $data['stok_alt_limit'],
            ':skt' => $data['skt'],
            ':birim' => $data['birim'],
            ':aktif_mi' => $data['aktif_mi'],
        ];
        if ($editing) {
            $params[':id'] = $id;
        }

        // hazirlanan sorguyu veritabani uzerinde calistir
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // islemden sonra urunler listesine geri don
        header('Location: products.php');
        exit;
    }
}

// sayfa icin layout u baslat ve basliklari ayarla
render_layout_start($editing ? 'Ürün Düzenle' : 'Yeni Ürün', 'Ürün bilgilerini girin', 'products');
?>

<div class="card form-card">
    <div class="card-header between">
        <h3><?php echo $editing ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle'; ?></h3>
        <a class="btn ghost" href="products.php">Geri dön</a>
    </div>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" class="grid form-grid">
        <div>
            <label>Ürün Adı *</label>
            <input type="text" name="urun_adi" required value="<?php echo htmlspecialchars($_POST['urun_adi'] ?? $product['urun_adi']); ?>">
        </div>
        <div>
            <label>Barkod</label>
            <input type="text" name="barkod" value="<?php echo htmlspecialchars($_POST['barkod'] ?? $product['barkod']); ?>">
        </div>
        <div>
            <label>Kategori *</label>
            <select name="kategori_id" required>
                <?php foreach ($cats as $c): ?>
                    <option value="<?php echo $c['kategori_id']; ?>" <?php echo ((int)($product['kategori_id']) === (int)$c['kategori_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['kategori_adi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Birim</label>
            <select name="birim">
                <?php foreach (['adet','kg','lt'] as $b): ?>
                    <option value="<?php echo $b; ?>" <?php echo (($product['birim'] ?? 'adet') === $b) ? 'selected' : ''; ?>><?php echo strtoupper($b); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Alış Fiyatı</label>
            <input type="number" step="0.01" name="alis_fiyat" value="<?php echo htmlspecialchars($_POST['alis_fiyat'] ?? $product['alis_fiyat']); ?>">
        </div>
        <div>
            <label>Satış Fiyatı *</label>
            <input type="number" step="0.01" name="satis_fiyat" required value="<?php echo htmlspecialchars($_POST['satis_fiyat'] ?? $product['satis_fiyat']); ?>">
        </div>
        <div>
            <label>Stok Miktarı *</label>
            <input type="number" name="stok_miktar" required value="<?php echo htmlspecialchars($_POST['stok_miktar'] ?? $product['stok_miktar']); ?>">
        </div>
        <div>
            <label>Stok Alt Limit</label>
            <input type="number" name="stok_alt_limit" value="<?php echo htmlspecialchars($_POST['stok_alt_limit'] ?? $product['stok_alt_limit']); ?>">
        </div>
        <div>
            <label>SKT</label>
            <input type="date" name="skt" value="<?php echo htmlspecialchars($_POST['skt'] ?? $product['skt']); ?>">
        </div>
        <div class="checkbox-row">
            <label><input type="checkbox" name="aktif_mi" <?php echo (($product['aktif_mi'] ?? 1) ? 'checked' : ''); ?>> Aktif</label>
        </div>
        <div class="form-actions">
            <button class="btn" type="button" onclick="history.back()">İptal</button>
            <button class="btn primary" type="submit">Kaydet</button>
        </div>
    </form>
</div>

<?php
render_layout_end();

