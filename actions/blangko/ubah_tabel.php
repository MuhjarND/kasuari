<?php
$sql = "UPDATE $tabel SET $field ='$isi' WHERE id=$id"; 
$query=	mysqli_query($koneksi,$sql);
mysqli_close($koneksi);
?>