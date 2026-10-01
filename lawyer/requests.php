<?php
$page_title = "Manage Client Requests";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('lawyer');

$lawyer_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Handle Lawyer Appointment Cancellation with Reason
if (isset($_POST['cancel_appointment'])) {
    $appt_id = (int)$_POST['appointment_id'];
    $reason  = sanitize($_POST['cancellation_reason']);

    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a cancellation reason.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT a.*, l.name as lawyer_name FROM appointments a JOIN lawyers l ON a.lawyer_id = l.lawyer_id WHERE a.appointment_id = ? AND a.lawyer_id = ?");
            $stmt->execute([$appt_id, $lawyer_id]);
            $appt = $stmt->fetch();

            if ($appt) {
                // Update status to 'Cancelled by Lawyer'
                $upd = $conn->prepare("UPDATE appointments SET status = 'Cancelled by Lawyer', cancellation_reason = ? WHERE appointment_id = ?");
                $upd->execute([$reason, $appt_id]);

                // Notify client
                $notif_title = "Appointment Cancelled";
                $notif_msg   = "Your appointment with Adv. " . $appt['lawyer_name'] . " scheduled for " . date('d M, Y', strtotime($appt['appointment_date'])) . " at " . date('h:i A', strtotime($appt['appointment_time'])) . " has been cancelled by the lawyer because " . $reason . ". The paid amount will be refunded within 2–3 working days. We apologize for the inconvenience.";

                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $notif_stmt->execute([$appt['user_id'], $notif_title, $notif_msg]);

                $_SESSION['success'] = "Appointment cancelled successfully and client notified regarding the 2–3 working days refund.";
            } else {
                $_SESSION['error'] = "Invalid consultation record.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    }
    header("Location: /legalconnect/lawyer/requests.php");
    exit();
}

// Handle GET Actions (Approve, Reject, Complete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $appt_id = (int)$_GET['id'];
    
    try {
        $check = $conn->prepare("SELECT lawyer_id FROM appointments WHERE appointment_id = ?");
        $check->execute([$appt_id]);
        $owner = $check->fetchColumn();
        
        if ($owner && (int)$owner === $lawyer_id) {
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE appointments SET status = 'Accepted' WHERE appointment_id = ?");
                $stmt->execute([$appt_id]);
                $_SESSION['success'] = "Consultation request accepted successfully! Awaiting client payment.";
            } elseif ($action === 'reject') {
                // Fetch appointment + client details for notification
                $rej_stmt = $conn->prepare("SELECT a.*, u.user_id as client_uid, l.name as lawyer_name FROM appointments a JOIN users u ON a.user_id = u.user_id JOIN lawyers l ON a.lawyer_id = l.lawyer_id WHERE a.appointment_id = ?");
                $rej_stmt->execute([$appt_id]);
                $rej_appt = $rej_stmt->fetch();

                $stmt = $conn->prepare("UPDATE appointments SET status = 'Rejected' WHERE appointment_id = ?");
                $stmt->execute([$appt_id]);

                // Notify client: no payment was made at Pending stage
                if ($rej_appt) {
                    $notif_msg = "Your consultation request with Adv. " . $rej_appt['lawyer_name']
                        . " scheduled for " . date('d M, Y', strtotime($rej_appt['appointment_date']))
                        . " at " . date('h:i A', strtotime($rej_appt['appointment_time']))
                        . " has been declined by the lawyer. No payment was made, so there is no charge. You may book another advocate from the Search Lawyers section.";
                    $rej_notif = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                    $rej_notif->execute([$rej_appt['client_uid'], "Consultation Request Declined", $notif_msg]);
                }

                $_SESSION['success'] = "Consultation request rejected. The client has been notified.";
            } elseif ($action === 'complete') {
                $dt_stmt = $conn->prepare("SELECT appointment_date, appointment_time, status FROM appointments WHERE appointment_id = ?");
                $dt_stmt->execute([$appt_id]);
                $appt_row = $dt_stmt->fetch();

                if ($appt_row && $appt_row['status'] === 'Approved') {
                    $appt_datetime = strtotime($appt_row['appointment_date'] . ' ' . $appt_row['appointment_time']);
                    if (time() >= $appt_datetime) {
                        $stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
                        $stmt->execute([$appt_id]);
                        $_SESSION['success'] = "Consultation marked as Completed.";
                    } else {
                        $_SESSION['error'] = "Cannot mark as completed before the session date and time has passed.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid action — appointment is not in Approved state.";
                }
            }
            header("Location: /legalconnect/lawyer/requests.php");
            exit();
        } else {
            $error_msg = "Access Denied: Invalid consultation record.";
        }
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// Fetch all requests
$requests = [];
try {
    $stmt = $conn->prepare("SELECT a.*, u.name as client_name, u.email as client_email, u.phone as client_phone FROM appointments a JOIN users u ON a.user_id = u.user_id WHERE a.lawyer_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    $stmt->execute([$lawyer_id]);
    $requests = $stmt->fetchAll();
    
    $pending_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_count->execute([$lawyer_id]);
    $count_pending = $pending_count->fetchColumn();

    $pending_qa = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_qa->execute([$lawyer_id]);
    $count_pending_qa = $pending_qa->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading list: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Client Requests | Legal Connect</title>
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
            <a class="nav-link" href="/legalconnect/lawyer/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link active" href="/legalconnect/lawyer/requests.php"><i class="fa-solid fa-calendar-days"></i> Client Requests <span class="badge bg-danger ms-auto"><?php echo $count_pending; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/support.php"><i class="fa-solid fa-circle-question"></i> Support Q&amp;A <span class="badge bg-danger ms-auto"><?php echo $count_pending_qa; ?></span></a>
            <a class="nav-link" href="/legalconnect/lawyer/chat.php"><i class="fa-solid fa-comments"></i> Client Chat</a>
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
                <h2 class="fw-bold text-dark mb-1">Client Consultation Requests</h2>
                <p class="text-muted mb-0">Review consultation bookings, approve requests, manage cancellations, and flag completed sessions.</p>
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
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Requests Directory Card -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-calendar-list text-warning me-2"></i>Consultations Log</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>LC ID</th>
                            <th>Client Name</th>
                            <th>Email &amp; Phone</th>
                            <th>Requested Schedule</th>
                            <th>Status</th>
                            <th class="text-center font-bold">Action Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>#LC-<?php echo $req['appointment_id']; ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($req['client_name']); ?></td>
                                    <td>
                                        <div class="small"><?php echo htmlspecialchars($req['client_email']); ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($req['client_phone']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo date('d M, Y', strtotime($req['appointment_date'])); ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-clock me-1 text-warning"></i><?php echo date('h:i A', strtotime($req['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-status <?php 
                                            if ($req['status'] === 'Pending') echo 'badge-pending';
                                            elseif ($req['status'] === 'Accepted') echo 'badge-accepted';
                                            elseif ($req['status'] === 'Approved') echo 'badge-approved';
                                            elseif ($req['status'] === 'Completed') echo 'badge-completed';
                                            else echo 'badge-cancelled';
                                        ?>"><?php echo $req['status']; ?></span>

                                        <?php if (!empty($req['cancellation_reason'])): ?>
                                            <small class="d-block text-danger mt-1" style="font-size:0.72rem;">
                                                <strong>Reason:</strong> <?php echo htmlspecialchars($req['cancellation_reason']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                            <?php if ($req['status'] === 'Pending'): ?>
                                                <a href="/legalconnect/lawyer/requests.php?action=approve&id=<?php echo $req['appointment_id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fa-solid fa-check me-1"></i>Accept
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $req['appointment_id']; ?>">
                                                    <i class="fa-solid fa-xmark me-1"></i>Cancel Appointment
                                                </button>
                                            <?php elseif ($req['status'] === 'Accepted'): ?>
                                                <span class="badge bg-light text-dark border small me-1 align-self-center"><i class="fa-solid fa-clock text-warning me-1"></i>Awaiting Payment</span>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $req['appointment_id']; ?>">
                                                    <i class="fa-solid fa-xmark me-1"></i>Cancel Appointment
                                                </button>
                                            <?php elseif ($req['status'] === 'Approved'):
                                                $appt_datetime = strtotime($req['appointment_date'] . ' ' . $req['appointment_time']);
                                                $session_passed = (time() >= $appt_datetime);
                                            ?>
                                                <?php if ($session_passed): ?>
                                                    <a href="/legalconnect/lawyer/requests.php?action=complete&id=<?php echo $req['appointment_id']; ?>"
                                                       class="btn btn-sm btn-primary"
                                                       onclick="return confirm('Mark this consultation as completed?');">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Mark Completed
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle me-1 align-self-center py-1">
                                                        <i class="fa-solid fa-hourglass-half me-1"></i>Session Upcoming
                                                    </span>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $req['appointment_id']; ?>">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>Cancel Appointment
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Lawyer Cancel Modal -->
                                <div class="modal fade" id="cancelModal<?php echo $req['appointment_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <div class="modal-header border-0 bg-danger text-white rounded-top-4">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Cancel Appointment</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="/legalconnect/lawyer/requests.php" method="POST">
                                                <div class="modal-body p-4">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $req['appointment_id']; ?>">
                                                    <p class="text-secondary small mb-3">
                                                        Cancelling this appointment with <strong><?php echo htmlspecialchars($req['client_name']); ?></strong> 
                                                        scheduled for <strong><?php echo date('d M, Y', strtotime($req['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($req['appointment_time'])); ?></strong>.
                                                    </p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small text-dark">Reason for Cancellation <span class="text-danger">*</span></label>
                                                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="e.g. Schedule conflict, court hearing overlap, personal emergency..." required></textarea>
                                                    </div>

                                                    <div class="alert alert-warning border-0 small mb-0">
                                                        <i class="fa-solid fa-circle-info me-1"></i>
                                                        The client will be notified immediately. If fee was paid, the client will be informed that the refund will be processed within <strong>2–3 working days</strong>.
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="cancel_appointment" class="btn btn-danger fw-bold">Confirm Cancellation</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No consultation requests filed for you yet.</td>
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
