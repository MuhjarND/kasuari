<?php
include '../../sys/sys_koneksi.php';


if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $sql = "SELECT * FROM hatibinwasbis WHERE id=$id";
    $query=mysqli_query($koneksi,$sql);
    $data=mysqli_fetch_assoc($query);
    
    echo json_encode($data);
}
?>