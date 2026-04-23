<?php
// ============================================================
// download.php  –  Certificate download
//
// Strategy: redirect to view.php with ?print=1 appended.
// A tiny JS snippet on that page triggers window.print()
// automatically, which lets the user Save as PDF via the
// browser's native print dialog — no server-side PDF library
// needed, and the output is byte-for-byte identical to the
// on-screen certificate.
//
// If you later want a server-rendered PDF (wkhtmltopdf /
// Puppeteer / mPDF), this file is the right place to add it.
// ============================================================

require_once __DIR__ . '/config.php';

$unique_id = trim($_GET['id'] ?? '');

if ($unique_id === '' || !preg_match('/^VC-[A-F0-9]{8}$/i', $unique_id)) {
    http_response_code(400);
    exit('Invalid or missing record ID.');
}

// Confirm the record actually exists before redirecting
$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT id FROM submissions WHERE unique_id = ? LIMIT 1');
$stmt->execute([$unique_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Record not found.');
}

// Redirect to the print-mode view
header('Location: view.php?id=' . urlencode($unique_id) . '&print=1');
exit;
