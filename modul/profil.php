<?php
require_once __DIR__ . '/../sys/sys_session.php';
require_once __DIR__ . '/../sys/sys_profile.php';
$nama_halaman = 'Profil Saya';
header('Cache-Control: no-store, private');
if (empty($_SESSION['csrf_profile'])) {
    $_SESSION['csrf_profile'] = bin2hex(random_bytes(32));
}
$errors = array();
$success = isset($_SESSION['profile_success']) ? $_SESSION['profile_success'] : '';
unset($_SESSION['profile_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_profile'], $csrf)) {
        http_response_code(403);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        try {
            $updated = kasuari_profile_update($koneksi, (int) $_SESSION['userid'], $_POST, $_FILES);
            $_SESSION['fullname'] = $updated['fullname'];
            $_SESSION['username'] = $updated['username'];
            $_SESSION['email'] = $updated['email'];
            $_SESSION['csrf_profile'] = bin2hex(random_bytes(32));
            $_SESSION['profile_success'] = 'Perubahan profil berhasil disimpan.';
            session_regenerate_id(true);
            header('Location: profil', true, 303);
            exit;
        } catch (InvalidArgumentException $error) {
            http_response_code(422);
            $errors[] = $error->getMessage();
        } catch (Throwable $error) {
            error_log('Kasuari profile update failed: ' . get_class($error));
            http_response_code(503);
            $errors[] = 'Profil belum dapat disimpan. Silakan coba kembali atau hubungi administrator.';
        }
    }
}
$csrfToken = $_SESSION['csrf_profile'];
$photo = kasuari_profile_photo_path($koneksi, (int) $_SESSION['userid']);
function kasuari_profile_escape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
require __DIR__ . '/../sys/header.php';
?>
<div class="app-content-header"><div class="container-fluid"><h1 class="h4 mb-0">Profil Saya</h1></div></div>
<div class="app-content"><div class="container-fluid kasuari-profile-page">
  <?php foreach ($errors as $error): ?><div class="alert alert-danger" role="alert"><?php echo kasuari_profile_escape($error); ?></div><?php endforeach; ?>
  <?php if ($success): ?><div class="alert alert-success" role="status"><?php echo kasuari_profile_escape($success); ?></div><?php endif; ?>
  <section class="kasuari-profile-section">
    <div><h2>Foto Profil</h2><p class="text-body-secondary mb-0"><?php echo kasuari_profile_escape($_SESSION['fullname']); ?></p></div>
    <form action="profil" method="post" enctype="multipart/form-data" class="kasuari-profile-photo-form">
      <input type="hidden" name="csrf_token" value="<?php echo kasuari_profile_escape($csrfToken); ?>">
      <div class="kasuari-profile-photo" id="profile-photo-preview">
        <?php if ($photo): ?><img src="<?php echo kasuari_profile_escape($photo); ?>" alt="Foto profil Anda"><?php else: ?><i class="bi bi-person" aria-hidden="true"></i><?php endif; ?>
      </div>
      <div class="flex-grow-1">
        <label class="form-label" for="profile-photo">Pilih foto</label>
        <input class="form-control" type="file" id="profile-photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button class="btn btn-primary" type="submit" name="action" value="photo"><i class="bi bi-upload" aria-hidden="true"></i> Simpan foto</button>
          <button class="btn btn-outline-danger" type="submit" name="action" value="remove_photo" formnovalidate <?php echo $photo ? '' : 'disabled'; ?>><i class="bi bi-trash" aria-hidden="true"></i> Hapus foto</button>
        </div>
      </div>
    </form>
  </section>
  <section class="kasuari-profile-section">
    <div><h2>Identitas Akun</h2></div>
    <form action="profil" method="post" class="kasuari-profile-fields">
      <input type="hidden" name="csrf_token" value="<?php echo kasuari_profile_escape($csrfToken); ?>"><input type="hidden" name="action" value="identity">
      <div><label class="form-label" for="profile-fullname">Nama lengkap</label><input class="form-control" id="profile-fullname" name="fullname" maxlength="255" required value="<?php echo kasuari_profile_escape($_SESSION['fullname']); ?>" autocomplete="name"></div>
      <div><label class="form-label" for="profile-username">Username</label><input class="form-control" id="profile-username" name="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9._-]{3,30}" required value="<?php echo kasuari_profile_escape($_SESSION['username']); ?>" autocomplete="username"></div>
      <div><label class="form-label" for="profile-email">Email</label><input class="form-control" id="profile-email" type="email" name="email" maxlength="100" required value="<?php echo kasuari_profile_escape($_SESSION['email']); ?>" autocomplete="email"></div>
      <div><label class="form-label" for="identity-password">Password saat ini</label><input class="form-control" id="identity-password" type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="kasuari-profile-wide"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan profil</button></div>
    </form>
  </section>
  <section class="kasuari-profile-section">
    <div><h2>Ubah Password</h2></div>
    <form action="profil" method="post" class="kasuari-profile-fields">
      <input type="hidden" name="csrf_token" value="<?php echo kasuari_profile_escape($csrfToken); ?>"><input type="hidden" name="action" value="password">
      <input type="hidden" name="username" autocomplete="username" value="<?php echo kasuari_profile_escape($_SESSION['username']); ?>">
      <div class="kasuari-profile-wide"><label class="form-label" for="password-current">Password saat ini</label><input class="form-control" id="password-current" type="password" name="current_password" required autocomplete="current-password"></div>
      <div><label class="form-label" for="password-new">Password baru</label><input class="form-control" id="password-new" type="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password"></div>
      <div><label class="form-label" for="password-confirm">Konfirmasi password baru</label><input class="form-control" id="password-confirm" type="password" name="password_confirmation" minlength="8" maxlength="72" required autocomplete="new-password"></div>
      <div class="kasuari-profile-wide"><button class="btn btn-primary" type="submit"><i class="bi bi-lock" aria-hidden="true"></i> Simpan password</button></div>
    </form>
  </section>
</div></div>
<script>
document.getElementById('profile-photo').addEventListener('change', function () {
  var preview = document.getElementById('profile-photo-preview');
  var file = this.files[0];
  this.setCustomValidity('');
  if (!file) return;
  if (file.size > 2 * 1024 * 1024 || ['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
    this.setCustomValidity('Gunakan foto JPG, PNG, atau WEBP maksimal 2 MB.');
    this.reportValidity();
    return;
  }
  var img = document.createElement('img');
  var url = URL.createObjectURL(file);
  img.alt = 'Pratinjau foto profil';
  img.onload = function () { URL.revokeObjectURL(url); };
  img.src = url;
  preview.replaceChildren(img);
});
</script>
<?php require __DIR__ . '/../sys/footer.php'; ?>
