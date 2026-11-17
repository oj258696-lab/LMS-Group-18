# Library Management System

**GitHub repository:** *(https://github.com/oj258696-lab/LMS-Group-18.git)*

A complete web-based Library Management System built for Database II. Librarians can manage books, members, loans, authors and categories through a browser interface. All data is stored in MySQL.

## Features

- Full CRUD for Books, Members and Loans
- List + Add for Authors and Categories
- Login / Logout with session protection
- Prepared statements, input validation, password hashing
- Business rules enforced in code:
  - Cannot issue a book with zero available copies
  - Issuing / returning updates available copies inside a **database transaction**
  - Member cannot borrow the same book twice at the same time
  - Suspended members cannot borrow
  - Due date automatically set to 14 days after issue
  - Cannot delete a book or member that still has unreturned loans
- **Fine calculation** on return:
  - `Fine = MAX(0, days late) × 700 UGX`
  - Fine is stored on the loan record
  - Loan update + book availability change happen in a single transaction

## Requirements

- XAMPP (Apache + MySQL/MariaDB + PHP 8.x)
- Modern browser

## Installation (fresh XAMPP)

1. Download and install XAMPP from https://www.apachefriends.org (default location `C:\xampp`).
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Copy the entire `library-system` folder into `C:\xampp\htdocs\`.
4. Open http://localhost/phpmyadmin, create a database named `library_db` with collation `utf8mb4_general_ci`.
5. Select the `library_db` database → **Import** → choose `database/library_db.sql` and click Go.
6. Confirm that `config/database.php` uses:
   - host: `localhost`
   - database: `library_db`
   - user: `root`
   - password: *(empty for default XAMPP)*
7. Open http://localhost/library-system in your browser.

## Test login

| Username   | Password     | Role      |
|------------|--------------|-----------|
| oj258696@students.cavendish.ac.ug      | Joshleilani@59  | admin     |
| librarian1 | password123  | librarian |

## Project structure

```
library-system/
├── index.php
├── README.md
├── config/database.php
├── database/
│   ├── library_db.sql
│   ├── queries.sql
│   └── erd.png
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   ├── auth_check.php
│   └── functions.php
├── auth/
│   ├── login.php
│   └── logout.php
├── dashboard/
├── books/
├── members/
├── loans/
├── authors/
├── categories/
├── assets/
└── docs/
```

## Fine calculation details

When a book is returned after the due date:

```
days_late = MAX(0, DATEDIFF(returned_date, due_date))
fine      = days_late * 700   (UGX)
```

Both the loan record (date_returned, fine, status) and the book’s `available_copies` are updated inside a single PDO transaction so they either both succeed or both roll back.

## Sample data included

- 7 authors
- 7 categories
- 12 books
- 12 members
- 12 loans (mix of returned, active and overdue, some with fines already recorded)

## Queries

See `database/queries.sql` for JOIN and aggregate examples (books on loan, overdue list with estimated fine, loans per category, etc.).

## Security notes

- All user input is handled with prepared statements.
- Passwords are stored with `password_hash()` / verified with `password_verify()`.
- Every protected page includes `auth_check.php`.
- Output is escaped with `htmlspecialchars()`.
- Raw SQL errors are never shown to the user.