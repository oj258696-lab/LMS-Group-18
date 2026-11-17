<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Dashboard - LMS';

// Summary stats
$stats = [
    'books'      => $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
    'members'    => $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn(),
    'active_loans' => $pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL")->fetchColumn(),
    'overdue'    => $pdo->query("SELECT COUNT(*) FROM loans WHERE date_returned IS NULL AND due_date < CURDATE()")->fetchColumn(),
    'total_fines'=> $pdo->query('SELECT COALESCE(SUM(fine),0) FROM loans')->fetchColumn(),
];

// Recent loans
$recent = $pdo->query("
    SELECT l.id, b.title, m.full_name, l.date_issued, l.due_date, l.date_returned, l.fine, l.status
    FROM loans l
    JOIN books b ON b.id = l.book_id
    JOIN members m ON m.id = l.member_id
    ORDER BY l.created_at DESC
    LIMIT 8
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <span class="text-muted">Welcome, <?= e($_SESSION['full_name']) ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small">Total Books</div>
                <div class="fs-3 fw-bold"><?= (int)$stats['books'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small">Members</div>
                <div class="fs-3 fw-bold"><?= (int)$stats['members'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small">Active Loans</div>
                <div class="fs-3 fw-bold text-primary"><?= (int)$stats['active_loans'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small">Overdue</div>
                <div class="fs-3 fw-bold text-danger"><?= (int)$stats['overdue'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small">Fines Collected</div>
                <div class="fs-5 fw-bold"><?= format_ugx((float)$stats['total_fines']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Loans</span>
        <a href="/library-system/loans/" class="btn btn-sm btn-outline-primary">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Member</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th>Returned</th>
                    <th>Fine</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="7" class="text-center text-muted">No loans yet</td></tr>
            <?php else: ?>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['date_issued']) ?></td>
                    <td><?= e($row['due_date']) ?></td>
                    <td><?= e($row['date_returned'] ?? '—') ?></td>
                    <td class="<?= $row['fine'] > 0 ? 'fine-amount' : '' ?>">
                        <?= $row['fine'] > 0 ? format_ugx((float)$row['fine']) : '—' ?>
                    </td>
                    <td>
                        <?php
                        $badge = match ($row['status']) {
                            'returned' => 'bg-success',
                            'overdue'  => 'bg-danger',
                            default    => 'bg-primary',
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e(ucfirst($row['status'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>