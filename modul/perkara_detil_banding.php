<?php
include_once("sys/sys_session.php");
$nama_halaman = "INFORMASI DETAIL PERKARA BANDING";
include_once("sys/header.php");
$id = $_GET['id'];
$sql = "SELECT 
          perkara.jenis_perkara_nama,
          perkara_banding.nomor_urut_register ,
          perkara_banding.perkara_id,
          perkara_banding.pn_id,
          perkara_banding.id,
          perkara_banding.panitera_pengganti_banding,
          perkara_banding.majelis_hakim_banding,
          perkara_banding.status_banding_text,
          perkara_banding.nomor_perkara_banding,
          perkara_banding.nomor_perkara_pn,
          convert_tanggal_indonesia(perkara_banding.permohonan_banding) as permohonanbanding,
          convert_tanggal_indonesia(perkara_banding.pengiriman_berkas_banding) as pengirimanberkasbanding,
          convert_tanggal_indonesia(perkara_banding.tanggal_pendaftaran_banding) as tanggalpendaftaranbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_banding) AS putusanbanding,
          convert_tanggal_indonesia(perkara_banding.putusan_pn) AS putusanpn,
          perkara_banding.status_banding_text,
          pengadilan_agama.nama AS pengaju,
          perkara_banding.penerimaan_memori_banding,
          perkara_banding.penerimaan_kontra_banding,
          perkara_banding.pelaksanaan_inzage,
          perkara_putusan.amar_putusan,
          perkara_banding.pelaksanaan_inzage_terbanding
        FROM perkara_banding
        LEFT JOIN pengadilan_agama ON pengadilan_agama.id =perkara_banding.pn_id 
        LEFT JOIN perkara ON perkara.perkara_id =perkara_banding.perkara_id AND perkara.pn_id=perkara_banding.pn_id
        LEFT JOIN perkara_putusan ON perkara_putusan.perkara_id =perkara_banding.perkara_id AND perkara_putusan.pn_id=perkara_banding.pn_id
        WHERE perkara_banding.id=$id";
$query = mysqli_query($koneksi, $sql);
while ($data = mysqli_fetch_assoc($query)) {
    foreach ($data as $key => $value) {
        $$key = $value;
    }
}
?>
<link rel="stylesheet" type="text/css" href="assets/plugins/jstable/jstable.css" />
<script src="assets/plugins/jstable/jstable.min.js" type="text/javascript"></script>

<!--begin::App Content Header-->
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Row--> 
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
                <h3 class="card-title">INFORMASI DETAIL PERKARA BANDING</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 16rem">
                        <button type="button" class="btn btn-outline-primary form-control" data-bs-toggle="modal" data-bs-target="#modalDetailPenuh"> <i class="bi bi-arrows-printer  me-2"></i> Template Dokumen</button>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <div id="results_content">
                    <!-- Row Utama untuk Ringkasan Perkara -->
                    <div class="row g-3 mb-4">
                        <!-- Card Perkara Tingkat Banding -->
                        <div class="col-md-4">
                            <div class="card h-100 border-start border-primary border-2 shadow-sm">
                                <div class="card-body">
                                    <span class="text-muted d-block small text-uppercase fw-bold mb-1">Nomor Perkara
                                        Banding</span>
                                    <h4 class="text-primary fw-bolder mb-2"><?php echo $nomor_perkara_banding ?></h4>

                                    <span class="badge text-bg-secondary px-2 py-1 fs-7"> Register Tanggal
                                        <?php echo $tanggalpendaftaranbanding ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Perkara Tingkat Pertama -->
                        <div class="col-md-4">
                            <div class="card h-100 border-start border-secondary border-2 shadow-sm">
                                <div class="card-body">
                                    <span class="text-muted d-block small text-uppercase fw-bold mb-1">Pengadilan
                                        Pengaju (Tingkat I)</span>
                                    <h6 class="fw-bold text-dark mb-1"><?php echo $pengaju ?></h6>
                                    <span class="text-secondary  d-block">Nomor Perkara :
                                        <b><?php echo $nomor_perkara_pn ?></b></span>
                                    <span
                                        class="badge text-bg-secondary px-2 py-1 fs-7"><?php echo $jenis_perkara_nama ?></span>
                                    <span class="badge text-bg-secondary px-2 py-1 fs-7"> Putus Tanggal :
                                        <?php echo $putusanpn ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Status Terakhir -->
                        <div class="col-md-4">
                            <div class="card h-100 border-start border-success border-2 shadow-sm">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="text-center">
                                        <span class="text-muted d-block small text-uppercase fw-bold mb-1">Status
                                            Terakhir</span>
                                        <span class="badge text-bg-success fs-6 px-3 py-2 mt-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            <b><?php echo $status_banding_text ?></b>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Kolom Majelis Hakim -->
                        <div class="col-lg-6">
                            <div class="card card-outline card-primary shadow-sm h-100">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Majelis
                                        Hakim & PP</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="m-3">
                                        <?php echo $majelis_hakim_banding ?><br>Panitera Pengganti:
                                        <?php echo $panitera_pengganti_banding ?>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Kolom Dokumen & Inzage -->
                        <div class="col-lg-6">
                            <div class="card card-outline card-secondary shadow-sm h-100">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0 fw-bold"><i
                                            class="bi bi-calendar-event-fill me-2"></i>Timeline / Berkas</h5>
                                </div>


                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label text-muted small fw-bold mb-1">Memori
                                                Banding</label>
                                            <input class="form-control form-control-sm bg-light fw-bold" type="date"
                                                value="<?php echo $penerimaan_memori_banding ?>"
                                                onchange="edit_tabel('perkara_banding', 'penerimaan_memori_banding', 'id', <?php echo $id ?>, this.value)">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label text-muted small fw-bold mb-1">Kontra Memori
                                                Banding</label>
                                            <input class="form-control form-control-sm bg-light fw-bold" type="date"
                                                value="<?php echo $penerimaan_kontra_banding ?>"
                                                onchange="edit_tabel('perkara_banding', 'penerimaan_kontra_banding', 'id', <?php echo $id ?>, this.value)">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label text-muted small fw-bold mb-1">Inzage
                                                Pembanding</label>
                                            <input class="form-control form-control-sm bg-light fw-bold" type="date"
                                                value="<?php echo $pelaksanaan_inzage ?>"
                                                onchange="edit_tabel('perkara_banding', 'pelaksanaan_inzage', 'id', <?php echo $id ?>, this.value)">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label text-muted small fw-bold mb-1">Inzage
                                                Terbanding</label>
                                            <input class="form-control form-control-sm bg-light fw-bold" type="date"
                                                value="<?php echo $pelaksanaan_inzage_terbanding ?>"
                                                onchange="edit_tabel('perkara_banding', 'pelaksanaan_inzage_terbanding', 'id', <?php echo $id ?>, this.value)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="card-title mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Daftar Pihak
                                Banding</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php
                                $sql1 = "SELECT 
                            CASE WHEN 
                              pihak_diwakili='Y' 
                            THEN concat(perkara_banding_detil.pihak_nama, '<br>(',perkara_banding_detil.pemohon_nama,')')
                            ELSE 
                                perkara_banding_detil.pihak_nama
                            END AS namane
                            
                            ,perkara_banding_detil.pihak_asal_text
                            ,perkara_banding_detil.status_pihak_text
                      FROM perkara_banding_detil
                      WHERE perkara_banding_detil.perkara_id=$perkara_id AND pn_id=$pn_id ORDER BY status_pihak_id ASC, urutan_banding ASC";
                                $query1 = mysqli_query($koneksi, $sql1);
                                $no = 0;
                                while ($data1 = mysqli_fetch_assoc($query1)) {
                                    foreach ($data1 as $key => $value) {
                                        $$key = $value;
                                    }
                                    $no++;
                                    echo '<li  class="list-group-item p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-bolder text-dark">' . $namane . '</h6>
                                            <span
                                                class="badge bg-light text-secondary border border-secondary-subtle small">' . $pihak_asal_text . '</span>
                                        </div>
                                        <!--<small class="text-muted d-block">
                                            <i class="bi bi-briefcase me-1 text-primary"></i> Kuasa Hukum: <strong
                                                class="text-dark">Marhendra Handoko, SHI, MH</strong>
                                        </small>-->
                                    </div>
                                    <div class="text-sm-end">
                                        <span
                                            class="badge text-bg-primary px-3 py-2 fs-7 rounded-pill">' . $status_pihak_text . '</span>
                                    </div>
                                </li>';
                                }

                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0 text-dark fw-bold">
                                <i class="bi bi-gavel me-2 text-danger"></i> Amar Putusan Tingkat Pertama (Pengadilan Agama)
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Blok Khusus dengan border kiri tebal (Aksen Putusan) -->
                            <div class="p-3 rounded bg-light border-start border-danger border-4 text-dark lh-lg shadow-inner ks-rich-text"
                                style="white-space: pre-line; fs-7;">
                               <?php echo kasuari_safe_rich_text($amar_putusan ?? '', 'Belum ada amar putusan.'); ?>
                            </div>
                        </div>
                    </div> 
            </div>
            <div class="card-footer text-secondary small">

            </div>
        </div>
    </div>
</div>

<!-- Modal Full Screen -->
<div class="modal fade" id="modalDetailPenuh" tabindex="-1" aria-labelledby="modalDetailPenuhLabel" aria-hidden="true">
    <!-- Class kunci di bawah ini: modal-fullscreen -->
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-light">

            <!-- Header Modal -->
            <div class="modal-header bg-white border-bottom shadow-sm py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="p-2 bg-primary-subtle rounded-3 text-primary me-3">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bolder text-dark mb-0" id="modalDetailPenuhLabel">
                            Penyusunan Draft Putusan/ Penetapan Nomor Perkara: <?php echo $nomor_perkara_banding?>
                        </h5>
                        <small class="text-muted">Silahkan Pilih Kategori Blangko</small>
                    </div>
                </div>
                <!-- Tombol Close Bawaan Bootstrap 5 -->
                <button type="button" class="btn-close fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body Modal (Konten Utama) -->
            <div class="modal-body p-4" style="overflow-y: auto;">
                <div class="container-fluid">
                    <div class="row g-4">

                        <div class="card card-primary card-outline mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Kategori Blangko</h3>
                            </div>
                            <div class="card-body" id="modal_Results_isi">

                                <!-- Mulai Accordion -->
                                <div class="accordion" id="accordionAdminLTE">

                                    <!-- Item Accordion 1 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingOne">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseOne" aria-expanded="true"
                                                aria-controls="collapseOne">
                                                Item Accordion Pertama
                                            </button>
                                        </h2>
                                        <div id="collapseOne" class="accordion-collapse collapse "
                                            aria-labelledby="headingOne" data-bs-parent="#accordionAdminLTE">
                                            <div class="accordion-body">
                                                <strong>Ini adalah isi (body) dari item pertama.</strong> Secara
                                                default, item ini terbuka karena menggunakan kelas
                                                <code>collapse show</code>. Anda dapat meletakkan teks, form, atau
                                                bahkan tabel di dalam sini.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="modal-footer bg-white border-top shadow-sm justify-content-between py-3 px-4">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Tekan <kbd>ESC</kbd> untuk keluar dari tampilan penuh.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary px-4">
                        <i class="bi bi-printer me-2"></i> Cetak Dokumen
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">

<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>

<script type="text/javascript">
    function __(object) {
        return document.getElementById(object);
    }
    function pilih_blangko() {
        __("loader").style = 'display:block';
        var xhr = new XMLHttpRequest();
        var url = 'api';
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
                __("modal_Results_isi").innerHTML = xhr.responseText;
                __("loader").style.display = 'none';
            }
        }
        xhr.send("aksi=<?php echo base64_encode("pilih_blangko") ?>&id=" + btoa("<?php echo $id ?>"));
    }
    function myFunct(id) {
        var x = document.getElementById(id);
        if (x.classList) {
            x.classList.toggle("w3-show");
            //x.previousElementSibling.classList.toggle("w3-dark-grey");
        } else {
            // Fallback for IE9 and earlier
            if (x.className.indexOf("w3-show") == -1)
                x.className = x.className + " w3-show";
            else
                x.className = x.className.replace("w3-show", "");
        }
    }
    function tutup_modal() {
        __("modal_Results").style.display = "none";
    }
    function buka_blangko(url) {
        //alert(url);
        window.location.replace(url);
    }
    function edit_tabel(tabel, field, kunci, id, isi) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "api", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
                var pesan = xhr.responseText;
                notifier.show('Pesan!', pesan, '', '', 5000);
            }
        }
        xhr.send("aksi=" + btoa("edit_tabel") + "&tabel=" + btoa(tabel) + "&field=" + btoa(field) + "&kunci=" + btoa(kunci) + "&id=" + btoa(id) + "&isi=" + btoa(isi));
    }
    pilih_blangko();
</script>
<?php include_once("sys/footer.php"); ?>
