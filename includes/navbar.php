<?php
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
$unread_count = 0;
if ($role) {
    try {
        $n_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? AND ? = 'user' AND is_read = 0) OR (lawyer_id = ? AND ? = 'lawyer' AND is_read = 0) OR (is_admin = 1 AND ? = 'admin' AND is_read = 0)");
        $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $n_stmt->execute([$uid, $role, $uid, $role, $role]);
        $unread_count = $n_stmt->fetchColumn();
    } catch (PDOException $e) {}
}
?>
<nav class="navbar navbar-expand-lg navbar-dark-custom navbar-dark">
    <div class="container">
        <a class="navbar-brand fs-4" href="/legalconnect/index.php">
            <i class="fa-solid fa-scale-balanced text-warning me-2"></i>LEGAL <span>CONNECT</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Common Guest Links -->
                <?php if (!$role): ?>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/index.php">Home</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/about.php">About Us</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/contact.php">Contact Us</a></li>
                <?php endif; ?>

                <!-- Admin specific links -->
                <?php if ($role === 'admin'): ?>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/dashboard.php">Dashboard</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/categories.php">Categories</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/ipc.php">IPC Sections</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/legal_information.php">Legal Information</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/users.php">Manage Users</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/lawyers.php">Manage Lawyers</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/appointments.php">Appointments</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/monitoring.php">System Monitoring</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/admin/notifications.php">Notifications <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                <?php endif; ?>

                <!-- Lawyer specific links -->
                <?php if ($role === 'lawyer'): ?>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/dashboard.php">Dashboard</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/requests.php">Client Requests</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/support.php">Legal Support Q&A</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/chat.php">Client Chat</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/profile.php">Profile Management</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/lawyer/notifications.php">Notifications <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                <?php endif; ?>

                <!-- User/Client specific links -->
                <?php if ($role === 'user'): ?>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/dashboard.php">Dashboard</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/lawyers.php">Search Lawyers</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/legal_information.php">Legal Information</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/questions.php">Ask Legal Q&A</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/chat.php">Chat Support</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/reports.php">Client Reports</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/profile.php">Profile Management</a></li>
                    <li class="nav-link-item"><a class="nav-link" href="/legalconnect/user/notifications.php">Notifications <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <?php if ($role): ?>
                    <!-- Logged In User Options -->
                    <span class="text-white me-2">
                        <i class="fa-solid fa-circle-user text-warning me-1"></i>
                        Welcome, <strong><?php echo htmlspecialchars($name); ?></strong> 
                        <span class="badge bg-secondary text-uppercase ms-1" style="font-size: 0.7rem;"><?php echo $role; ?></span>
                    </span>
                    <a href="/legalconnect/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <!-- Guest Options -->
                    <a href="/legalconnect/login.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-right-to-bracket me-1"></i>Login
                    </a>
                    <a href="/legalconnect/register.php" class="btn btn-warning btn-sm rounded-pill px-3 text-dark">
                        <i class="fa-solid fa-user-plus me-1"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
