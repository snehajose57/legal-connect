<?php
$page_title = "Register";
require_once __DIR__ . '/config/db.php';

// Fetch categories for Lawyer Specialization selector
$categories = [];
try {
    $stmt = $conn->query("SELECT category_name FROM categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fail silently
}

$error_msg = "";
$success_msg = "";

if (isset($_POST['register_btn'])) {
    $role = sanitize($_POST['role']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {
        $error_msg = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        if ($role === 'user') {
            // Check for duplicate email in users
            $checkUser = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $checkUser->execute([$email]);
            if ($checkUser->rowCount() > 0) {
                $error_msg = "Email is already registered as a client!";
            } else {
                // Upload Profile Image if any
                $profile_img_name = 'default_user.png';
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/uploads/profiles/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $profile_img_name = 'user_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $profile_img_name);
                }

                // Insert into users
                try {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, profile_image, password, status) VALUES (?, ?, ?, ?, ?, ?, 'Approved')");
                    $stmt->execute([$name, $email, $phone, $address, $profile_img_name, $hashed_password]);
                    $success_msg = "Account created successfully! You can now login.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        } elseif ($role === 'lawyer') {
            $qualification = sanitize($_POST['qualification']);
            $specialization = sanitize($_POST['specialization']);
            $experience = (int)$_POST['experience'];

            // Check for duplicate email in lawyers
            $checkLawyer = $conn->prepare("SELECT email FROM lawyers WHERE email = ?");
            $checkLawyer->execute([$email]);
            if ($checkLawyer->rowCount() > 0) {
                $error_msg = "Email is already registered as a lawyer!";
            } elseif (!isset($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
                $error_msg = "Lawyer license/certificate is required!";
            } else {
                // Create Upload folders if needed
                $profile_dir = __DIR__ . '/uploads/profiles/';
                $cert_dir = __DIR__ . '/uploads/certificates/';
                if (!is_dir($profile_dir)) mkdir($profile_dir, 0777, true);
                if (!is_dir($cert_dir)) mkdir($cert_dir, 0777, true);

                // Upload Profile Image
                $profile_img_name = 'default_lawyer.png';
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $profile_img_name = 'lawyer_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_dir . $profile_img_name);
                }

                // Upload Certificate
                $cert_ext = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
                $cert_name = 'cert_' . uniqid() . '.' . $cert_ext;
                move_uploaded_file($_FILES['certificate']['tmp_name'], $cert_dir . $cert_name);

                // Insert into lawyers
                try {
                    $stmt = $conn->prepare("INSERT INTO lawyers (name, email, phone, address, qualification, specialization, experience, certificate, profile_image, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt->execute([$name, $email, $phone, $address, $qualification, $specialization, $experience, $cert_name, $profile_img_name, $hashed_password]);
                    $success_msg = "Lawyer account created successfully! Approval pending from Admin.";
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=4" rel="stylesheet">
</head>
<body class="auth-bg py-5">

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9 col-sm-12">
            <div class="text-center mb-4">
                <a href="/legalconnect/index.php" class="text-white text-decoration-none fs-2 fw-bold">
                    <i class="fa-solid fa-scale-balanced text-warning me-2"></i>LEGAL <span class="text-warning">CONNECT</span>
                </a>
            </div>
            
            <div class="card card-auth p-4 p-md-5 bg-white">
                <h3 class="fw-bold mb-3 text-center text-dark">Register Account</h3>
                <p class="text-muted text-center mb-4">Select your profile type to fill registration forms</p>
                
                <?php if ($error_msg !== ""): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg !== ""): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?>
                        <a href="/legalconnect/login.php" class="alert-link">Login here</a>
                        <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Nav Tabs -->
                <ul class="nav nav-pills mb-4 nav-justified" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-user-tab" data-bs-toggle="pill" data-bs-target="#pills-user" type="button" role="tab" onclick="toggleRoleForm('user')">Register Client</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-lawyer-tab" data-bs-toggle="pill" data-bs-target="#pills-lawyer" type="button" role="tab" onclick="toggleRoleForm('lawyer')">Register Lawyer</button>
                    </li>
                </ul>

                <form method="POST" action="" enctype="multipart/form-data" id="registerForm" onsubmit="return lcRegisterSubmit(this)" novalidate>
                    <input type="hidden" name="role" id="roleRegisterInput" value="user">
                    
                    <!-- Common Fields -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" autocomplete="name" placeholder="John Doe">
                            <div class="lc-feedback" id="fb-name"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="text" class="form-control" id="email" name="email" autocomplete="email" placeholder="john@example.com">
                            <div class="lc-feedback" id="fb-email"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-bold">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" autocomplete="tel" placeholder="1234567890" maxlength="10" inputmode="numeric">
                            <div class="lc-feedback" id="fb-phone"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_image" class="form-label fw-bold">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-bold">Address Details</label>
                            <textarea class="form-control" id="address" name="address" rows="2" placeholder="123 Main Street, Area, City"></textarea>
                            <div class="lc-feedback" id="fb-address"></div>
                        </div>
                    </div>

                    <!-- Lawyer Specific Fields -->
                    <div id="lawyerSpecificFields" class="row g-3 mt-1" style="display: none;">
                        <hr class="my-4 text-muted">
                        <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Professional Details</h5>
                        <div class="col-md-6">
                            <label for="qualification" class="form-label fw-bold">Qualifications</label>
                            <input type="text" class="form-control lawyer-req" id="qualification" name="qualification" placeholder="e.g. LL.B., LL.M.">
                        </div>
                        <div class="col-md-6">
                            <label for="specialization" class="form-label fw-bold">Specialization</label>
                            <select class="form-select lawyer-req" id="specialization" name="specialization">
                                <option value="">Choose Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="experience" class="form-label fw-bold">Years of Experience</label>
                            <input type="number" class="form-control lawyer-req" id="experience" name="experience" min="0" placeholder="e.g. 5">
                        </div>
                        <div class="col-md-6">
                            <label for="certificate" class="form-label fw-bold">Lawyer License / Certificate Proof</label>
                            <input type="file" class="form-control lawyer-req" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text">Upload Bar council certificate or license.</div>
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div class="row g-3 mt-1">
                        <hr class="my-4 text-muted">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" placeholder="••••••••">
                            <div class="lc-feedback" id="fb-password"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" placeholder="••••••••">
                            <div class="lc-feedback" id="fb-confirm"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 rounded-pill py-3 fw-bold text-dark fs-5 mt-4 shadow-sm" name="register_btn">
                        <i class="fa-solid fa-user-plus me-2"></i>Sign Up
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <span class="text-muted small">Already have an account? <a href="/legalconnect/login.php" class="text-warning fw-bold text-decoration-none">Login Here</a></span>
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
<!-- Legal Connect Real-Time Validation -->
<script src="/legalconnect/assets/js/lc-validation.js"></script>
<script>
function toggleRoleForm(role) {
    document.getElementById('roleRegisterInput').value = role;
    const lawyerFields = document.getElementById('lawyerSpecificFields');
    const reqFields = document.querySelectorAll('.lawyer-req');
    
    if (role === 'lawyer') {
        lawyerFields.style.display = 'flex';
        reqFields.forEach(field => field.setAttribute('required', 'required'));
    } else {
        lawyerFields.style.display = 'none';
        reqFields.forEach(field => field.removeAttribute('required'));
    }
}
</script>
</body>
</html>
