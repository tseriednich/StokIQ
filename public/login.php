<?php
// login.php: Giriş sayfası
require_once __DIR__ . '/../app/auth.php';

// Çıkış işlemi (logout parametresi gelirse)
if (isset($_GET['logout'])) {
    logout();
}

// Oturum varsa direkt panele at
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Form gönderildiğinde giriş kontrolü yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Kullanıcı adı veya şifre hatalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - StokIQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets.css">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-logo">StokIQ</div>
        <h2>Giriş Yap</h2>
        <p class="subtitle">Hesabınıza erişin</p>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post" class="form">
            <label>Kullanıcı Adı</label>
            <div class="input">
                <i class="fa fa-user"></i>
                <input type="text" name="username" required placeholder="Kullanıcı adınız" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <label>Şifre</label>
            <div class="input">
                <i class="fa fa-lock"></i>
                <input type="password" name="password" required placeholder="******">
            </div>
            
            <button type="submit" class="btn primary full" style="margin-top: 10px;">Giriş Yap</button>
        </form>

        <div style="margin-top: 20px; font-size: 14px; color: #6b7280;">
            Hesabınız yok mu? <a href="register.php" style="color: #4f8bff; font-weight: 600;">Kayıt Ol</a>
        </div>
    </div>
</body>
</html>