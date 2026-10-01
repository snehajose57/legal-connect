<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

// Gather Stats
try {
    $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers")->fetchColumn();
    $total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $total_categories = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $total_ipc = $conn->query("SELECT COUNT(*) FROM ipc_sections")->fetchColumn();
    $total_questions = $conn->query("SELECT COUNT(*) FROM legal_questions")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    
    // Fetch recent lawyer registrations
    $recent_lawyers = $conn->query("SELECT * FROM lawyers ORDER BY created_at DESC LIMIT 5")->fetchAll();
    // Fetch recent appointments
    $recent_appointments = $conn->query("SELECT a.*, u.name as client_name, l.name as lawyer_name FROM appointments a JOIN users u ON a.user_id = u.user_id JOIN lawyers l ON a.lawyer_id = l.lawyer_id ORDER BY a.created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    die("Error fetching dashboard statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
            <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
            <a class="nav-link" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
            <a class="nav-link" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients</a>
            <a class="nav-link" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
            <a class="nav-link" href="/legalconnect/admin/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link" href="/legalconnect/admin/reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
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
                <h2 class="fw-bold text-dark mb-1">Control Dashboard</h2>
                <p class="text-muted mb-0">Overview of system operations, pending approvals, and active logs.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
                <div class="avatar-circle">A</div>
            </div>
        </header>

        <!-- Dynamic Success Message from Session -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards Grid -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Users</h6>
                            <h2 class="fw-bold mb-0"><?php echo $total_users; ?></h2>
                        </div>
                        <i class="fa-solid fa-users stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-success text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Lawyers</h6>
                            <h2 class="fw-bold mb-0"><?php echo $total_lawyers; ?></h2>
                        </div>
                        <i class="fa-solid fa-user-tie stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-warning text-dark p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 text-uppercase fw-bold mb-1">Total Bookings</h6>
                            <h2 class="fw-bold mb-0"><?php echo $total_appointments; ?></h2>
                        </div>
                        <i class="fa-solid fa-calendar-check stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-info text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Legal Queries</h6>
                            <h2 class="fw-bold mb-0"><?php echo $total_questions; ?></h2>
                        </div>
                        <i class="fa-solid fa-circle-question stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 p-3 bg-white text-center">
                    <h6 class="text-muted fw-bold mb-1">Pending Lawyers</h6>
                    <h3 class="fw-bold text-danger mb-0"><?php echo $pending_lawyers; ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 p-3 bg-white text-center">
                    <h6 class="text-muted fw-bold mb-1">Practice Sectors</h6>
                    <h3 class="fw-bold text-primary mb-0"><?php echo $total_categories; ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 p-3 bg-white text-center">
                    <h6 class="text-muted fw-bold mb-1">IPC Code List</h6>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_ipc; ?></h3>
                </div>
            </div>
        </div>

        <!-- Recent Activities Lists -->
        <div class="row g-4">
            <!-- Recent Lawyers pending approval -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-plus text-warning me-2"></i>New Registrations</h5>
                        <a href="/legalconnect/admin/lawyers.php" class="btn btn-sm btn-outline-warning rounded-pill">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_lawyers)): ?>
                                    <?php foreach ($recent_lawyers as $lawyer): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($lawyer['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($lawyer['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($lawyer['specialization']); ?></td>
                                            <td>
                                                <span class="badge badge-status <?php 
                                                    if ($lawyer['status'] === 'Pending') echo 'badge-pending';
                                                    elseif ($lawyer['status'] === 'Approved') echo 'badge-approved';
                                                    else echo 'badge-cancelled';
                                                ?>"><?php echo $lawyer['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No lawyer accounts registered yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments activity -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Recent Appointments</h5>
                        <a href="/legalconnect/admin/appointments.php" class="btn btn-sm btn-outline-warning rounded-pill">Monitor</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client / Lawyer</th>
                                    <th>Date/Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_appointments)): ?>
                                    <?php foreach ($recent_appointments as $appt): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Client: <?php echo htmlspecialchars($appt['client_name']); ?></div>
                                                <div class="text-muted small">Adv: <?php echo htmlspecialchars($appt['lawyer_name']); ?></div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-status <?php 
                                                    if ($appt['status'] === 'Pending') echo 'badge-pending';
                                                    elseif ($appt['status'] === 'Accepted') echo 'badge-accepted';
                                                    elseif ($appt['status'] === 'Approved') echo 'badge-approved';
                                                    elseif ($appt['status'] === 'Completed') echo 'badge-completed';
                                                    else echo 'badge-cancelled';
                                                ?>"><?php echo $appt['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No appointments booked yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
