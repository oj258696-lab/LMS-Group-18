<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid book.');
    redirect('/library-system/books/');
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
$stmt->execute([$id]);
$book = $stmt->fetch();
if (!$book) {
    set_flash('error', 'Book not found.');
    redirect('/library-system/books/');
}

$pageTitle = 'Edit Book - LMS';
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

    // Available copies cannot exceed new total
    $issued = $book['total_copies'] - $book['available_copies'];
    if ($total_copies < $issued) {
        $errors[] = "Total copies cannot be less than currently issued copies ($issued).";
    }

    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM books WHERE isbn = ? AND id != ?');
        $chk->execute([$isbn, $id]);
        if ($chk->fetch()) {
            $errors[] = 'A book with this ISBN already exists.';
        }
    }

    if (empty($errors)) {
        $newAvailable = $total_copies - $issued;
        $stmt = $pdo->prepare("
            UPDATE books
            SET isbn = ?, title = ?, author_id = ?, category_id = ?,
                publisher = ?, year_published = ?, total_copies = ?, available_copies = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $isbn, $title, $author_id, $category_id,
            $publisher ?: null,
            $year_published ?: null,
            $total_copies,
            $newAvailable,
            $id
        ]);
        set_flash('success', 'Book updated successfully.');
        redirect('/library-system/books/');
    }

    // Keep form values
    $book = array_merge($book, $_POST);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h5 mb-0">Edit Book</h1>
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
                                   value="<?= e($book['isbn']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?= e($book['title']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Author *</label>
                            <select name="author_id" class="form-select" required>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= $a['id'] ?>"
                                        <?= (int)$book['author_id'] === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= e($a['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= (int)$book['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-control"
                                   value="<?= e($book['publisher'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year Published</label>
                            <input type="number" name="year_published" class="form-control" min="1000" max="2099"
                                   value="<?= e($book['year_published'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Copies *</label>
                            <input type="number" name="total_copies" class="form-control" min="1" required
                                   value="<?= e($book['total_copies']) ?>">
                            <div class="form-text">Currently available: <?= (int)$book['available_copies'] ?></div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Book</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>