<?php
$page_title = "Lawyer Dashboard";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('lawyer');

$lawyer_id = $_SESSION['user_id'];
$error_msg = "";

try {
    // Gather statistics
    $total_bookings = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ?");
    $total_bookings->execute([$lawyer_id]);
    $count_bookings = $total_bookings->fetchColumn();

    $upcoming_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ? AND status = 'Approved' AND appointment_date >= CURDATE()");
    $upcoming_stmt->execute([$lawyer_id]);
    $count_upcoming = $upcoming_stmt->fetchColumn();

    $questions_stmt = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id = ?");
    $questions_stmt->execute([$lawyer_id]);
    $count_questions = $questions_stmt->fetchColumn();

    $rating_stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0.0) as avg_rating, COUNT(feedback_id) as total_feedback FROM feedbacks WHERE lawyer_id = ?");
    $rating_stmt->execute([$lawyer_id]);
    $rating_data = $rating_stmt->fetch();
    $avg_rating = $rating_data['avg_rating'];
    $feedback_count = $rating_data['total_feedback'];

    // Fetch upcoming appointments
    $appointments_stmt = $conn->prepare("SELECT a.*, u.name as client_name, u.phone as client_phone FROM appointments a JOIN users u ON a.user_id = u.user_id WHERE a.lawyer_id = ? AND a.status = 'Approved' AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 5");
    $appointments_stmt->execute([$lawyer_id]);
    $upcoming_appointments = $appointments_stmt->fetchAll();

    // Fetch pending requests
    $requests_stmt = $conn->prepare("SELECT a.*, u.name as client_name FROM appointments a JOIN users u ON a.user_id = u.user_id WHERE a.lawyer_id = ? AND a.status = 'Pending' ORDER BY a.created_at DESC LIMIT 5");
    $requests_stmt->execute([$lawyer_id]);
    $pending_requests = $requests_stmt->fetchAll();
    
    // Fetch count of pending requests
    $pending_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_count->execute([$lawyer_id]);
    $count_pending = $pending_count->fetchColumn();

    // Fetch count of pending Q&A
    $pending_qa = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_qa->execute([$lawyer_id]);
    $count_pending_qa = $pending_qa->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading dashboard metrics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lawyer Dashboard | Legal Connect</title>
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
            <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Lawyer Portal</h4>
        </div>
        <nav class="nav flex-column py-3">
            <a class="nav-link active" href="/legalconnect/lawyer/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/lawyer/requests.php"><i class="fa-solid fa-calendar-days"></i> Client Requests <span class="badge bg-danger ms-auto"><?php echo $count_pending; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/support.php"><i class="fa-solid fa-circle-question"></i> Support Q&A <span class="badge bg-danger ms-auto"><?php echo $count_pending_qa; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/chat.php"><i class="fa-solid fa-comments"></i> Client Chat</a>
            <a class="nav-link" href="/legalconnect/lawyer/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link" href="/legalconnect/lawyer/reports.php"><i class="fa-solid fa-chart-pie"></i> My Reports</a>
            <a class="nav-link" href="/legalconnect/lawyer/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Details</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Advocate Dashboard</h2>
                <p class="text-muted mb-0">Track schedules, respond to client consultations, and reply to legal questions.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Advocate: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <?php if (isset($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default_lawyer.png'): ?>
                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-2 shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-circle bg-warning text-dark"><?php echo substr($_SESSION['name'], 0, 1); ?></div>
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

        <!-- Stats Cards Grid -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Client Requests</h6>
                            <h2 class="fw-bold mb-0"><?php echo $count_bookings; ?></h2>
                        </div>
                        <i class="fa-solid fa-folder-open stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-success text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Upcoming Consults</h6>
                            <h2 class="fw-bold mb-0"><?php echo $count_upcoming; ?></h2>
                        </div>
                        <i class="fa-solid fa-calendar-check stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-info text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold mb-1">Total Q&A Queries</h6>
                            <h2 class="fw-bold mb-0"><?php echo $count_questions; ?></h2>
                        </div>
                        <i class="fa-solid fa-clipboard-question stats-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card bg-warning text-dark p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 text-uppercase fw-bold mb-1">Advocate Rating</h6>
                            <h2 class="fw-bold mb-0">
                                <?php echo number_format($avg_rating, 1); ?>
                                <small class="fs-6 fw-normal text-muted">(<?php echo $feedback_count; ?> reviews)</small>
                            </h2>
                        </div>
                        <i class="fa-solid fa-star stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Side: Upcoming Appointments list -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-calendar-check text-success me-2"></i>Upcoming Consultations</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client Name</th>
                                    <th>Scheduled Date</th>
                                    <th>Time Slot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($upcoming_appointments)): ?>
                                    <?php foreach ($upcoming_appointments as $appt): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($appt['client_name']); ?></div>
                                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($appt['client_phone']); ?></small>
                                            </td>
                                            <td><?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fa-solid fa-clock text-warning me-1"></i>
                                                    <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No upcoming consultations. Go to Client Requests to accept new bookings.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Pending Requests quick look -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-folder-open text-warning me-2"></i>Pending Booking Requests</h5>
                        <a href="/legalconnect/lawyer/requests.php" class="btn btn-sm btn-outline-warning rounded-pill">Manage</a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (!empty($pending_requests)): ?>
                            <?php foreach ($pending_requests as $req): ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($req['client_name']); ?></div>
                                            <small class="text-muted"><i class="fa-solid fa-calendar me-1"></i><?php echo date('d M, Y', strtotime($req['appointment_date'])); ?></small>
                                        </div>
                                        <span class="badge badge-status badge-pending">Pending Approval</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-calendar-xmark fs-2 text-muted mb-2"></i>
                                <div>No pending booking requests.</div>
                            </div>
                        <?php endif; ?>
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
