<?php
include_once("sys/sys_session.php");
$nama_halaman = "Register Perkara";
include_once("sys/header.php");
?>
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
        <h3 class="card-title">Daftar Perkara Teregister</h3>
        <div class="card-tools">
          <div class="input-group input-group-sm" style="width: 16rem">


          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="d-flex gap-2 mb-3 d-none">
          <button id="export-csv" type="button" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>
            Export CSV
          </button>
          <button id="export-json" type="button" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-filetype-json me-1" aria-hidden="true"></i>
            Export JSON
          </button>
          <button id="print-table" type="button" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1" aria-hidden="true"></i>
            Print
          </button>
        </div>
        <div id="results_content">
          <?php
          $table = '<div class="w3-responsive"><table class="w3-table-all w3-border" id="datane_result"><thead><tr>
<th>No</th>
<th>Nomor Perkara Banding</th>
<th>PA Pengaju</th>
<th>Nomor Perkara Tk. 1</th>
<th>Tanggal Pendaftaran</th>
<th>Tanggal Putusan</th>
<th>Status Banding</th>
<th>Link</th>
</tr></thead><tbody>';
          $sql = "SELECT 
          perkara_banding.id,
          perkara_banding.nomor_perkara_banding,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.tanggal_pendaftaran_banding) as tanggalpendaftaranbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_banding) AS putusanbanding,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        WHERE perkara_banding.tanggal_pendaftaran_banding IS NOT NULL 
        ORDER BY perkara_banding.tanggal_pendaftaran_banding DESC, nomor_urut_register DESC LIMIT 25";
          $query = mysqli_query($koneksi, $sql);
          $no = 0;
          while ($data = mysqli_fetch_assoc($query)) {
            $no++;
            $table .= '<tr>
  <td class="w3-center">' . $no . '</td>
  <td class="w3-left-align">' . $data["nomor_perkara_banding"] . '</td>
  <td class="w3-left-align">' . str_replace("PENGADILAN AGAMA", "PA", $data["pengaju"]) . '</td>
  <td class="w3-left-align">' . $data["nomor_perkara_pn"] . '</td>
  <td class="w3-left-align">' . $data["tanggalpendaftaranbanding"] . '</td>
  <td class="w3-left-align">' . $data["putusanbanding"] . '</td>
  <td class="w3-left-align">' . $data["status_banding_text"] . '</td>
  <td class="w3-center"><a href="perkara_detil_banding&id=' . $data["id"] . '" title="Detail Perkara">Link</a></td>
  </tr>';
          }
          if ($no == 0) {
            $table .= '<tr><td class="w3-center w3-text-red" colspan="11">Tidak ada Data</td></tr>';
          }
          $table .= "</tbody></table></div>";
          echo "$table";
          ?>
        </div>
      </div>
      <div class="card-footer text-secondary small">

      </div>
    </div>
  </div>
</div>



<script type="text/javascript">
  var table = new JSTable("#datane_result", {
    serverSide: true,
    deferLoading: <?php echo mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM perkara_banding WHERE tanggal_pendaftaran_banding IS NOT NULL ")) ?>,
    ajax: "register_perkara_data"
  });

  function __(object) {
    return document.getElementById(object);
  }
  function result_data() {
    __("loader").style = 'display:block';
    var xhr = new XMLHttpRequest();
    var url = 'api';
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
        __("results_content").innerHTML = xhr.responseText;
        //__("modal_Carbillo").style.display='block';
        var table = new DataTable("#datane_result", { perPage: 25, perPageSelect: [10, 25, 50, 100, 500] });
        __("loader").style.display = 'none';
      }
    }
    xhr.send("aksi=<?php echo base64_encode("result_data") ?>");
  }
</script>
<?php include_once("sys/footer.php"); ?>