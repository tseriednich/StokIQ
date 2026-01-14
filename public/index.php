<?php
// --------------------------------------------------------------------------
// KARŞILAMA SAYFASI (index.php)
// Amacı: Siteye giren ziyaretçiyi karşılamak ve giriş sayfasına yönlendirmek.
// Önemli Özellik: Eğer kullanıcı zaten giriş yapmışsa, bu sayfayı görmeden
// direkt Dashboard'a yönlendirilir.
// --------------------------------------------------------------------------

// 1. ADIM: Oturum kütüphanesini çağır
// current_user() fonksiyonunu kullanabilmek için auth.php'yi dahil ediyoruz.
require_once __DIR__ . '/../app/auth.php';

// 2. ADIM: Akıllı Yönlendirme (Redirection)
// Eğer kullanıcının zaten açık bir oturumu varsa (Giriş yapmışsa):
if (current_user()) {
    // Onu tekrar karşılama ekranında bekletme, direkt panele gönder.
    header('Location: dashboard.php');
    
    // Yönlendirme komutundan sonra kodun çalışmasını durdur (Güvenlik ve Performans için).
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StokIQ - Akıllı Stok Yönetimi</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets.css">
    
    <style>
        .landing-body {
            /* Arka plan için koyu lacivert gradient geçiş */
            background: linear-gradient(135deg, #1f3042 0%, #111827 100%);
            color: #fff;
            /* İçeriği tam ortalamak için Flexbox kullanımı */
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 100vh; /* Tüm ekranı kapla */
            text-align: center; padding: 20px;
        }
        
        /* Başlık Stili */
        .hero-title { font-size: 48px; font-weight: 800; margin-bottom: 16px; letter-spacing: -1px; }
        .hero-title span { color: #4f8bff; } /* 'IQ' kısmını mavi yap */
        
        /* Açıklama Yazısı Stili */
        .hero-desc { font-size: 18px; color: #9ca3af; max-width: 600px; margin-bottom: 40px; line-height: 1.6; }
        
        /* 'Hemen Başla' Butonu Stili */
        .hero-btn {
            background: #4f8bff; color: #fff; padding: 12px 36px; border-radius: 8px;
            font-size: 16px; font-weight: 600; text-decoration: none; transition: background 0.2s ease;
        }
        .hero-btn:hover { background: #3b76e1; } /* Mouse üzerine gelince koyulaş */
    </style>
</head>
<body class="landing-body">
    
    <div class="hero-title">Stok<span>IQ</span></div>
    
    <p class="hero-desc">
        İşletmeniz için modern, hızlı ve güvenli stok takip çözümü. 
        Envanterinizi kontrol altına alın, satışlarınızı analiz edin ve işinizi büyütün.
    </p>
    
    <a href="login.php" class="hero-btn">Hemen Başla</a>
    
</body>
</html>