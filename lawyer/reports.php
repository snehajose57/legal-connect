<?php
$page_title = "My Reports";
require_once __DIR__ . '/../config/db.php';
check_login('lawyer');
$lawyer_id = $_SESSION['user_id'];
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
    // All-time KPIs
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=?"); $s->execute([$lawyer_id]); $count_total=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Completed'"); $s->execute([$lawyer_id]); $count_completed_all=$s->fetchColumn();

    // ── Date-filtered KPIs ───────────────────────────────────────────────
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_appts=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Completed' AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_completed=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Pending' AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_pending=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Approved' AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_upcoming=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND (status='Cancelled' OR status='Rejected') AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_cancelled=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Accepted' AND DATE(appointment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_accepted=$s->fetchColumn();
    $s=$conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE lawyer_id=? AND payment_status='Completed' AND DATE(payment_date) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $total_revenue=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id=? AND DATE(created_at) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_qa=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id=? AND status='Answered' AND DATE(created_at) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $count_qa_answered=$s->fetchColumn();
    $s=$conn->prepare("SELECT COALESCE(AVG(rating),0) as avg_rating, COUNT(feedback_id) as total FROM feedbacks WHERE lawyer_id=? AND DATE(created_at) BETWEEN ? AND ?"); $s->execute([$lawyer_id,$qfrom,$qto]); $rating_data=$s->fetch(); $avg_rating=$rating_data['avg_rating']; $review_count=$rating_data['total'];

    // Daily appointments for chart
    $s=$conn->prepare("SELECT DATE_FORMAT(appointment_date,'%d %b') as day_label,DATE(appointment_date) as day_key,COUNT(*) as total FROM appointments WHERE lawyer_id=? AND DATE(appointment_date) BETWEEN ? AND ? GROUP BY day_key,day_label ORDER BY day_key ASC");
    $s->execute([$lawyer_id,$qfrom,$qto]); $daily_appts=$s->fetchAll();

    // Daily earnings for chart
    $s=$conn->prepare("SELECT DATE_FORMAT(payment_date,'%d %b') as day_label,DATE(payment_date) as day_key,COALESCE(SUM(amount),0) as total FROM payments WHERE lawyer_id=? AND payment_status='Completed' AND DATE(payment_date) BETWEEN ? AND ? GROUP BY day_key,day_label ORDER BY day_key ASC");
    $s->execute([$lawyer_id,$qfrom,$qto]); $daily_earnings=$s->fetchAll();

    // Rating distribution
    $rating_dist=[];
    for($r=5;$r>=1;$r--){
        $rs=$conn->prepare("SELECT COUNT(*) FROM feedbacks WHERE lawyer_id=? AND rating=? AND DATE(created_at) BETWEEN ? AND ?");
        $rs->execute([$lawyer_id,$r,$qfrom,$qto]);
        $rating_dist[$r]=$rs->fetchColumn();
    }

    // Recent payments (date-filtered)
    $s=$conn->prepare("SELECT p.*,u.name as client_name FROM payments p JOIN users u ON p.user_id=u.user_id WHERE p.lawyer_id=? AND DATE(p.payment_date) BETWEEN ? AND ? ORDER BY p.payment_date DESC LIMIT 50");
    $s->execute([$lawyer_id,$qfrom,$qto]); $my_payments=$s->fetchAll();

    // Feedbacks (date-filtered)
    $s=$conn->prepare("SELECT f.*,u.name as client_name FROM feedbacks f JOIN users u ON f.user_id=u.user_id WHERE f.lawyer_id=? AND DATE(f.created_at) BETWEEN ? AND ? ORDER BY f.created_at DESC LIMIT 30");
    $s->execute([$lawyer_id,$qfrom,$qto]); $my_feedbacks=$s->fetchAll();

    // Q&A list (date-filtered)
    $s=$conn->prepare("SELECT q.*,u.name as client_name FROM legal_questions q JOIN users u ON q.user_id=u.user_id WHERE q.lawyer_id=? AND DATE(q.created_at) BETWEEN ? AND ? ORDER BY q.created_at DESC LIMIT 30");
    $s->execute([$lawyer_id,$qfrom,$qto]); $qa_list=$s->fetchAll();

    // Sidebar badges
    $s=$conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id=? AND status='Pending'"); $s->execute([$lawyer_id]); $nav_pending=$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id=? AND status='Pending'"); $s->execute([$lawyer_id]); $count_pending_qa=$s->fetchColumn();

    // ── Get Full Booking/Appointment Details for Selected Date or Date Range ──
    $s=$conn->prepare("
        SELECT a.*, u.name as client_name, u.phone as client_phone, u.email as client_email, u.address as client_address,
               p.amount, p.payment_method, p.payment_status, p.transaction_id,
               c.case_number, c.case_title, c.status as case_status
        FROM appointments a
        JOIN users u ON a.user_id=u.user_id
        LEFT JOIN payments p ON p.appointment_id=a.appointment_id
        LEFT JOIN cases c ON c.appointment_id=a.appointment_id
        WHERE a.lawyer_id = ? AND DATE(a.appointment_date) BETWEEN ? AND ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $s->execute([$lawyer_id, $qfrom, $qto]);
    $day_appointments = $s->fetchAll();

} catch (PDOException $e) { $error_msg="Error: ".$e->getMessage(); }

$chart_days=json_encode(array_column($daily_appts,'day_label'));
$chart_appts=json_encode(array_column($daily_appts,'total'));
$earn_days=json_encode(array_column($daily_earnings,'day_label'));
$earn_totals=json_encode(array_column($daily_earnings,'total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports | Legal Connect Lawyer</title>
    <meta name="description" content="Date-wise performance reports for Legal Connect advocates.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
    <style>
        /* ── Filter Bar ── */
        .filter-bar{background:linear-gradient(135deg,#0a192f 0%,#0d2b4e 100%);border-radius:16px;padding:22px 26px;margin-bottom:28px;box-shadow:0 6px 28px rgba(10,25,47,.2);}
        .filter-label{color:rgba(255,255,255,.65);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px;}
        .mode-tabs{display:flex;gap:0;background:rgba(255,255,255,.07);border-radius:10px;padding:3px;margin-bottom:18px;width:fit-content;}
        .mode-tab{background:transparent;border:none;color:rgba(255,255,255,.55);font-size:.82rem;font-weight:600;padding:7px 18px;border-radius:8px;cursor:pointer;transition:all .2s;}
        .mode-tab.active{background:#d4af37;color:#0a192f;}
        /* Calendar date input wrapper */
        .cal-input-wrap{position:relative;display:flex;align-items:center;}
        .cal-input-wrap .form-control{border-radius:10px;border:1.5px solid rgba(212,175,55,.35);background:rgba(255,255,255,.07);color:#fff;font-size:.9rem;font-weight:600;padding:8px 38px 8px 12px;min-width:160px;cursor:pointer;}
        .cal-input-wrap .form-control:focus{background:rgba(255,255,255,.13);border-color:#d4af37;color:#fff;box-shadow:0 0 0 3px rgba(212,175,55,.18);}
        .cal-input-wrap .form-control::placeholder{color:rgba(255,255,255,.38);}
        .cal-input-wrap .cal-icon{position:absolute;right:11px;color:#d4af37;font-size:1rem;pointer-events:none;}
        /* Presets */
        .preset-btn{background:rgba(255,255,255,.08);border:1.5px solid rgba(212,175,55,.25);color:rgba(255,255,255,.75);font-size:.78rem;font-weight:600;border-radius:8px;padding:5px 13px;cursor:pointer;transition:all .2s;white-space:nowrap;}
        .preset-btn:hover,.preset-btn.active{background:#d4af37;border-color:#d4af37;color:#0a192f;}
        .btn-filter-apply{background:linear-gradient(135deg,#d4af37,#b8942a);border:none;border-radius:10px;color:#0a192f;font-weight:700;font-size:.9rem;padding:8px 22px;transition:transform .15s,box-shadow .15s;white-space:nowrap;}
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
        .star-bar-label{width:45px;font-size:.8rem;font-weight:600;}
        .star-bar-track{flex:1;height:10px;background:#f0f0f0;border-radius:5px;overflow:hidden;}
        .star-bar-fill{height:100%;background:#d4af37;border-radius:5px;}
        .star-count{width:30px;font-size:.8rem;text-align:right;color:#888;}
        /* Single-day booking details */
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
        <div class="sidebar-header">
            <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Lawyer Portal</h4>
        </div>
        <nav class="nav flex-column py-3">
            <a class="nav-link" href="/legalconnect/lawyer/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/lawyer/requests.php"><i class="fa-solid fa-calendar-days"></i> Client Requests <span class="badge bg-danger ms-auto"><?php echo $nav_pending; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/support.php"><i class="fa-solid fa-circle-question"></i> Support Q&amp;A <span class="badge bg-danger ms-auto"><?php echo $count_pending_qa; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/chat.php"><i class="fa-solid fa-comments"></i> Client Chat</a>
            <a class="nav-link active" href="/legalconnect/lawyer/reports.php"><i class="fa-solid fa-chart-pie"></i> My Reports</a>
            <a class="nav-link" href="/legalconnect/lawyer/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Details</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie text-warning me-2"></i>My Performance Reports</h2>
                <p class="text-muted mb-0">Select a date to instantly view booking details, or filter by range for trends.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Advocate: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <?php if(isset($_SESSION['profile_image'])&&$_SESSION['profile_image']!=='default_lawyer.png'): ?>
                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-2 shadow-sm" style="width:32px;height:32px;object-fit:cover;">
                <?php else: ?>
                    <div class="avatar-circle bg-warning text-dark"><?php echo substr($_SESSION['name'],0,1); ?></div>
                <?php endif; ?>
            </div>
        </header>
        <?php if($error_msg!=="")echo '<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>'.$error_msg.'</div>'; ?>

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
                    <span class="date-range-badge"><i class="fa-regular fa-calendar me-1"></i><?php echo $dt_from_label; ?> &nbsp;&rarr;&nbsp; <?php echo $dt_to_label; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Booking Details Section (Mode: Single Date or Date Range) ── -->
        <div class="report-section-title mb-4">
            <i class="fa-solid fa-calendar-check text-warning me-2"></i>
            <?php echo $mode === 'single' ? "My Consultations &amp; Bookings ($sel_label)" : "My Scheduled Bookings &amp; Consultations ($dt_from_label to $dt_to_label)"; ?>
        </div>
        
        <?php if (empty($day_appointments)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-5 bg-white">
                <i class="fa-regular fa-calendar-times text-muted fs-1 mb-3"></i>
                <h5 class="text-dark fw-bold mb-1">No Consultations</h5>
                <p class="text-muted mb-0">You have no clients scheduled in the selected period.</p>
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
                                    <h6 class="text-muted small text-uppercase fw-bold mb-2">Client Info (Who Booked)</h6>
                                    <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user text-info me-2 fs-6"></i><?php echo htmlspecialchars($appt['client_name']); ?></h5>
                                    <p class="text-muted mb-1 small"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_phone']); ?></p>
                                    <p class="text-muted mb-1 small"><i class="fa-solid fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_email']); ?></p>
                                    <p class="text-muted mb-0 small"><i class="fa-solid fa-map-marker-alt me-2 text-secondary"></i><?php echo htmlspecialchars($appt['client_address']); ?></p>
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
                                    <h6 class="text-muted small text-uppercase fw-bold mb-2">Earnings &amp; Payment</h6>
                                    <?php if ($appt['transaction_id']): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-success">&#8377;<?php echo number_format($appt['amount'], 2); ?></span>
                                            <span class="badge bg-light border text-dark"><?php echo htmlspecialchars($appt['payment_method']); ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-2">Txn: <code class="text-dark"><?php echo htmlspecialchars($appt['transaction_id']); ?></code></small>
                                    <?php else: ?>
                                        <span class="text-danger small"><i class="fa-solid fa-circle-exclamation me-1"></i>Awaiting payment details</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="report-section-title mb-3">
            <i class="fa-solid fa-chart-bar me-2 text-warning"></i>My Key Metrics
            <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">
                (<?php echo $mode==='single' ? "On $sel_label" : "$dt_from_label to $dt_to_label"; ?>)
            </span>
        </div>
        <div class="row g-3 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-success text-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-white">Total Earnings</div><div class="kpi-value">&#8377;<?php echo number_format($total_revenue,0); ?></div><small class="opacity-75"><?php echo count($my_payments); ?> transactions</small></div>
                    <i class="fa-solid fa-indian-rupee-sign kpi-icon"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-primary text-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-white">Consultations</div><div class="kpi-value"><?php echo $count_appts; ?></div><small class="opacity-75"><?php echo $count_completed; ?> completed</small></div>
                    <i class="fa-solid fa-calendar-check kpi-icon"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-warning text-dark d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label" style="opacity:.65">My Rating</div><div class="kpi-value"><?php echo number_format($avg_rating,1); ?> <small class="fs-5 fw-normal" style="opacity:.65">/ 5</small></div><small style="opacity:.7"><?php echo $review_count; ?> reviews</small></div>
                    <i class="fa-solid fa-star kpi-icon"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-info text-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-white">Q&amp;A Queries</div><div class="kpi-value"><?php echo $count_qa; ?></div><small class="opacity-75"><?php echo $count_qa_answered; ?> answered</small></div>
                    <i class="fa-solid fa-circle-question kpi-icon"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-muted">Upcoming</div><div class="kpi-value text-dark"><?php echo $count_upcoming; ?></div><small class="text-muted">Approved consults</small></div>
                    <i class="fa-solid fa-clock kpi-icon text-success"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-muted">Pending Requests</div><div class="kpi-value text-warning"><?php echo $count_pending; ?></div><small class="text-muted">Awaiting action</small></div>
                    <i class="fa-solid fa-hourglass-half kpi-icon text-warning"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-muted">Completed</div><div class="kpi-value text-success"><?php echo $count_completed; ?></div><small class="text-muted">In selected period</small></div>
                    <i class="fa-solid fa-circle-check kpi-icon text-success"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card bg-white d-flex justify-content-between align-items-center">
                    <div><div class="kpi-label text-muted">Cancelled</div><div class="kpi-value text-danger"><?php echo $count_cancelled; ?></div><small class="text-muted">In selected period</small></div>
                    <i class="fa-solid fa-circle-xmark kpi-icon text-danger"></i>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="report-section-title"><i class="fa-solid fa-chart-line me-2 text-warning"></i>Performance Trends <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">(Selected Period)</span></div>
        <div class="row g-4 mb-5">
            <div class="col-lg-6"><div class="chart-card card p-4 h-100"><h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-calendar-days text-success me-2"></i>Daily Consultations</h6><canvas id="apptChart" height="200"></canvas></div></div>
            <div class="col-lg-6"><div class="chart-card card p-4 h-100"><h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-indian-rupee-sign text-success me-2"></i>Daily Earnings (&#8377;)</h6><canvas id="earningsChart" height="200"></canvas></div></div>
            <div class="col-lg-6">
                <div class="chart-card card p-4 h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-half-stroke text-warning me-2"></i>Consultation Status</h6>
                    <canvas id="statusChart" height="240"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card card p-4 h-100">
                    <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-star text-warning me-2"></i>Rating Distribution</h6>
                    <?php for($r=5;$r>=1;$r--): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="star-bar-label text-muted"><?php echo $r; ?> <i class="fa-solid fa-star text-warning fa-xs"></i></div>
                        <div class="star-bar-track"><div class="star-bar-fill" style="width:<?php echo ($review_count>0?round($rating_dist[$r]/$review_count*100):0); ?>%"></div></div>
                        <div class="star-count"><?php echo $rating_dist[$r]; ?></div>
                    </div>
                    <?php endfor; ?>
                    <div class="text-center mt-3">
                        <span class="fw-bold text-warning fs-4"><?php echo number_format($avg_rating,1); ?></span>
                        <span class="text-muted small"> / 5.0 &middot; <?php echo $review_count; ?> reviews</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Records -->
        <div class="report-section-title"><i class="fa-solid fa-list-check me-2 text-warning"></i>Detailed Records <span class="fw-normal text-muted" style="font-size:.82rem;text-transform:none;">(Selected Period)</span></div>
        <div class="chart-card card p-4 mb-5">
            <ul class="nav nav-tabs mb-4" id="lawyerReportTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active fw-bold" id="pay-tab" data-bs-toggle="tab" data-bs-target="#pay-content" type="button"><i class="fa-solid fa-credit-card me-2 text-success"></i>Earnings History</button></li>
                <li class="nav-item"><button class="nav-link fw-bold" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa-content" type="button"><i class="fa-solid fa-clipboard-question me-2 text-info"></i>Q&amp;A Log</button></li>
                <li class="nav-item"><button class="nav-link fw-bold" id="fb-tab" data-bs-toggle="tab" data-bs-target="#fb-content" type="button"><i class="fa-solid fa-star me-2 text-warning"></i>Client Feedback</button></li>
            </ul>
            <div class="tab-content" id="lawyerReportTabsContent">
                <!-- Earnings -->
                <div class="tab-pane fade show active" id="pay-content">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>#</th><th>Transaction ID</th><th>Client</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if(!empty($my_payments)): foreach($my_payments as $i=>$pay): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $i+1; ?></td>
                                    <td><span class="fw-bold text-dark small"><?php echo htmlspecialchars($pay['transaction_id']); ?></span></td>
                                    <td><?php echo htmlspecialchars($pay['client_name']); ?></td>
                                    <td class="fw-bold text-success">&#8377;<?php echo number_format($pay['amount'],2); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                                    <td><small class="text-muted"><?php echo date('d M Y, h:i A',strtotime($pay['payment_date'])); ?></small></td>
                                    <td><span class="badge bg-success rounded-pill px-3"><?php echo $pay['payment_status']; ?></span></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No payment records in this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Q&A Log -->
                <div class="tab-pane fade" id="qa-content">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>#</th><th>Client</th><th>Subject</th><th>Question</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php if(!empty($qa_list)): foreach($qa_list as $i=>$q): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $i+1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($q['client_name']); ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($q['subject']); ?></td>
                                    <td><small class="text-muted d-inline-block text-truncate" style="max-width:250px"><?php echo htmlspecialchars($q['question']); ?></small></td>
                                    <td><span class="badge rounded-pill <?php echo $q['status']==='Answered'?'bg-success':'bg-warning text-dark'; ?> px-3"><?php echo $q['status']; ?></span></td>
                                    <td><small class="text-muted"><?php echo date('d M Y',strtotime($q['created_at'])); ?></small></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No Q&amp;A records in this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Feedback -->
                <div class="tab-pane fade" id="fb-content">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>#</th><th>Client</th><th>Rating</th><th>Comments</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php if(!empty($my_feedbacks)): foreach($my_feedbacks as $i=>$fb): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $i+1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($fb['client_name']); ?></td>
                                    <td>
                                        <span class="text-warning"><?php for($s=1;$s<=5;$s++) echo '<i class="fa-'.($s<=$fb['rating']?'solid':'regular').' fa-star fa-sm"></i>'; ?></span>
                                        <span class="ms-1 fw-bold text-dark"><?php echo $fb['rating']; ?></span>
                                    </td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($fb['comments']??'—'); ?></td>
                                    <td><small class="text-muted"><?php echo date('d M Y',strtotime($fb['created_at'])); ?></small></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No feedback in this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
        // Auto-submit single date form instantly
        document.getElementById('singleForm').submit();
    }
});

flatpickr("#date_from",{dateFormat:"Y-m-d",allowInput:true});
flatpickr("#date_to",{dateFormat:"Y-m-d",allowInput:true});

// Presets
const today=new Date();
function fmt(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
document.querySelectorAll('.preset-btn').forEach(btn=>{
    btn.addEventListener('click',function(){
        document.querySelectorAll('.preset-btn').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        let from,to=fmt(today);
        const p=this.dataset.preset;
        if(p==='today'){from=fmt(today);}
        else if(p==='week'){let d=new Date(today);d.setDate(today.getDate()-today.getDay());from=fmt(d);}
        else if(p==='month'){from=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01';}
        else if(p==='last3'){let d=new Date(today);d.setMonth(today.getMonth()-3);from=fmt(d);}
        else if(p==='last6'){let d=new Date(today);d.setMonth(today.getMonth()-6);from=fmt(d);}
        else if(p==='year'){from=today.getFullYear()+'-01-01';}
        document.getElementById('date_from').value=from;
        document.getElementById('date_to').value=to;
    });
});

(function(){
    const from=document.getElementById('date_from').value,to=document.getElementById('date_to').value,t=fmt(today);
    const ms=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-01';
    if(from===t&&to===t)document.querySelector('[data-preset="today"]').classList.add('active');
    else if(from===ms&&to===t)document.querySelector('[data-preset="month"]').classList.add('active');
})();

// Charts
const blue='#0d6efd',green='#198754',red='#dc3545',info='#0dcaf0',gold='#d4af37';
new Chart(document.getElementById('apptChart'),{type:'bar',data:{labels:<?php echo $chart_days;?>,datasets:[{label:'Consultations',data:<?php echo $chart_appts;?>,backgroundColor:'rgba(13,110,253,.75)',borderColor:blue,borderWidth:2,borderRadius:8}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(document.getElementById('earningsChart'),{type:'line',data:{labels:<?php echo $earn_days;?>,datasets:[{label:'Earnings',data:<?php echo $earn_totals;?>,borderColor:green,backgroundColor:'rgba(25,135,84,.1)',fill:true,tension:.4,pointBackgroundColor:green,pointRadius:5}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:['Pending','Accepted','Upcoming','Completed','Cancelled/Rejected'],datasets:[{data:[<?php echo $count_pending;?>,<?php echo $count_accepted;?>,<?php echo $count_upcoming;?>,<?php echo $count_completed;?>,<?php echo $count_cancelled;?>],backgroundColor:['#ffc107',info,blue,green,red],borderWidth:2}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
</script>
</body>
</html>
