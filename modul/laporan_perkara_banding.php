<?php
include_once(__DIR__ . '/../sys/sys_session.php');
include_once(__DIR__ . '/../sys/sys_laporan_pdf.php');

$month = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$year = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
$type = isset($_GET['jenis']) ? (int) $_GET['jenis'] : 1;
$month = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
$year = ($year >= 2000 && $year <= 2100) ? $year : (int) date('Y');
$type = ($type >= 1 && $type <= 4) ? $type : 1;

$months = array(1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER');
$monthTitle = $months[$month];
$start = sprintf('%04d-%02d-01', $year, $month);
$next = date('Y-m-d', strtotime($start . ' +1 month'));
$dateFilter = function ($field) use ($start, $next) { return "{$field} >= '{$start}' AND {$field} < '{$next}'"; };

$specs = array(
    1 => array(
        'title' => 'LAPORAN PERKARA BANDING PENGADILAN TINGGI AGAMA PAPUA BARAT',
        'filename' => 'laporan_perkara_banding',
        'filter' => $dateFilter('pb.tanggal_pendaftaran_banding'),
        'columns' => array('No.', 'Nomor Perkara', 'Kode Perkara', 'Nama Majelis Hakim', 'Nama Panitera/PP', 'Penerimaan', 'PMH', 'Sidang Pertama', 'Diputus', 'Belum Dibagi', 'Belum Diputus', 'Belum Diminutir', 'Ket.'),
        'widths' => array(28, 112, 78, 140, 112, 58, 58, 58, 58, 58, 58, 58, 84),
        'grouped' => true,
    ),
    2 => array(
        'title' => 'LAPORAN PENGIRIMAN SALINAN PUTUSAN PENGADILAN TINGGI AGAMA PAPUA BARAT KE PENGADILAN AGAMA PENGAJU',
        'filename' => 'laporan_pengiriman_salinan_putusan',
        'filter' => $dateFilter('pb.tgl_kirim_salinan_putusan'),
        'columns' => array('No.', 'Nomor Perkara', 'Pengadilan Agama Pengaju', 'Tanggal Putusan', 'Nomor Putusan', 'Tanggal Pengiriman', 'Status', 'Ket.'),
        'widths' => array(30, 155, 160, 110, 120, 130, 145, 110),
        'grouped' => false,
    ),
    3 => array(
        'title' => 'LAPORAN PUBLIKASI PUTUSAN PENGADILAN TINGGI AGAMA PAPUA BARAT',
        'filename' => 'laporan_publikasi_putusan',
        'filter' => "((pb.tgl_minutasi >= '{$start}' AND pb.tgl_minutasi < '{$next}') OR (pb.tgl_minutasi IS NULL AND pb.minutasi_banding >= '{$start}' AND pb.minutasi_banding < '{$next}'))",
        'columns' => array('No.', 'Nomor Perkara', 'Pengadilan Agama Pengaju', 'Tanggal Putusan', 'Nomor Putusan', 'Tanggal Publikasi/Minutasi', 'Ket.'),
        'widths' => array(30, 180, 170, 130, 140, 140, 170),
        'grouped' => false,
    ),
    4 => array(
        'title' => 'REGISTER PERKARA PENGADILAN TINGGI AGAMA PAPUA BARAT',
        'filename' => 'register_perkara_pengadilan_tinggi_agama_papua_barat',
        'filter' => $dateFilter('pb.tanggal_pendaftaran_banding'),
        'columns' => array('#', 'Asal Pengadilan', 'Nama Pemohon Banding', 'Nomor Perkara Tk. I', 'Jenis Perkara', 'Tgl Register', 'Nomor Perkara Banding', 'Lama Proses', 'Status Perkara', 'link'),
        'widths' => array(30, 84, 178, 100, 74, 76, 100, 62, 136, 120),
        'grouped' => false,
        'register' => true,
    ),
);
$spec = $specs[$type];

function laporan_pdf_date($value)
{
    $value = trim((string) $value);
    if ($value === '' || strpos($value, '0000-00-00') === 0) return '-';
    $timestamp = strtotime($value);
    return $timestamp === false ? '-' : date('d/m/Y', $timestamp);
}

function laporan_pdf_value($value)
{
    $value = trim((string) $value);
    return $value === '' ? '-' : $value;
}

function laporan_pdf_party($value)
{
    $value = (string) $value;
    $value = preg_replace('/<br\s*\/?\s*>/i', "\n", $value);
    $value = preg_replace('/<\/(p|li|div)>/i', "\n", $value);
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/[ \t]+/', ' ', $value);
    $value = preg_replace('/\n\s*\n+/', "\n", $value);
    return trim($value) === '' ? '-' : trim($value);
}

function laporan_pdf_query_rows($koneksi, $type, $filter)
{
    $select = "pb.nomor_urut_register, pb.nomor_perkara_banding, pb.nomor_perkara_pn, pb.jenis_banding,
        pb.para_pihak, pb.pemohon_banding,
        pb.majelis_hakim_banding, pb.panitera_pengganti_banding, pb.tanggal_pendaftaran_banding,
        pb.tanggal_penetapan_sidang_pertama, pb.tanggal_sidang_pertama, pb.putusan_banding,
        pb.nomor_putusan_banding, pb.tgl_kirim_salinan_putusan, pb.tgl_minutasi, pb.minutasi_banding,
        pb.pemberitahuan_putusan_banding, pb.tgl_pemberitahuan_putusan, pb.tanggal_cabut,
        pb.status_banding_text, pb.status_putusan_banding_text,
        DATEDIFF(COALESCE(NULLIF(pb.putusan_banding, '0000-00-00'), CURDATE()), pb.tanggal_pendaftaran_banding) AS lama_proses,
        pa.nama AS pengaju";
    $order = 'pb.tanggal_pendaftaran_banding DESC, pb.nomor_urut_register DESC';
    if ($type === 2) $order = 'pb.tgl_kirim_salinan_putusan DESC, pb.nomor_urut_register DESC';
    if ($type === 3) $order = 'COALESCE(pb.tgl_minutasi, pb.minutasi_banding) DESC, pb.nomor_urut_register DESC';
    $sql = "SELECT {$select} FROM perkara_banding pb
        LEFT JOIN pengadilan_agama pa ON pa.id = pb.pn_id
        WHERE {$filter} ORDER BY {$order}";
    $result = mysqli_query($koneksi, $sql);
    if (!$result) {
        error_log('KASUARI laporan bulanan query failed: ' . mysqli_error($koneksi));
        return array();
    }
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function laporan_pdf_rows($rows, $type)
{
    $output = array();
    $registerNumber = 0;
    foreach ($rows as $row) {
        $pa = str_replace('PENGADILAN AGAMA', 'PA', laporan_pdf_value($row['pengaju'] ?? ''));
        if ($type === 1) {
            $belumDibagi = trim((string) ($row['majelis_hakim_banding'] ?? '')) === '' ? 'Ya' : '-';
            $belumDiputus = trim((string) ($row['putusan_banding'] ?? '')) === '' ? 'Ya' : '-';
            $belumDiminutir = trim((string) ($row['tgl_minutasi'] ?? '')) === '' && trim((string) ($row['minutasi_banding'] ?? '')) === '' ? 'Ya' : '-';
            $output[] = array(
                laporan_pdf_value($row['nomor_urut_register'] ?? ''), laporan_pdf_value($row['nomor_perkara_banding'] ?? ''), laporan_pdf_value($row['jenis_banding'] ?? ''), laporan_pdf_value($row['majelis_hakim_banding'] ?? ''), laporan_pdf_value($row['panitera_pengganti_banding'] ?? ''),
                laporan_pdf_date($row['tanggal_pendaftaran_banding'] ?? ''), laporan_pdf_date($row['tanggal_penetapan_sidang_pertama'] ?? ''), laporan_pdf_date($row['tanggal_sidang_pertama'] ?? ''), laporan_pdf_date($row['putusan_banding'] ?? ''), $belumDibagi, $belumDiputus, $belumDiminutir, kasuari_status_banding_tampil($row),
            );
        } elseif ($type === 2) {
            $output[] = array(laporan_pdf_value($row['nomor_urut_register'] ?? ''), laporan_pdf_value($row['nomor_perkara_banding'] ?? ''), $pa, laporan_pdf_date($row['putusan_banding'] ?? ''), laporan_pdf_value($row['nomor_putusan_banding'] ?? ''), laporan_pdf_date($row['tgl_kirim_salinan_putusan'] ?? ''), laporan_pdf_value($row['status_putusan_banding_text'] ?? $row['status_banding_text'] ?? ''), '-');
        } elseif ($type === 3) {
            $publicationDate = $row['tgl_minutasi'] ?? $row['minutasi_banding'] ?? '';
            $output[] = array(laporan_pdf_value($row['nomor_urut_register'] ?? ''), laporan_pdf_value($row['nomor_perkara_banding'] ?? ''), $pa, laporan_pdf_date($row['putusan_banding'] ?? ''), laporan_pdf_value($row['nomor_putusan_banding'] ?? ''), laporan_pdf_date($publicationDate), '');
        } else {
            $registerNumber++;
            $asalPengadilan = preg_replace('/^(PA|PENGADILAN AGAMA)\s+/i', '', $pa);
            $party = laporan_pdf_party($row['para_pihak'] ?? '');
            if ($party === '-') $party = laporan_pdf_party($row['pemohon_banding'] ?? '');
            $status = kasuari_status_banding_tampil($row);
            $minuteDate = laporan_pdf_date($row['tgl_minutasi'] ?? ($row['minutasi_banding'] ?? ''));
            if ($minuteDate !== '-') $status .= "\nMinutasi tanggal: " . $minuteDate;
            $output[] = array((string) $registerNumber, laporan_pdf_value($asalPengadilan), $party, laporan_pdf_value($row['nomor_perkara_pn'] ?? ''), laporan_pdf_value($row['jenis_banding'] ?? ''), laporan_pdf_date($row['tanggal_pendaftaran_banding'] ?? ''), laporan_pdf_value($row['nomor_perkara_banding'] ?? ''), laporan_pdf_value($row['lama_proses'] ?? '') . ' hari', $status, 'Detail');
        }
    }
    return $output;
}

function laporan_pdf_draw_header($pdf, $spec, $monthTitle, $year)
{
    $pdf->kop(24, 18, 960, 181.3);
    $pdf->text(504, 217, $spec['title'], 13, true, 'center');
    $pdf->text(504, 235, $monthTitle . ' ' . $year, 11, true, 'center');
    $pdf->line(24, 249, 984, 249, 0.75);
}

function laporan_pdf_draw_table_header($pdf, $spec, $top)
{
    $x0 = 24; $widths = $spec['widths']; $columns = $spec['columns']; $headerHeight = $spec['grouped'] ? 62 : 32; $x = $x0;
    $pdf->fillRect($x0, $top, 960, $headerHeight, array(245, 247, 250));
    if ($spec['grouped']) {
        $row1 = 22;
        foreach ($widths as $i => $w) {
            if ($i >= 5 && $i <= 8) continue;
            if ($i >= 9 && $i <= 11) continue;
            $pdf->rect($x, $top, $w, $headerHeight);
            $pdf->wrappedText($x + 3, $top + 22, $w - 6, $columns[$i], 6.5, 8, true, 3);
            $x += $w;
        }
        $groupX = $x0 + array_sum(array_slice($widths, 0, 5)); $groupW = array_sum(array_slice($widths, 5, 4));
        $pdf->rect($groupX, $top, $groupW, $row1); $pdf->text($groupX + ($groupW / 2), $top + 6, 'Tanggal', 7.2, true, 'center'); $x = $groupX;
        for ($i = 5; $i <= 8; $i++) { $pdf->rect($x, $top + $row1, $widths[$i], 40); $pdf->wrappedText($x + 2, $top + $row1 + 5, $widths[$i] - 4, $columns[$i], 5.8, 7, true, 3); $x += $widths[$i]; }
        $groupX = $x0 + array_sum(array_slice($widths, 0, 9)); $groupW = array_sum(array_slice($widths, 9, 3));
        $pdf->rect($groupX, $top, $groupW, $row1); $pdf->text($groupX + ($groupW / 2), $top + 6, 'Sisa Akhir Bulan', 7.2, true, 'center'); $x = $groupX;
        for ($i = 9; $i <= 11; $i++) { $pdf->rect($x, $top + $row1, $widths[$i], 40); $pdf->wrappedText($x + 2, $top + $row1 + 5, $widths[$i] - 4, $columns[$i], 5.8, 7, true, 3); $x += $widths[$i]; }
        $x = $x0; foreach ($widths as $i => $w) { $pdf->text($x + ($w / 2), $top + $headerHeight - 16, (string) ($i + 1), 6.5, true, 'center'); $x += $w; }
    } else {
        foreach ($widths as $i => $w) { $pdf->rect($x, $top, $w, $headerHeight); $pdf->wrappedText($x + 3, $top + 8, $w - 6, $columns[$i], 6.6, 8, true, 3); $x += $w; }
    }
    return $headerHeight;
}

function laporan_pdf_draw_register_header($pdf, $spec, $top)
{
    $x = 24; $height = 44;
    $pdf->fillRect(24, $top, 960, $height, array(37, 58, 81));
    $pdf->setFillForReport(array(255, 255, 255));
    foreach ($spec['widths'] as $i => $width) {
        $pdf->rect($x, $top, $width, $height);
        $pdf->wrappedText($x + 3, $top + 7, $width - 6, $spec['columns'][$i], 6.5, 7.5, true, 4);
        $x += $width;
    }
    $pdf->setFillForReport(array(0, 0, 0));
    return $height;
}

function laporan_pdf_draw_register_rows($pdf, $spec, $rows, $top, $pageBottom, $monthTitle, $year)
{
    $widths = $spec['widths']; $x0 = 24; $headerHeight = laporan_pdf_draw_register_header($pdf, $spec, $top); $y = $top + $headerHeight; $rowNumber = 0;
    foreach ($rows as $row) {
        $lineCount = 1;
        foreach ($row as $i => $value) $lineCount = max($lineCount, count($pdf->wrapForReport($value, $widths[$i] - 8, 6.5)));
        $rowHeight = max(28, min(86, $lineCount * 8 + 8));
        if ($y + $rowHeight > $pageBottom) {
            $pdf->addPage();
            laporan_pdf_draw_header($pdf, $spec, $monthTitle, $year);
            $headerHeight = laporan_pdf_draw_register_header($pdf, $spec, 258);
            $y = 258 + $headerHeight;
        }
        $rowNumber++;
        $x = $x0;
        foreach ($widths as $i => $width) {
            $pdf->fillRect($x, $y, $width, $rowHeight, ($rowNumber % 2 === 1) ? array(242, 242, 242) : array(255, 255, 255));
            $pdf->rect($x, $y, $width, $rowHeight);
            $lines = $pdf->wrapForReport($row[$i] ?? '-', $width - 8, 6.5);
            $lineY = $y + (($rowHeight - (count($lines) * 8)) / 2) + 2;
            foreach (array_slice($lines, 0, 10) as $line) { $pdf->text($x + ($i === 0 ? $width / 2 : 4), $lineY, $line, 6.5, false, $i === 0 ? 'center' : 'left'); $lineY += 8; }
            $x += $width;
        }
        $y += $rowHeight;
    }
    return $y;
}

function laporan_pdf_draw_rows($pdf, $spec, $rows, $top, $pageBottom, $monthTitle, $year)
{
    if (!empty($spec['register'])) return laporan_pdf_draw_register_rows($pdf, $spec, $rows, $top, $pageBottom, $monthTitle, $year);
    $widths = $spec['widths']; $x0 = 24; $headerHeight = laporan_pdf_draw_table_header($pdf, $spec, $top); $y = $top + $headerHeight;
    foreach ($rows as $row) {
        $lineCount = 1; foreach ($row as $i => $value) $lineCount = max($lineCount, count($pdf->wrapForReport($value, $widths[$i] - 8, 6.7)));
        $rowHeight = max(20, min(48, $lineCount * 8 + 8));
        if ($y + $rowHeight > $pageBottom) { $pdf->addPage(); laporan_pdf_draw_header($pdf, $spec, $monthTitle, $year); $headerHeight = laporan_pdf_draw_table_header($pdf, $spec, 258); $y = 258 + $headerHeight; }
        $x = $x0;
        foreach ($widths as $i => $w) {
            $pdf->rect($x, $y, $w, $rowHeight); $value = $row[$i] ?? '-'; $lines = $pdf->wrapForReport($value, $w - 8, 6.7); $lineY = $y + (($rowHeight - (count($lines) * 8)) / 2) + 2;
            foreach (array_slice($lines, 0, 5) as $line) { $pdf->text($x + ($i === 0 ? $w / 2 : 4), $lineY, $line, 6.7, false, $i === 0 ? 'center' : 'left'); $lineY += 8; }
            $x += $w;
        }
        $y += $rowHeight;
    }
    return $y;
}

function laporan_pdf_draw_signatures($pdf, $config, $y, $year, $monthTitle)
{
    $base = min(548, max($y + 18, 500)); $ketua = laporan_pdf_value($config['nama_ketua'] ?? ''); $panitera = laporan_pdf_value($config['nama_panitera'] ?? '');
    $pdf->text(190, $base, 'Mengetahui,', 8.5, false, 'center'); $pdf->text(190, $base + 12, 'Ketua PTA Papua Barat', 8.5, false, 'center'); $pdf->text(190, $base + 55, $ketua, 8.5, true, 'center'); $pdf->line(115, $base + 57, 265, $base + 57, 0.45); $pdf->text(190, $base + 68, 'NIP. ........................................', 7.5, false, 'center');
    $pdf->text(785, $base, 'Manokwari, ........................ ' . ucfirst(strtolower($monthTitle)) . ' ' . $year, 8.5, false, 'center'); $pdf->text(785, $base + 12, 'Panitera PTA Papua Barat', 8.5, false, 'center'); $pdf->text(785, $base + 55, $panitera, 8.5, true, 'center'); $pdf->line(710, $base + 57, 860, $base + 57, 0.45); $pdf->text(785, $base + 68, 'NIP. ........................................', 7.5, false, 'center');
}

$rows = laporan_pdf_rows(laporan_pdf_query_rows($koneksi, $type, $spec['filter']), $type);
$config = array('nama_ketua' => '', 'nama_panitera' => '');
$configResult = @mysqli_query($koneksi, 'SELECT nama_ketua, nama_panitera FROM sys_konfig ORDER BY id ASC LIMIT 1');
if ($configResult && ($configRow = mysqli_fetch_assoc($configResult))) $config = array_merge($config, $configRow);

$pdf = new KasuariLaporanPdf(__DIR__ . '/../assets/kop_undangan.png');
$pdf->addPage(); laporan_pdf_draw_header($pdf, $spec, $monthTitle, $year);
$lastY = laporan_pdf_draw_rows($pdf, $spec, $rows, 258, 485, $monthTitle, $year);
laporan_pdf_draw_signatures($pdf, $config, $lastY, $year, $monthTitle);
$pdf->output($spec['filename'] . '_' . strtolower($monthTitle) . '_' . $year . '.pdf');
