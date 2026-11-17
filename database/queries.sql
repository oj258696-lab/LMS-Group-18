-- Library Management System - Sample Queries
-- Demonstrates JOINs and aggregate reports

USE library_db;

-- 1. Books currently on loan with the member's name
SELECT 
    l.id AS loan_id,
    b.title AS book_title,
    b.isbn,
    m.full_name AS member_name,
    m.member_number,
    l.date_issued,
    l.due_date,
    DATEDIFF(CURDATE(), l.due_date) AS days_overdue
FROM loans l
JOIN books b ON l.book_id = b.id
JOIN members m ON l.member_id = m.id
WHERE l.date_returned IS NULL
ORDER BY l.due_date ASC;

-- 2. Overdue loans (still out and past due date)
SELECT 
    l.id AS loan_id,
    b.title,
    m.full_name AS member_name,
    m.phone,
    l.date_issued,
    l.due_date,
    DATEDIFF(CURDATE(), l.due_date) AS days_late,
    (GREATEST(0, DATEDIFF(CURDATE(), l.due_date)) * 700) AS estimated_fine_ugx
FROM loans l
JOIN books b ON l.book_id = b.id
JOIN members m ON l.member_id = m.id
WHERE l.date_returned IS NULL
  AND l.due_date < CURDATE()
ORDER BY days_late DESC;

-- 3. Number of loans per category (aggregate with GROUP BY)
SELECT 
    c.name AS category,
    COUNT(l.id) AS total_loans,
    SUM(CASE WHEN l.date_returned IS NULL THEN 1 ELSE 0 END) AS currently_out,
    SUM(l.fine) AS total_fines_collected_ugx
FROM categories c
LEFT JOIN books b ON b.category_id = c.id
LEFT JOIN loans l ON l.book_id = b.id
GROUP BY c.id, c.name
ORDER BY total_loans DESC;

-- 4. Extra: Members with the highest total fines paid
SELECT 
    m.member_number,
    m.full_name,
    COUNT(l.id) AS loans_returned,
    SUM(l.fine) AS total_fines_paid_ugx
FROM members m
JOIN loans l ON l.member_id = m.id
WHERE l.date_returned IS NOT NULL
GROUP BY m.id, m.member_number, m.full_name
HAVING total_fines_paid_ugx > 0
ORDER BY total_fines_paid_ugx DESC;