<?php
$page_title = "Manage Profile";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Handle Profile Updates
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Fetch current user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current = $stmt->fetch();

        // 1. Password check
        $hashed_password = $current['password'];
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match!");
            } elseif (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        }

        // 2. Profile picture check
        $profile_img_name = $current['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $profile_img_name = 'user_' . uniqid() . '.' . $ext;
            
            // Delete old file if it wasn't default
            if ($current['profile_image'] !== 'default_user.png' && file_exists($upload_dir . $current['profile_image'])) {
                unlink($upload_dir . $current['profile_image']);
            }
            
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $profile_img_name);
        }

        // 3. Update Database
        $update = $conn->prepare("UPDATE users 
                                  SET name = ?, phone = ?, address = ?, profile_image = ?, password = ? 
                                  WHERE user_id = ?");
        $update->execute([$name, $phone, $address, $profile_img_name, $hashed_password, $user_id]);

        // Update session name
        $_SESSION['name'] = $name;
        $_SESSION['profile_image'] = $profile_img_name;
        $_SESSION['success'] = "Profile details updated successfully!";
        header("Location: /legalconnect/user/profile.php");
        exit();

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Fetch current user details
$user = null;
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error_msg = "Error reading profile: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Client Portal</h4>
        </div>
        <nav class="nav flex-column py-3">
            <a class="nav-link" href="/legalconnect/user/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/user/lawyers.php"><i class="fa-solid fa-user-tie"></i> Search Lawyers</a>
            <a class="nav-link" href="/legalconnect/user/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
            <a class="nav-link" href="/legalconnect/user/questions.php"><i class="fa-solid fa-circle-question"></i> Legal Q&amp;A</a>
            <a class="nav-link" href="/legalconnect/user/chat.php"><i class="fa-solid fa-comments"></i> Chat Support</a>
            <a class="nav-link" href="/legalconnect/user/reports.php"><i class="fa-solid fa-chart-pie"></i> Client Reports</a>
            <a class="nav-link" href="/legalconnect/user/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link active" href="/legalconnect/user/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Settings</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Profile Management</h2>
                <p class="text-muted mb-0">Update your contact information, credentials, and password.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Client Account</span>
                <?php if (isset($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default_user.png'): ?>
                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-2 shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-circle"><?php echo substr($_SESSION['name'], 0, 1); ?></div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Form Card -->
        <?php if ($user): ?>
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Left avatar side -->
                        <div class="col-lg-3 text-center border-end border-light">
                            <div class="mb-3">
                                <?php if ($user['profile_image'] && $user['profile_image'] !== 'default_user.png'): ?>
                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-3 shadow" style="width: 140px; height: 140px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle avatar-large mx-auto bg-primary text-white mb-2"><?php echo substr($user['name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($user['name']); ?></h5>
                            <span class="badge bg-secondary text-uppercase mt-1">Client Profile</span>
                            
                            <div class="mt-4 text-start">
                                <label for="profile_image" class="form-label fw-bold text-muted small">Update Profile Image</label>
                                <input class="form-control form-control-sm" type="file" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                        </div>

                        <!-- Right details inputs -->
                        <div class="col-lg-9">
                            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-address-card text-warning me-2"></i>Account Details</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-bold">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-bold">Email Address</label>
                                    <input type="email" class="form-control bg-light" id="email" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
                                    <div class="form-text text-muted">Email cannot be changed after registration.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label fw-bold">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" required value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label fw-bold">Address Details</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                            </div>

                            <h5 class="fw-bold text-dark mt-5 mb-4"><i class="fa-solid fa-lock text-warning me-2"></i>Security Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-bold">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label fw-bold">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current">
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" name="update_profile" class="btn btn-gold px-4">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>Update Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
