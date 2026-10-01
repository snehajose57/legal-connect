<?php
$page_title = "Client Reports";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id   = $_SESSION['user_id'];
$error_msg = "";

// Filter inputs
$filter_lawyer = isset($_GET['lawyer_id']) ? (int)$_GET['lawyer_id'] : 0;
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date   = isset($_GET['date_range']) ? sanitize($_GET['date_range']) : '';

try {
    // ── SUMMARY METRICS (Top Cards) ─────────────────────────────────────────
    $total_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
    $count_total = $total_stmt->fetchColumn();

    $completed_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status = 'Completed'");
    $completed_stmt->execute([$user_id]);
    $count_completed = $completed_stmt->fetchColumn();

    $cancelled_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status LIKE 'Cancelled%'");
    $cancelled_stmt->execute([$user_id]);
    $count_cancelled = $cancelled_stmt->fetchColumn();

    $spent_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = ? AND payment_status = 'Completed'");
    $spent_stmt->execute([$user_id]);
    $total_payments = $spent_stmt->fetchColumn();

    // ── LAWYERS LIST FOR FILTER ─────────────────────────────────────────────
    $lawyers_stmt = $conn->prepare("
        SELECT DISTINCT l.lawyer_id, l.name, l.specialization 
        FROM lawyers l 
        JOIN appointments a ON l.lawyer_id = a.lawyer_id 
        WHERE a.user_id = ? ORDER BY l.name ASC");
    $lawyers_stmt->execute([$user_id]);
    $my_lawyers = $lawyers_stmt->fetchAll();

    // ── 1. APPOINTMENT REPORT ───────────────────────────────────────────────
    $appt_sql = "SELECT a.*, l.name as lawyer_name, l.specialization,
                        p.payment_id, p.amount, p.payment_method, p.payment_status, p.transaction_id
                 FROM appointments a
                 JOIN lawyers l ON a.lawyer_id = l.lawyer_id
                 LEFT JOIN payments p ON a.appointment_id = p.appointment_id
                 WHERE a.user_id = ?";
    $appt_params = [$user_id];

    if ($filter_lawyer > 0) {
        $appt_sql .= " AND a.lawyer_id = ?";
        $appt_params[] = $filter_lawyer;
    }
    if ($filter_status !== '') {
        $appt_sql .= " AND a.status = ?";
        $appt_params[] = $filter_status;
    }
    if ($filter_search !== '') {
        $appt_sql .= " AND (l.name LIKE ? OR a.status LIKE ?)";
        $term = '%' . $filter_search . '%';
        $appt_params[] = $term;
        $appt_params[] = $term;
    }
    $appt_sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $appt_stmt = $conn->prepare($appt_sql);
    $appt_stmt->execute($appt_params);
    $appointment_report = $appt_stmt->fetchAll();

    // ── 2. PAYMENT REPORT ───────────────────────────────────────────────────
    $pay_sql = "SELECT p.*, l.name as lawyer_name, l.specialization, a.appointment_date
                FROM payments p
                JOIN lawyers l ON p.lawyer_id = l.lawyer_id
                JOIN appointments a ON p.appointment_id = a.appointment_id
                WHERE p.user_id = ?";
    $pay_params = [$user_id];

    if ($filter_lawyer > 0) {
        $pay_sql .= " AND p.lawyer_id = ?";
        $pay_params[] = $filter_lawyer;
    }
    if ($filter_search !== '') {
        $pay_sql .= " AND (l.name LIKE ? OR p.transaction_id LIKE ? OR p.payment_method LIKE ?)";
        $term = '%' . $filter_search . '%';
        $pay_params[] = $term;
        $pay_params[] = $term;
        $pay_params[] = $term;
    }
    $pay_sql .= " ORDER BY p.payment_date DESC";
    $pay_stmt = $conn->prepare($pay_sql);
    $pay_stmt->execute($pay_params);
    $payment_report = $pay_stmt->fetchAll();

    // ── 3. LEGAL Q&A REPORT ─────────────────────────────────────────────────
    $qa_sql = "SELECT q.*, l.name as lawyer_name, l.specialization
               FROM legal_questions q
               JOIN lawyers l ON q.lawyer_id = l.lawyer_id
               WHERE q.user_id = ?";
    $qa_params = [$user_id];

    if ($filter_lawyer > 0) {
        $qa_sql .= " AND q.lawyer_id = ?";
        $qa_params[] = $filter_lawyer;
    }
    if ($filter_search !== '') {
        $qa_sql .= " AND (q.subject LIKE ? OR q.question LIKE ? OR l.name LIKE ?)";
        $term = '%' . $filter_search . '%';
        $qa_params[] = $term;
        $qa_params[] = $term;
        $qa_params[] = $term;
    }
    $qa_sql .= " ORDER BY q.created_at DESC";
    $qa_stmt = $conn->prepare($qa_sql);
    $qa_stmt->execute($qa_params);
    $qa_report = $qa_stmt->fetchAll();

    // ── 4. FEEDBACK & RATING REPORT ─────────────────────────────────────────
    $fb_sql = "SELECT f.*, l.name as lawyer_name, l.specialization
               FROM feedbacks f
               JOIN lawyers l ON f.lawyer_id = l.lawyer_id
               WHERE f.user_id = ?";
    $fb_params = [$user_id];

    if ($filter_lawyer > 0) {
        $fb_sql .= " AND f.lawyer_id = ?";
        $fb_params[] = $filter_lawyer;
    }
    $fb_sql .= " ORDER BY f.created_at DESC";
    $fb_stmt = $conn->prepare($fb_sql);
    $fb_stmt->execute($fb_params);
    $feedback_report = $fb_stmt->fetchAll();

    // ── 5. CHAT ACTIVITY SUMMARY ─────────────────────────────────────────────
    $chat_stmt = $conn->prepare("
        SELECT c.lawyer_id, l.name as lawyer_name, l.specialization,
               MAX(c.date_time) as last_chat_date,
               COUNT(c.chat_id) as total_messages
        FROM chats c
        JOIN lawyers l ON c.lawyer_id = l.lawyer_id
        WHERE c.user_id = ?
        GROUP BY c.lawyer_id, l.name, l.specialization
        ORDER BY last_chat_date DESC");
    $chat_stmt->execute([$user_id]);
    $chat_report = $chat_stmt->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Error loading client reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Reports | Legal Connect</title>
    <meta name="description" content="View your appointments, payments, consultations, legal questions, and feedback records.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
    <style>
        .kpi-card {
            border: none;
            border-radius: 16px;
            padding: 20px 24px;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: transform 0.25s ease;
        }
        .kpi-card:hover { transform: translateY(-4px); }
        .kpi-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        .kpi-value { font-size: 2rem; font-weight: 800; line-height: 1.1; color: #0f172a; }
        .kpi-icon { font-size: 2.2rem; opacity: 0.2; }
        
        @media print {
            .sidebar, header, .filter-card, .btn-print, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
    </style>
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
            <a class="nav-link active" href="/legalconnect/user/reports.php"><i class="fa-solid fa-chart-pie"></i> Client Reports</a>
            <a class="nav-link" href="/legalconnect/user/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
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
                <h2 class="fw-bold text-dark mb-1">Client Reports</h2>
                <p class="text-muted mb-0">View your appointments, payments, consultations and feedback records.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-gold btn-sm rounded-pill px-3 fw-bold btn-print me-2">
                    <i class="fa-solid fa-print me-1"></i> Print Report
                </button>
                <span class="text-dark small">Client: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <div class="avatar-circle"><?php echo substr($_SESSION['name'], 0, 1); ?></div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- ── REPORT SUMMARY (KPI CARDS) ─────────────────────────────────── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="kpi-card d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Total Appointments</div>
                        <div class="kpi-value text-primary"><?php echo $count_total; ?></div>
                    </div>
                    <div class="kpi-icon text-primary"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Completed</div>
                        <div class="kpi-value text-success"><?php echo $count_completed; ?></div>
                    </div>
                    <div class="kpi-icon text-success"><i class="fa-solid fa-circle-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Cancelled</div>
                        <div class="kpi-value text-danger"><?php echo $count_cancelled; ?></div>
                    </div>
                    <div class="kpi-icon text-danger"><i class="fa-solid fa-ban"></i></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-card d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Total Payments</div>
                        <div class="kpi-value text-warning">₹<?php echo number_format($total_payments, 2); ?></div>
                    </div>
                    <div class="kpi-icon text-warning"><i class="fa-solid fa-wallet"></i></div>
                </div>
            </div>
        </div>

        <!-- ── REPORT FILTERING CARD ─────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-3 rounded-4 mb-4 bg-white filter-card">
            <form action="/legalconnect/user/reports.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Search Keywords</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search lawyer name or ID..." value="<?php echo htmlspecialchars($filter_search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Filter by Lawyer</label>
                    <select name="lawyer_id" class="form-select form-select-sm">
                        <option value="0">All Lawyers</option>
                        <?php foreach ($my_lawyers as $law): ?>
                            <option value="<?php echo $law['lawyer_id']; ?>" <?php if ($filter_lawyer === (int)$law['lawyer_id']) echo 'selected'; ?>>
                                Adv. <?php echo htmlspecialchars($law['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Filter Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php if ($filter_status === 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Accepted" <?php if ($filter_status === 'Accepted') echo 'selected'; ?>>Accepted</option>
                        <option value="Approved" <?php if ($filter_status === 'Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Completed" <?php if ($filter_status === 'Completed') echo 'selected'; ?>>Completed</option>
                        <option value="Cancelled by Lawyer" <?php if ($filter_status === 'Cancelled by Lawyer') echo 'selected'; ?>>Cancelled by Lawyer</option>
                        <option value="Cancelled by Client" <?php if ($filter_status === 'Cancelled by Client') echo 'selected'; ?>>Cancelled by Client</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="/legalconnect/user/reports.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>

        <!-- ── 1. APPOINTMENT REPORT ──────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-days text-warning me-2"></i>1. Appointment History Report</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Appointment ID</th>
                            <th>Lawyer Name</th>
                            <th>Date &amp; Time</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointment_report)): ?>
                            <?php foreach ($appointment_report as $appt): ?>
                                <tr>
                                    <td class="fw-bold">#LC-<?php echo $appt['appointment_id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($appt['lawyer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($appt['specialization']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-clock me-1 text-warning"></i><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-status <?php 
                                            if ($appt['status'] === 'Pending') echo 'badge-pending';
                                            elseif ($appt['status'] === 'Accepted') echo 'badge-accepted';
                                            elseif ($appt['status'] === 'Approved') echo 'badge-approved';
                                            elseif ($appt['status'] === 'Completed') echo 'badge-completed';
                                            else echo 'badge-cancelled';
                                        ?>"><?php echo htmlspecialchars($appt['status']); ?></span>

                                        <!-- Refund Info for Lawyer Cancelled appointments that were paid -->
                                        <?php if ($appt['status'] === 'Cancelled by Lawyer'): ?>
                                            <div class="alert alert-warning p-2 mt-2 mb-0 small border-0" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                <strong>Refund Information:</strong> Refund will be processed within 2–3 working days.
                                                <?php if (!empty($appt['cancellation_reason'])): ?>
                                                    <div class="text-muted mt-1"><strong>Reason:</strong> <?php echo htmlspecialchars($appt['cancellation_reason']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('d M, Y h:i A', strtotime($appt['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No appointment records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── 2. PAYMENT REPORT ──────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-warning me-2"></i>2. Payment History Report</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Payment ID</th>
                            <th>Lawyer Name</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Transaction ID</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payment_report)): ?>
                            <?php foreach ($payment_report as $pay): ?>
                                <tr>
                                    <td class="fw-bold">#PAY-<?php echo $pay['payment_id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($pay['lawyer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($pay['specialization']); ?></small>
                                    </td>
                                    <td class="fw-bold text-dark">₹<?php echo number_format($pay['amount'], 2); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                                    <td><code class="text-dark small"><?php echo htmlspecialchars($pay['transaction_id']); ?></code></td>
                                    <td>
                                        <span class="badge bg-success rounded-pill px-3"><?php echo htmlspecialchars($pay['payment_status']); ?></span>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('d M, Y h:i A', strtotime($pay['payment_date'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No payment records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── 3. LEGAL Q&A REPORT ────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments-question text-warning me-2"></i>3. Legal Q&amp;A Activity Report</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Subject &amp; Question</th>
                            <th>Assigned Lawyer</th>
                            <th>Status</th>
                            <th>Lawyer Response</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($qa_report)): ?>
                            <?php foreach ($qa_report as $q): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($q['subject']); ?></div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 250px;"><?php echo htmlspecialchars($q['question']); ?></small>
                                    </td>
                                    <td class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($q['lawyer_name']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $q['status'] === 'Answered' ? 'bg-success' : 'bg-warning text-dark'; ?> px-3">
                                            <?php echo htmlspecialchars($q['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($q['response'])): ?>
                                            <small class="text-secondary d-block text-truncate" style="max-width: 220px;"><?php echo htmlspecialchars($q['response']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">Awaiting Response</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('d M, Y', strtotime($q['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No legal questions submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── 4. FEEDBACK & RATING REPORT ───────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-star text-warning me-2"></i>4. Feedback &amp; Rating Report</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Lawyer Name</th>
                            <th>Rating Given</th>
                            <th>Feedback Comments</th>
                            <th>Submitted Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feedback_report)): ?>
                            <?php foreach ($feedback_report as $fb): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($fb['lawyer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($fb['specialization']); ?></small>
                                    </td>
                                    <td>
                                        <div class="text-warning small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa-<?php echo $i <= $fb['rating'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1 fw-bold text-dark"><?php echo $fb['rating']; ?>/5</span>
                                        </div>
                                    </td>
                                    <td><small class="text-secondary"><?php echo htmlspecialchars($fb['comments']); ?></small></td>
                                    <td><small class="text-muted"><?php echo date('d M, Y', strtotime($fb['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No feedback records submitted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── 5. CHAT ACTIVITY SUMMARY ────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-warning me-2"></i>5. Chat Activity Summary</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Lawyer Name</th>
                            <th>Specialization</th>
                            <th>Total Messages</th>
                            <th>Last Chat Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($chat_report)): ?>
                            <?php foreach ($chat_report as $ch): ?>
                                <tr>
                                    <td class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($ch['lawyer_name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ch['specialization']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $ch['total_messages']; ?> messages</span></td>
                                    <td><small class="text-muted"><?php echo date('d M, Y h:i A', strtotime($ch['last_chat_date'])); ?></small></td>
                                    <td class="text-center">
                                        <a href="/legalconnect/user/chat.php?lawyer_id=<?php echo $ch['lawyer_id']; ?>" class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-3">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Open Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No chat activity recorded yet.</td>
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
