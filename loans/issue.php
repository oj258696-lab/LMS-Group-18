<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Issue Book - LMS';
$errors = [];

// Available books (available_copies > 0)
$books = $pdo->query("
    SELECT b.id, b.title, b.isbn, b.available_copies, a.name AS author_name
    FROM books b
    JOIN authors a ON a.id = b.author_id
    WHERE b.available_copies > 0
    ORDER BY b.title
")->fetchAll();

// Active members only
$members = $pdo->query("
    SELECT id, member_number, full_name
    FROM members
    WHERE status = 'active'
    ORDER BY full_name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id   = (int)($_POST['member_id'] ?? 0);
    $book_id     = (int)($_POST['book_id'] ?? 0);
    $date_issued = $_POST['date_issued'] ?? date('Y-m-d');
    $loan_days   = 14; // business rule: due date = issue date + 14 days

    if ($member_id <= 0) $errors[] = 'Please select a member.';
    if ($book_id <= 0)   $errors[] = 'Please select a book.';

    if (empty($errors)) {
        // Re-check member status
        $stmt = $pdo->prepare('SELECT status FROM members WHERE id = ?');
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        if (!$member) {
            $errors[] = 'Member not found.';
        } elseif ($member['status'] !== 'active') {
            $errors[] = 'Suspended members cannot borrow books.';
        }
    }

    if (empty($errors)) {
        // Re-check available copies
        $stmt = $pdo->prepare('SELECT available_copies, title FROM books WHERE id = ? FOR UPDATE');
        // We will start a transaction shortly
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock the book row
            $stmt = $pdo->prepare('SELECT available_copies, title FROM books WHERE id = ? FOR UPDATE');
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception('Book not found.');
            }
            if ((int)$book['available_copies'] < 1) {
                throw new Exception('This book has no available copies.');
            }

            // Business rule: member cannot borrow the same book twice at the same time
            $stmt = $pdo->prepare("
                SELECT id FROM loans
                WHERE member_id = ? AND book_id = ? AND date_returned IS NULL
            ");
            $stmt->execute([$member_id, $book_id]);
            if ($stmt->fetch()) {
                throw new Exception('This member already has an active loan for this book.');
            }

            $due_date = (new DateTime($date_issued))->modify("+{$loan_days} days")->format('Y-m-d');

            // Insert loan
            $stmt = $pdo->prepare("
                INSERT INTO loans (member_id, book_id, date_issued, due_date, status)
                VALUES (?, ?, ?, ?, 'issued')
            ");
            $stmt->execute([$member_id, $book_id, $date_issued, $due_date]);

            // Decrease available copies
            $stmt = $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ?');
            $stmt->execute([$book_id]);

            $pdo->commit();
            set_flash('success', 'Book issued successfully. Due date: ' . $due_date);
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
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h1 class="h5 mb-0">Issue Book to Member</h1></div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Member *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">Select active member</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"
                                    <?= (int)($_POST['member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= e($m['full_name']) ?> (<?= e($m['member_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book *</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">Select available book</option>
                            <?php foreach ($books as $b): ?>
                                <option value="<?= $b['id'] ?>"
                                    <?= (int)($_POST['book_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                                    <?= e($b['title']) ?> — <?= e($b['author_name']) ?>
                                    (<?= (int)$b['available_copies'] ?> available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="date_issued" class="form-control"
                               value="<?= e($_POST['date_issued'] ?? date('Y-m-d')) ?>">
                        <div class="form-text">Due date will be automatically set to 14 days after the issue date.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Issue Book</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>