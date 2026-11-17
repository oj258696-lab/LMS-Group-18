<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Add Book - LMS';
$errors = [];

$authors    = $pdo->query('SELECT id, name FROM authors ORDER BY name')->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn           = trim($_POST['isbn'] ?? '');
    $title          = trim($_POST['title'] ?? '');
    $author_id      = (int)($_POST['author_id'] ?? 0);
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $publisher      = trim($_POST['publisher'] ?? '');
    $year_published = trim($_POST['year_published'] ?? '');
    $total_copies   = (int)($_POST['total_copies'] ?? 1);

    $errors = require_fields(['isbn', 'title'], $_POST);
    if ($author_id <= 0) $errors[] = 'Author is required.';
    if ($category_id <= 0) $errors[] = 'Category is required.';
    if ($total_copies < 1) $errors[] = 'Total copies must be at least 1.';

    if (empty($errors)) {
        // Unique ISBN check
        $chk = $pdo->prepare('SELECT id FROM books WHERE isbn = ?');
        $chk->execute([$isbn]);
        if ($chk->fetch()) {
            $errors[] = 'A book with this ISBN already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO books (isbn, title, author_id, category_id, publisher, year_published, total_copies, available_copies)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $isbn, $title, $author_id, $category_id,
            $publisher ?: null,
            $year_published ?: null,
            $total_copies,
            $total_copies
        ]);
        set_flash('success', 'Book added successfully.');
        redirect('/library-system/books/');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h5 mb-0">Add New Book</h1>
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?= e($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ISBN *</label>
                            <input type="text" name="isbn" class="form-control" required
                                   value="<?= e($_POST['isbn'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?= e($_POST['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Author *</label>
                            <select name="author_id" class="form-select" required>
                                <option value="">Select author</option>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= $a['id'] ?>"
                                        <?= (int)($_POST['author_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= e($a['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= (int)($_POST['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-control"
                                   value="<?= e($_POST['publisher'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year Published</label>
                            <input type="number" name="year_published" class="form-control" min="1000" max="2099"
                                   value="<?= e($_POST['year_published'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Copies *</label>
                            <input type="number" name="total_copies" class="form-control" min="1" required
                                   value="<?= e($_POST['total_copies'] ?? '1') ?>">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Book</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>