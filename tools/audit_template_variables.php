<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/sys/sys_koneksi.php';

$modeResult = mysqli_query($koneksi, 'SELECT @@SESSION.sql_mode AS sql_mode');
$modeRow = $modeResult ? mysqli_fetch_assoc($modeResult) : array('sql_mode' => '');
$sqlMode = isset($modeRow['sql_mode']) ? (string) $modeRow['sql_mode'] : '';
if (stripos(',' . $sqlMode . ',', ',ONLY_FULL_GROUP_BY,') === false) {
    $strictMode = trim($sqlMode . ',ONLY_FULL_GROUP_BY', ',');
    mysqli_query(
        $koneksi,
        "SET SESSION sql_mode='" . mysqli_real_escape_string($koneksi, $strictMode) . "'"
    );
}

function auditRows($connection, $sql)
{
    $result = mysqli_query($connection, $sql);
    if (!$result) {
        throw new RuntimeException(mysqli_error($connection));
    }

    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

function auditPlaceholders($content)
{
    preg_match_all('/#([0-9]+)#/', $content, $matches);
    return array_values(array_unique($matches[1]));
}

$errors = array();
$warnings = array();
$usedVariables = array();
$templateCount = 0;

try {
    $templates = auditRows($koneksi, 'SELECT id, kode, nama FROM template_dokumen ORDER BY id');
    $variables = auditRows($koneksi, 'SELECT * FROM master_variabel ORDER BY var_nomor');
    $cases = auditRows(
        $koneksi,
        'SELECT DISTINCT perkara_id, pn_id FROM perkara_banding ORDER BY pn_id, perkara_id'
    );
} catch (RuntimeException $exception) {
    fwrite(STDERR, 'Audit gagal membaca database: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$variableMap = array();
foreach ($variables as $variable) {
    $variableMap[$variable['var_nomor']] = $variable;
}

foreach ($templates as $template) {
    $path = dirname(__DIR__) . '/template/' . $template['kode'] . '.rtf';
    if (!is_file($path)) {
        $errors[] = 'Berkas template tidak ditemukan: ' . $path;
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = 'Berkas template tidak dapat dibaca: ' . $path;
        continue;
    }

    $templateCount++;
    foreach (auditPlaceholders($content) as $number) {
        $usedVariables[$number] = true;
        if (!isset($variableMap[$number])) {
            $errors[] = 'Template ' . $template['kode'] . ' memakai variabel #' . $number . '# yang belum terdaftar.';
        }
    }
}

foreach (array_keys($usedVariables) as $number) {
    if (!isset($variableMap[$number])) {
        continue;
    }

    $variable = $variableMap[$number];
    foreach (auditPlaceholders((string) $variable['var_default_data']) as $nestedNumber) {
        if (!isset($variableMap[$nestedNumber])) {
            $errors[] = 'Default variabel #' . $number . '# memakai variabel #' . $nestedNumber . '# yang belum terdaftar.';
        }
    }

    $isManual = $variable['var_tabel'] === 'data_teks';
    if ($isManual || $variable['var_model'] === 'tanya_jawab') {
        continue;
    }

    if ($variable['var_model'] === 'sql' && trim((string) $variable['var_sql_data']) === '') {
        $errors[] = 'Variabel SQL #' . $number . '# tidak memiliki query.';
        continue;
    }

    if ($variable['var_model'] !== 'sql') {
        $table = trim((string) $variable['var_tabel']);
        $field = trim((string) $variable['var_field']);
        if ($table === '' || $field === '') {
            $errors[] = 'Variabel otomatis #' . $number . '# tidak memiliki tabel atau field.';
            continue;
        }
        $tableEscaped = mysqli_real_escape_string($koneksi, $table);
        $fieldEscaped = mysqli_real_escape_string($koneksi, $field);
        $column = auditRows(
            $koneksi,
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $tableEscaped . "'
               AND COLUMN_NAME='" . $fieldEscaped . "' LIMIT 1"
        );
        if (count($column) === 0) {
            $errors[] = 'Sumber variabel #' . $number . '# tidak ditemukan: ' . $table . '.' . $field . '.';
            continue;
        }
    }

    $hasData = false;
    foreach ($cases as $case) {
        if ($variable['var_model'] === 'sql') {
            $sql = str_replace(
                array('#perkara_id#', '#pn_id#'),
                array((int) $case['perkara_id'], (int) $case['pn_id']),
                $variable['var_sql_data']
            );
        } else {
            $sql = 'SELECT ' . $variable['var_field'] . ' AS DATA FROM ' . $variable['var_tabel'] .
                ' WHERE perkara_id=' . (int) $case['perkara_id'] .
                ' AND pn_id=' . (int) $case['pn_id'];
        }

        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            $errors[] = 'Query variabel #' . $number . '# gagal untuk perkara ' .
                $case['perkara_id'] . '/' . $case['pn_id'] . ': ' . mysqli_error($koneksi);
            break;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($row['DATA']) && trim((string) $row['DATA']) !== '') {
                $hasData = true;
            }
        }
        mysqli_free_result($result);
    }
    if (count($cases) > 0 && !$hasData) {
        $warnings[] = 'Variabel otomatis #' . $number . '# belum memiliki nilai pada seluruh perkara uji.';
    }
}

sort($errors);
sort($warnings);

echo 'Template diperiksa : ' . $templateCount . PHP_EOL;
echo 'Variabel digunakan : ' . count($usedVariables) . PHP_EOL;
echo 'Kasus uji satker    : ' . count($cases) . PHP_EOL;
echo 'Error               : ' . count($errors) . PHP_EOL;
foreach ($errors as $error) {
    echo '[ERROR] ' . $error . PHP_EOL;
}
foreach ($warnings as $warning) {
    echo '[WARN] ' . $warning . PHP_EOL;
}

exit(count($errors) === 0 ? 0 : 1);
