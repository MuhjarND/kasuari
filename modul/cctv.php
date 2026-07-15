<?php
include_once("sys/sys_session.php");
$nama_halaman = "CCTV Badilag";
include_once("sys/header.php");
?>

<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">

<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="assets/plugins/jstable/jstable.css" />
<script src="assets/plugins/jstable/jstable.min.js" type="text/javascript"></script>
<!--begin::App Content Header-->
<div class="app-content-header">
  <!--begin::Container-->
  <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
      <div class="col-sm-6">
        <h1 class="mb-0 fs-3"><?php echo @$nama_halaman ?></h1>
      </div>
      <div class="col-sm-6">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item">
              <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo @$nama_halaman ?></li>
          </ol>
        </nav>
      </div>
    </div>
    <!--end::Row-->
  </div>
  <!--end::Container-->
</div>
<!--end::App Content Header-->
<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Laporan Pengawasan CCTV Badilag</h3>
        <div class="card-tools">
          <div class="input-group input-group-sm float-end">
            <label for="satker" class="form-label me-2 text-primary">Pilih Satker:</label>
                <select class="form-select bg-primary text-white" onchange="loadData(this.value)">
                    <option value="">Pilih Satker</option>
                    <option value="kaimana">PA. Kaimana</option>
                    <option value="fak-fak">PA. Fak-Fak</option>
                    <option value="manokwari">PA. Manokwari</option>
                    <option value="sorong">PA. Sorong</option>
                </select>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div id="isi">
          <?php if (isset($_GET['satker'])) {
            $satker = $_GET['satker'];
            include_once("actions/cctv/$satker.php");
           }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script> 
function loadData(satker) {
    window.location.href = "cctv&satker=" + satker;
}
  </script>
<?php include_once("sys/footer.php"); ?>