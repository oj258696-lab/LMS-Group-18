<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Books - LMS';

$search   = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$author   = (int)($_GET['author'] ?? 0);

// Build WHERE clause (shared by count + data queries)
$where  = ' WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= ' AND (b.title LIKE ? OR b.isbn LIKE ? OR a.name LIKE ?)';
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term]);
}
if ($category > 0) {
    $where .= ' AND b.category_id = ?';
    $params[] = $category;
}
if ($author > 0) {
    $where .= ' AND b.author_id = ?';
    $params[] = $author;
}

// Total count for pagination
$countSql = "
    SELECT COUNT(*)
    FROM books b
    JOIN authors a ON a.id = b.author_id
    JOIN categories c ON c.id = b.category_id
    $where
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = get_pagination($total, 10);

// Data query with LIMIT
$sql = "
    SELECT b.*, a.name AS author_name, c.name AS category_name
    FROM books b
    JOIN authors a ON a.id = b.author_id
    JOIN categories c ON c.id = b.category_id
    $where
    ORDER BY b.title ASC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$authors    = $pdo->query('SELECT id, name FROM authors ORDER BY name')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Books</h1>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Book</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search title, ISBN, author..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="category" class="form-select">
            <option value="0">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="author" class="form-select">
            <option value="0">All authors</option>
            <?php foreach ($authors as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $author === (int)$a['id'] ? 'selected' : '' ?>>
                    <?= e($a['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Year</th>
                    <th>Copies</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($books)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No books found</td></tr>
            <?php else: ?>
                <?php foreach ($books as $b): ?>
                <tr>
                    <td><code><?= e($b['isbn']) ?></code></td>
                    <td><?= e($b['title']) ?></td>
                    <td><?= e($b['author_name']) ?></td>
                    <td><?= e($b['category_name']) ?></td>
                    <td><?= e($b['year_published']) ?></td>
                    <td>
                        <span class="badge <?= $b['available_copies'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
                            <?= (int)$b['available_copies'] ?> / <?= (int)$b['total_copies'] ?>
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <a href="delete.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="Delete this book? This cannot be undone if there are no active loans.">
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
