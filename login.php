<?php
$page_title = "Login";
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') header("Location: /legalconnect/admin/dashboard.php");
    elseif ($_SESSION['role'] === 'lawyer') header("Location: /legalconnect/lawyer/dashboard.php");
    else header("Location: /legalconnect/user/dashboard.php");
    exit();
}

$error_msg = "";

if (isset($_POST['login_btn'])) {
    $role = sanitize($_POST['role']);
    $password = $_POST['password'];

    if ($role === 'lawyer') {
        $email = sanitize($_POST['identity']);
        $stmt = $conn->prepare("SELECT * FROM lawyers WHERE email = ?");
        $stmt->execute([$email]);
        $lawyer = $stmt->fetch();

        if ($lawyer && password_verify($password, $lawyer['password'])) {
            if ($lawyer['status'] === 'Pending') {
                $error_msg = "Your registration is pending administrator approval.";
            } elseif ($lawyer['status'] === 'Blocked' || $lawyer['status'] === 'Rejected') {
                $error_msg = "Your account has been " . strtolower($lawyer['status']) . " by admin.";
            } else {
                $_SESSION['user_id'] = $lawyer['lawyer_id'];
                $_SESSION['name'] = $lawyer['name'];
                $_SESSION['email'] = $lawyer['email'];
                $_SESSION['role'] = 'lawyer';
                $_SESSION['profile_image'] = $lawyer['profile_image'];
                $_SESSION['success'] = "Welcome back, Advocate " . $lawyer['name'];
                header("Location: /legalconnect/lawyer/dashboard.php");
                exit();
            }
        } else {
            $error_msg = "Invalid Lawyer Email or Password.";
        }
    } elseif ($role === 'user') {
        $email = sanitize($_POST['identity']);
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'Blocked' || $user['status'] === 'Rejected') {
                $error_msg = "Your account has been " . strtolower($user['status']) . " by admin.";
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'user';
                $_SESSION['profile_image'] = $user['profile_image'];
                $_SESSION['success'] = "Welcome back, " . $user['name'];
                header("Location: /legalconnect/user/dashboard.php");
                exit();
            }
        } else {
            $error_msg = "Invalid User Email or Password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Legal Connect</title>
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
                <h3 class="fw-bold mb-4 text-center text-dark">Access Portal</h3>
                
                <?php if ($error_msg !== ""): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?> 
                        <br><small class="text-muted">Debug - Submitted Role: <strong><?php echo htmlspecialchars($_POST['role'] ?? 'none'); ?></strong> | Identity: <strong><?php echo htmlspecialchars($_POST['identity'] ?? 'none'); ?></strong></small>
                        <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Nav Tabs -->
                <ul class="nav nav-pills mb-4 nav-justified" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-user-tab" data-bs-toggle="pill" data-bs-target="#pills-user" type="button" role="tab" aria-selected="true" onclick="setRole('user', 'Email Address')">Client</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-lawyer-tab" data-bs-toggle="pill" data-bs-target="#pills-lawyer" type="button" role="tab" aria-selected="false" onclick="setRole('lawyer', 'Email Address')">Lawyer</button>
                    </li>
                </ul>

                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="role" id="roleInput" value="user">
                    
                    <div class="mb-3">
                        <label for="identity" class="form-label fw-bold" id="identityLabel">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user" id="identityIcon"></i></span>
                            <input type="text" class="form-control" id="identity" name="identity" required placeholder="Enter login credential">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <a href="/legalconnect/forgot_password.php" class="text-warning small fw-bold text-decoration-none">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-gold w-100 rounded-pill py-2 fw-bold text-dark fs-5 shadow-sm" name="login_btn">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <span class="text-muted small" id="registerFooter">Don't have an account? <a href="/legalconnect/register.php" class="text-warning fw-bold text-decoration-none">Register Here</a></span>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="/legalconnect/index.php" class="text-white-50 text-decoration-none small"><i class="fa-solid fa-arrow-left me-1"></i>Back to Homepage</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setRole(role, labelText) {
    document.getElementById('roleInput').value = role;
    document.getElementById('identityLabel').innerText = labelText;
    const icon = document.getElementById('identityIcon');
    const footer = document.getElementById('registerFooter');
    
    icon.className = "fa-solid fa-envelope";
    footer.style.display = "inline";
}
</script>
</body>
</html>
