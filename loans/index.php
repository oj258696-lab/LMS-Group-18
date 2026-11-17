<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Loans - LMS';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where  = ' WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= ' AND (b.title LIKE ? OR m.full_name LIKE ? OR m.member_number LIKE ?)';
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term]);
}
if ($status === 'issued' || $status === 'returned' || $status === 'overdue') {
    $where .= ' AND l.status = ?';
    $params[] = $status;
}

// Count
$countSql = "
    SELECT COUNT(*)
    FROM loans l
    JOIN books b ON b.id = l.book_id
    JOIN members m ON m.id = l.member_id
    $where
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = get_pagination($total, 10);

// Data
$sql = "
    SELECT l.*, b.title AS book_title, b.isbn,
           m.full_name AS member_name, m.member_number
    FROM loans l
    JOIN books b ON b.id = l.book_id
    JOIN members m ON m.id = l.member_id
    $where
    ORDER BY l.date_issued DESC, l.id DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();

// Auto-flag overdue for display
foreach ($loans as &$loan) {
    if ($loan['date_returned'] === null && $loan['due_date'] < date('Y-m-d') && $loan['status'] !== 'overdue') {
        $loan['status'] = 'overdue';
    }
}
unset($loan);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Loans</h1>
    <a href="issue.php" class="btn btn-primary"><i class="bi bi-journal-plus"></i> Issue Book</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search book or member..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
            <option value="returned" <?= $status === 'returned' ? 'selected' : '' ?>>Returned</option>
            <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th>Returned</th>
                    <th>Fine</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($loans)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No loans found</td></tr>
            <?php else: ?>
                <?php foreach ($loans as $l): ?>
                <tr>
                    <td><?= (int)$l['id'] ?></td>
                    <td>
                        <div><?= e($l['book_title']) ?></div>
                        <small class="text-muted"><?= e($l['isbn']) ?></small>
                    </td>
                    <td>
                        <div><?= e($l['member_name']) ?></div>
                        <small class="text-muted"><?= e($l['member_number']) ?></small>
                    </td>
                    <td><?= e($l['date_issued']) ?></td>
                    <td><?= e($l['due_date']) ?></td>
                    <td><?= e($l['date_returned'] ?? '—') ?></td>
                    <td class="<?= $l['fine'] > 0 ? 'fine-amount' : '' ?>">
                        <?= $l['fine'] > 0 ? format_ugx((float)$l['fine']) : '—' ?>
                    </td>
                    <td>
                        <?php
                        $badge = match ($l['status']) {
                            'returned' => 'bg-success',
                            'overdue'  => 'bg-danger',
                            default    => 'bg-primary',
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e(ucfirst($l['status'])) ?></span>
                    </td>
                    <td class="text-end">
                        <?php if ($l['date_returned'] === null): ?>
                            <a href="return.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-success">
                                Return
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_pagination($pagination); ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
