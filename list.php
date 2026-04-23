<?php
// ============================================================
// list.php  –  Admin list of all submissions
// ============================================================

require_once __DIR__ . '/config.php';

$pdo  = get_pdo();
$rows = $pdo->query(
    'SELECT id, unique_id, name, certi_no, nationality, gender,
            total_dose, created_at
     FROM submissions
     ORDER BY id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submissions – List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #1a202c; color: #e2e8f0; }
        .card  { background: #2d3748; border: 1px solid #4a5568; }
        thead  { background: #4a5568; color: #fff; }
        tbody tr:hover { background: #374151; }
        a      { color: #63b3ed; }
        .badge-pill { font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">📋 All Submissions
            <span class="badge badge-secondary ml-2"><?= count($rows) ?></span>
        </h4>
        <a href="form.php" class="btn btn-sm btn-success">+ New Entry</a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">No records yet. <a href="form.php">Add the first one.</a></div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="color:#e2e8f0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Public ID</th>
                            <th>Name</th>
                            <th>Certificate No</th>
                            <th>Nationality</th>
                            <th>Gender</th>
                            <th>Doses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <code style="color:#81e6d9"><?= htmlspecialchars($r['unique_id']) ?></code>
                            </td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= htmlspecialchars($r['certi_no']) ?></td>
                            <td><?= htmlspecialchars($r['nationality']) ?></td>
                            <td><?= htmlspecialchars($r['gender']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-info"><?= (int)$r['total_dose'] ?></span>
                            </td>
                            <td><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></td>
                            <td nowrap>
                                <a href="view.php?id=<?= urlencode($r['unique_id']) ?>"
                                   class="btn btn-xs btn-primary btn-sm mr-1">View</a>
                                <a href="download.php?id=<?= urlencode($r['unique_id']) ?>"
                                   class="btn btn-xs btn-secondary btn-sm">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
