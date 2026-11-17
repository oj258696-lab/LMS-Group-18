<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid loan.');
    redirect('/library-system/loans/');
}

// Load loan details
$stmt = $pdo->prepare("
    SELECT l.*, b.title AS book_title, m.full_name AS member_name
    FROM loans l
    JOIN books b ON b.id = l.book_id
    JOIN members m ON m.id = l.member_id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('error', 'Loan not found.');
    redirect('/library-system/loans/');
}

if ($loan['date_returned'] !== null) {
    set_flash('warning', 'This loan has already been returned.');
    redirect('/library-system/loans/');
}

$pageTitle = 'Return Book - LMS';
$errors = [];
$previewFine = 0;

// Default return date = today
$returnDate = $_POST['date_returned'] ?? date('Y-m-d');

// Preview fine for the form
$previewFine = calculate_fine($loan['due_date'], $returnDate);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnDate = $_POST['date_returned'] ?? date('Y-m-d');

    if ($returnDate < $loan['date_issued']) {
        $errors[] = 'Return date cannot be before the issue date.';
    }

    if (empty($errors)) {
        $fine = calculate_fine($loan['due_date'], $returnDate);

        try {
            $pdo->beginTransaction();

            // Update loan record (status + returned date + fine)
            $stmt = $pdo->prepare("
                UPDATE loans
                SET date_returned = ?, fine = ?, status = 'returned'
                WHERE id = ? AND date_returned IS NULL
            ");
            $stmt->execute([$returnDate, $fine, $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Loan could not be updated (maybe already returned).');
            }

            // Increase available copies
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = ?');
            $stmt->execute([$loan['book_id']]);

            $pdo->commit();

            $msg = 'Book returned successfully.';
            if ($fine > 0) {
                $msg .= ' Fine charged: ' . format_ugx($fine) . '.';
            }
            set_flash('success', $msg);
            redirect('/library-system/loans/');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h1 class="h5 mb-0">Return Book</h1></div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <dl class="row mb-4">
                    <dt class="col-sm-4">Book</dt>
                    <dd class="col-sm-8"><?= e($loan['book_title']) ?></dd>
                    <dt class="col-sm-4">Member</dt>
                    <dd class="col-sm-8"><?= e($loan['member_name']) ?></dd>
                    <dt class="col-sm-4">Issued</dt>
                    <dd class="col-sm-8"><?= e($loan['date_issued']) ?></dd>
                    <dt class="col-sm-4">Due Date</dt>
                    <dd class="col-sm-8"><?= e($loan['due_date']) ?></dd>
                </dl>

                <form method="post" id="returnForm">
                    <div class="mb-3">
                        <label class="form-label">Return Date *</label>
                        <input type="date" name="date_returned" id="date_returned" class="form-control" required
                               value="<?= e($returnDate) ?>"
                               min="<?= e($loan['date_issued']) ?>">
                    </div>

                    <div class="alert alert-info" id="finePreview">
                        Estimated fine:
                        <strong class="fine-amount" id="fineAmount">
                            <?= format_ugx($previewFine) ?>
                        </strong>
                        <div class="small text-muted mt-1">
                            Fine = max(0, days late) × 700 UGX
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            Confirm Return
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Live fine preview (client-side, server still recalculates)
const dueDate = '<?= e($loan['due_date']) ?>';
const finePerDay = 700;

document.getElementById('date_returned').addEventListener('change', function () {
    const ret = new Date(this.value);
    const due = new Date(dueDate);
    let daysLate = 0;
    if (ret > due) {
        const diffTime = ret - due;
        daysLate = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    }
    const fine = Math.max(0, daysLate) * finePerDay;
    document.getElementById('fineAmount').textContent =
        fine.toLocaleString('en-UG') + ' UGX';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>