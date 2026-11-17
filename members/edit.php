<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid member.');
    redirect('/library-system/members/');
}

$stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();
if (!$member) {
    set_flash('error', 'Member not found.');
    redirect('/library-system/members/');
}

$pageTitle = 'Edit Member - LMS';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_number = trim($_POST['member_number'] ?? '');
    $full_name     = trim($_POST['full_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $status        = $_POST['status'] ?? 'active';
    $date_joined   = $_POST['date_joined'] ?? $member['date_joined'];

    $errors = require_fields(['member_number', 'full_name', 'email'], $_POST);
    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!in_array($status, ['active', 'suspended'], true)) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM members WHERE (member_number = ? OR email = ?) AND id != ?');
        $chk->execute([$member_number, $email, $id]);
        if ($chk->fetch()) {
            $errors[] = 'Member number or email already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE members
            SET member_number = ?, full_name = ?, email = ?, phone = ?, status = ?, date_joined = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $member_number, $full_name, $email,
            $phone ?: null, $status, $date_joined, $id
        ]);
        set_flash('success', 'Member updated successfully.');
        redirect('/library-system/members/');
    }

    $member = array_merge($member, $_POST);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h1 class="h5 mb-0">Edit Member</h1></div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Member Number *</label>
                            <input type="text" name="member_number" class="form-control" required
                                   value="<?= e($member['member_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= e($member['full_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= e($member['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= e($member['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $member['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Joined</label>
                            <input type="date" name="date_joined" class="form-control"
                                   value="<?= e($member['date_joined']) ?>">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Member</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>