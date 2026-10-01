<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if ($role === 'admin') {
    header("Location: /legalconnect/admin/login.php");
} else {
    header("Location: /legalconnect/login.php");
}
exit();
?>
