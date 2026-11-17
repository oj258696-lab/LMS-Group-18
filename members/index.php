<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Members - LMS';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where  = ' WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= ' AND (full_name LIKE ? OR member_number LIKE ? OR email LIKE ?)';
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term]);
}
if ($status === 'active' || $status === 'suspended') {
    $where .= ' AND status = ?';
    $params[] = $status;
}

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM members $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = get_pagination($total, 10);

// Data
$sql = "SELECT * FROM members $where ORDER BY full_name ASC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Members</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Member</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search name, member number, email..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
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
                    <th>Member No.</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No members found</td></tr>
            <?php else: ?>
                <?php foreach ($members as $m): ?>
                <tr>
                    <td><code><?= e($m['member_number']) ?></code></td>
                    <td><?= e($m['full_name']) ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td><?= e($m['phone'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $m['status'] === 'active' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= e(ucfirst($m['status'])) ?>
                        </span>
                    </td>
                    <td><?= e($m['date_joined']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="edit.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="delete.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="Delete this member? This cannot be undone if they have no active loans.">
                            Delete
                        </a>
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
