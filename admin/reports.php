<?php
$page_title = "Platform Reports";
require_once __DIR__ . '/../config/db.php';
check_login('admin');
$error_msg = "";

// ── Mode: single date OR date range ─────────────────────────────────────────
$mode      = isset($_GET['mode']) ? $_GET['mode'] : 'single';
$sel_date  = (isset($_GET['sel_date']) && $_GET['sel_date'] !== '') ? $_GET['sel_date'] : date('Y-m-d');
$date_from = (isset($_GET['date_from']) && $_GET['date_from'] !== '') ? $_GET['date_from'] : date('Y-m-01');
$date_to   = (isset($_GET['date_to'])   && $_GET['date_to']   !== '') ? $_GET['date_to']   : date('Y-m-d');

// Validate
if (!strtotime($sel_date))  $sel_date  = date('Y-m-d');
if (!strtotime($date_from)) $date_from = date('Y-m-01');
if (!strtotime($date_to))   $date_to   = date('Y-m-d');
if ($date_from > $date_to)  { [$date_from,$date_to] = [$date_to,$date_from]; }

$sel_label      = date('d M Y', strtotime($sel_date));
$dt_from_label  = date('d M Y', strtotime($date_from));
$dt_to_label    = date('d M Y', strtotime($date_to));

// Effective range for queries
$qfrom = ($mode === 'single') ? $sel_date : $date_from;
$qto   = ($mode === 'single') ? $sel_date : $date_to;

try {
    $pending_users   = $conn->query("SELECT COUNT(*) FROM users WHERE status='Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status='Pending'")->fetchColumn();
    $total_users   = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers")->fetchColumn();

    // KPIs
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $total_appointments=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $total_payments=$s->fetchColumn();
    $s=$conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Completed' AND DATE(payment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $total_revenue=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $total_questions=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM feedbacks WHERE DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $total_feedbacks=$s->fetchColumn();
    $s=$conn->prepare("SELECT COALESCE(AVG(rating),0) FROM feedbacks WHERE DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $avg_platform_rating=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $new_users=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM lawyers WHERE DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $new_lawyers=$s->fetchColumn();

    // Appointment status
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE status='Pending'   AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $appt_pending=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE status='Accepted'  AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $appt_accepted=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE status='Approved'  AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $appt_approved=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE status='Completed' AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $appt_completed=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE (status='Cancelled' OR status='Rejected') AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $appt_cancelled=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE status='Pending'  AND DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $qa_pending=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE status='Answered' AND DATE(created_at) BETWEEN ? AND ?"); $s->execute([$qfrom,$qto]); $qa_answered=$s->fetchColumn();

    // Account statuses (all-time)
    $users_approved=$conn->query("SELECT COUNT(*) FROM users WHERE status='Approved'")->fetchColumn();
    $users_pending=$conn->query("SELECT COUNT(*) FROM users WHERE status='Pending'")->fetchColumn();
    $users_blocked=$conn->query("SELECT COUNT(*) FROM users WHERE status='Blocked'")->fetchColumn();
    $users_rejected=$conn->query("SELECT COUNT(*) FROM users WHERE status='Rejected'")->fetchColumn();
    $lawyers_approved=$conn->query("SELECT COUNT(*) FROM lawyers WHERE status='Approved'")->fetchColumn();
    $lawyers_pending=$conn->query("SELECT COUNT(*) FROM lawyers WHERE status='Pending'")->fetchColumn();
    $lawyers_blocked=$conn->query("SELECT COUNT(*) FROM lawyers WHERE status='Blocked'")->fetchColumn();
    $lawyers_rejected=$conn->query("SELECT COUNT(*) FROM lawyers WHERE status='Rejected'")->fetchColumn();

    // Top lawyers
    $s=$conn->prepare("SELECT l.name,l.specialization,COALESCE(SUM(p.amount),0) as earnings,COUNT(p.payment_id) as tx_count FROM lawyers l LEFT JOIN payments p ON l.lawyer_id=p.lawyer_id AND p.payment_status='Completed' AND DATE(p.payment_date) BETWEEN ? AND ? GROUP BY l.lawyer_id ORDER BY earnings DESC LIMIT 5"); $s->execute([$qfrom,$qto]); $top_lawyers=$s->fetchAll();
    $s=$conn->prepare("SELECT l.name,l.specialization,COALESCE(AVG(f.rating),0) as avg_rating,COUNT(f.feedback_id) as review_count FROM lawyers l LEFT JOIN feedbacks f ON l.lawyer_id=f.lawyer_id AND DATE(f.created_at) BETWEEN ? AND ? GROUP BY l.lawyer_id HAVING review_count>0 ORDER BY avg_rating DESC LIMIT 5"); $s->execute([$qfrom,$qto]); $top_rated=$s->fetchAll();

    // Daily chart data
    $s=$conn->prepare("SELECT DATE_FORMAT(appointment_date,'%d %b') as day_label,DATE(appointment_date) as day_key,COUNT(*) as total FROM appointments WHERE DATE(appointment_date) BETWEEN ? AND ? GROUP BY day_key,day_label ORDER BY day_key ASC"); $s->execute([$qfrom,$qto]); $daily_appts=$s->fetchAll();
    $s=$conn->prepare("SELECT DATE_FORMAT(payment_date,'%d %b') as day_label,DATE(payment_date) as day_key,COALESCE(SUM(amount),0) as total FROM payments WHERE payment_status='Completed' AND DATE(payment_date) BETWEEN ? AND ? GROUP BY day_key,day_label ORDER BY day_key ASC"); $s->execute([$qfrom,$qto]); $daily_revenue=$s->fetchAll();
    $s=$conn->prepare("SELECT payment_method,COUNT(*) as total FROM payments WHERE DATE(payment_date) BETWEEN ? AND ? GROUP BY payment_method"); $s->execute([$qfrom,$qto]); $pay_methods=$s->fetchAll();

    // Payment transactions
    $s=$conn->prepare("SELECT p.*,u.name as client_name,l.name as lawyer_name FROM payments p JOIN users u ON p.user_id=u.user_id JOIN lawyers l ON p.lawyer_id=l.lawyer_id WHERE DATE(p.payment_date) BETWEEN ? AND ? ORDER BY p.payment_date DESC LIMIT 100"); $s->execute([$qfrom,$qto]); $all_payments=$s->fetchAll();

    // ── Get Full Booking/Appointment Details for Selected Date or Date Range ──
    $s=$conn->prepare("
        SELECT a.*, u.name as client_name, u.phone as client_phone, u.email as client_email, u.address as client_address,
               l.name as lawyer_name, l.specialization, l.phone as lawyer_phone, l.qualification as lawyer_qualification, l.experience as lawyer_experience,
               p.amount, p.payment_method, p.payment_status, p.transaction_id,
               c.case_number, c.case_title, c.status as case_status
        FROM appointments a
        JOIN users u ON a.user_id=u.user_id
        JOIN lawyers l ON a.lawyer_id=l.lawyer_id
        LEFT JOIN payments p ON p.appointment_id=a.appointment_id
        LEFT JOIN cases c ON c.appointment_id=a.appointment_id
        WHERE DATE(a.appointment_date) BETWEEN ? AND ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $s->execute([$qfrom,$qto]);
    $day_appointments = $s->fetchAll();

} catch (PDOException $e) { $error_msg="Error: ".$e->getMessage(); }

$chart_days=json_encode(array_column($daily_appts,'day_label'));
$chart_appts_data=json_encode(array_column($daily_appts,'total'));
$rev_days=json_encode(array_column($daily_revenue,'day_label'));
$rev_totals=json_encode(array_column($daily_revenue,'total'));
$pm_labels=json_encode(array_column($pay_methods,'payment_method'));
$pm_values=json_encode(array_column($pay_methods,'total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Platform Reports | Legal Connect Admin</title>
<meta name="description" content="Date-wise platform analytics for Legal Connect administrators.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
<style>
/* ── Filter panel ── */
.filter-bar{background:linear-gradient(135deg,#0a192f 0%,#0d2b4e 100%);border-radius:18px;padding:22px 26px;margin-bottom:28px;box-shadow:0 6px 28px rgba(10,25,47,.22);}
.filter-label{color:rgba(255,255,255,.6);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px;}
.mode-tabs{display:flex;gap:0;background:rgba(255,255,255,.07);border-radius:10px;padding:3px;margin-bottom:18px;width:fit-content;}
.mode-tab{background:transparent;border:none;color:rgba(255,255,255,.55);font-size:.82rem;font-weight:600;padding:7px 18px;border-radius:8px;cursor:pointer;transition:all .2s;}
.mode-tab.active{background:#d4af37;color:#0a192f;}
/* Calendar date input wrapper */
.cal-input-wrap{position:relative;display:flex;align-items:center;}
.cal-input-wrap .form-control{border-radius:10px;border:1.5px solid rgba(212,175,55,.35);background:rgba(255,255,255,.07);color:#fff;font-size:.9rem;font-weight:600;padding:8px 38px 8px 12px;min-width:160px;cursor:pointer;}
.cal-input-wrap .form-control:focus{background:rgba(255,255,255,.13);border-color:#d4af37;color:#fff;box-shadow:0 0 0 3px rgba(212,175,55,.18);}
.cal-input-wrap .form-control::placeholder{color:rgba(255,255,255,.38);}
.cal-input-wrap .cal-icon{position:absolute;right:11px;color:#d4af37;font-size:1rem;pointer-events:none;}
/* Preset buttons */
.preset-btn{background:rgba(255,255,255,.08);border:1.5px solid rgba(212,175,55,.22);color:rgba(255,255,255,.7);font-size:.77rem;font-weight:600;border-radius:8px;padding:5px 13px;cursor:pointer;transition:all .2s;white-space:nowrap;}
.preset-btn:hover,.preset-btn.active{background:#d4af37;border-color:#d4af37;color:#0a192f;}
.btn-filter-apply{background:linear-gradient(135deg,#d4af37,#b8942a);border:none;border-radius:10px;color:#0a192f;font-weight:700;font-size:.9rem;padding:9px 24px;transition:transform .15s,box-shadow .15s;white-space:nowrap;}
.btn-filter-apply:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(212,175,55,.45);}
.date-range-badge{background:rgba(212,175,55,.12);border:1px solid rgba(212,175,55,.3);border-radius:8px;color:#d4af37;font-size:.8rem;font-weight:600;padding:5px 14px;}
/* Section titles */
.report-section-title{font-size:1rem;font-weight:700;color:#0a192f;text-transform:uppercase;letter-spacing:.5px;border-left:4px solid #d4af37;padding-left:12px;margin-bottom:1.25rem;}
/* KPI cards */
.kpi-card{border:none;border-radius:16px;padding:20px 24px;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:transform .25s,box-shadow .25s;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(0,0,0,.1);}
.kpi-label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.75;}
.kpi-value{font-size:2rem;font-weight:800;line-height:1.1;}
.kpi-icon{font-size:2rem;opacity:.25;}
.chart-card{border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.progress-bar-custom{height:8px;border-radius:4px;}
.top-lawyer-rank{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;}
/* Booking details card */
.booking-card{border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;transition:transform .2s,box-shadow .2s;}
.booking-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.12);}
.booking-header{background:linear-gradient(135deg,#0a192f,#0d2b4e);color:#fff;padding:14px 18px;}
.booking-time-badge{background:#d4af37;color:#0a192f;border-radius:8px;padding:4px 12px;font-weight:700;font-size:.82rem;}
.status-pill{border-radius:30px;padding:3px 14px;font-size:.78rem;font-weight:700;}
.flatpickr-calendar{border-radius:14px!important;box-shadow:0 10px 40px rgba(0,0,0,.18)!important;border:none!important;}
</style>
</head>
<body>
<div class="dashboard-container">
<aside class="sidebar">
    <div class="sidebar-header"><h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Admin Panel</h4></div>
    <nav class="nav flex-column py-3">
        <a class="nav-link" href="/legalconnect/admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a class="nav-link" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
        <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
        <a class="nav-link" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
        <a class="nav-link" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients <span class="badge bg-danger ms-auto"><?php echo $pending_users; ?></span></a>
        <a class="nav-link" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
        <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
        <a class="nav-link active" href="/legalconnect/admin/reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
        <a class="nav-link" href="/legalconnect/admin/monitoring.php"><i class="fa-solid fa-chart-line"></i> Monitoring &amp; Audit</a>
        <hr class="border-secondary mx-3">
        <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
    </nav>
</aside>
<main class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie text-warning me-2"></i>Platform Reports</h2>
            <p class="text-muted mb-0">View scheduled bookings, client details, and payment histories for any date or range.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
            <div class="avatar-circle">A</div>
        </div>
    </header>
    <?php if($error_msg!=="") echo '<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>'.$error_msg.'</div>'; ?>

    <!-- ── Filter Bar ── -->
    <div class="filter-bar">
        <!-- Mode tabs -->
        <div class="mode-tabs mb-3">
            <button type="button" class="mode-tab <?php echo $mode==='single'?'active':''; ?>" onclick="switchMode('single')"><i class="fa-solid fa-calendar-day me-1"></i>Single Date</button>
            <button type="button" class="mode-tab <?php echo $mode==='range'?'active':''; ?>" onclick="switchMode('range')"><i class="fa-solid fa-calendar-week me-1"></i>Date Range</button>
        </div>

        <!-- Single date form -->
        <div id="form-single" style="<?php echo $mode!=='single'?'display:none':''; ?>">
            <form method="GET" id="singleForm">
                <input type="hidden" name="mode" value="single">
                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <div class="filter-label"><i class="fa-solid fa-calendar-day me-1"></i>Select Date (Instant Booking View)</div>
                        <div class="cal-input-wrap">
                            <input type="text" name="sel_date" id="sel_date" class="form-control"
                                   value="<?php echo htmlspecialchars($sel_date); ?>"
                                   placeholder="Pick a date..." readonly style="min-width:195px;">
                            <i class="fa-solid fa-calendar-days cal-icon"></i>
                        </div>
                    </div>
                </div>
            </form>
            <?php if($mode==='single'): ?>
            <div class="mt-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-calendar-check text-warning"></i>
                <span class="date-range-badge">Showing bookings for: <strong><?php echo $sel_label; ?></strong></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Date range form -->
        <div id="form-range" style="<?php echo $mode!=='range'?'display:none':''; ?>">
            <form method="GET" id="rangeForm">
                <input type="hidden" name="mode" value="range">
                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <div class="filter-label">From Date</div>
                        <div class="cal-input-wrap">
                            <input type="text" name="date_from" id="date_from" class="form-control"
                                   value="<?php echo htmlspecialchars($date_from); ?>" placeholder="Start date" readonly style="min-width:155px;">
                            <i class="fa-solid fa-calendar cal-icon"></i>
                        </div>
                    </div>
                    <div>
                        <div class="filter-label">To Date</div>
                        <div class="cal-input-wrap">
                            <input type="text" name="date_to" id="date_to" class="form-control"
                                   value="<?php echo htmlspecialchars($date_to); ?>" placeholder="End date" readonly style="min-width:155px;">
                            <i class="fa-solid fa-calendar cal-icon"></i>
                        </div>
                    </div>
                    <div>
                        <div class="filter-label mb-1">Quick Presets</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="preset-btn" data-preset="today">Today</button>
                            <button type="button" class="preset-btn" data-preset="week">This Week</button>
                            <button type="button" class="preset-btn" data-preset="month">This Month</button>
                            <button type="button" class="preset-btn" data-preset="last3">Last 3 Months</button>
                            <button type="button" class="preset-btn" data-preset="last6">Last 6 Months</button>
                            <button type="button" class="preset-btn" data-preset="year">This Year</button>
                        </div>
                    </div>
                    <div class="ms-auto">
                        <button type="submit" class="btn-filter-apply"><i class="fa-solid fa-filter me-1"></i>Apply Filter</button>
                    </div>
                </div>
            </form>
            <?php if($mode==='range'): ?>
            <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                <i class="fa-solid fa-calendar-days text-warning"></i>
                <span class="date-range-badge"><i class="fa-regular fa-calendar me-1"></i><?php echo $dt_from_label; ?> &rarr; <?php echo $dt_to_label; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Booking Details Section (Shows for both Single Date and Date Range) ── -->
    <div class="report-section-title mb-4">
        <i class="fa-solid fa-calendar-check text-warning me-2"></i>
        <?php echo $mode === 'single' ? "Booking Details &amp; Schedule ($sel_label)" : "Scheduled Bookings &amp; Consultations ($dt_from_label to $dt_to_label)"; ?>
    </div>
    
    <?php if (empty($day_appointments)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-5 bg-white">
            <i class="fa-regular fa-calendar-times text-muted fs-1 mb-3"></i>
            <h5 class="text-dark fw-bold mb-1">No Bookings Found</h5>
            <p class="text-muted mb-0">There are no consultations scheduled in the selected period.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($day_appointments as $appt): 
                $time_formatted = date('h:i A', strtotime($appt['appointment_time']));
                $date_formatted = date('d M Y', strtotime($appt['appointment_date']));
                $status_class = 'bg-warning text-dark';
                if ($appt['status'] === 'Completed') $status_class = 'bg-success text-white';
                elseif ($appt['status'] === 'Approved') $status_class = 'bg-primary text-white';
                elseif ($appt['status'] === 'Accepted') $status_class = 'bg-info text-white';
                elseif ($appt['status'] === 'Cancelled' || $appt['status'] === 'Rejected') $status_class = 'bg-danger text-white';
            ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="booking-card card h-100 bg-white">
                        <div class="booking-header d-flex justify-content-between align-items-center">
                            <span class="booking-time-badge"><i class="fa-regular fa-clock me-1"></i><?php echo $time_formatted; ?></span>
                            <span class="status-pill <?php echo $status_class; ?>"><?php echo htmlspecialchars($appt['status']); ?></span>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3 border-bottom pb-3">
                                <h6 class="text-muted small text-uppercase fw-bold mb-2">Client Details (Who Booked)</h6>
                                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user text-info me-2 fs-6"></i><?php echo htmlspecialchars($appt['client_name']); ?></h5>
                                <p class="text-muted mb-1 small"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_phone']); ?></p>
                                <p class="text-muted mb-1 small"><i class="fa-solid fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_email']); ?></p>
                                <p class="text-muted mb-0 small"><i class="fa-solid fa-map-marker-alt me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_address']); ?></p>
                            </div>
                            <div class="mb-3 border-bottom pb-3">
                                <h6 class="text-muted small text-uppercase fw-bold mb-2">Lawyer Assigned (For Which Lawyer)</h6>
                                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-tie text-warning me-2 fs-6"></i>Adv. <?php echo htmlspecialchars($appt['lawyer_name']); ?></h5>
                                <span class="badge bg-light text-dark border px-2 py-1 mb-2 small d-inline-block"><?php echo htmlspecialchars($appt['specialization']); ?></span>
                                <p class="text-muted mb-1 small"><strong>Qualification:</strong> <?php echo htmlspecialchars($appt['lawyer_qualification']); ?></p>
                                <p class="text-muted mb-1 small"><strong>Experience:</strong> <?php echo htmlspecialchars($appt['lawyer_experience']); ?> Years</p>
                                <p class="text-muted mb-0 small"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($appt['lawyer_phone']); ?></p>
                            </div>
                            <div class="mb-3 border-bottom pb-3">
                                <h6 class="text-muted small text-uppercase fw-bold mb-2">Date &amp; Time Info</h6>
                                <p class="text-dark mb-1 small"><strong>Consultation Date:</strong> <?php echo $date_formatted; ?></p>
                                <p class="text-dark mb-1 small"><strong>Consultation Time:</strong> <?php echo $time_formatted; ?></p>
                                <p class="text-muted mb-0 small"><strong>Booked On:</strong> <?php echo date('d M Y, h:i A', strtotime($appt['created_at'])); ?></p>
                            </div>
                            <?php if ($appt['case_number']): ?>
                            <div class="mb-3 border-bottom pb-3 bg-light p-2 rounded">
                                <h6 class="text-muted small text-uppercase fw-bold mb-1">Associated Case Details</h6>
                                <p class="mb-1 small"><strong>Case No:</strong> <code class="text-dark"><?php echo htmlspecialchars($appt['case_number']); ?></code></p>
                                <p class="mb-1 small"><strong>Title:</strong> <?php echo htmlspecialchars($appt['case_title']); ?></p>
                                <p class="mb-0 small"><strong>Status:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($appt['case_status']); ?></span></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold mb-2">Payment Details</h6>
                                <?php if ($appt['transaction_id']): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-success">&#8377;<?php echo number_format($appt['amount'], 2); ?></span>
                                        <span class="badge bg-light border text-dark"><?php echo htmlspecialchars($appt['payment_method']); ?></span>
                                    </div>
                                    <small class="text-muted d-block mt-2">Txn: <code class="text-dark"><?php echo htmlspecialchars($appt['transaction_id']); ?></code></small>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fa-solid fa-circle-exclamation me-1"></i>No payment details recorded (Unpaid)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── KPI Summary Cards (Mode-dependent metrics) ── -->
    <div class="report-section-title mb-3">
        <i class="fa-solid fa-chart-bar me-2 text-warning"></i>Key Metrics
        <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">
            (<?php echo $mode === 'single' ? "On $sel_label" : "$dt_from_label to $dt_to_label"; ?>)
        </span>
    </div>
    <div class="row g-3 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-white">Revenue</div>
                    <div class="kpi-value">&#8377;<?php echo number_format($total_revenue, 0); ?></div>
                    <small class="opacity-75"><?php echo $total_payments; ?> transactions</small>
                </div>
                <i class="fa-solid fa-indian-rupee-sign kpi-icon"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-white">Appointments</div>
                    <div class="kpi-value"><?php echo $total_appointments; ?></div>
                    <small class="opacity-75"><?php echo $appt_completed; ?> completed</small>
                </div>
                <i class="fa-solid fa-calendar-check kpi-icon"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-info text-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-white">New Clients</div>
                    <div class="kpi-value"><?php echo $new_users; ?></div>
                    <small class="opacity-75">Registered in period</small>
                </div>
                <i class="fa-solid fa-user-plus kpi-icon"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-warning text-dark d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label" style="opacity:.65">New Lawyers</div>
                    <div class="kpi-value"><?php echo $new_lawyers; ?></div>
                    <small style="opacity:.7">Registered in period</small>
                </div>
                <i class="fa-solid fa-user-tie kpi-icon"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-muted">Legal Q&amp;A</div>
                    <div class="kpi-value text-dark"><?php echo $total_questions; ?></div>
                    <small class="text-muted"><?php echo $qa_answered; ?> answered</small>
                </div>
                <i class="fa-solid fa-circle-question kpi-icon text-info"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-muted">Avg Platform Rating</div>
                    <div class="kpi-value text-dark"><?php echo number_format($avg_platform_rating, 1); ?> <small class="fs-5 fw-normal text-muted">/ 5</small></div>
                    <small class="text-muted"><?php echo $total_feedbacks; ?> reviews</small>
                </div>
                <i class="fa-solid fa-star kpi-icon text-warning"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-muted">Total Lawyers (All)</div>
                    <div class="kpi-value text-dark"><?php echo $total_lawyers; ?></div>
                    <small class="text-muted"><?php echo $lawyers_approved; ?> approved</small>
                </div>
                <i class="fa-solid fa-user-tie kpi-icon text-warning"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-label text-muted">Total Clients (All)</div>
                    <div class="kpi-value text-dark"><?php echo $total_users; ?></div>
                    <small class="text-muted"><?php echo $users_approved; ?> approved</small>
                </div>
                <i class="fa-solid fa-users kpi-icon text-info"></i>
            </div>
        </div>
    </div>

    <!-- Charts & breakdowns (always shown, representing the selected period) -->
    <div class="report-section-title">
        <i class="fa-solid fa-chart-line me-2 text-warning"></i>Trends &amp; Distribution
        <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">
            (<?php echo $mode === 'single' ? "On $sel_label" : "$dt_from_label to $dt_to_label"; ?>)
        </span>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-days text-success me-2"></i>Daily Appointments</h6>
                <canvas id="apptChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-indian-rupee-sign text-primary me-2"></i>Daily Revenue (&#8377;)</h6>
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-half-stroke text-warning me-2"></i>Appointment Status</h6>
                <canvas id="apptStatusChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-info me-2"></i>Payment Methods</h6>
                <canvas id="payMethodChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-success me-2"></i>Q&amp;A Resolution</h6>
                <canvas id="qaStatusChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Account Status Breakdown -->
    <div class="report-section-title"><i class="fa-solid fa-users me-2 text-warning"></i>Account Status <small class="fw-normal text-muted" style="font-size:.8rem;text-transform:none;">(Platform-wide)</small></div>
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-user text-info me-2"></i>Client Accounts (<?php echo $total_users; ?> total)</h6>
                <?php $ust=[['Approved',$users_approved,'bg-success'],['Pending',$users_pending,'bg-warning'],['Blocked',$users_blocked,'bg-danger'],['Rejected',$users_rejected,'bg-secondary']]; foreach($ust as [$lbl,$val,$col]): $pct=$total_users>0?round($val/$total_users*100):0; ?>
                <div class="mb-3"><div class="d-flex justify-content-between mb-1"><span class="fw-semibold small"><?php echo $lbl; ?></span><span class="text-muted small"><?php echo $val; ?> (<?php echo $pct; ?>%)</span></div><div class="progress progress-bar-custom"><div class="progress-bar <?php echo $col; ?>" style="width:<?php echo $pct; ?>%"></div></div></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-user-tie text-warning me-2"></i>Lawyer Accounts (<?php echo $total_lawyers; ?> total)</h6>
                <?php $lst=[['Approved',$lawyers_approved,'bg-success'],['Pending',$lawyers_pending,'bg-warning'],['Blocked',$lawyers_blocked,'bg-danger'],['Rejected',$lawyers_rejected,'bg-secondary']]; foreach($lst as [$lbl,$val,$col]): $pct=$total_lawyers>0?round($val/$total_lawyers*100):0; ?>
                <div class="mb-3"><div class="d-flex justify-content-between mb-1"><span class="fw-semibold small"><?php echo $lbl; ?></span><span class="text-muted small"><?php echo $val; ?> (<?php echo $pct; ?>%)</span></div><div class="progress progress-bar-custom"><div class="progress-bar <?php echo $col; ?>" style="width:<?php echo $pct; ?>%"></div></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top Lawyers -->
    <div class="report-section-title"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top Performing Lawyers <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">(Selected Period)</span></div>
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-ranking-star text-success me-2"></i>Top 5 by Earnings</h6>
                <?php if(!empty($top_lawyers)): foreach($top_lawyers as $i=>$lw): ?>
                <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded-3 <?php echo $i===0?'bg-warning bg-opacity-10 border border-warning border-opacity-25':''; ?>">
                    <div class="top-lawyer-rank <?php echo $i===0?'bg-warning text-dark':'bg-light text-muted'; ?>"><?php echo $i+1; ?></div>
                    <div class="flex-grow-1"><div class="fw-bold text-dark"><?php echo htmlspecialchars($lw['name']); ?></div><small class="text-muted"><?php echo htmlspecialchars($lw['specialization']); ?></small></div>
                    <div class="text-end"><div class="fw-bold text-success">&#8377;<?php echo number_format($lw['earnings'],0); ?></div><small class="text-muted"><?php echo $lw['tx_count']; ?> payments</small></div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center text-muted py-4"><i class="fa-solid fa-chart-bar fs-3 mb-2 d-block"></i>No payment data for this period.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card card p-4 h-100">
                <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-star text-warning me-2"></i>Top 5 by Client Rating</h6>
                <?php if(!empty($top_rated)): foreach($top_rated as $i=>$lw): ?>
                <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded-3 <?php echo $i===0?'bg-warning bg-opacity-10 border border-warning border-opacity-25':''; ?>">
                    <div class="top-lawyer-rank <?php echo $i===0?'bg-warning text-dark':'bg-light text-muted'; ?>"><?php echo $i+1; ?></div>
                    <div class="flex-grow-1"><div class="fw-bold text-dark"><?php echo htmlspecialchars($lw['name']); ?></div><small class="text-muted"><?php echo htmlspecialchars($lw['specialization']); ?></small></div>
                    <div class="text-end"><div class="fw-bold text-warning"><?php echo number_format($lw['avg_rating'],1); ?> <i class="fa-solid fa-star fa-xs"></i></div><small class="text-muted"><?php echo $lw['review_count']; ?> reviews</small></div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center text-muted py-4"><i class="fa-solid fa-star fs-3 mb-2 d-block"></i>No rating data for this period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Transactions Table -->
    <div class="report-section-title"><i class="fa-solid fa-receipt me-2 text-warning"></i>Recent Payment Transactions <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">(Selected Period)</span></div>
    <div class="chart-card card p-4 mb-5">
        <?php if(empty($all_payments)): ?>
            <div class="text-center text-muted py-5"><i class="fa-solid fa-receipt fs-1 mb-3 d-block opacity-25"></i>No payment transactions found for this period.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Transaction ID</th><th>Client</th><th>Advocate</th><th>Method</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($all_payments as $i=>$pay): ?>
                    <tr>
                        <td class="text-muted small"><?php echo $i+1; ?></td>
                        <td><span class="fw-bold text-dark small"><?php echo htmlspecialchars($pay['transaction_id']); ?></span></td>
                        <td><?php echo htmlspecialchars($pay['client_name']); ?></td>
                        <td>Adv. <?php echo htmlspecialchars($pay['lawyer_name']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                        <td class="fw-bold text-success">&#8377;<?php echo number_format($pay['amount'],2); ?></td>
                        <td><small class="text-muted"><?php echo date('d M Y, h:i A',strtotime($pay['payment_date'])); ?></small></td>
                        <td><span class="badge bg-success text-white rounded-pill px-3"><?php echo $pay['payment_status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Switch Filter Mode
function switchMode(m) {
    if (m === 'single') {
        document.getElementById('form-single').style.display = 'block';
        document.getElementById('form-range').style.display = 'none';
    } else {
        document.getElementById('form-single').style.display = 'none';
        document.getElementById('form-range').style.display = 'block';
    }
}

// Flatpickr setup
flatpickr("#sel_date", {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: function(selectedDates, dateStr, instance) {
        // Auto-submit form immediately upon selecting a date
        document.getElementById('singleForm').submit();
    }
});

flatpickr("#date_from", { dateFormat: "Y-m-d", allowInput: true });
flatpickr("#date_to",   { dateFormat: "Y-m-d", allowInput: true });

// Presets
const today = new Date();
function fmt(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }

document.querySelectorAll('.preset-btn').forEach(btn=>{
    btn.addEventListener('click',function(){
        document.querySelectorAll('.preset-btn').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        let from, to=fmt(today);
        const p=this.dataset.preset;
        if(p==='today'){ from=fmt(today); }
        else if(p==='week'){ let d=new Date(today); d.setDate(today.getDate()-today.getDay()); from=fmt(d); }
        else if(p==='month'){ from=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01'; }
        else if(p==='last3'){ let d=new Date(today); d.setMonth(today.getMonth()-3); from=fmt(d); }
        else if(p==='last6'){ let d=new Date(today); d.setMonth(today.getMonth()-6); from=fmt(d); }
        else if(p==='year') { from=today.getFullYear()+'-01-01'; }
        document.getElementById('date_from').value=from;
        document.getElementById('date_to').value=to;
    });
});

(function(){
    const from=document.getElementById('date_from').value;
    const to=document.getElementById('date_to').value;
    const t=fmt(today);
    const ms=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01';
    if(from===t&&to===t) document.querySelector('[data-preset="today"]').classList.add('active');
    else if(from===ms&&to===t) document.querySelector('[data-preset="month"]').classList.add('active');
})();

// Charts
const gold='#d4af37',blue='#0d6efd',green='#198754',red='#dc3545',info='#0dcaf0';

new Chart(document.getElementById('apptChart'),{
    type:'bar',
    data:{ labels:<?php echo $chart_days; ?>, datasets:[{label:'Appointments',data:<?php echo $chart_appts_data; ?>,backgroundColor:'rgba(25,135,84,.75)',borderColor:green,borderWidth:2,borderRadius:8}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}
});

new Chart(document.getElementById('revenueChart'),{
    type:'line',
    data:{ labels:<?php echo $rev_days; ?>, datasets:[{label:'Revenue',data:<?php echo $rev_totals; ?>,borderColor:blue,backgroundColor:'rgba(13,110,253,.1)',fill:true,tension:.4,pointBackgroundColor:blue,pointRadius:5}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});

new Chart(document.getElementById('apptStatusChart'),{
    type:'doughnut',
    data:{labels:['Pending','Accepted','Approved','Completed','Cancelled/Rejected'],
          datasets:[{data:[<?php echo $appt_pending; ?>,<?php echo $appt_accepted; ?>,<?php echo $appt_approved; ?>,<?php echo $appt_completed; ?>,<?php echo $appt_cancelled; ?>],
                    backgroundColor:['#ffc107',info,green,blue,red],borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom'}}}
});

new Chart(document.getElementById('payMethodChart'),{
    type:'doughnut',
    data:{labels:<?php echo $pm_labels; ?>,datasets:[{data:<?php echo $pm_values; ?>,backgroundColor:[gold,blue,green,info,red],borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom'}}}
});

new Chart(document.getElementById('qaStatusChart'),{
    type:'doughnut',
    data:{labels:['Answered','Pending'],datasets:[{data:[<?php echo $qa_answered; ?>,<?php echo $qa_pending; ?>],backgroundColor:[green,'#ffc107'],borderWidth:2}]},
    options:{responsive:true,plugins:{legend:{position:'bottom'}}}
});
</script>
</body>
</html>
