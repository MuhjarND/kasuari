<?php
  $nama_halaman="Halaman tidak ditemukan";
  include_once("sys/sys_header.php");
?>
<div class="w3-row" id="AppContent">
  <div class="w3-content">
    <div class="w3-container">
      <div class="w3-row w3-center">
        <h3><?php echo $nama_halaman?></h3>
        <p class="w3-text-purple" style="font-size: 100px; line-height: 1em; margin: 20px 0 30px 0; font-weight: 900;">404</p>
      </div>
      <div class="w3-row">
      </div>
    </div>
    <div class="w3-row">
      <br><br><br><br>
    </div> 
  </div>
</div>
<script type="text/javascript">
  document.getElementById("loader").style.display="none";
</script>
<?php include_once("sys/sys_footer.php");?>

