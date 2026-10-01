<?php
$page_title = "System Monitoring";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg = "";

// Fetch Payment history
$payments = [];
$total_revenue = 0.00;
try {
    $payments = $conn->query("SELECT p.*, u.name as client_name, l.name as lawyer_name FROM payments p JOIN users u ON p.user_id = u.user_id JOIN lawyers l ON p.lawyer_id = l.lawyer_id ORDER BY p.payment_date DESC")->fetchAll();
    $total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0.00) FROM payments WHERE payment_status = 'Completed'")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading payments: " . $e->getMessage();
}

// Fetch Feedback history
$feedbacks = [];
$avg_rating = 0.0;
try {
    $feedbacks = $conn->query("SELECT f.*, u.name as client_name, l.name as lawyer_name FROM feedbacks f JOIN users u ON f.user_id = u.user_id JOIN lawyers l ON f.lawyer_id = l.lawyer_id ORDER BY f.created_at DESC")->fetchAll();
    $avg_rating = $conn->query("SELECT COALESCE(AVG(rating), 0.0) FROM feedbacks")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading feedback: " . $e->getMessage();
}

// Fetch Q&A activity log
$queries = [];
try {
    $queries = $conn->query("SELECT q.*, u.name as client_name, l.name as lawyer_name FROM legal_questions q JOIN users u ON q.user_id = u.user_id JOIN lawyers l ON q.lawyer_id = l.lawyer_id ORDER BY q.created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error loading questions log: " . $e->getMessage();
}

try {
    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring | Legal Connect</title>
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
            <a class="nav-link" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients <span class="badge bg-danger ms-auto"><?php echo $pending_users; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
            <a class="nav-link" href="/legalconnect/admin/reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
            <a class="nav-link active" href="/legalconnect/admin/monitoring.php"><i class="fa-solid fa-chart-line"></i> Monitoring &amp; Audit</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Platform Monitoring & Audit</h2>
                <p class="text-muted mb-0">Review client checkout records, legal support logs, and advocate feedback stats.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
                <div class="avatar-circle">A</div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Stat summaries for metrics -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-4 bg-white rounded-4 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1">Total Consultation Billing Revenue</h6>
                        <h2 class="fw-bold text-success mb-0">&#8377;<?php echo number_format($total_revenue, 2); ?></h2>
                    </div>
                    <div class="p-3 bg-light text-success rounded-circle"><i class="fa-solid fa-file-invoice-dollar fs-3"></i></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-4 bg-white rounded-4 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1">Platform Rating Average</h6>
                        <h2 class="fw-bold text-warning mb-0">
                            <?php echo number_format($avg_rating, 1); ?> / 5.0 
                            <small class="fs-6 fw-normal text-muted">(<?php echo count($feedbacks); ?> reviews)</small>
                        </h2>
                    </div>
                    <div class="p-3 bg-light text-warning rounded-circle"><i class="fa-solid fa-star-half-stroke fs-3"></i></div>
                </div>
            </div>
        </div>

        <!-- Monitoring Section Tabs -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <ul class="nav nav-tabs mb-4" id="monitorTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-content" type="button" role="tab" aria-selected="true">
                        <i class="fa-solid fa-credit-card me-2 text-primary"></i>Payments Ledger
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa-content" type="button" role="tab" aria-selected="false">
                        <i class="fa-solid fa-clipboard-question me-2 text-info"></i>Q&A Activity Log
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback-content" type="button" role="tab" aria-selected="false">
                        <i class="fa-solid fa-comments me-2 text-warning"></i>Feedback & Ratings
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="monitorTabsContent">
                <!-- Payments Ledger Panel -->
                <div class="tab-pane fade show active" id="payments-content" role="tabpanel" aria-labelledby="payments-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Client</th>
                                    <th>Advocate</th>
                                    <th>Method</th>
                                    <th>Paid Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($payments)): ?>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($pay['transaction_id']); ?></td>
                                            <td><?php echo htmlspecialchars($pay['client_name']); ?></td>
                                            <td>Adv. <?php echo htmlspecialchars($pay['lawyer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                            <td class="fw-bold text-success">&#8377;<?php echo number_format($pay['amount'], 2); ?></td>
                                            <td><small class="text-muted"><?php echo date('d M, Y h:i A', strtotime($pay['payment_date'])); ?></small></td>
                                            <td>
                                                <span class="badge bg-success text-white rounded-pill px-3"><?php echo $pay['payment_status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">No payment transactions processed yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Q&A Activity Log Panel -->
                <div class="tab-pane fade" id="qa-content" role="tabpanel" aria-labelledby="qa-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Client / Advocate</th>
                                    <th>Subject & Question</th>
                                    <th>Status</th>
                                    <th>Date Asked</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($queries)): ?>
                                    <?php foreach ($queries as $q): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">Client: <?php echo htmlspecialchars($q['client_name']); ?></div>
                                                <div class="text-muted small">Adv: <?php echo htmlspecialchars($q['lawyer_name']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($q['subject']); ?></div>
                                                <small class="text-muted d-inline-block text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($q['question']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $q['status'] === 'Answered' ? 'bg-success' : 'bg-warning text-dark'; ?> px-3">
                                                    <?php echo $q['status']; ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?php echo date('d M, Y', strtotime($q['created_at'])); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No legal questions submitted yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback & Ratings Panel -->
                <div class="tab-pane fade" id="feedback-content" role="tabpanel" aria-labelledby="feedback-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Client Name</th>
                                    <th>Advocate</th>
                                    <th>Rating Star</th>
                                    <th>Comments</th>
                                    <th>Date Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedbacks)): ?>
                                    <?php foreach ($feedbacks as $fb): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($fb['client_name']); ?></td>
                                            <td>Adv. <?php echo htmlspecialchars($fb['lawyer_name']); ?></td>
                                            <td>
                                                <span class="text-warning">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fa-solid fa-star<?php echo $i <= $fb['rating'] ? '' : '-o text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?php echo nl2br(htmlspecialchars($fb['comments'])); ?></td>
                                            <td><small class="text-muted"><?php echo date('d M, Y', strtotime($fb['created_at'])); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No client reviews submitted yet.</td>
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
