<?php
$page_title = "Notifications";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "All notifications marked as read.";
        header("Location: /legalconnect/user/notifications.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error updating notifications: " . $e->getMessage();
    }
}

// Mark single as read if requested
if (isset($_GET['read_id']) && !empty($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$read_id, $user_id]);
        header("Location: /legalconnect/user/notifications.php");
        exit();
    } catch (PDOException $e) {}
}

// Fetch all notifications for the user
$notifications = [];
try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error loading notifications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/user/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link" href="/legalconnect/user/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Settings</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Notifications</h2>
                <p class="text-muted mb-0">System updates, status progression logs, hearings schedules, and notices.</p>
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
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-bell text-warning me-2"></i>Inbox Logs</h5>
                <?php if (count(array_filter($notifications, function($n) { return $n['is_read'] == 0; })) > 0): ?>
                    <form method="POST" action="">
                        <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-warning rounded-pill">
                            <i class="fa-solid fa-circle-check me-1"></i>Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="list-group list-group-flush">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): 
                        // Style based on notification type/title
                        $icon = 'fa-bell';
                        $color = 'text-warning';
                        $title = strtolower($notif['title']);
                        
                        if (strpos($title, 'register') !== false) {
                            $icon = 'fa-folder-plus';
                            $color = 'text-success';
                        } elseif (strpos($title, 'accept') !== false) {
                            $icon = 'fa-handshake';
                            $color = 'text-primary';
                        } elseif (strpos($title, 'reject') !== false) {
                            $icon = 'fa-ban';
                            $color = 'text-danger';
                        } elseif (strpos($title, 'request') !== false) {
                            $icon = 'fa-file-signature';
                            $color = 'text-warning';
                        } elseif (strpos($title, 'upload') !== false) {
                            $icon = 'fa-file-upload';
                            $color = 'text-success';
                        } elseif (strpos($title, 'hearing') !== false) {
                            $icon = 'fa-gavel';
                            $color = 'text-info';
                        } elseif (strpos($title, 'closed') !== false) {
                            $icon = 'fa-circle-check';
                            $color = 'text-secondary';
                        }
                    ?>
                        <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-start <?php echo $notif['is_read'] == 0 ? 'bg-light border-start border-warning border-3 ps-3' : ''; ?>">
                            <div class="d-flex gap-3">
                                <span class="fs-4 <?php echo $color; ?>"><i class="fa-solid <?php echo $icon; ?>"></i></span>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1 small"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                    <p class="text-muted mb-1 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php if ($notif['is_read'] == 0): ?>
                                <a href="/legalconnect/user/notifications.php?read_id=<?php echo $notif['notification_id']; ?>" class="btn btn-xs btn-outline-secondary py-0 px-2 small" style="font-size: 0.65rem;" title="Mark as read">
                                    <i class="fa-solid fa-check"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-regular fa-bell-slash fs-1 mb-3"></i>
                        <p class="mb-0">No notifications in your logs yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
