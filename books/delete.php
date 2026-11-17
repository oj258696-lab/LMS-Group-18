<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid book.');
    redirect('/library-system/books/');
}

// Business rule: cannot delete a book that still has unreturned loans
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE book_id = ? AND date_returned IS NULL");
$stmt->execute([$id]);
$activeLoans = (int)$stmt->fetchColumn();

if ($activeLoans > 0) {
    set_flash('error', "Cannot delete this book. It has $activeLoans unreturned loan(s). Return the books first.");
    redirect('/library-system/books/');
}

// Also block if any loan history exists and we want to keep referential integrity
// (RESTRICT is already on the FK, but we give a friendly message)
try {
    $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Book deleted successfully.');
    } else {
        set_flash('error', 'Book not found.');
    }
} catch (PDOException $e) {
    // Likely foreign key restriction from returned loans history
    set_flash('error', 'Cannot delete this book because it has loan history. Consider marking it inactive instead.');
}

redirect('/library-system/books/');