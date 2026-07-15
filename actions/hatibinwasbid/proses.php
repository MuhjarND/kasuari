<?php
include '../../sys/sys_koneksi.php';
if(isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    die("Aksi tidak ditentukan.");
    exit;
}

// --- PROSES INSERT ---
if ($action == 'insert') {
    $periode = $_POST['periode'];
    $tgl_mulai    = $_POST['tgl_mulai'];
    $tgl_sampai   = $_POST['tgl_sampai'];
    $tim          = $_POST['tim'];
    $catatan      = $_POST['catatan'];
    
    $sql = "INSERT INTO hatibinwasbid (periode, tgl_mulai, tgl_sampai, tim, catatan) VALUES ('$periode', '$tgl_mulai', '$tgl_sampai', '$tim', '$catatan')";
    $query = mysqli_query($koneksi, $sql);
    //echo "$sql";
    if(mysqli_affected_rows($koneksi)<>1){
         $last_id = mysqli_insert_id($koneksi);
         echo "Gagal menyimpan data.";
    }else{
         echo "Data berhasil ditambahkan!";
    }
    mysqli_close($koneksi);
}

// --- PROSES UPDATE ---
elseif ($action == 'update') {
    $id           = $_POST['id'];
    $periode = $_POST['periode'];
    $tgl_mulai    = $_POST['tgl_mulai'];
    $tgl_sampai   = $_POST['tgl_sampai'];
    $tim          = $_POST['tim'];
    $catatan      = $_POST['catatan'];
    $laporan      = $_POST['laporan'];
    
    $sql="UPDATE hatibinwasbid SET laporan='$laporan', periode='$periode', tgl_mulai='$tgl_mulai', tgl_sampai='$tgl_sampai', tim='$tim', catatan='$catatan' WHERE id=$id";
    $query = mysqli_query($koneksi, $sql);
    if(mysqli_affected_rows($koneksi)<>1){
         echo "Gagal mengupdate data.";
    }else{
         echo "Data berhasil diupdate!";
    }
    mysqli_close($koneksi);
}

// --- PROSES DELETE ---
elseif ($action == 'delete') {
    $id = $_POST['id'];
    
    $sql="delete FROM hatibinwasbid WHERE id = $id";
    $result_file = mysqli_query($koneksi, $sql);
    if(mysqli_affected_rows($koneksi)<>1){
         echo "Gagal Menghapus data.";
    }else{
         echo "Data berhasil dihapus!";
    }
    mysqli_close($koneksi);
}
 
?>