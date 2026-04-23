<?php
// ============================================================
// submit_handler.php  –  Receives POST from form.php,
//                        inserts one row into `submissions`,
//                        then redirects to view.php?id=…
// ============================================================

require_once __DIR__ . '/config.php';

// ── Only accept POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

// ── Helper: sanitise a single string field ──────────────────
function p(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

// ── 1. Resolve "display" values for vaccine names ──────────
//    dose-1 select uses numeric codes; map them to real names.
$vaccine_map = [
    '1'      => 'Pfizer (Pfizer-BioNTech)',
    '2'      => 'COVISHIELD (AstraZeneca)',
    '3'      => 'Moderna (Moderna)',
    '4'      => 'Vero Cell (Sinopharm)',
    '5'      => 'Janssen (Johnson & Johnson)',
    '6'      => 'Pfizer',
    'other1' => '',   // will use free-text below
    'other2' => '',
    'other3' => '',
];

$d1_code = p('doseone_name');
$d2_code = p('dosetwo_name');
$d3_code = p('dosethree_name');

$d1_display = $vaccine_map[$d1_code] ?? $d1_code;
$d2_display = $vaccine_map[$d2_code] ?? $d2_code;
$d3_display = $vaccine_map[$d3_code] ?? $d3_code;

// If "other" was chosen, fall back to free-text input
if (in_array($d1_code, ['other1', ''], true)) {
    $d1_display = p('doseone_name2', $d1_display);
}
if (in_array($d2_code, ['other2', ''], true)) {
    $d2_display = p('dosetwo_name2', $d2_display);
}
if (in_array($d3_code, ['other3', ''], true)) {
    $d3_display = p('dosethree_name2', $d3_display);
}

// ── 2. Vaccination centre display ──────────────────────────
$vc_code    = p('vacc_center');
$vc_display = ($vc_code === 'other') ? p('vacc_center2') : $vc_code;

// ── 3. Convert DD-MM-YYYY or YYYY-MM-DD → MySQL DATE ───────
function to_mysql_date(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') return null;

    // Already YYYY-MM-DD (from <input type="date"> or pre-filled values)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    // DD-MM-YYYY
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return null; // unknown format → store NULL
}

$date_birth    = to_mysql_date(p('date_birth'));
$doseone_date  = to_mysql_date(p('doseone_date'));
$dosetwo_date  = to_mysql_date(p('dosetwo_date'));
$dosethree_date = to_mysql_date(p('dosethree_date'));

// ── 4. Generate a unique public ID: VC-XXXXXXXX ────────────
//    Retry up to 5 times on the tiny chance of a collision.
function generate_uid(): string
{
    return 'VC-' . strtoupper(bin2hex(random_bytes(4))); // VC-A1B2C3D4
}

$pdo      = get_pdo();
$unique_id = '';
for ($i = 0; $i < 5; $i++) {
    $try = generate_uid();
    $chk = $pdo->prepare('SELECT id FROM submissions WHERE unique_id = ? LIMIT 1');
    $chk->execute([$try]);
    if (!$chk->fetch()) { $unique_id = $try; break; }
}
if ($unique_id === '') {
    exit('Could not generate a unique ID. Please try again.');
}

// ── 5. INSERT ───────────────────────────────────────────────
$sql = '
INSERT INTO submissions (
    unique_id, certi_no, type, national_id, birth_id, passport_no,
    nationality, name, date_birth, gender,
    doseone_date,  doseone_name,  doseone_name2,  doseone_name_display,
    dosetwo_date,  dosetwo_name,  dosetwo_name2,  dosetwo_name_display,
    dosethree_date, dosethree_name, dosethree_name2, dosethree_name_display,
    vacc_center, vacc_center2, vacc_center_display,
    vacc_by, total_dose
) VALUES (
    :unique_id, :certi_no, :type, :national_id, :birth_id, :passport_no,
    :nationality, :name, :date_birth, :gender,
    :doseone_date,  :doseone_name,  :doseone_name2,  :doseone_name_display,
    :dosetwo_date,  :dosetwo_name,  :dosetwo_name2,  :dosetwo_name_display,
    :dosethree_date, :dosethree_name, :dosethree_name2, :dosethree_name_display,
    :vacc_center, :vacc_center2, :vacc_center_display,
    :vacc_by, :total_dose
)';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':unique_id'              => $unique_id,
    ':certi_no'               => p('certi_no'),
    ':type'                   => p('type', 'One'),
    ':national_id'            => p('national_id', 'N/A'),
    ':birth_id'               => p('birth_id'),
    ':passport_no'            => p('passport_no'),
    ':nationality'            => p('nationality'),
    ':name'                   => p('name'),
    ':date_birth'             => $date_birth,
    ':gender'                 => p('gender'),

    ':doseone_date'           => $doseone_date,
    ':doseone_name'           => $d1_code,
    ':doseone_name2'          => p('doseone_name2'),
    ':doseone_name_display'   => $d1_display,

    ':dosetwo_date'           => $dosetwo_date,
    ':dosetwo_name'           => $d2_code,
    ':dosetwo_name2'          => p('dosetwo_name2'),
    ':dosetwo_name_display'   => $d2_display,

    ':dosethree_date'         => $dosethree_date,
    ':dosethree_name'         => $d3_code,
    ':dosethree_name2'        => p('dosethree_name2'),
    ':dosethree_name_display' => $d3_display,

    ':vacc_center'            => $vc_code,
    ':vacc_center2'           => p('vacc_center2'),
    ':vacc_center_display'    => $vc_display,
    ':vacc_by'                => p('vacc_by'),
    ':total_dose'             => (int) p('total_dose', '0'),
]);

// ── 6. Redirect to the public view page ────────────────────
header('Location: view.php?id=' . urlencode($unique_id));
exit;
