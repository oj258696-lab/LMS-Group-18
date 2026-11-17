<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /library-system/dashboard/');
} else {
    header('Location: /library-system/auth/login.php');
}
exit;