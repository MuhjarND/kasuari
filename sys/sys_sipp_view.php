<?php
require_once __DIR__ . '/sys_sipp.php';

function kasuari_sipp_status_badge($state)
{
    if (!$state) { $tone = 'neutral'; $label = 'Belum disinkronkan'; }
    elseif ($state['state'] === 'ready') { $tone = 'success'; $label = 'Tersedia'; }
    elseif ($state['state'] === 'missing_columns') { $tone = 'warning'; $label = 'Kolom belum lengkap'; }
    else { $tone = 'warning'; $label = 'Sumber belum tersedia'; }
    return '<span class="ks-sipp-status ks-sipp-status--' . $tone . '">' . kasuari_sipp_escape($label) . '</span>';
}

function kasuari_sipp_group_icon($group)
{
    $icons = array('Perkara' => 'files', 'Persidangan' => 'calendar3', 'Putusan' => 'journal-check',
        'Mediasi' => 'people', 'Upaya Hukum' => 'bank', 'Eksekusi' => 'file-earmark-check',
        'Keuangan' => 'cash-stack', 'Arsip' => 'archive', 'Delegasi' => 'send',
        'Administrasi Berkas' => 'folder2-open', 'Layanan Elektronik' => 'globe2', 'Penugasan' => 'person-badge');
    return $icons[$group] ?? 'grid';
}

function kasuari_sipp_render_directory($pn, $catalog, $states, $filter)
{
    $e = 'kasuari_sipp_escape'; $groups = array(); $available = 0; $unavailable = 0; $records = 0; $latest = '';
    foreach ($catalog as $id => $spec) {
        $groups[$spec['group']][$id] = $spec;
        $state = $states[$id] ?? null;
        if (!$state) continue;
        if ($state['state'] === 'ready') $available++; else $unavailable++;
        if ($state['synced_at']) $records += (int) $state['row_count'];
        if (($state['checked_at'] ?? '') > $latest) $latest = $state['checked_at'];
    }
    $query = $filter['q'] ?? ''; $category = $filter['group'] ?? ''; $matches = 0;
    foreach ($catalog as $spec) if (($category === '' || $category === $spec['group']) && ($query === '' || mb_stripos($spec['label'] . ' ' . $spec['group'], $query) !== false)) $matches++;
    ?>
    <dl class="ks-sipp-summary" aria-label="Ringkasan sinkronisasi">
      <div><dt>Modul tersedia</dt><dd><?php echo $available; ?><small> / <?php echo count($catalog); ?></small></dd></div>
      <div><dt>Record tersimpan</dt><dd><?php echo $states ? number_format($records, 0, ',', '.') : '-'; ?></dd></div>
      <div><dt>Sumber belum tersedia</dt><dd><?php echo $states ? $unavailable : '-'; ?></dd></div>
      <div><dt>Pengiriman terakhir</dt><dd class="ks-sipp-summary-date"><?php echo $latest ? $e(kasuari_tanggal_indonesia(substr($latest, 0, 10))) : 'Belum ada'; ?><?php if ($latest): ?><small><?php echo $e(substr($latest, 11)); ?> WIT</small><?php endif; ?></dd></div>
    </dl>
    <section class="ks-sipp-section ks-sipp-directory" data-sipp-directory>
      <div class="ks-sipp-section-title"><h2>Direktori modul</h2><span data-sipp-result-count aria-live="polite"><?php echo $matches; ?> modul</span></div>
      <form method="get" class="ks-sipp-directory-tools" data-sipp-directory-form>
        <input type="hidden" name="modul" value="sipp"><input type="hidden" name="satker" value="<?php echo $pn; ?>">
        <label class="ks-sipp-search" for="sipp-module-search"><span class="visually-hidden">Cari modul</span><i class="bi bi-search" aria-hidden="true"></i><input id="sipp-module-search" name="q" type="search" placeholder="Cari modul..." value="<?php echo $e($query); ?>" maxlength="150" autocomplete="off"></label>
        <label for="sipp-module-group" class="visually-hidden">Kelompok modul</label><select class="form-select" id="sipp-module-group" name="group"><option value="">Semua kelompok</option><?php foreach ($groups as $group => $items): ?><option value="<?php echo $e($group); ?>" <?php echo $category === $group ? 'selected' : ''; ?>><?php echo $e($group); ?></option><?php endforeach; ?></select>
        <button class="btn btn-outline-secondary ks-sipp-icon-button" type="reset" title="Reset filter modul" aria-label="Reset filter modul"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button><noscript><button class="btn btn-primary" type="submit">Cari</button></noscript>
      </form>
      <div class="ks-sipp-directory-columns" aria-hidden="true"><span>Modul</span><span>Data</span><span>Sinkronisasi terakhir</span><span>Status</span><span></span></div>
      <?php foreach ($groups as $group => $items): $groupMatches = 0;
        foreach ($items as $spec) if (($category === '' || $category === $group) && ($query === '' || mb_stripos($spec['label'] . ' ' . $group, $query) !== false)) $groupMatches++;
        ?>
      <section class="ks-sipp-module-group" data-sipp-group="<?php echo $e($group); ?>" <?php echo $groupMatches ? '' : 'hidden'; ?>>
        <h3><?php echo $e($group); ?><span><?php echo count($items); ?> modul</span></h3>
        <?php foreach ($items as $id => $spec): $state = $states[$id] ?? null;
            $visible = ($category === '' || $category === $group) && ($query === '' || mb_stripos($spec['label'] . ' ' . $group, $query) !== false); ?>
        <a class="ks-sipp-module" data-sipp-label="<?php echo $e($spec['label'] . ' ' . $group); ?>" href="<?php echo $e(kasuari_sipp_url($pn, $id)); ?>" <?php echo $visible ? '' : 'hidden'; ?>>
          <span class="ks-sipp-module-name"><i class="bi bi-<?php echo kasuari_sipp_group_icon($group); ?>" aria-hidden="true"></i><span><?php echo $e($spec['label']); ?></span></span>
          <span class="ks-sipp-module-count"><span class="visually-hidden">Record: </span><?php echo $state && $state['synced_at'] ? number_format($state['row_count'], 0, ',', '.') : '-'; ?></span>
          <span class="ks-sipp-module-time"><?php if ($state && $state['synced_at']): ?><time datetime="<?php echo $e(str_replace(' ', 'T', $state['synced_at']) . '+09:00'); ?>"><?php echo $e(kasuari_tanggal_indonesia(substr($state['synced_at'], 0, 10))); ?><small><?php echo $e(substr($state['synced_at'], 11)); ?> WIT</small></time><?php else: ?>-<?php endif; ?></span>
          <span class="ks-sipp-module-state"><?php echo kasuari_sipp_status_badge($state); ?><?php if ($state && $state['state'] !== 'ready' && $state['synced_at']): ?><small>Snapshot sebelumnya</small><?php endif; ?></span>
          <i class="bi bi-arrow-right ks-sipp-module-arrow" aria-hidden="true"></i>
        </a>
        <?php endforeach; ?>
      </section>
      <?php endforeach; ?>
      <div class="ks-sipp-empty" data-sipp-no-modules <?php echo $matches ? 'hidden' : ''; ?>><i class="bi bi-search" aria-hidden="true"></i><h3>Modul tidak ditemukan</h3></div>
    </section>
    <?php
}

function kasuari_sipp_render_source_menu($db, $pn, $ready, $filter)
{
    $e = 'kasuari_sipp_escape';
    $config = $ready ? kasuari_sipp_select($db, 'SELECT source_url FROM kasuari_sipp_tokens WHERE pn_id=?', 'i', array($pn)) : array();
    $sourceUrl = $config[0]['source_url'] ?? '';
    $file = fopen(__DIR__ . '/sipp_menu_inventory.csv', 'r');
    if (!$file) { echo '<div class="alert alert-warning">Inventaris menu belum tersedia.</div>'; return; }
    $headers = fgetcsv($file); $headers[0] = ltrim($headers[0], "\xef\xbb\xbf");
    $menus = array();
    while (($values = fgetcsv($file)) !== false) {
        $menu = array_combine($headers, $values);
        if ($filter['q'] === '' || stripos($menu['hierarki'], $filter['q']) !== false) $menus[] = $menu;
    }
    fclose($file);
    $page = min(max(1, (int) $filter['page']), max(1, (int) ceil(count($menus) / 50)));
    ?>
    <section class="ks-sipp-section"><h2>Menu pada salinan SIPP</h2><p>Definisi menu mengikuti salinan SIPP Manokwari, termasuk menu lingkungan peradilan lain. Hak akses dan operasi pada aplikasi asal mengikuti akun SIPP satker.</p>
      <?php if (!$sourceUrl): ?><p class="text-secondary">Alamat SIPP satker belum diatur. <a href="<?php echo $e(kasuari_sipp_url($pn, 'integrasi')); ?>">Atur koneksi</a></p><?php endif; ?>
      <form method="get" class="ks-sipp-filters"><input type="hidden" name="modul" value="sipp"><input type="hidden" name="satker" value="<?php echo $pn; ?>"><input type="hidden" name="bagian" value="menu_sumber"><label for="menu-q">Cari menu<input class="form-control" id="menu-q" name="q" value="<?php echo $e($filter['q']); ?>"></label><button class="btn btn-primary">Cari</button></form>
      <div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Menu</th><th>Status definisi</th><th>Aplikasi asal</th></tr></thead><tbody>
      <?php foreach (array_slice($menus, ($page - 1) * 50, 50) as $menu): ?>
        <tr><td><?php echo $e($menu['hierarki']); ?></td><td><?php echo $menu['aktif_dengan_induk'] === '1' ? 'Published pada salinan' : 'Tidak aktif pada salinan'; ?></td><td><?php if ($sourceUrl && preg_match('/^[a-z0-9_\/-]+$/iD', $menu['route'])): ?><a href="<?php echo $e($sourceUrl . ltrim($menu['route'], '/')); ?>" target="_blank" rel="noopener noreferrer">Buka SIPP <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a><?php else: ?>-<?php endif; ?></td></tr>
      <?php endforeach; if (!$menus): ?><tr><td colspan="3">Tidak ada menu yang sesuai.</td></tr><?php endif; ?></tbody></table></div>
      <nav class="ks-sipp-pagination" aria-label="Halaman menu"><span><?php echo count($menus); ?> menu · Halaman <?php echo $page; ?></span><div><?php foreach (array(-1 => 'Sebelumnya', 1 => 'Berikutnya') as $offset => $label): if ($page + $offset < 1 || ($page + $offset - 1) * 50 >= count($menus)) continue; ?><a class="btn btn-outline-primary" href="<?php echo $e(kasuari_sipp_url($pn, 'menu_sumber', array('q' => $filter['q'], 'page' => $page + $offset))); ?>"><?php echo $label; ?></a> <?php endforeach; ?></div></nav>
    </section>
    <?php
}

function kasuari_sipp_render_timeline($db, $pn, $caseId, $catalog)
{
    $e = 'kasuari_sipp_escape';
    $rows = kasuari_sipp_select($db, 'SELECT dataset, record_key, data_json FROM kasuari_sipp_rows WHERE pn_id=? AND case_id=? ORDER BY record_date, dataset LIMIT 1001', 'is', array($pn, $caseId));
    echo '<h3 class="fs-6 mt-4">Riwayat dan data terkait perkara</h3><div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Tanggal</th><th>Peristiwa</th><th>Modul</th></tr></thead><tbody>';
    $events = array();
    foreach (array_slice($rows, 0, 1000) as $row) {
        $spec = $catalog[$row['dataset']]; $data = json_decode($row['data_json'], true);
        foreach ($data as $field => $value) {
            if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                $events[] = array('date' => $value, 'label' => $spec['fields'][$field], 'dataset' => $row['dataset'], 'key' => $row['record_key']);
            }
        }
    }
    usort($events, function ($a, $b) { return strcmp($b['date'], $a['date']); });
    foreach (array_slice($events, 0, 200) as $event) echo '<tr><td>' . $e(kasuari_tanggal_indonesia($event['date'])) . '</td><td>' . $e($event['label']) . '</td><td><a href="' . $e(kasuari_sipp_url($pn, $event['dataset'], array('key' => $event['key']))) . '">' . $e($catalog[$event['dataset']]['label']) . '</a></td></tr>';
    if (!$events) echo '<tr><td colspan="3">Belum ada peristiwa bertanggal pada snapshot modul terkait.</td></tr>';
    echo '</tbody></table></div><p class="text-secondary">Maksimal 200 peristiwa ditampilkan. Filter ID perkara ' . $e($caseId) . ' tersedia pada masing-masing daftar modul.</p>';
    echo '<details class="ks-sipp-related-disclosure"><summary>Data terkait perkara</summary><div class="ks-sipp-related">';
    foreach ($catalog as $key => $spec) if ($spec['case_field']) echo '<a href="' . $e(kasuari_sipp_url($pn, $key, array('case_id' => $caseId))) . '">' . $e($spec['label']) . '</a>';
    echo '</div></details>';
}

function kasuari_sipp_render_report($db, $pn, $catalog, $states, $filter, $mode)
{
    $e = 'kasuari_sipp_escape';
    $from = $filter['from'] ?: date('Y-01-01'); $to = $filter['to'] ?: date('Y-m-d');
    echo '<section class="ks-sipp-section"><h2>' . ($mode === 'kualitas' ? 'Kelengkapan snapshot modul' : 'Rekap perkara dan penugasan') . '</h2>';
    if ($mode === 'statistik') {
        echo '<form class="ks-sipp-filters" method="get"><input type="hidden" name="modul" value="sipp"><input type="hidden" name="satker" value="' . $pn . '"><input type="hidden" name="bagian" value="statistik"><label>Dari<input class="form-control" type="date" name="from" value="' . $e($from) . '"></label><label>Sampai<input class="form-control" type="date" name="to" value="' . $e($to) . '"></label><button class="btn btn-primary">Tampilkan</button><button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Cetak</button></form>';
        if (isset($states['perkara'], $states['putusan']) && $states['perkara']['state'] === 'ready' && $states['putusan']['state'] === 'ready') {
            $data = kasuari_sipp_statistics($db, $pn, $from, $to);
            echo '<div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Jenis perkara</th><th>Sisa awal</th><th>Masuk</th><th>Putus</th><th>Sisa akhir</th></tr></thead><tbody>';
            foreach ($data['types'] as $type => $totals) echo '<tr><td>' . $e($type) . '</td><td>' . $totals['awal'] . '</td><td>' . $totals['masuk'] . '</td><td>' . $totals['putus'] . '</td><td>' . $totals['akhir'] . '</td></tr>';
            echo '</tbody></table></div><p class="text-secondary">Rekap berdasarkan tanggal daftar dan tanggal putusan yang tercatat. Sisa tidak ditentukan dari label status terakhir. Perubahan atau koreksi sumber mengikuti snapshot masing-masing modul.</p>';
        } else echo '<div class="alert alert-info">Rekap masuk/putus/sisa menunggu snapshot Register Perkara dan Metadata Putusan yang lengkap.</div>';
        if (kasuari_sipp_schema($db)) {
            $workload = kasuari_sipp_workload($db, $pn);
            echo '<h3 class="fs-6 mt-4">Penugasan aktif hakim, PP, dan jurusita</h3><div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Peran</th><th>Petugas</th><th>Perkara unik</th></tr></thead><tbody>';
            foreach ($workload as $row) echo '<tr><td>' . $e($row['role']) . '</td><td>' . $e($row['name']) . '</td><td>' . $row['count'] . '</td></tr>';
            if (!$workload) echo '<tr><td colspan="3">Penugasan aktif belum tercatat pada snapshot.</td></tr>';
            echo '</tbody></table></div>';
        }
    }
    echo '<h3 class="fs-6 mt-4">Ketersediaan data per modul</h3><p class="text-secondary">Data yang belum tersedia tidak dihitung sebagai nol aktivitas satker.</p><div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Modul</th><th>Record</th><th>Status sumber</th><th>Terakhir sinkron (WIT)</th><th>Pemeriksaan</th></tr></thead><tbody>';
    foreach ($catalog as $key => $spec) {
        $state = $states[$key] ?? null;
        $missing = $state ? array_diff(array_keys($spec['fields']), json_decode($state['columns_json'], true) ?: array()) : array();
        $message = !$state ? 'Belum ada pengiriman' : ($state['state'] !== 'ready' ? $state['message'] : ($missing ? count($missing) . ' kolom opsional tidak tersedia' : 'Kolom katalog tersedia'));
        $statusLabels = array('ready' => 'Tersedia', 'unavailable' => 'Sumber belum tersedia', 'missing_columns' => 'Kolom wajib belum lengkap');
        echo '<tr><td><a href="' . $e(kasuari_sipp_url($pn, $key)) . '">' . $e($spec['label']) . '</a></td><td>' . ($state && $state['synced_at'] ? number_format($state['row_count'], 0, ',', '.') : '-') . '</td><td>' . $e($statusLabels[$state['state'] ?? ''] ?? 'Belum tersinkron') . '</td><td>' . $e($state['synced_at'] ?? '-') . '</td><td>' . $e($message) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';
    if ($mode === 'kualitas' && isset($states['putusan']) && $states['putusan']['state'] === 'ready') {
        $columns = json_decode($states['putusan']['columns_json'], true) ?: array();
        $records = kasuari_sipp_page($db, $pn, 'putusan', array_merge($filter, array('size' => 100, 'page' => 1)));
        echo '<section class="ks-sipp-section"><h2>Catatan kelengkapan putusan</h2><p class="text-secondary">Pemeriksaan 100 record terbaru sesuai filter. Ini menunjukkan data yang belum tercatat, bukan kesimpulan keterlambatan atau kekuatan hukum.</p><div class="ks-sipp-table-wrap"><table class="table"><thead><tr><th>Perkara</th><th>Catatan</th><th>Detail</th></tr></thead><tbody>';
        $found = 0;
        foreach ($records['rows'] as $record) {
            $messages = array();
            foreach (array('tanggal_minutasi' => 'Tanggal minutasi belum tercatat', 'tanggal_bht' => 'Tanggal BHT belum tercatat') as $field => $label) {
                if (in_array($field, $columns, true) && (empty($record['data'][$field]) || strpos((string) $record['data'][$field], '0000-00-00') === 0)) $messages[] = $label;
            }
            if (!$messages) continue;
            $found++;
            echo '<tr><td>' . $e($record['case_number'] ?: $record['case_id']) . '</td><td>' . $e(implode('; ', $messages)) . '</td><td><a href="' . $e(kasuari_sipp_url($pn, 'putusan', array('key' => $record['record_key']))) . '">Lihat putusan</a></td></tr>';
        }
        if (!$found) echo '<tr><td colspan="3">Tidak ada catatan pada record yang diperiksa, atau kolom sumber belum tersedia.</td></tr>';
        echo '</tbody></table></div></section>';
    }
}

function kasuari_sipp_render_dataset($db, $pn, $section, $filter, $key, $ready, $state)
{
    $spec = kasuari_sipp_catalog()[$section]; $e = 'kasuari_sipp_escape';
    $url = function ($args = array()) use ($pn, $section) { return kasuari_sipp_escape(kasuari_sipp_url($pn, $section, $args)); }; ?>
    <section class="ks-sipp-section ks-sipp-records">
      <div class="ks-sipp-section-title"><div><h2><?php echo $key !== '' ? 'Informasi record' : 'Data ' . $e($spec['label']); ?></h2><span class="ks-sipp-hint"><?php echo $state && $state['synced_at'] ? 'Sinkronisasi: ' . $e($state['synced_at']) . ' WIT' : 'Belum disinkronkan'; ?></span></div><?php echo kasuari_sipp_status_badge($state); ?></div>
      <?php if (!$state): ?><div class="alert alert-info">Belum ada pengiriman modul ini dari satker.</div>
      <?php elseif ($state['state'] !== 'ready'): ?><div class="alert alert-warning">Sumber belum tersedia pada pengiriman terakhir. <?php echo $e($state['message']); ?><?php if ($state['synced_at']): ?> Data di bawah adalah snapshot sebelumnya.<?php endif; ?></div><?php endif; ?>
      <?php if ($ready && $key !== ''):
        $found = kasuari_sipp_select($db, 'SELECT * FROM kasuari_sipp_rows WHERE pn_id=? AND dataset=? AND record_key=?', 'iss', array($pn, $section, $key));
        $record = $found ? json_decode($found[0]['data_json'], true) : null; ?>
        <a class="btn btn-outline-secondary mb-3" href="<?php echo $url($filter); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar</a>
        <?php if (!$record): ?><div class="alert alert-warning">Record tidak ditemukan pada satker ini.</div><?php else: ?>
          <dl class="ks-sipp-details"><?php foreach ($spec['fields'] as $field => $label): if (!array_key_exists($field, $record)) continue; ?><div><dt><?php echo $e($label); ?></dt><dd><?php echo nl2br($e(kasuari_sipp_value($record[$field], $field))); ?></dd></div><?php endforeach; ?></dl>
          <?php if (!empty($found[0]['case_id'])) kasuari_sipp_render_timeline($db, $pn, $found[0]['case_id'], kasuari_sipp_catalog()); ?>
        <?php endif; ?>
      <?php else:
        $page = $ready ? kasuari_sipp_page($db, $pn, $section, $filter) : array('rows' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'size' => 25); ?>
        <form method="get" action="index.php" class="ks-sipp-filters"><input type="hidden" name="modul" value="sipp"><input type="hidden" name="satker" value="<?php echo $pn; ?>"><input type="hidden" name="bagian" value="<?php echo $e($section); ?>">
          <label for="sipp-q">Pencarian<input class="form-control" id="sipp-q" name="q" value="<?php echo $e($filter['q']); ?>" placeholder="Cari data perkara..." maxlength="150"></label>
          <label for="sipp-from">Dari<input type="date" class="form-control" id="sipp-from" name="from" value="<?php echo $e($filter['from']); ?>"></label>
          <label for="sipp-to">Sampai<input type="date" class="form-control" id="sipp-to" name="to" value="<?php echo $e($filter['to']); ?>"></label>
          <label for="sipp-size">Baris<select class="form-select" id="sipp-size" name="size"><?php foreach (array(25, 50, 100) as $size): ?><option <?php echo $size === (int) $filter['size'] ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select></label>
          <?php if ($filter['case_id'] !== ''): ?><input type="hidden" name="case_id" value="<?php echo $e($filter['case_id']); ?>"><?php endif; ?>
          <button class="btn btn-primary">Tampilkan</button><a class="btn btn-outline-secondary" href="<?php echo $url(); ?>">Reset</a>
          <?php if ($state && $state['synced_at']): ?><a class="btn btn-outline-success" href="<?php echo $url(array_merge($filter, array('export' => 'csv'))); ?>"><i class="bi bi-download" aria-hidden="true"></i> CSV</a><?php endif; ?>
        </form>
        <?php if ($spec['date']): ?><p class="ks-sipp-hint">Periode berdasarkan <?php echo $e(strtolower($spec['fields'][$spec['date']])); ?>.</p><?php endif; ?>
        <div class="ks-sipp-table-wrap ks-sipp-record-table-wrap"><table class="table table-hover ks-sipp-record-table" aria-label="<?php echo $e($spec['label']); ?>"><thead><tr><?php foreach ($spec['columns'] as $field): ?><th scope="col"><?php echo $e($spec['fields'][$field]); ?></th><?php endforeach; ?><th scope="col">Aksi</th></tr></thead><tbody>
          <?php foreach ($page['rows'] as $row): ?><tr><?php foreach ($spec['columns'] as $field): ?><td data-label="<?php echo $e($spec['fields'][$field]); ?>"><?php if ($field === $spec['case_field'] && !empty($row['case_number'])): ?><?php echo $e($row['case_number']); ?><small class="d-block text-secondary">ID <?php echo $e($row['case_id']); ?></small><?php else: ?><?php echo $e(kasuari_sipp_value($row['data'][$field] ?? null, $field)); ?><?php endif; ?></td><?php endforeach; ?><td data-label="Aksi"><a class="btn btn-sm btn-outline-primary" href="<?php echo $url(array_merge($filter, array('key' => $row['record_key']))); ?>" aria-label="Detail <?php echo $e($spec['label']); ?>" title="Lihat detail"><i class="bi bi-eye" aria-hidden="true"></i></a></td></tr><?php endforeach; ?>
          <?php if (!$page['rows']): ?><tr><td colspan="<?php echo count($spec['columns']) + 1; ?>" class="text-center py-4"><?php echo $state && $state['synced_at'] ? 'Tidak ada data yang sesuai.' : 'Data belum tersedia.'; ?></td></tr><?php endif; ?>
        </tbody></table></div>
        <nav class="ks-sipp-pagination" aria-label="Halaman data"><span><?php echo number_format($page['total'], 0, ',', '.'); ?> data · Halaman <?php echo $page['page']; ?> / <?php echo $page['pages']; ?></span><div><?php if ($page['page'] > 1): ?><a class="btn btn-outline-secondary" href="<?php echo $url(array_merge($filter, array('page' => $page['page'] - 1))); ?>">Sebelumnya</a><?php endif; ?> <?php if ($page['page'] < $page['pages']): ?><a class="btn btn-outline-primary" href="<?php echo $url(array_merge($filter, array('page' => $page['page'] + 1))); ?>">Berikutnya</a><?php endif; ?></div></nav>
      <?php endif; ?>
    </section>
    <?php
}

function kasuari_sipp_navigation($pn, $section)
{
    $catalog = kasuari_sipp_catalog();
    $html = '<li class="nav-item ks-sipp-nav ' . ($pn ? 'menu-open' : '') . '"><a href="#" class="nav-link ' . ($pn ? 'active' : '') . '"><i class="nav-icon bi bi-building" aria-hidden="true"></i><p>SIPP<i class="nav-arrow bi bi-chevron-right" aria-hidden="true"></i></p></a><ul class="nav nav-treeview">';
    foreach (kasuari_sipp_courts() as $court => $name) {
        $html .= '<li class="nav-item ' . ($pn === $court ? 'menu-open' : '') . '"><a href="#" class="nav-link ' . ($pn === $court ? 'active' : '') . '"><i class="nav-icon bi bi-dot" aria-hidden="true"></i><p>' . kasuari_sipp_escape($name) . '<i class="nav-arrow bi bi-chevron-right" aria-hidden="true"></i></p></a><ul class="nav nav-treeview">';
        foreach (array('' => 'Ringkasan', 'statistik' => 'Statistik dan laporan', 'kualitas' => 'Pemeriksaan data', 'menu_sumber' => 'Menu SIPP asal', 'integrasi' => 'Koneksi satker') as $key => $label) {
            $html .= '<li class="nav-item"><a class="nav-link ' . ($pn === $court && $section === $key ? 'active' : '') . '" href="' . kasuari_sipp_escape(kasuari_sipp_url($court, $key)) . '"><i class="nav-icon bi bi-dot" aria-hidden="true"></i><p>' . $label . '</p></a></li>';
        }
        $groups = array();
        foreach ($catalog as $key => $spec) $groups[$spec['group']][$key] = $spec['label'];
        $modulesOpen = $pn === $court && isset($catalog[$section]);
        $html .= '<li class="nav-item ks-sipp-module-tree ' . ($modulesOpen ? 'menu-open' : '') . '"><a href="#" class="nav-link"><i class="nav-icon bi bi-grid" aria-hidden="true"></i><p>Modul satker<i class="nav-arrow bi bi-chevron-right" aria-hidden="true"></i></p></a><ul class="nav nav-treeview">';
        foreach ($groups as $group => $items) {
            $open = $pn === $court && isset($items[$section]);
            $html .= '<li class="nav-item ' . ($open ? 'menu-open' : '') . '"><a href="#" class="nav-link"><i class="nav-icon bi bi-folder2" aria-hidden="true"></i><p>' . kasuari_sipp_escape($group) . '<i class="nav-arrow bi bi-chevron-right" aria-hidden="true"></i></p></a><ul class="nav nav-treeview">';
            foreach ($items as $key => $label) {
                $html .= '<li class="nav-item"><a class="nav-link ' . ($pn === $court && $section === $key ? 'active' : '') . '" href="' . kasuari_sipp_escape(kasuari_sipp_url($court, $key)) . '"><i class="nav-icon bi bi-dot" aria-hidden="true"></i><p>' . kasuari_sipp_escape($label) . '</p></a></li>';
            }
            $html .= '</ul></li>';
        }
        $html .= '</ul></li>';
        $html .= '</ul></li>';
    }
    return $html . '</ul></li>';
}

function kasuari_sipp_render($db, $pn, $section, $filter, $key = '')
{
    $catalog = kasuari_sipp_catalog(); $courts = kasuari_sipp_courts();
    $states = kasuari_sipp_states($db, $pn);
    $ready = kasuari_sipp_schema($db);
    $extra = array('' => 'Ringkasan SIPP', 'statistik' => 'Statistik dan laporan', 'kualitas' => 'Pemeriksaan data',
        'menu_sumber' => 'Menu SIPP asal', 'integrasi' => 'Koneksi satker');
    if (!isset($extra[$section]) && !isset($catalog[$section])) { http_response_code(404); echo '<div class="alert alert-danger">Modul SIPP tidak dikenal.</div>'; return; }
    $title = $extra[$section] ?? $catalog[$section]['label'];
    $url = function ($part, $args = array()) use ($pn) { return kasuari_sipp_escape(kasuari_sipp_url($pn, $part, $args)); };
    $e = 'kasuari_sipp_escape';
    ?>
    <div class="app-content"><div class="container-fluid ks-sipp">
      <header class="ks-sipp-heading"><div><h1><?php echo $e($title); ?></h1><p class="ks-sipp-court-caption"><?php echo $e($courts[$pn]); ?><span aria-hidden="true"> &middot; </span>Kode <?php echo $pn; ?></p></div>
        <form method="get" action="index.php"><input type="hidden" name="modul" value="sipp"><input type="hidden" name="bagian" value="<?php echo $e($section); ?>">
          <label for="sipp-court" class="visually-hidden">Pilih satker</label><select name="satker" id="sipp-court" class="form-select" onchange="this.form.submit()">
            <?php foreach ($courts as $id => $name): ?><option value="<?php echo $id; ?>" <?php echo $id === $pn ? 'selected' : ''; ?>><?php echo $e($name); ?></option><?php endforeach; ?>
          </select><noscript><button class="btn btn-primary">Tampilkan</button></noscript></form>
      </header>
      <nav class="ks-sipp-topnav" aria-label="SIPP satker"><?php foreach (array('' => 'Ringkasan', 'statistik' => 'Statistik dan laporan', 'kualitas' => 'Pemeriksaan data', 'menu_sumber' => 'Menu SIPP asal', 'integrasi' => 'Koneksi') as $part => $label): ?><a href="<?php echo $url($part); ?>" <?php echo $section === $part ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a><?php endforeach; ?></nav>
      <?php foreach (array('sipp_notice' => 'success', 'sipp_error' => 'danger') as $flash => $tone): if (isset($_SESSION[$flash])): ?>
        <div class="alert alert-<?php echo $tone; ?>" role="status"><?php echo $e($_SESSION[$flash]); unset($_SESSION[$flash]); ?></div>
      <?php endif; endforeach; ?>
      <?php if (!$ready): ?><div class="alert alert-warning" role="status">Modul SIPP belum diaktifkan pada database pusat. Jalankan migrasi portal SIPP, lalu hubungkan satker.</div><?php endif; ?>
      <?php if ($section === 'integrasi'):
        kasuari_sipp_render_settings($db, $pn, $ready);
      elseif ($section === ''):
        kasuari_sipp_render_directory($pn, $catalog, $states, $filter);
      elseif ($section === 'menu_sumber'):
        kasuari_sipp_render_source_menu($db, $pn, $ready, $filter);
      elseif ($section === 'statistik' || $section === 'kualitas'):
        kasuari_sipp_render_report($db, $pn, $catalog, $states, $filter, $section);
      else:
        kasuari_sipp_render_dataset($db, $pn, $section, $filter, $key, $ready, $states[$section] ?? null);
      endif; ?>
    </div></div>
    <?php
}

function kasuari_sipp_render_settings($db, $pn, $ready)
{
    $e = 'kasuari_sipp_escape'; $courts = kasuari_sipp_courts();
    $rows = $ready ? kasuari_sipp_select($db, 'SELECT created_at, source_url FROM kasuari_sipp_tokens WHERE pn_id=?', 'i', array($pn)) : array();
    $config = $rows ? $rows[0] : array('created_at' => null, 'source_url' => ''); ?>
    <section class="ks-sipp-section"><h2>Sinkronisasi SIPP</h2>
      <dl class="ks-sipp-details"><div><dt>Satker</dt><dd><?php echo $e($courts[$pn]); ?> (<?php echo $pn; ?>)</dd></div><div><dt>Konfigurasi client</dt><dd><?php echo $config['created_at'] ? 'Token sudah dibuat' : 'Token belum dibuat'; ?></dd></div></dl>
      <p>Unduh konfigurasi satker, tempatkan pada <code>kasuari_satker/sys/sys_sipp.local.php</code>, lalu gunakan tombol Kirimkan Data pada aplikasi satker.</p>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sippTokenModal" <?php echo !$ready ? 'disabled' : ''; ?>><i class="bi bi-download me-1" aria-hidden="true"></i><?php echo $config['created_at'] ? 'Ganti konfigurasi token' : 'Unduh konfigurasi token'; ?></button>
      <form action="index.php?modul=sipp_config" method="post" class="ks-sipp-config-form">
        <input type="hidden" name="csrf" value="<?php echo $e($_SESSION['sipp_csrf']); ?>"><input type="hidden" name="satker" value="<?php echo $pn; ?>"><input type="hidden" name="aksi" value="url">
        <label for="sipp-source-url" class="form-label">Alamat SIPP satker</label><input type="url" class="form-control" name="source_url" id="sipp-source-url" value="<?php echo $e($config['source_url']); ?>" placeholder="https://sipp.example.go.id/" maxlength="480">
        <button class="btn btn-outline-primary mt-2" <?php echo !$ready ? 'disabled' : ''; ?>>Simpan alamat</button>
      </form>
    </section>
    <div class="modal fade ks-sipp-token-modal" id="sippTokenModal" tabindex="-1" aria-labelledby="sippTokenTitle" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="sippTokenTitle">Konfigurasi <?php echo $e($courts[$pn]); ?></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">Token baru menggantikan token sebelumnya. Setelah diunduh, pasang file konfigurasi baru pada satker agar pengiriman modul SIPP tetap berjalan.</div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><form method="post" action="index.php?modul=sipp_config"><input type="hidden" name="csrf" value="<?php echo $e($_SESSION['sipp_csrf']); ?>"><input type="hidden" name="satker" value="<?php echo $pn; ?>"><input type="hidden" name="aksi" value="token"><button class="btn btn-primary">Buat dan unduh</button></form></div></div></div></div>
    <?php
}
