<?php
include_once("sys/sys_session.php");
$nama_halaman = "Hatibinwasbid";
include_once("sys/header.php");
?>

<link rel="stylesheet" href="assets/plugins/notifier/css/notifier.min.css">
<script src="assets/plugins/notifier/js/notifier.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="assets/plugins/jstable/jstable.css" />
<script src="assets/plugins/jstable/jstable.min.js" type="text/javascript"></script>

<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="kasuari-page-title">
      <div>
        <h1>Hatibinwasbid</h1>
        <p>Laporan Pengawasan Hakim Tinggi Pengawas Bidang — PTA Papua Barat.</p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2" onclick="tambahData()">
        <i class="bi bi-plus-lg"></i> Tambah Kegiatan
      </button>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<!--begin::App Content-->
<div class="app-content">
  <div class="container-fluid">
    <div class="kasuari-panel">

      <div class="kasuari-panel-title">
        <h3><i class="bi bi-clipboard2-pulse me-2" style="color:#3b82f6;"></i>Daftar Kegiatan Pengawasan Bidang</h3>
        <span id="badge-total" class="kasuari-chip">
          <i class="bi bi-calendar2-check"></i>
          <span id="jumlah-kegiatan">—</span> kegiatan
        </span>
      </div>

      <div class="kasuari-table-wrap table-responsive">
        <table id="datane" class="table align-middle">
          <thead>
            <tr>
              <th style="width:50px;">No</th>
              <th>Periode</th>
              <th>Pelaksanaan</th>
              <th>Tim</th>
              <th>Laporan</th>
              <th>Catatan</th>
              <th style="width:90px;">Aksi</th>
            </tr>
          </thead>
          <tbody id="dataKegiatan">
            <tr>
              <td colspan="7" class="kasuari-empty-state">
                <div class="py-4">
                  <i class="bi bi-hourglass-split d-block mb-2" style="font-size:1.5rem;color:#9ca3af;"></i>
                  Memuat data…
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
<!--end::App Content-->

<!-- ── Modal Tambah / Edit ─────────────────────────────────── -->
<div class="modal fade" id="modalKegiatan" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:14px;border:1px solid #e1e8f0;box-shadow:0 20px 48px rgba(15,31,61,.14);">

      <div class="modal-header" style="background:#0f1f3d;border-radius:14px 14px 0 0;padding:18px 22px;">
        <h5 class="modal-title" id="modalTitle"
            style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;color:#fff;font-size:1rem;margin:0;">
          Tambah Kegiatan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form id="formKegiatan">
        <div class="modal-body" style="padding:22px;">
          <input type="hidden" name="id"     id="id">
          <input type="hidden" name="action" id="action" value="insert">

          <div class="mb-3">
            <label class="form-label" for="periode">Periode</label>
            <input type="text" class="form-control" id="periode" name="periode"
                   list="list_periode" placeholder="Contoh: Triwulan I Tahun <?php echo date('Y'); ?>"
                   required autocomplete="off">
            <datalist id="list_periode">
              <option value="Triwulan I Tahun <?php echo date('Y'); ?>">
              <option value="Triwulan II Tahun <?php echo date('Y'); ?>">
              <option value="Triwulan III Tahun <?php echo date('Y'); ?>">
              <option value="Triwulan IV Tahun <?php echo date('Y'); ?>">
            </datalist>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label" for="tgl_mulai">Tanggal Mulai</label>
              <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label" for="tgl_sampai">Tanggal Selesai</label>
              <input type="date" class="form-control" id="tgl_sampai" name="tgl_sampai" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="tim">Tim Pengawas</label>
            <textarea class="form-control" id="tim" name="tim" rows="4"
                      placeholder="Ketua Tim: &#10;Anggota (Hakim Tinggi): &#10;Sekretaris/Pendamping:"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label" for="laporan">URL Laporan</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
              <input type="text" class="form-control" id="laporan" name="laporan"
                     placeholder="https://…" required>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label" for="catatan">Catatan</label>
            <textarea class="form-control" id="catatan" name="catatan" rows="2"
                      placeholder="Catatan tambahan (opsional)"></textarea>
          </div>
        </div>

        <div class="modal-footer" style="border-top:1px solid #e1e8f0;padding:14px 22px;background:#f8fafd;border-radius:0 0 14px 14px;">
          <button type="button" class="btn btn-outline-danger btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Simpan
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
let modalKegiatan;

document.addEventListener("DOMContentLoaded", function () {
  modalKegiatan = new bootstrap.Modal(document.getElementById('modalKegiatan'));
  loadData();

  document.getElementById('formKegiatan').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…';

    fetch('actions/hatibinwasbid/proses.php', { method: 'POST', body: new FormData(this) })
      .then(r => r.text())
      .then(data => {
        notifier.show('Berhasil', data, '', '', 4000);
        modalKegiatan.hide();
        loadData();
      })
      .catch(() => notifier.show('Error', 'Terjadi kesalahan sistem.', '', '', 4000))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan';
      });
  });
});

function loadData() {
  fetch('actions/hatibinwasbid/load_data.php')
    .then(r => r.text())
    .then(data => {
      document.getElementById('dataKegiatan').innerHTML = data;
      const rows = document.querySelectorAll('#dataKegiatan tr[data-id]');
      document.getElementById('jumlah-kegiatan').textContent = rows.length;
    })
    .catch(() => {
      document.getElementById('dataKegiatan').innerHTML =
        '<tr><td colspan="7" class="kasuari-empty-state text-center py-4">Gagal memuat data.</td></tr>';
    });
}

function tambahData() {
  document.getElementById('formKegiatan').reset();
  document.getElementById('id').value     = '';
  document.getElementById('action').value = 'insert';
  document.getElementById('modalTitle').innerText = 'Tambah Kegiatan';
  modalKegiatan.show();
}

function editData(id) {
  const fd = new FormData();
  fd.append('id', id);
  fetch('actions/hatibinwasbid/get_data.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      document.getElementById('id').value        = data.id;
      document.getElementById('action').value    = 'update';
      document.getElementById('periode').value   = data.periode ?? data.satuan_kerja ?? '';
      document.getElementById('laporan').value   = data.laporan;
      document.getElementById('tgl_mulai').value = data.tgl_mulai;
      document.getElementById('tgl_sampai').value= data.tgl_sampai;
      document.getElementById('tim').value       = data.tim;
      document.getElementById('catatan').value   = data.catatan;
      document.getElementById('modalTitle').innerText = 'Edit Kegiatan';
      modalKegiatan.show();
    })
    .catch(() => notifier.show('Error', 'Gagal mengambil data.', '', '', 4000));
}

function hapusData(id) {
  if (!confirm("Hapus data kegiatan ini beserta seluruh file laporannya?")) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fetch('actions/hatibinwasbid/proses.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(data => { notifier.show('Berhasil', data, '', '', 4000); loadData(); })
    .catch(() => notifier.show('Error', 'Gagal menghapus data.', '', '', 4000));
}

let myTable = new JSTable("#datane");
</script>

<?php include_once("sys/footer.php"); ?>