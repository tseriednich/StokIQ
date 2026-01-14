<?php
// users.php: Kullanıcıları ve yetkilerini yönetme sayfası
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/layout.php';

require_login();
$currentUser = current_user();

// GÜVENLİK: Sadece 'admin' rolündekiler bu sayfaya girebilir!
if (($currentUser['role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pdo = get_db_connection();
$msg = '';
$msgType = '';

// Form İşlemleri (Rol Güncelleme veya Silme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)$_POST['user_id'];
    
    // Kendisi üzerinde işlem yapmaya çalışıyorsa engelle
    if ($targetId === (int)$currentUser['id']) {
        $msg = 'Kendi yetkilerinizi değiştiremez veya kendinizi silemezsiniz!';
        $msgType = 'error';
    } else {
        // Rol Güncelleme
        if (isset($_POST['update_role'])) {
            $newRole = $_POST['role'];
            if (in_array($newRole, ['admin', 'personel'])) {
                $stmt = $pdo->prepare("UPDATE kullanicilar SET rol = :rol WHERE kullanici_id = :id");
                $stmt->execute([':rol' => $newRole, ':id' => $targetId]);
                $msg = 'Kullanıcı yetkisi güncellendi.';
                $msgType = 'success';
            }
        }
        // Kullanıcı Silme
        elseif (isset($_POST['delete_user'])) {
            $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE kullanici_id = :id");
            $stmt->execute([':id' => $targetId]);
            $msg = 'Kullanıcı silindi.';
            $msgType = 'success';
        }
    }
}

// Tüm kullanıcıları çek
$users = $pdo->query("SELECT * FROM kullanicilar ORDER BY kullanici_id ASC")->fetchAll();

render_layout_start('Kullanıcı Yönetimi', 'Personel ve yetki ayarları', 'users');
?>

<div class="card">
    <div class="card-header">
        <h3>Kullanıcı Listesi</h3>
        <p class="muted">Toplam <?php echo count($users); ?> kayıtlı kullanıcı var.</p>
    </div>

    <?php if ($msg): ?>
        <div class="alert <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table">
        <div class="table-head" style="grid-template-columns: 1fr 2fr 1fr 2fr;">
            <div>ID</div>
            <div>Kullanıcı Adı / E-posta</div>
            <div>Mevcut Rol</div>
            <div>İşlem</div>
        </div>
        <?php foreach ($users as $u): ?>
        <div class="table-row" style="grid-template-columns: 1fr 2fr 1fr 2fr;">
            <div>#<?php echo $u['kullanici_id']; ?></div>
            <div>
                <div style="font-weight:600;"><?php echo htmlspecialchars($u['kullanici_adi']); ?></div>
                <div style="font-size:12px; color:#888;"><?php echo htmlspecialchars($u['email'] ?? '-'); ?></div>
            </div>
            <div>
                <?php if ($u['rol'] === 'admin'): ?>
                    <span class="pill info">Admin</span>
                <?php else: ?>
                    <span class="pill neutral">Personel</span>
                <?php endif; ?>
            </div>
            <div>
                <?php if ((int)$u['kullanici_id'] !== (int)$currentUser['id']): ?>
                    <form method="post" style="display:flex; gap:8px; align-items:center;">
                        <input type="hidden" name="user_id" value="<?php echo $u['kullanici_id']; ?>">
                        
                        <select name="role" style="padding:6px; font-size:13px; width:auto;">
                            <option value="personel" <?php echo $u['rol'] === 'personel' ? 'selected' : ''; ?>>Personel</option>
                            <option value="admin" <?php echo $u['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        
                        <button type="submit" name="update_role" class="btn small primary" title="Yetkiyi Kaydet">
                            <i class="fa fa-save"></i>
                        </button>

                        <button type="submit" name="delete_user" class="btn small danger" title="Kullanıcıyı Sil" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="muted" style="font-size:12px;">(Kendiniz)</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php render_layout_end(); ?>