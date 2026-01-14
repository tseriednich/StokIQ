<?php
// urunler sayfasi: aktif urunlerin listesini gosterir ve urun arama/filtreleme islevi sunar.
// kullanici bu sayfadan urunleri goruntuleyebilir, yeni urun ekleyebilir veya mevcut urunleri duzenleyebilir.
// sayfa urunler ve kategoriler tablolarini birlestirerek urun bilgilerini kategori adi ile birlikte listeler.
// arama islevi urun adi, barkod veya kategori adina gore filtreleme yapabilir.
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

// sayfaya erisim icin kullanicinin giris yapmis olmasini zorunlu kil
require_login();
// veritabani baglantisini al
$pdo = get_db_connection();

// arama kutusundan gelen sorgu metnini al
$q = trim($_GET['q'] ?? '');

// aktif urunleri kategori adi ile birlikte listeleyen temel sorgu
$query = "
    SELECT u.*, k.kategori_adi
    FROM urunler u
    JOIN kategoriler k ON k.kategori_id = u.kategori_id
    WHERE u.aktif_mi = 1
";
$params = [];

// eger arama terimi bos degilse urun adi, barkod veya kategori adina gore filtre uygula
if ($q !== '') {
    $query .= " AND (u.urun_adi LIKE :q OR u.barkod LIKE :q OR k.kategori_adi LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

// urunleri ada gore sirala
$query .= " ORDER BY u.urun_adi ASC";
// sorguyu hazirla ve calistir
$stmt = $pdo->prepare($query);
$stmt->execute($params);
// tum urun kayitlarini al
$products = $stmt->fetchAll();

// sayfa icin genel layout u baslat
render_layout_start('Ürün Yönetimi', 'Tüm ürünlerinizi buradan yönetin', 'products');
?>

<div class="card">
    <div class="card-header between">
        <div>
            <h3>Ürünler</h3>
            <p class="muted">Aktif ürün sayısı: <?php echo count($products); ?></p>
        </div>
        <a class="btn primary" href="product_form.php"><i class="fa fa-plus"></i> Yeni Ürün</a>
    </div>
    <form class="toolbar" method="get">
        <div class="input">
            <i class="fa fa-search"></i>
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ürün ara (barkod, ad, kategori)...">
        </div>
        <button class="btn ghost" type="submit">Filtrele</button>
    </form>
    <div class="table">
        <div class="table-head">
            <div>Ürün Adı</div>
            <div>Barkod</div>
            <div>Kategori</div>
            <div>Stok</div>
            <div>Fiyat</div>
            <div>Durum</div>
            <div>İşlem</div>
        </div>
        <?php foreach ($products as $p): 
            $status = 'Stokta';
            $statusClass = 'success';
            if ((int)$p['stok_miktar'] <= 0) { $status = 'Tükendi'; $statusClass = 'danger'; }
            elseif ((int)$p['stok_miktar'] <= (int)$p['stok_alt_limit']) { $status = 'Düşük'; $statusClass = 'warning'; }
        ?>
        <div class="table-row">
            <div><?php echo htmlspecialchars($p['urun_adi']); ?></div>
            <div><?php echo htmlspecialchars($p['barkod']); ?></div>
            <div><?php echo htmlspecialchars($p['kategori_adi']); ?></div>
            <div><?php echo (int)$p['stok_miktar']; ?> adet</div>
            <div>₺<?php echo number_format((float)$p['satis_fiyat'], 2, ',', '.'); ?></div>
            <div><span class="pill <?php echo $statusClass; ?>"><?php echo $status; ?></span></div>
            <div class="actions">
                <a class="btn primary small" href="product_form.php?id=<?php echo $p['urun_id']; ?>">Düzenle</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <div class="table-row empty">Kayıt bulunamadı.</div>
        <?php endif; ?>
    </div>
</div>

<?php
render_layout_end();
?>