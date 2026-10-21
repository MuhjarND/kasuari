<?php

require_once __DIR__ . '/sys_password.php';

function kasuari_profile_statement($connection, $sql, $types = '', $values = array())
{
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        throw new RuntimeException('Layanan profil belum tersedia. Silakan hubungi administrator.');
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $code = mysqli_stmt_errno($stmt);
        mysqli_stmt_close($stmt);
        if ($code === 1062) {
            throw new InvalidArgumentException('Nama pengguna sudah digunakan.');
        }
        throw new RuntimeException('Profil belum dapat disimpan. Silakan coba kembali.');
    }
    return $stmt;
}

function kasuari_profile_photo_path($connection, $userId)
{
    $stmt = mysqli_prepare($connection, 'SELECT photo_path FROM sys_user_profiles WHERE userid = ?');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $photo);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $found && is_string($photo) && preg_match('~^uploads/profiles/[a-f0-9]{32}\.jpg$~D', $photo)
        && is_file(dirname(__DIR__) . '/' . $photo) ? $photo : null;
}

function kasuari_profile_delete_photo($path)
{
    if (is_string($path) && preg_match('~^uploads/profiles/[a-f0-9]{32}\.jpg$~D', $path)) {
        $file = dirname(__DIR__) . '/' . $path;
        if (is_file($file)) {
            unlink($file);
        }
    }
}

function kasuari_profile_upload_photo(array $file)
{
    if (!isset($file['error'], $file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK
        || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Pilih foto yang dapat diunggah, maksimal 2 MB.');
    }
    if (filesize($file['tmp_name']) > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran foto maksimal 2 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $size = @getimagesize($file['tmp_name']);
    if (!in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true) || !$size
        || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 16000000) {
        throw new InvalidArgumentException('Gunakan foto JPG, PNG, atau WEBP yang valid.');
    }
    if (!function_exists('imagecreatefromstring')) {
        throw new RuntimeException('Layanan foto belum tersedia. Silakan hubungi administrator.');
    }
    $source = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$source) {
        throw new InvalidArgumentException('Foto tidak dapat dibaca. Gunakan berkas lain.');
    }
    $scale = min(1, 512 / max($size[0], $size[1]));
    $width = max(1, (int) round($size[0] * $scale));
    $height = max(1, (int) round($size[1] * $scale));
    $canvas = imagecreatetruecolor($width, $height);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
    $directory = dirname(__DIR__) . '/uploads/profiles';
    $path = 'uploads/profiles/' . bin2hex(random_bytes(16)) . '.jpg';
    try {
        if ((!is_dir($directory) && !mkdir($directory, 0755, true))
            || !imagejpeg($canvas, dirname(__DIR__) . '/' . $path, 85)) {
            throw new RuntimeException('Foto belum dapat disimpan. Silakan coba kembali.');
        }
    } finally {
        imagedestroy($source);
        imagedestroy($canvas);
    }
    return $path;
}

function kasuari_profile_update($connection, $userId, array $input, array $files)
{
    $newPhoto = null;
    $oldPhoto = null;
    if (!mysqli_begin_transaction($connection)) {
        throw new RuntimeException('Layanan profil belum tersedia.');
    }
    try {
        // The caller supplies only the authenticated session ID, never a form user ID.
        $stmt = kasuari_profile_statement($connection,
            'SELECT userid, fullname, username, email, password, block FROM sys_users WHERE userid = ? FOR UPDATE',
            'i', array($userId));
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$user || (int) $user['block'] !== 0) {
            throw new InvalidArgumentException('Akun tidak aktif. Silakan hubungi administrator.');
        }
        $action = isset($input['action']) && is_string($input['action']) ? $input['action'] : '';
        $currentPassword = isset($input['current_password']) && is_string($input['current_password']) ? $input['current_password'] : '';
        if (in_array($action, array('identity', 'password'), true)
            && !kasuari_password_matches($currentPassword, $user['password'])) {
            throw new InvalidArgumentException('Password saat ini tidak sesuai.');
        }
        if ($action === 'identity') {
            $fullname = isset($input['fullname']) && is_string($input['fullname']) ? trim($input['fullname']) : '';
            $username = isset($input['username']) && is_string($input['username']) ? strtolower(trim($input['username'])) : '';
            $email = isset($input['email']) && is_string($input['email']) ? strtolower(trim($input['email'])) : '';
            if (strlen($fullname) < 3 || strlen($fullname) > 255) {
                throw new InvalidArgumentException('Nama lengkap harus berisi 3 sampai 255 karakter.');
            }
            if (!preg_match('/^[a-z0-9._-]{3,30}$/D', $username)) {
                throw new InvalidArgumentException('Username harus berisi 3-30 karakter: huruf kecil, angka, titik, garis bawah, atau tanda hubung.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                throw new InvalidArgumentException('Alamat email tidak valid.');
            }
            $stmt = kasuari_profile_statement($connection,
                'SELECT userid FROM sys_users WHERE userid <> ? AND (LOWER(TRIM(username)) = ? OR LOWER(TRIM(email)) = ?) LIMIT 1',
                'iss', array($userId, $username, $email));
            $duplicate = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
            mysqli_stmt_close($stmt);
            if ($duplicate) {
                throw new InvalidArgumentException('Username atau email sudah digunakan.');
            }
            $stmt = kasuari_profile_statement($connection,
                'UPDATE sys_users SET fullname = ?, username = ?, email = ?, diedit_oleh = ?, diedit_tanggal = NOW() WHERE userid = ? AND block = 0',
                'ssssi', array($fullname, $username, $email, $user['username'], $userId));
            mysqli_stmt_close($stmt);
            $user['fullname'] = $fullname;
            $user['username'] = $username;
            $user['email'] = $email;
        } elseif ($action === 'password') {
            $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
            $confirmation = isset($input['password_confirmation']) && is_string($input['password_confirmation']) ? $input['password_confirmation'] : '';
            if (strlen($password) < 8 || strlen($password) > 72) {
                throw new InvalidArgumentException('Password baru harus berisi 8 sampai 72 byte.');
            }
            if ($password !== $confirmation) {
                throw new InvalidArgumentException('Konfirmasi password tidak sama.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = kasuari_profile_statement($connection,
                'UPDATE sys_users SET password = ?, diedit_oleh = ?, diedit_tanggal = NOW() WHERE userid = ? AND block = 0',
                'ssi', array($hash, $user['username'], $userId));
            mysqli_stmt_close($stmt);
        } elseif (in_array($action, array('photo', 'remove_photo'), true)) {
            $oldPhoto = kasuari_profile_photo_path($connection, $userId);
            if ($action === 'photo') {
                $newPhoto = kasuari_profile_upload_photo(isset($files['photo']) && is_array($files['photo']) ? $files['photo'] : array());
            }
            $stmt = kasuari_profile_statement($connection,
                'INSERT INTO sys_user_profiles (userid, photo_path, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE photo_path = VALUES(photo_path), updated_at = NOW()',
                'is', array($userId, $newPhoto));
            mysqli_stmt_close($stmt);
        } else {
            throw new InvalidArgumentException('Permintaan perubahan profil tidak valid.');
        }
        if (!mysqli_commit($connection)) {
            throw new RuntimeException('Profil belum dapat disimpan. Silakan coba kembali.');
        }
    } catch (Throwable $error) {
        mysqli_rollback($connection);
        kasuari_profile_delete_photo($newPhoto);
        throw $error;
    }
    if ($oldPhoto && $oldPhoto !== $newPhoto) {
        kasuari_profile_delete_photo($oldPhoto);
    }
    unset($user['password']);
    return $user;
}
