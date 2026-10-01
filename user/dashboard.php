<?php
$page_title = "Client Dashboard";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";

try {
    // Gather client stats
    $bookings_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
    $bookings_stmt->execute([$user_id]);
    $count_bookings = $bookings_stmt->fetchColumn();

    $questions_stmt = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE user_id = ?");
    $questions_stmt->execute([$user_id]);
    $count_questions = $questions_stmt->fetchColumn();

    $completed_stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND status = 'Completed'");
    $completed_stmt->execute([$user_id]);
    $count_completed = $completed_stmt->fetchColumn();

    // Fetch client appointments
    $stmt = $conn->prepare("SELECT a.*, l.name as lawyer_name, l.specialization, p.payment_id 
                            FROM appointments a 
                            JOIN lawyers l ON a.lawyer_id = l.lawyer_id 
                            LEFT JOIN payments p ON a.appointment_id = p.appointment_id
                            WHERE a.user_id = ? 
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll();

    // Fetch client Q&A
    $q_stmt = $conn->prepare("SELECT q.*, l.name as lawyer_name FROM legal_questions q JOIN lawyers l ON q.lawyer_id = l.lawyer_id WHERE q.user_id = ? ORDER BY q.created_at DESC LIMIT 5");
    $q_stmt->execute([$user_id]);
    $questions = $q_stmt->fetchAll();

    // Fetch categories for cards
    $categories = $conn->query("SELECT * FROM categories LIMIT 4")->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error fetching user dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=4" rel="stylesheet">
    <style>
        /* Dashboard-specific clean layout refinements */
        .dash-welcome-card {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 70%, #24243e 100%);
            border: 1px solid rgba(245, 158, 11, 0.22);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            color: #ffffff;
            padding: 30px 32px;
            min-height: 170px;
            display: flex;
            align-items: center;
        }
        .dash-welcome-content {
            position: relative;
            z-index: 2;
            max-width: 65%;
        }
        .dash-welcome-tag {
            display: inline-flex;
            align-items: center;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid rgba(245, 158, 11, 0.25);
            margin-bottom: 10px;
        }
        .dash-welcome-title {
            font-size: 1.65rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            line-height: 1.25;
        }
        .dash-welcome-subtitle {
            font-size: 0.95rem;
            color: #cbd5e1;
            margin-bottom: 16px;
            line-height: 1.45;
        }
        .dash-welcome-visual {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 42%;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }
        .dash-welcome-visual img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            opacity: 0.38;
            filter: contrast(1.05) brightness(0.95);
            mask-image: linear-gradient(to left, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%);
            -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%);
        }

        /* Summary Stat Cards */
        .dash-metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 18px;
            height: 100%;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .dash-metric-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .dash-metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .icon-blue {
            background-color: #eff6ff;
            color: #2563eb;
        }
        .icon-gold {
            background-color: #fef3c7;
            color: #d97706;
        }
        .icon-green {
            background-color: #ecfdf5;
            color: #059669;
        }
        .dash-metric-info {
            flex-grow: 1;
        }
        .dash-metric-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .dash-metric-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .dash-metric-hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Quick Action Bar */
        .dash-quick-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
        }
        .dash-action-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            height: 100%;
        }
        .dash-action-link:hover {
            background: #ffffff;
            border-color: #f59e0b;
            color: #d97706;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.12);
        }
        .dash-action-link i {
            font-size: 1rem;
        }

        /* Section Cards & Tables */
        .dash-content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .dash-card-header {
            padding: 16px 22px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dash-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dash-table {
            margin-bottom: 0;
        }
        .dash-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 18px;
            border-bottom: 1px solid #e2e8f0;
        }
        .dash-table td {
            padding: 14px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 0.9rem;
        }
        .dash-table tr:last-child td {
            border-bottom: none;
        }
        .dash-table tbody tr:hover {
            background-color: #fafbfd;
        }

        /* Practice Areas Grid */
        .practice-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .practice-card:hover {
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .practice-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .practice-card-desc {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .practice-card-link {
            font-size: 0.82rem;
            font-weight: 600;
            color: #d97706;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .practice-card-link:hover {
            color: #b45309;
            text-decoration: underline;
        }

        /* Mobile Adjustments */
        @media (max-width: 991.98px) {
            .dash-welcome-content {
                max-width: 100%;
            }
            .dash-welcome-visual {
                opacity: 0.18;
                width: 70%;
            }
        }
        @media (max-width: 575.98px) {
            .dash-welcome-card {
                padding: 22px 20px;
            }
            .dash-welcome-title {
                font-size: 1.35rem;
            }
            .dash-welcome-subtitle {
                font-size: 0.85rem;
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
            <a class="nav-link active" href="/legalconnect/user/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/user/lawyers.php"><i class="fa-solid fa-user-tie"></i> Search Lawyers</a>
            <a class="nav-link" href="/legalconnect/user/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
            <a class="nav-link" href="/legalconnect/user/questions.php"><i class="fa-solid fa-circle-question"></i> Legal Q&amp;A</a>
            <a class="nav-link" href="/legalconnect/user/chat.php"><i class="fa-solid fa-comments"></i> Chat Support</a>
            <a class="nav-link" href="/legalconnect/user/reports.php"><i class="fa-solid fa-chart-pie"></i> Client Reports</a>
            <a class="nav-link" href="/legalconnect/user/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link" href="/legalconnect/user/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Settings</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Top Navigation / User Status Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h4 class="fw-bold text-dark mb-0">Client Overview</h4>
                <small class="text-muted">Legal Connect Management Portal</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-semibold text-dark small"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">Verified Client Account</div>
                </div>
                <?php if (isset($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default_user.png'): ?>
                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile" class="rounded-circle border border-secondary shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-circle" style="width: 38px; height: 38px; font-size: 0.95rem;"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
        </header>

        <!-- System Alert Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 1. Dashboard Welcome Hero (with Lady Justice subtle visual) -->
        <div class="dash-welcome-card mb-4">
            <div class="dash-welcome-content">
                <div class="dash-welcome-tag">
                    <i class="fa-solid fa-scale-balanced me-1"></i> Legal Connect Portal
                </div>
                <h2 class="dash-welcome-title">
                    Welcome to Legal Connect, <?php echo htmlspecialchars($_SESSION['name']); ?>
                </h2>
                <p class="dash-welcome-subtitle">
                    Your trusted platform for online legal support, verified advocate consultations, and case inquiries.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/legalconnect/user/lawyers.php" class="btn btn-warning btn-sm fw-bold text-dark px-3 py-2">
                        <i class="fa-solid fa-plus me-1"></i> Book New Consultation
                    </a>
                    <a href="/legalconnect/user/questions.php" class="btn btn-outline-light btn-sm fw-semibold px-3 py-2">
                        <i class="fa-solid fa-circle-question me-1"></i> Ask Legal Question
                    </a>
                </div>
            </div>
            <div class="dash-welcome-visual">
                <img src="/legalconnect/assets/images/lady_justice.jpg" alt="Lady Justice Statue">
            </div>
        </div>

        <!-- 2. Summary Section: Key Statistics Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="dash-metric-card">
                    <div class="dash-metric-icon icon-blue">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="dash-metric-info">
                        <div class="dash-metric-label">Consultation Bookings</div>
                        <div class="dash-metric-number"><?php echo $count_bookings; ?></div>
                        <div class="dash-metric-hint">Total scheduled appointments</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dash-metric-card">
                    <div class="dash-metric-icon icon-gold">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <div class="dash-metric-info">
                        <div class="dash-metric-label">Questions Submitted</div>
                        <div class="dash-metric-number"><?php echo $count_questions; ?></div>
                        <div class="dash-metric-hint">Inquiries sent to advocates</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dash-metric-card">
                    <div class="dash-metric-icon icon-green">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="dash-metric-info">
                        <div class="dash-metric-label">Completed Consults</div>
                        <div class="dash-metric-number"><?php echo $count_completed; ?></div>
                        <div class="dash-metric-hint">Successfully resolved sessions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Quick Actions Section -->
        <div class="dash-quick-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Actions</h6>
                <small class="text-muted">Direct Shortcuts</small>
            </div>
            <div class="row g-2">
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/lawyers.php" class="dash-action-link">
                        <i class="fa-solid fa-user-tie text-primary"></i>
                        <span>Find Lawyers</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/questions.php" class="dash-action-link">
                        <i class="fa-solid fa-circle-question text-warning"></i>
                        <span>Ask Question</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/chat.php" class="dash-action-link">
                        <i class="fa-solid fa-comments text-info"></i>
                        <span>Chat Support</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/legal_information.php" class="dash-action-link">
                        <i class="fa-solid fa-book-bookmark text-success"></i>
                        <span>Legal Info / IPC</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/reports.php" class="dash-action-link">
                        <i class="fa-solid fa-chart-pie text-secondary"></i>
                        <span>My Reports</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="/legalconnect/user/profile.php" class="dash-action-link">
                        <i class="fa-solid fa-circle-user text-dark"></i>
                        <span>Profile</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Main Content Area: Consultations & Q&A Grid -->
        <div class="row g-4 mb-4">
            <!-- Left Side: Consultations List -->
            <div class="col-lg-8">
                <div class="dash-content-card h-100">
                    <div class="dash-card-header">
                        <h5 class="dash-card-title">
                            <i class="fa-solid fa-calendar-check text-warning"></i>
                            <span>My Consultations</span>
                            <span class="badge bg-light text-dark border ms-1"><?php echo count($appointments); ?></span>
                        </h5>
                        <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-gold rounded-pill px-3">
                            <i class="fa-solid fa-plus me-1"></i>Book New
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table dash-table align-middle">
                            <thead>
                                <tr>
                                    <th>Advocate</th>
                                    <th>Date &amp; Time</th>
                                    <th>Status</th>
                                    <th class="text-center">Billing &amp; Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <?php
                                            // Compute refund preview for modal
                                            $appt_ts     = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
                                            $hrs_until   = ($appt_ts - time()) / 3600;
                                            $was_paid    = !empty($appt['payment_id']);
                                            $cancellable = in_array($appt['status'], ['Pending','Accepted','Approved']);

                                            if (!$was_paid) {
                                                $refund_preview = '<span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>No charges were made — this appointment will simply be removed.</span>';
                                            } elseif ($hrs_until > 24) {
                                                $refund_preview = '<span class="text-success fw-bold"><i class="fa-solid fa-money-bill-wave me-1"></i>Full refund of <strong>&#8377;300.00</strong> will be credited within 2&ndash;3 working days.</span>';
                                            } elseif ($hrs_until > 2) {
                                                $refund_preview = '<span class="text-warning fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Short-notice cancellation &mdash; Partial refund of <strong>&#8377;150.00 (50%)</strong> within 2&ndash;3 working days. The remaining 50% is non-refundable.</span>';
                                            } else {
                                                $refund_preview = '<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>Last-minute cancellation &mdash; Partial refund of <strong>&#8377;75.00 (25%)</strong> within 2&ndash;3 working days. The remaining 75% is non-refundable.</span>';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">Adv. <?php echo htmlspecialchars($appt['lawyer_name']); ?></div>
                                                <span class="badge bg-light text-muted border small mt-1"><?php echo htmlspecialchars($appt['specialization']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?></div>
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
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                <?php if ($appt['status'] === 'Accepted' && !$appt['payment_id']): ?>
                                                    <!-- Accepted and Unpaid: Pay + Cancel -->
                                                    <a href="/legalconnect/user/payment.php?appointment_id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-warning fw-bold text-dark">
                                                        <i class="fa-solid fa-credit-card me-1"></i>Pay Fee
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $appt['appointment_id']; ?>">
                                                        <i class="fa-solid fa-xmark me-1"></i>Cancel
                                                    </button>
                                                <?php elseif ($appt['status'] === 'Pending'): ?>
                                                    <!-- Pending: Cancel only -->
                                                    <span class="text-muted small fw-bold me-1 align-self-center">Awaiting Acceptance</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $appt['appointment_id']; ?>">
                                                        <i class="fa-solid fa-xmark me-1"></i>Cancel
                                                    </button>
                                                <?php elseif ($appt['status'] === 'Approved'): ?>
                                                    <!-- Approved (paid): Cancel with refund info -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $appt['appointment_id']; ?>">
                                                        <i class="fa-solid fa-xmark me-1"></i>Cancel
                                                    </button>
                                                <?php elseif ($appt['status'] === 'Completed'): ?>
                                                    <!-- Completed: Rate advocate -->
                                                    <a href="/legalconnect/user/feedback.php?lawyer_id=<?php echo $appt['lawyer_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa-solid fa-star me-1"></i>Rate Advocate
                                                    </a>
                                                <?php elseif (strpos($appt['status'], 'Cancelled') !== false || $appt['status'] === 'Rejected'): ?>
                                                    <span class="text-danger small fw-bold"><i class="fa-solid fa-ban me-1"></i><?php echo htmlspecialchars($appt['status']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                                                <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <?php if ($cancellable): ?>
                                        <!-- Cancel Confirmation Modal for appointment #<?php echo $appt['appointment_id']; ?> -->
                                        <div class="modal fade" id="cancelModal<?php echo $appt['appointment_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-3">
                                                    <div class="modal-header border-0 bg-danger text-white">
                                                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Cancel Appointment</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <p class="text-secondary small mb-3">
                                                             You are about to cancel your appointment with
                                                            <strong>Adv. <?php echo htmlspecialchars($appt['lawyer_name']); ?></strong>
                                                            scheduled for <strong><?php echo date('d M, Y', strtotime($appt['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></strong>.
                                                        </p>
                                                        <!-- Refund Policy Preview -->
                                                        <div class="border rounded-3 p-3 bg-light mb-3">
                                                            <div class="fw-bold text-dark small mb-2"><i class="fa-solid fa-info-circle text-warning me-1"></i>Refund Policy</div>
                                                            <div class="small"><?php echo $refund_preview; ?></div>
                                                        </div>
                                                        <div class="alert alert-warning border-0 small mb-0 py-2">
                                                            <i class="fa-solid fa-lock me-1"></i>This action cannot be undone once confirmed.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 bg-light">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                                                        <form method="POST" action="/legalconnect/user/cancel_appointment.php" style="display:inline;">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>Confirm Cancellation</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-calendar-xmark fs-3 text-muted mb-2 d-block"></i>
                                            No appointments scheduled yet. Click <strong>Book New</strong> above to browse advocates!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Recent Q&As -->
            <div class="col-lg-4">
                <div class="dash-content-card h-100">
                    <div class="dash-card-header">
                        <h5 class="dash-card-title">
                            <i class="fa-solid fa-circle-question text-warning"></i>
                            <span>Legal Q&amp;A Inquiries</span>
                        </h5>
                        <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View All</a>
                    </div>
                    <div class="p-3">
                        <?php if (!empty($questions)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($questions as $q): ?>
                                    <div class="list-group-item px-2 py-3 border-bottom">
                                        <div class="fw-semibold text-dark text-truncate mb-1" style="font-size: 0.92rem;"><?php echo htmlspecialchars($q['subject']); ?></div>
                                        <div class="text-muted small mb-2">Advocate: Adv. <?php echo htmlspecialchars($q['lawyer_name']); ?></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge badge-status <?php echo $q['status'] === 'Answered' ? 'badge-approved' : 'badge-pending'; ?>"><?php echo htmlspecialchars($q['status']); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d M, Y', strtotime($q['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-clipboard-question fs-2 text-muted mb-2 d-block"></i>
                                <div class="small">No questions submitted yet.</div>
                                <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-warning mt-3">Ask a Question</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Practice Areas / Specializations Section -->
        <div class="dash-content-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="dash-card-title">
                        <i class="fa-solid fa-scale-balanced text-warning"></i>
                        <span>Browse Practice Specializations</span>
                    </h5>
                    <small class="text-muted">Find qualified advocates categorized by area of expertise</small>
                </div>
                <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-outline-secondary">Browse All Categories</a>
            </div>
            <div class="row g-3">
                <?php foreach ($categories as $cat): ?>
                    <div class="col-lg-3 col-sm-6">
                        <div class="practice-card">
                            <div>
                                <h6 class="practice-card-title"><?php echo htmlspecialchars($cat['category_name']); ?></h6>
                                <p class="practice-card-desc"><?php echo htmlspecialchars($cat['description']); ?></p>
                            </div>
                            <a href="/legalconnect/user/lawyers.php?specialization=<?php echo urlencode($cat['category_name']); ?>" class="practice-card-link">
                                View Lawyers <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
