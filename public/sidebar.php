<?php
// --------------------------------------------------------------------------
// SIDEBAR.PHP: YAN MENÜ
// Amacı: Sol taraftaki navigasyon menüsünü oluşturmak ve
// kullanıcının yetkisine göre (Admin/Personel) linkleri filtrelemek.
// --------------------------------------------------------------------------

// 1. GÜVENLİK VE KULLANICI BİLGİSİ
// Eğer bu dosya tek başına çağrılırsa hata vermesin diye auth.php kontrolü yapıyoruz.
if (!function_exists('current_user')) {
    require_once __DIR__ . '/../app/auth.php';
}

$user = current_user();
// Null Coalescing (??) operatörü: Kullanıcı rolü yoksa varsayılan 'personel' olsun.
$role = $user['role'] ?? 'personel'; 
$displayName = htmlspecialchars($user['username'] ?? 'Misafir');
// Kullanıcının baş harfini al (Avatar için)
$initial = strtoupper(mb_substr($displayName, 0, 1));

// 2. AKTİF SAYFAYI BULMA
// Tarayıcı adres çubuğundaki dosya adını (örn: 'dashboard.php') alır.
// Bu bilgi, menüdeki ilgili butonu "Mavi" (active) yakmak için kullanılır.
$currentPage = basename($_SERVER['PHP_SELF']);

// 3. MENÜ LİNKLERİ LİSTESİ (DİZİ)
// HTML'de tek tek <a> etiketi yazmak yerine bir dizi (array) oluşturuyoruz.
// Böylece yeni bir sayfa eklemek istersek sadece buraya yazmamız yeterli.
$items = [
    'dashboard.php' => ['label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
    'products.php'  => ['label' => 'Ürünler', 'icon' => 'fa-box'],
    'sales.php'     => ['label' => 'Satış Analizi', 'icon' => 'fa-square-poll-vertical'],
    'promotions.php'=> ['label' => 'Promosyonlar', 'icon' => 'fa-gift'],
];

// 4. YETKİ KONTROLÜ (Yetkisiz Menüleri Gizle)
// Eğer giren kişi 'admin' ise listeye özel yönetim sayfalarını da ekle.
// Personel giriş yaparsa bu if bloğu çalışmayacak ve bu linkleri göremeyecek.
if ($role === 'admin') {
    $items['reports.php']  = ['label' => 'Raporlar', 'icon' => 'fa-file-lines'];
    $items['users.php']    = ['label' => 'Kullanıcılar', 'icon' => 'fa-users'];
    $items['settings.php'] = ['label' => 'Ayarlar', 'icon' => 'fa-gear'];
}
?>

<aside class="sidebar">
    <div class="brand">StokIQ</div>
    
    <div class="profile-box">
        <div class="avatar"><?php echo $initial; ?></div>
        <div class="profile-meta">
            <div class="name"><?php echo $displayName; ?></div>
            <div class="email" style="font-size: 11px; opacity: 0.7; text-transform:capitalize;">
                <?php echo htmlspecialchars($role); ?> Hesabı
            </div>
        </div>
    </div>

    <nav class="menu">
        <?php foreach ($items as $file => $item): ?>
            <a class="menu-item <?php echo ($currentPage === $file) ? 'active' : ''; ?>" href="<?php echo $file; ?>">
                <i class="fa <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a class="menu-item" href="logout.php">
            <i class="fa fa-right-from-bracket"></i>
            <span>Çıkış</span>
        </a>
    </div>
</aside>