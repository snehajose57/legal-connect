<?php
$page_title = "Forgot Password";
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
$simulated_link = "";

if (isset($_POST['forgot_btn'])) {
    $role = sanitize($_POST['role']);
    $email = sanitize($_POST['email']);

    try {
        if ($role === 'lawyer') {
            $stmt = $conn->prepare("SELECT * FROM lawyers WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        }
        $stmt->execute([$email]);
        $account = $stmt->fetch();

        if ($account) {
            // Generate token and expiry
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Update database
            if ($role === 'lawyer') {
                $upd = $conn->prepare("UPDATE lawyers SET reset_token = ?, reset_token_expire = ? WHERE lawyer_id = ?");
                $upd->execute([$token, $expiry, $account['lawyer_id']]);
            } else {
                $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE user_id = ?");
                $upd->execute([$token, $expiry, $account['user_id']]);
            }

            // Generate link
            $reset_link = "http://localhost/legalconnect/reset_password.php?token=" . $token . "&role=" . $role;
            $simulated_link = $reset_link;

            // Log to local file
            $log_dir = __DIR__ . '/logs/';
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0777, true);
            }
            $log_msg = "[" . date('Y-m-d H:i:s') . "] Role: $role | Email: $email | Link: $reset_link\n";
            file_put_contents($log_dir . 'password_resets.log', $log_msg, FILE_APPEND);

            $success_msg = "Password reset instructions have been generated successfully!";
        } else {
            $error_msg = "No account found with that email address under the selected role.";
        }
    } catch (Exception $e) {
        $error_msg = "Error processing request: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Legal Connect</title>
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
                <h3 class="fw-bold mb-3 text-center text-dark">Reset Password</h3>
                <p class="text-muted text-center mb-4">Enter your registered email below to receive a password reset link.</p>
                
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

                    <?php if (!empty($simulated_link)): ?>
                        <div class="border rounded-3 p-3 bg-light mb-4">
                            <div class="fw-bold text-dark small mb-2"><i class="fa-solid fa-envelope-open-text text-warning me-1"></i>Mock Email Inbox (Development Mode)</div>
                            <p class="text-muted small mb-2">Since we are on a local environment, the email output is captured below and saved in <code>logs/password_resets.log</code>.</p>
                            <a href="<?php echo htmlspecialchars($simulated_link); ?>" class="btn btn-warning btn-sm fw-bold text-dark w-100 rounded-pill mt-2">
                                <i class="fa-solid fa-key me-1"></i>Follow Reset Link
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($success_msg === ""): ?>
                    <form method="POST" action="">
                        <!-- Role Selector -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">I am a:</label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleUser" value="user" checked>
                                    <label class="form-check-label" for="roleUser">Client</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleLawyer" value="lawyer">
                                    <label class="form-check-label" for="roleLawyer">Lawyer</label>
                                </div>
                            </div>
                        </div>

                        <!-- Email Input -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your registered email">
                            </div>
                        </div>

                        <button type="submit" name="forgot_btn" class="btn btn-gold w-100 rounded-pill py-2 fw-bold text-dark fs-5 shadow-sm">
                            <i class="fa-solid fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="/legalconnect/login.php" class="text-warning fw-bold text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
