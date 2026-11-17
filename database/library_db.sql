-- Library Management System
-- Database: library-db
-- Collation: utf8mb4_general_ci

CREATE DATABASE IF NOT EXISTS library_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE library-db;

-- -----------------------------------------------------
-- Table: authors
-- -----------------------------------------------------
CREATE TABLE authors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  country VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: categories
-- -----------------------------------------------------
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: books
-- -----------------------------------------------------
CREATE TABLE books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  isbn VARCHAR(20) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  author_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  publisher VARCHAR(150) DEFAULT NULL,
  year_published YEAR DEFAULT NULL,
  total_copies INT UNSIGNED NOT NULL DEFAULT 1,
  available_copies INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_author FOREIGN KEY (author_id) REFERENCES authors(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_copies CHECK (available_copies <= total_copies AND available_copies >= 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: members
-- -----------------------------------------------------
CREATE TABLE members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_number VARCHAR(20) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  date_joined DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: users (librarians)
-- -----------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role ENUM('admin', 'librarian') NOT NULL DEFAULT 'librarian',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: loans
-- Includes fine column for late returns (UGX)
-- -----------------------------------------------------
CREATE TABLE loans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  date_issued DATE NOT NULL,
  due_date DATE NOT NULL,
  date_returned DATE DEFAULT NULL,
  fine DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Fine in UGX, calculated on return',
  status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_fine CHECK (fine >= 0)
) ENGINE=InnoDB;

-- Indexes for common queries
CREATE INDEX idx_loans_status ON loans(status);
CREATE INDEX idx_loans_due_date ON loans(due_date);
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_members_name ON members(full_name);

-- -----------------------------------------------------
-- Sample Data
-- -----------------------------------------------------

-- Authors (5+)
INSERT INTO authors (name, country) VALUES
('Chinua Achebe', 'Nigeria'),
('Ngũgĩ wa Thiong\'o', 'Kenya'),
('Okot p\'Bitek', 'Uganda'),
('Wole Soyinka', 'Nigeria'),
('Ama Ata Aidoo', 'Ghana'),
('Nuruddin Farah', 'Somalia'),
('Tsitsi Dangarembga', 'Zimbabwe');

-- Categories (5+)
INSERT INTO categories (name, description) VALUES
('Fiction', 'Novels and short stories'),
('Poetry', 'Poetry collections and verse'),
('Drama', 'Plays and theatrical works'),
('History', 'Historical accounts and analysis'),
('Science', 'Scientific and technical works'),
('Children', 'Books for young readers'),
('Biography', 'Life stories and memoirs');

-- Books (10+)
INSERT INTO books (isbn, title, author_id, category_id, publisher, year_published, total_copies, available_copies) VALUES
('978-0141186883', 'Things Fall Apart', 1, 1, 'Penguin Classics', 1958, 5, 3),
('978-0435908447', 'A Grain of Wheat', 2, 1, 'Heinemann', 1967, 4, 2),
('978-0435901660', 'Song of Lawino', 3, 2, 'Heinemann', 1966, 3, 1),
('978-0199113103', 'Death and the King\'s Horseman', 4, 3, 'Oxford University Press', 1975, 2, 2),
('978-0435909741', 'Changes: A Love Story', 5, 1, 'Heinemann', 1991, 3, 3),
('978-0141185237', 'Maps', 6, 1, 'Penguin', 1986, 2, 1),
('978-0435908461', 'Nervous Conditions', 7, 1, 'Heinemann', 1988, 4, 4),
('978-0141188764', 'No Longer at Ease', 1, 1, 'Penguin', 1960, 3, 2),
('978-0435905354', 'Petals of Blood', 2, 1, 'Heinemann', 1977, 2, 0),
('978-0435901677', 'Song of Ocol', 3, 2, 'Heinemann', 1970, 2, 2),
('978-0435910129', 'The Beautyful Ones Are Not Yet Born', 1, 1, 'Heinemann', 1968, 3, 3),
('978-0143106715', 'Half of a Yellow Sun', 1, 1, 'Penguin', 2006, 4, 3);

-- Members (10+)
INSERT INTO members (member_number, full_name, email, phone, status, date_joined) VALUES
('CUU-257-646', 'Baguma Alvin', 'ba257646@students.cavendish.ac.ug', '+256723487654', 'active', '2024-01-15'),
('CUU-611-750', 'Brandy Brenda', 'bb611750@students.cavendish.ac.ug', '+256787822830', 'active', '2024-02-20'),
('CUU-234-665', 'James Mwanje', 'mj234665@students.cavendish.ac.ug', '+256771234567', 'suspended', '2024-03-10'),
('CUU-122-482', 'Jimmy Kaliisa Kyomuhendo', 'kkj122482@students.cavendish.ac.ug', '+256786803607', 'active', '2024-04-05'),
('CUU-252-431', 'Kamukama Tadeo', 'kt252431@students.cavendish.ac.ug', '+256749926583', 'active', '2024-05-12'),
('CUU-258-696', 'Otim Joshua', 'oj258696@students.cavendish.ac.ug', '+256705412771', 'active', '2024-06-18'),
('CUU-278-614', 'Melado Apasi Victor', 'mav278614@students.cavendish.ac.ug', '+2567794011468', 'active', '2024-07-22'),
('CUU-149-956', 'Musa Ali Yahya', 'mya@students.cavendish.ac.ug', '+256752398765', 'active', '2024-08-30'),
('CUU-275-498', 'Mutesi Prossy', 'mp275498@students.cavendish.ac.ug', '+256756789012', 'suspended', '2024-09-14'),
('CUU-270-829', 'Nelima Midrate', 'nm270829@students.cavendish.ac.ug', '+256747910028', 'active', '2024-10-01'),
('CUU-269-411', 'Ssebutinde Charles', 'sc269411@students.cavendish.ac.ug', '+256776261765', 'active', '2025-01-08'),
('CUU-264-897', 'Tumusiime Hillary', 'th264987@students.cavendish.ac.ug', '+256743370827', 'active', '2025-02-14');

-- Users (librarians) - password for all test accounts is: password123
INSERT INTO users (username, password_hash, full_name, role) VALUES
('oj258696@students.cavendish.ac.ug', '$2y$10$VFPh.Xii2oDMptS08O0/ZOvMiSEIrOsH0GUnrGMHC1/y4IMopGGY2', 'Joshua Simpson Otim', 'admin'),
('librarian1', '$2y$10$VFPh.Xii2oDMptS08O0/ZOvMiSEIrOsH0GUnrGMHC1/y4IMopGGY2', 'Mutesi Prossy', 'librarian'),
('librarian2', '$2y$10$VFPh.Xii2oDMptS08O0/ZOvMiSEIrOsH0GUnrGMHC1/y4IMopGGY2', 'Nelima Midrate', 'librarian');

-- Loans (10+) - mix of returned, still out, and overdue
-- Assuming current date around 2026-11-15 for overdue calculation
INSERT INTO loans (member_id, book_id, date_issued, due_date, date_returned, fine, status) VALUES
(1, 1, '2026-09-01', '2026-09-15', '2026-09-14', 0.00, 'returned'),
(2, 2, '2026-09-10', '2026-09-24', '2026-09-28', 2800.00, 'returned'),  -- 4 days late * 700
(4, 3, '2026-10-01', '2026-10-15', NULL, 0.00, 'issued'),
(5, 4, '2026-10-05', '2026-10-19', '2026-10-18', 0.00, 'returned'),
(6, 1, '2026-10-10', '2026-10-24', NULL, 0.00, 'issued'),
(7, 5, '2026-08-15', '2026-08-29', NULL, 0.00, 'overdue'),  -- long overdue
(8, 6, '2026-10-20', '2026-11-03', NULL, 0.00, 'issued'),
(1, 8, '2026-09-20', '2026-10-04', '2026-10-10', 4200.00, 'returned'), -- 6 days late
(10, 9, '2026-10-25', '2026-11-08', NULL, 0.00, 'issued'),
(11, 2, '2026-11-01', '2026-11-15', NULL, 0.00, 'issued'),
(12, 7, '2026-09-05', '2026-09-19', '2026-09-25', 4200.00, 'returned'),
(4, 10, '2026-10-15', '2026-10-29', NULL, 0.00, 'issued');

-- Update available_copies to match current loans (already set reasonably in INSERT)
-- For books that still have active loans we already reduced available_copies above.