<?php
/**
 * LAYOUT (ŞABLON) DOSYASI - GÜNCELLENDİ
 * * Bu dosya, sayfa yapısını oluşturur.
 * Sidebar artık harici 'sidebar.php' dosyasından çağrılmaktadır.
 * Bu sayede tüm sayfalarda sidebar boyutu ve duruşu %100 aynı kalır.
 */

require_once __DIR__ . '/../app/auth.php';

/**
 * render_head: HTML başlığını ve CSS bağlantılarını oluşturur.
 * @param string $title Sayfa sekmesinde görünecek başlık
 */
function render_head(string $title = 'StokIQ'): void
{
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - StokIQ</title>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600&display=swap" rel="stylesheet">
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-zN6K2YWSs5HblF5h6O4hA0vYV7FpC18JNpDutVdGs14Q6gttxyPjdvVSxGInxjeaUp43EIBosHuLl5E5T7k2Og==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        
        <link rel="stylesheet" href="assets.css">
    </head>
    <body>
    <?php
}

/**
 * render_sidebar: Sol taraftaki menüyü oluşturur.
 * ARTIK HTML KODLARI BURADA DEĞİL, sidebar.php İÇİNDEN GELİYOR.
 */
function render_sidebar(string $active = ''): void
{
    // Sidebar dosyasını dahil et
    // Not: sidebar.php dosyası public klasöründe, bu dosyanın yanında olmalı.
    if (file_exists(__DIR__ . '/sidebar.php')) {
        include __DIR__ . '/sidebar.php';
    } else {
        echo "<div style='color:red; padding:20px;'>HATA: sidebar.php dosyası bulunamadı!</div>";
    }
}

/**
 * render_topbar: Sayfanın en üstündeki başlık alanını oluşturur.
 */
function render_topbar(string $title, string $subtitle = ''): void
{
    ?>
    <header class="topbar">
        <div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <?php if ($subtitle): ?>
                <p class="subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </header>
    <?php
}

/**
 * render_layout_start: Sayfa içeriğini başlatan ana fonksiyon.
 */
function render_layout_start(string $pageTitle, string $subtitle = '', string $active = 'dashboard'): void
{
    render_head($pageTitle);
    ?>
    <div class="page">
        
        <?php 
        // Sidebar'ı çağır
        render_sidebar($active); 
        ?>
        
        <main class="content">
            <?php render_topbar($pageTitle, $subtitle); ?>
            <div class="page-inner">
    <?php
}

/**
 * render_layout_end: Sayfa içeriğini kapatan ve JS dosyalarını yükleyen fonksiyon.
 */
function render_layout_end(): void
{
    ?>
            </div> </main> </div> <script src="app.js"></script>
    </body>
    </html>
    <?php
}
?>