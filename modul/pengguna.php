<?php
include_once("sys/sys_session.php");
include_once("sys/sys_config.php");
include_once("sys/sys_authorization.php");
kasuari_require_admin();

$nama_halaman = "Kelola Pengguna";
$errors = array();
$form = array(
  'fullname' => '',
  'username' => '',
  'email' => ''
);

if (empty($_SESSION['csrf_pengguna'])) {
  $_SESSION['csrf_pengguna'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_pengguna'];

function kasuari_managed_user_group($connection, $userId)
{
  $targetGroup = null;
  $find = mysqli_prepare($connection, "SELECT `group` FROM sys_users WHERE userid = ? LIMIT 1");
  mysqli_stmt_bind_param($find, 'i', $userId);
  mysqli_stmt_execute($find);
  mysqli_stmt_bind_result($find, $targetGroup);
  $found = mysqli_stmt_fetch($find);
  mysqli_stmt_close($find);
  return $found ? (int) $targetGroup : null;
}

function kasuari_user_identity_errors($fullname, $username, $email)
{
  $validationErrors = array();
  if (strlen($fullname) < 3 || strlen($fullname) > 255) {
    $validationErrors[] = 'Nama lengkap harus berisi 3 sampai 255 karakter.';
  }
  if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) {
    $validationErrors[] = 'Nama pengguna harus berisi 3 sampai 30 karakter: huruf kecil, angka, titik, garis bawah, atau tanda hubung.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $validationErrors[] = 'Alamat email tidak valid.';
  }
  return $validationErrors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
  if (!hash_equals($csrfToken, $postedToken)) {
    $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
  } else {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

    if ($action === 'create') {
      $form['fullname'] = trim(isset($_POST['fullname']) ? $_POST['fullname'] : '');
      $form['username'] = strtolower(trim(isset($_POST['username']) ? $_POST['username'] : ''));
      $form['email'] = strtolower(trim(isset($_POST['email']) ? $_POST['email'] : ''));
      $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
      $passwordConfirmation = isset($_POST['password_confirmation']) ? (string) $_POST['password_confirmation'] : '';

      $errors = array_merge($errors, kasuari_user_identity_errors($form['fullname'], $form['username'], $form['email']));
      if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
      }
      if ($password !== $passwordConfirmation) {
        $errors[] = 'Konfirmasi password tidak sama.';
      }

      if (empty($errors)) {
        $check = mysqli_prepare($koneksi, "SELECT userid FROM sys_users WHERE username = ? OR email = ? LIMIT 1");
        mysqli_stmt_bind_param($check, 'ss', $form['username'], $form['email']);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
          $errors[] = 'Nama pengguna atau email sudah digunakan.';
        }
        mysqli_stmt_close($check);
      }

      if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $group = 1;
        $block = 0;
        $createdBy = isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'administrator';
        $insert = mysqli_prepare(
          $koneksi,
          "INSERT INTO sys_users
            (fullname, username, password, `group`, email, block, diinput_oleh, diinput_tanggal)
           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        mysqli_stmt_bind_param(
          $insert,
          'sssisis',
          $form['fullname'],
          $form['username'],
          $passwordHash,
          $group,
          $form['email'],
          $block,
          $createdBy
        );

        if (mysqli_stmt_execute($insert)) {
          $_SESSION['pengguna_flash'] = array(
            'type' => 'success',
            'message' => 'Akun ' . $form['username'] . ' berhasil dibuat.'
          );
          mysqli_stmt_close($insert);
          header('Location: pengguna');
          exit;
        }

        $errors[] = 'Akun belum dapat disimpan. Periksa koneksi database dan coba kembali.';
        mysqli_stmt_close($insert);
      }
    } elseif ($action === 'update') {
      $targetUserId = isset($_POST['userid']) ? (int) $_POST['userid'] : 0;
      $currentUserId = isset($_SESSION['userid']) ? (int) $_SESSION['userid'] : 0;
      $editFullname = trim(isset($_POST['edit_fullname']) ? $_POST['edit_fullname'] : '');
      $editUsername = strtolower(trim(isset($_POST['edit_username']) ? $_POST['edit_username'] : ''));
      $editEmail = strtolower(trim(isset($_POST['edit_email']) ? $_POST['edit_email'] : ''));
      $targetGroup = kasuari_managed_user_group($koneksi, $targetUserId);

      if ($targetUserId <= 0 || $targetGroup === null || ((int) $targetGroup === 0 && $targetUserId !== $currentUserId)) {
        $errors[] = 'Administrator hanya dapat mengubah akun miliknya sendiri.';
      } else {
        $errors = array_merge($errors, kasuari_user_identity_errors($editFullname, $editUsername, $editEmail));
      }

      if (empty($errors)) {
        $check = mysqli_prepare(
          $koneksi,
          "SELECT userid FROM sys_users WHERE (username = ? OR email = ?) AND userid <> ? LIMIT 1"
        );
        mysqli_stmt_bind_param($check, 'ssi', $editUsername, $editEmail, $targetUserId);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
          $errors[] = 'Nama pengguna atau email sudah digunakan akun lain.';
        }
        mysqli_stmt_close($check);
      }

      if (empty($errors)) {
        $editedBy = isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'administrator';
        $update = mysqli_prepare(
          $koneksi,
          "UPDATE sys_users
           SET fullname = ?, username = ?, email = ?, diedit_oleh = ?, diedit_tanggal = NOW()
           WHERE userid = ?"
        );
        mysqli_stmt_bind_param($update, 'ssssi', $editFullname, $editUsername, $editEmail, $editedBy, $targetUserId);
        if (mysqli_stmt_execute($update)) {
          $_SESSION['pengguna_flash'] = array('type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.');
          mysqli_stmt_close($update);
          header('Location: pengguna');
          exit;
        }
        $errors[] = 'Data pengguna belum dapat diperbarui.';
        mysqli_stmt_close($update);
      }
    } elseif ($action === 'toggle_status') {
      $targetUserId = isset($_POST['userid']) ? (int) $_POST['userid'] : 0;
      $currentUserId = isset($_SESSION['userid']) ? (int) $_SESSION['userid'] : 0;

      if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
        $errors[] = 'Status akun tersebut tidak dapat diubah.';
      } else {
        $targetGroup = null;
        $targetBlock = null;
        $find = mysqli_prepare($koneksi, "SELECT `group`, block FROM sys_users WHERE userid = ? LIMIT 1");
        mysqli_stmt_bind_param($find, 'i', $targetUserId);
        mysqli_stmt_execute($find);
        mysqli_stmt_bind_result($find, $targetGroup, $targetBlock);
        $found = mysqli_stmt_fetch($find);
        mysqli_stmt_close($find);

        if (!$found || (int) $targetGroup === 0) {
          $errors[] = 'Akun Administrator tidak dapat dinonaktifkan dari halaman ini.';
        } else {
          $newBlock = (int) $targetBlock === 1 ? 0 : 1;
          $editedBy = isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'administrator';
          $update = mysqli_prepare(
            $koneksi,
            "UPDATE sys_users SET block = ?, diedit_oleh = ?, diedit_tanggal = NOW()
             WHERE userid = ? AND `group` <> 0"
          );
          mysqli_stmt_bind_param($update, 'isi', $newBlock, $editedBy, $targetUserId);
          if (mysqli_stmt_execute($update)) {
            $_SESSION['pengguna_flash'] = array(
              'type' => 'success',
              'message' => $newBlock === 1 ? 'Akun berhasil dinonaktifkan.' : 'Akun berhasil diaktifkan.'
            );
            mysqli_stmt_close($update);
            header('Location: pengguna');
            exit;
          }
          $errors[] = 'Status akun belum dapat diperbarui.';
          mysqli_stmt_close($update);
        }
      }
    } elseif ($action === 'reset_password') {
      $targetUserId = isset($_POST['userid']) ? (int) $_POST['userid'] : 0;
      $currentUserId = isset($_SESSION['userid']) ? (int) $_SESSION['userid'] : 0;
      $newPassword = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
      $newPasswordConfirmation = isset($_POST['new_password_confirmation']) ? (string) $_POST['new_password_confirmation'] : '';
      $targetGroup = kasuari_managed_user_group($koneksi, $targetUserId);

      if ($targetUserId <= 0 || $targetGroup === null || ((int) $targetGroup === 0 && $targetUserId !== $currentUserId)) {
        $errors[] = 'Administrator hanya dapat mengubah password miliknya sendiri.';
      }
      if (strlen($newPassword) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
      }
      if ($newPassword !== $newPasswordConfirmation) {
        $errors[] = 'Konfirmasi password baru tidak sama.';
      }

      if (empty($errors)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $editedBy = isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'administrator';
        $update = mysqli_prepare(
          $koneksi,
          "UPDATE sys_users SET password = ?, diedit_oleh = ?, diedit_tanggal = NOW()
           WHERE userid = ?"
        );
        mysqli_stmt_bind_param($update, 'ssi', $passwordHash, $editedBy, $targetUserId);
        if (mysqli_stmt_execute($update)) {
          $_SESSION['pengguna_flash'] = array('type' => 'success', 'message' => 'Password pengguna berhasil diperbarui.');
          mysqli_stmt_close($update);
          header('Location: pengguna');
          exit;
        }
        $errors[] = 'Password pengguna belum dapat diperbarui.';
        mysqli_stmt_close($update);
      }
    } elseif ($action === 'delete') {
      $targetUserId = isset($_POST['userid']) ? (int) $_POST['userid'] : 0;
      $targetGroup = kasuari_managed_user_group($koneksi, $targetUserId);

      if ($targetUserId <= 0 || $targetGroup === null || $targetGroup === 0) {
        $errors[] = 'Akun Administrator tidak dapat dihapus.';
      } else {
        $delete = mysqli_prepare($koneksi, "DELETE FROM sys_users WHERE userid = ? AND `group` <> 0");
        mysqli_stmt_bind_param($delete, 'i', $targetUserId);
        if (mysqli_stmt_execute($delete)) {
          $_SESSION['pengguna_flash'] = array('type' => 'success', 'message' => 'Akun pengguna berhasil dihapus.');
          mysqli_stmt_close($delete);
          header('Location: pengguna');
          exit;
        }
        $errors[] = 'Akun pengguna belum dapat dihapus.';
        mysqli_stmt_close($delete);
      }
    }
  }
}

$flash = isset($_SESSION['pengguna_flash']) ? $_SESSION['pengguna_flash'] : null;
unset($_SESSION['pengguna_flash']);

$users = array();
$userQuery = mysqli_query(
  $koneksi,
  "SELECT userid, fullname, username, `group`, email, block, diinput_tanggal
   FROM sys_users
   ORDER BY (`group` = 0) DESC, fullname ASC, userid ASC"
);
if ($userQuery) {
  while ($user = mysqli_fetch_assoc($userQuery)) {
    $users[] = $user;
  }
}

include_once("sys/header.php");
?>

<div class="app-content-header">
  <div class="container-fluid">
    <div class="kasuari-page-title">
      <div>
        <h1><?php echo htmlspecialchars($nama_halaman, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Kelola akun yang dapat masuk ke aplikasi KASUARI.</p>
      </div>
      <span class="kasuari-chip">
        <i class="bi bi-people" aria-hidden="true"></i>
        <?php echo count($users); ?> akun
      </span>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <?php if ($flash) { ?>
      <div class="alert alert-success kasuari-alert" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php } ?>

    <?php if (!empty($errors)) { ?>
      <div class="alert alert-danger kasuari-alert" role="alert">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        <div>
          <?php foreach ($errors as $error) { ?>
            <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>

    <div class="row g-4 align-items-start">
      <div class="col-xl-4">
        <section class="kasuari-panel kasuari-user-form-panel">
          <div class="kasuari-toolbar">
            <div>
              <span class="kasuari-section-kicker">Akun Baru</span>
              <h3>Tambah Pengguna</h3>
            </div>
            <span class="kasuari-section-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
          </div>

          <form method="post" action="pengguna" class="kasuari-user-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="create">

            <div class="mb-3">
              <label for="fullname" class="form-label">Nama Lengkap</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                <input type="text" class="form-control" id="fullname" name="fullname" maxlength="255" required
                  value="<?php echo htmlspecialchars($form['fullname'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="new_username" class="form-label">Nama Pengguna</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-at" aria-hidden="true"></i></span>
                <input type="text" class="form-control" id="new_username" name="username" maxlength="30" required
                  value="<?php echo htmlspecialchars($form['username'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <input type="email" class="form-control" id="email" name="email" maxlength="100" required
                  value="<?php echo htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                <input type="password" class="form-control" id="new_password" name="password" minlength="8" required autocomplete="new-password">
              </div>
            </div>

            <div class="mb-4">
              <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
              </div>
            </div>

            <div class="kasuari-role-preview">
              <span class="kasuari-role-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
              <div>
                <small>Peran akun</small>
                <strong>Pengguna</strong>
              </div>
              <i class="bi bi-lock-fill" aria-label="Peran dikunci"></i>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4">
              <i class="bi bi-person-plus" aria-hidden="true"></i>
              Buat Akun
            </button>
          </form>
        </section>
      </div>

      <div class="col-xl-8">
        <section class="kasuari-panel kasuari-user-list-panel">
          <div class="kasuari-toolbar">
            <div>
              <span class="kasuari-section-kicker">Akses Aplikasi</span>
              <h3>Daftar Pengguna</h3>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle kasuari-user-table mb-0">
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Peran</th>
                  <th>Status</th>
                  <th>Dibuat</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user) {
                  $isAdminUser = (int) $user['group'] === 0;
                  $isCurrentUser = (int) $user['userid'] === (int) $_SESSION['userid'];
                  $isBlocked = (int) $user['block'] === 1;
                  $userName = trim((string) $user['fullname']);
                  $userInitial = strtoupper(function_exists('mb_substr') ? mb_substr($userName, 0, 1) : substr($userName, 0, 1));
                  $createdDate = !empty($user['diinput_tanggal']) ? date('d M Y', strtotime($user['diinput_tanggal'])) : '-';
                ?>
                  <tr>
                    <td>
                      <div class="kasuari-user-identity">
                        <span class="kasuari-user-avatar <?php echo $isAdminUser ? 'admin' : ''; ?>">
                          <?php echo htmlspecialchars($userInitial !== '' ? $userInitial : 'U', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <div>
                          <strong><?php echo htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8'); ?></strong>
                          <span>@<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="kasuari-role-badge <?php echo $isAdminUser ? 'admin' : 'user'; ?>">
                        <i class="bi <?php echo $isAdminUser ? 'bi-shield-check' : 'bi-person'; ?>" aria-hidden="true"></i>
                        <?php echo $isAdminUser ? 'Administrator' : 'Pengguna'; ?>
                      </span>
                    </td>
                    <td>
                      <span class="kasuari-account-status <?php echo $isBlocked ? 'inactive' : 'active'; ?>">
                        <?php echo $isBlocked ? 'Nonaktif' : 'Aktif'; ?>
                      </span>
                    </td>
                    <td><span class="kasuari-user-date"><?php echo htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td class="text-end">
                      <?php if (!$isAdminUser || $isCurrentUser) { ?>
                        <div class="kasuari-user-actions">
                          <button type="button" class="kasuari-icon-action edit" title="Ubah data pengguna"
                            aria-label="Ubah data pengguna"
                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                            data-user-id="<?php echo (int) $user['userid']; ?>"
                            data-user-fullname="<?php echo htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-user-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                          </button>

                          <button type="button" class="kasuari-icon-action password" title="Reset password"
                            aria-label="Reset password"
                            data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                            data-user-id="<?php echo (int) $user['userid']; ?>"
                            data-user-name="<?php echo htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-key" aria-hidden="true"></i>
                          </button>

                          <?php if (!$isAdminUser) { ?>
                            <form method="post" action="pengguna" class="d-inline">
                              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                              <input type="hidden" name="action" value="toggle_status">
                              <input type="hidden" name="userid" value="<?php echo (int) $user['userid']; ?>">
                              <button type="submit" class="kasuari-icon-action <?php echo $isBlocked ? 'activate' : 'deactivate'; ?>"
                                title="<?php echo $isBlocked ? 'Aktifkan akun' : 'Nonaktifkan akun'; ?>"
                                aria-label="<?php echo $isBlocked ? 'Aktifkan akun' : 'Nonaktifkan akun'; ?>">
                                <i class="bi <?php echo $isBlocked ? 'bi-person-check' : 'bi-person-x'; ?>" aria-hidden="true"></i>
                              </button>
                            </form>

                            <form method="post" action="pengguna" class="d-inline"
                              onsubmit="return confirm('Hapus akun @<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>?');">
                              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="userid" value="<?php echo (int) $user['userid']; ?>">
                              <button type="submit" class="kasuari-icon-action delete" title="Hapus akun" aria-label="Hapus akun">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                              </button>
                            </form>
                          <?php } ?>
                        </div>
                      <?php } else { ?>
                        <span class="kasuari-protected-account" title="Akun utama dilindungi"><i class="bi bi-lock-fill"></i></span>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>

<div class="modal fade kasuari-user-modal" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="pengguna">
        <div class="modal-header">
          <div>
            <span class="kasuari-section-kicker">Data Akun</span>
            <h2 class="modal-title" id="editUserModalLabel">Ubah Pengguna</h2>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="userid" id="edit_userid">

          <div class="mb-3">
            <label for="edit_fullname" class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" id="edit_fullname" name="edit_fullname" maxlength="255" required>
          </div>
          <div class="mb-3">
            <label for="edit_username" class="form-label">Nama Pengguna</label>
            <input type="text" class="form-control" id="edit_username" name="edit_username" maxlength="30" required>
          </div>
          <div>
            <label for="edit_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="edit_email" name="edit_email" maxlength="100" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2" aria-hidden="true"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade kasuari-user-modal" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="pengguna">
        <div class="modal-header">
          <div>
            <span class="kasuari-section-kicker">Keamanan Akun</span>
            <h2 class="modal-title" id="resetPasswordModalLabel">Reset Password</h2>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="userid" id="reset_userid">

          <p class="kasuari-reset-target">Password baru untuk <strong id="reset_user_name">pengguna</strong></p>
          <div class="mb-3">
            <label for="reset_new_password" class="form-label">Password Baru</label>
            <input type="password" class="form-control" id="reset_new_password" name="new_password" minlength="8" required autocomplete="new-password">
          </div>
          <div>
            <label for="reset_password_confirmation" class="form-label">Konfirmasi Password</label>
            <input type="password" class="form-control" id="reset_password_confirmation" name="new_password_confirmation" minlength="8" required autocomplete="new-password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-key" aria-hidden="true"></i> Perbarui Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var editModal = document.getElementById('editUserModal');
  var resetModal = document.getElementById('resetPasswordModal');

  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('edit_userid').value = button.getAttribute('data-user-id') || '';
      document.getElementById('edit_fullname').value = button.getAttribute('data-user-fullname') || '';
      document.getElementById('edit_username').value = button.getAttribute('data-user-username') || '';
      document.getElementById('edit_email').value = button.getAttribute('data-user-email') || '';
    });
  }

  if (resetModal) {
    resetModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('reset_userid').value = button.getAttribute('data-user-id') || '';
      document.getElementById('reset_user_name').textContent = button.getAttribute('data-user-name') || 'pengguna';
      document.getElementById('reset_new_password').value = '';
      document.getElementById('reset_password_confirmation').value = '';
    });
  }
});
</script>

<?php include_once("sys/footer.php"); ?>
