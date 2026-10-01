<?php
$page_title = "Reset Password";
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') header("Location: /legalconnect/admin/dashboard.php");
    elseif ($_SESSION['role'] === 'lawyer') header("Location: /legalconnect/lawyer/dashboard.php");
    else header("Location: /legalconnect/user/dashboard.php");
    exit();
}

$error_msg = "";
$success_msg = "";
$valid_token = false;

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$role = isset($_GET['role']) ? sanitize($_GET['role']) : '';

$account = null;

if (!empty($token) && !empty($role)) {
    try {
        $now = date('Y-m-d H:i:s');
        if ($role === 'lawyer') {
            $stmt = $conn->prepare("SELECT * FROM lawyers WHERE reset_token = ? AND reset_token_expire >= ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expire >= ?");
        }
        $stmt->execute([$token, $now]);
        $account = $stmt->fetch();

        if ($account) {
            $valid_token = true;
        } else {
            $error_msg = "Invalid or expired password reset token.";
        }
    } catch (Exception $e) {
        $error_msg = "Database verification error: " . $e->getMessage();
    }
} else {
    $error_msg = "Missing password reset parameters.";
}

// Handle submission
if ($valid_token && isset($_POST['reset_btn'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_msg = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            if ($role === 'lawyer') {
                $upd = $conn->prepare("UPDATE lawyers SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE lawyer_id = ?");
                $upd->execute([$hashed, $account['lawyer_id']]);
            } else {
                $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE user_id = ?");
                $upd->execute([$hashed, $account['user_id']]);
            }

            $success_msg = "Your password has been successfully reset! You can now log in.";
            $valid_token = false; // Hide form
        } catch (Exception $e) {
            $error_msg = "Error updating password: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
</head>
<body class="auth-bg">

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8 col-sm-11">
            <div class="text-center mb-4">
                <a href="/legalconnect/index.php" class="text-white text-decoration-none fs-2 fw-bold">
                    <i class="fa-solid fa-scale-balanced text-warning me-2"></i>LEGAL <span class="text-warning">CONNECT</span>
                </a>
            </div>
            
            <div class="card card-auth p-4 p-md-5 bg-white">
                <h3 class="fw-bold mb-3 text-center text-dark">Create New Password</h3>
                <p class="text-muted text-center mb-4">Please type and confirm your new secure account credentials below.</p>
                
                <?php if ($error_msg !== ""): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg !== ""): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?>
                    </div>
                    <a href="/legalconnect/login.php" class="btn btn-gold w-100 rounded-pill py-2 fw-bold text-dark fs-5 shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Log In Now
                    </a>
                <?php endif; ?>

                <?php if ($valid_token): ?>
                    <form method="POST" action="">
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="At least 6 characters">
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Must match exactly">
                            </div>
                        </div>

                        <button type="submit" name="reset_btn" class="btn btn-gold w-100 rounded-pill py-2 fw-bold text-dark fs-5 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (!$valid_token && $success_msg === ""): ?>
                    <div class="text-center mt-3">
                        <a href="/legalconnect/forgot_password.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                            <i class="fa-solid fa-rotate-left me-1"></i>Request New Link
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
