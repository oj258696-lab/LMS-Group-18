<?php
/**
 * Shared helper functions
 */

/**
 * Set a flash message (success / error / warning / info)
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Display and clear flash message
 */
function display_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $type = htmlspecialchars($_SESSION['flash']['type']);
        $msg  = htmlspecialchars($_SESSION['flash']['message']);
        $alertClass = match ($type) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        echo "<div class=\"alert {$alertClass} alert-dismissible fade show\" role=\"alert\">";
        echo $msg;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash']);
    }
}

/**
 * Escape output for HTML
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Calculate fine in UGX
 * Fine = MAX(0, days late) * 700
 */
function calculate_fine(string $dueDate, string $returnDate): float
{
    $due   = new DateTime($dueDate);
    $ret   = new DateTime($returnDate);
    $diff  = $due->diff($ret);
    $daysLate = $diff->invert ? 0 : (int)$diff->days; // invert means return is before due
    return max(0, $daysLate) * 700;
}

/**
 * Format currency for display (UGX)
 */
function format_ugx(float $amount): string
{
    return number_format($amount, 0, '.', ',') . ' UGX';
}

/**
 * Validate email
 */
function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Simple required field check
 */
function require_fields(array $fields, array $data): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    return $errors;
}

/**
 * Pagination helper
 * Returns: ['page' => int, 'per_page' => int, 'offset' => int, 'total' => int, 'total_pages' => int]
 */
function get_pagination(int $total, int $perPage = 10): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    return [
        'page'        => $page,
        'per_page'    => $perPage,
        'offset'      => $offset,
        'total'       => $total,
        'total_pages' => $totalPages,
    ];
}

/**
 * Render Bootstrap pagination links
 * Keeps existing query-string parameters (search, filters, etc.)
 */
function render_pagination(array $p, string $baseUrl = '?'): void
{
    if ($p['total_pages'] <= 1) {
        return;
    }

    // Preserve current GET params except page
    $params = $_GET;
    unset($params['page']);

    echo '<nav aria-label="Page navigation" class="mt-3">';
    echo '<ul class="pagination justify-content-center flex-wrap">';

    // Previous
    $prevDisabled = $p['page'] <= 1 ? ' disabled' : '';
    $prevParams = $params;
    $prevParams['page'] = $p['page'] - 1;
    $prevUrl = $baseUrl . http_build_query($prevParams);
    echo '<li class="page-item' . $prevDisabled . '">';
    echo '<a class="page-link" href="' . e($prevUrl) . '">Previous</a></li>';

    // Page numbers (show a reasonable window)
    $start = max(1, $p['page'] - 2);
    $end   = min($p['total_pages'], $p['page'] + 2);

    if ($start > 1) {
        $firstParams = $params;
        $firstParams['page'] = 1;
        echo '<li class="page-item"><a class="page-link" href="' . e($baseUrl . http_build_query($firstParams)) . '">1</a></li>';
        if ($start > 2) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $p['page'] ? ' active' : '';
        $pageParams = $params;
        $pageParams['page'] = $i;
        echo '<li class="page-item' . $active . '">';
        echo '<a class="page-link" href="' . e($baseUrl . http_build_query($pageParams)) . '">' . $i . '</a></li>';
    }

    if ($end < $p['total_pages']) {
        if ($end < $p['total_pages'] - 1) {
            echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $lastParams = $params;
        $lastParams['page'] = $p['total_pages'];
        echo '<li class="page-item"><a class="page-link" href="' . e($baseUrl . http_build_query($lastParams)) . '">' . $p['total_pages'] . '</a></li>';
    }

    // Next
    $nextDisabled = $p['page'] >= $p['total_pages'] ? ' disabled' : '';
    $nextParams = $params;
    $nextParams['page'] = $p['page'] + 1;
    $nextUrl = $baseUrl . http_build_query($nextParams);
    echo '<li class="page-item' . $nextDisabled . '">';
    echo '<a class="page-link" href="' . e($nextUrl) . '">Next</a></li>';

    echo '</ul>';
    echo '<div class="text-center text-muted small">Showing page ' . $p['page'] . ' of ' . $p['total_pages'] . ' (' . $p['total'] . ' records)</div>';
    echo '</nav>';
}