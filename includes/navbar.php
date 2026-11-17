<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/library-system/dashboard/">
            <i class="bi bi-book-half"></i> LMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (!empty($_SESSION['user_id'])): ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/dashboard/"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/books/"><i class="bi bi-book"></i> Books</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/members/"><i class="bi bi-people"></i> Members</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/loans/"><i class="bi bi-journal-arrow-up"></i> Loans</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/authors/"><i class="bi bi-pen"></i> Authors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/categories/"><i class="bi bi-tags"></i> Categories</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="navbar-text text-white me-3">
                        <i class="bi bi-person-circle"></i>
                        <?= e($_SESSION['full_name'] ?? 'User') ?>
                        <small class="opacity-75">(<?= e($_SESSION['role'] ?? '') ?>)</small>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/library-system/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>