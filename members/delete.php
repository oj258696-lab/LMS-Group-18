<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid member.');
    redirect('/library-system/members/');
}

// Business rule: cannot delete a member with unreturned books
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ? AND date_returned IS NULL");
$stmt->execute([$id]);
$activeLoans = (int)$stmt->fetchColumn();

if ($activeLoans > 0) {
    set_flash('error', "Cannot delete this member. They have $activeLoans unreturned book(s). Return the books first.");
    redirect('/library-system/members/');
}

try {
    $stmt = $pdo->prepare('DELETE FROM members WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Member deleted successfully.');
    } else {
        set_flash('error', 'Member not found.');
    }
} catch (PDOException $e) {
    set_flash('error', 'Cannot delete this member because they have loan history.');
}

redirect('/library-system/members/');