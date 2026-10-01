<?php
$page_title = "Manage Users";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg = "";
$success_msg = "";

// Handle User Actions (Approve, Reject, Block, Unblock, Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $user_id = (int)$_GET['id'];
    
    try {
        if ($action === 'block') {
            $stmt = $conn->prepare("UPDATE users SET status = 'Blocked' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Client blocked successfully.";
        } elseif ($action === 'unblock') {
            $stmt = $conn->prepare("UPDATE users SET status = 'Approved' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Client account restored successfully.";
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Client deleted from system database.";
        }
        
        header("Location: /legalconnect/admin/users.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Operation failed: " . $e->getMessage();
    }
}

// Search Filter
$search_query = "";
$params = [];
$sql = "SELECT * FROM users";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = sanitize($_GET['search']);
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $params = ["%$search_query%", "%$search_query%", "%$search_query%"];
}

$sql .= " ORDER BY created_at DESC";

// Fetch Clients
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error listing users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Client Users | Legal Connect</title>
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
            <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Admin Panel</h4>
        </div>
        <nav class="nav flex-column py-3">
            <a class="nav-link" href="/legalconnect/admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
            <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
            <a class="nav-link" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
            <a class="nav-link active" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients</a>
            <a class="nav-link" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
            <a class="nav-link" href="/legalconnect/admin/monitoring.php"><i class="fa-solid fa-chart-line"></i> Monitoring &amp; Audit</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Manage Client Users</h2>
                <p class="text-muted mb-0">Monitor registered user profiles, search, approve or block clients.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
                <div class="avatar-circle">A</div>
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

        <!-- Search Bar Card -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-9 col-sm-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" name="search" placeholder="Search clients by name, email, or phone number..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gold w-100"><i class="fa-solid fa-filter me-2"></i>Search</button>
                            <?php if (!empty($search_query)): ?>
                                <a href="/legalconnect/admin/users.php" class="btn btn-outline-secondary" title="Clear Search"><i class="fa-solid fa-rotate-left"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table Card -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-address-book text-warning me-2"></i>Clients Directory</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Reg Date</th>
                            <th class="text-center">Action Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['profile_image'] && $user['profile_image'] !== 'default_user.png'): ?>
                                            <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar-circle"><?php echo substr($user['name'], 0, 1); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;"><?php echo htmlspecialchars($user['address']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <span class="badge badge-status <?php 
                                            if ($user['status'] === 'Pending') echo 'badge-pending';
                                            elseif ($user['status'] === 'Approved') echo 'badge-approved';
                                            elseif ($user['status'] === 'Blocked') echo 'badge-blocked';
                                            else echo 'badge-rejected';
                                        ?>"><?php echo $user['status']; ?></span>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('d M, Y', strtotime($user['created_at'])); ?></small></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <?php if ($user['status'] === 'Blocked'): ?>
                                                <a href="/legalconnect/admin/users.php?action=unblock&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-success" title="Unblock Client">
                                                    <i class="fa-solid fa-circle-check"></i> Unblock
                                                </a>
                                            <?php else: ?>
                                                <a href="/legalconnect/admin/users.php?action=block&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger" title="Block Client">
                                                    <i class="fa-solid fa-ban"></i> Block
                                                </a>
                                            <?php endif; ?>
                                            <a href="/legalconnect/admin/users.php?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" title="Delete Client" onclick="return confirm('Are you sure you want to delete this user profile?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No client records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
