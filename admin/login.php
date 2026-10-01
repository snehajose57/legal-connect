<?php
$page_title = "Admin Login";
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') header("Location: /legalconnect/admin/dashboard.php");
    elseif ($_SESSION['role'] === 'lawyer') header("Location: /legalconnect/lawyer/dashboard.php");
    else header("Location: /legalconnect/user/dashboard.php");
    exit();
}

$error_msg = "";

// Hardcoded admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

if (isset($_POST['login_btn'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['user_id'] = 1;
        $_SESSION['name'] = ADMIN_USERNAME;
        $_SESSION['role'] = 'admin';
        $_SESSION['success'] = "Welcome to Admin Dashboard, " . ADMIN_USERNAME;
        header("Location: /legalconnect/admin/dashboard.php");
        exit();
    } else {
        $error_msg = "Invalid Admin Username or Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
</head>
<body class="auth-bg">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8 col-sm-11">
            <div class="text-center mb-4">
                <a href="/legalconnect/index.php" class="text-white text-decoration-none fs-2 fw-bold">
                    <i class="fa-solid fa-scale-balanced text-warning me-2"></i>LEGAL <span class="text-warning">CONNECT</span>
                </a>
            </div>
            
            <div class="card card-auth p-4 p-md-5 bg-white">
                <h3 class="fw-bold mb-2 text-center text-dark">Admin Portal</h3>
                <p class="text-muted text-center small mb-4">Secure administrator sign-in</p>
                
                <?php if ($error_msg !== ""): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Admin Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user-gear"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required placeholder="Enter username">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-gold w-100 rounded-pill py-2 fw-bold text-dark fs-5 shadow-sm" name="login_btn">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In As Admin
                    </button>
                </form>
            </div>
            
            <div class="text-center mt-3">
                <a href="/legalconnect/login.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>Back to General Login</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
