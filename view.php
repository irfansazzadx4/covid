<?php
// ============================================================
// view.php  –  Public certificate page
//              URL: view.php?id=VC-XXXXXXXX
//
// This file is a thin wrapper: it fetches the row from the DB
// and then includes result.php (your existing template) which
// already expects $data[] and the helper functions defined here.
// ============================================================

require_once __DIR__ . '/config.php';

// ── 1. Validate ?id= ────────────────────────────────────────
$unique_id = trim($_GET['id'] ?? '');

if ($unique_id === '' || !preg_match('/^VC-[A-F0-9]{8}$/i', $unique_id)) {
    http_response_code(400);
    exit('Invalid or missing record ID.');
}

// ── 2. Fetch from DB ────────────────────────────────────────
$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM submissions WHERE unique_id = ? LIMIT 1');
$stmt->execute([$unique_id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit('Record not found.');
}

// ── 3. Build $data (same keys result.php template expects) ──
function fmt_display(?string $d): string
{
    if (empty($d)) return '';
    $parts = explode('-', $d);
    if (count($parts) === 3 && strlen($parts[0]) === 4) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $d;
}

$data = $row;
$data['date_birth_display']     = fmt_display($row['date_birth']);
$data['doseone_date_display']   = fmt_display($row['doseone_date']);
$data['dosetwo_date_display']   = fmt_display($row['dosetwo_date']);
$data['dosethree_date_display'] = fmt_display($row['dosethree_date']);

// ── 4. Helper used throughout result.php ────────────────────
function d($data, $key, $fallback = '') {
    return isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null
        ? htmlspecialchars((string)$data[$key])
        : $fallback;
}

// ── 5. Dose-3 visibility flag (used in result.php) ──────────
$show_dose3 = !empty($data['dosethree_date']) || !empty($data['dosethree_name_display']);

// ── 6. Render the existing result.php certificate template ──
//    result.php already starts with its own <?php block which
//    re-defines these same variables — so we include ONLY the
//    HTML portion below its PHP header.
//
//    Two ways to use this:
//
//    OPTION A (recommended – zero changes to result.php):
//      Include result.php directly; the duplicate function
//      definitions are guarded by function_exists checks IF
//      you add them in result.php (see CHANGES section below).
//
//    OPTION B (simpler if you're okay with one small edit):
//      Rename result.php to _result_tpl.php, remove its top
//      PHP block, and include it here.  Then update form.php's
//      action and any direct links accordingly.
//
// For now we directly include result.php. PHP will trigger a
// "Cannot redeclare" fatal error if fmt_display / d() are
// declared twice — add function_exists guards in result.php
// as shown in the CHANGES section of the README.
// ──────────────────────────────────────────────────────────────
include __DIR__ . '/result.php';
