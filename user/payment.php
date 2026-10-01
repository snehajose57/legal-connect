<?php
$page_title = "Consultation Payment";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

$appt_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : null;
$appointment = null;

if ($appt_id) {
    try {
        $stmt = $conn->prepare("SELECT a.*, l.name as lawyer_name, l.specialization 
                                FROM appointments a 
                                JOIN lawyers l ON a.lawyer_id = l.lawyer_id 
                                WHERE a.appointment_id = ? AND a.user_id = ?");
        $stmt->execute([$appt_id, $user_id]);
        $appointment = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Error loading appointment: " . $e->getMessage();
    }
}

if (!$appointment) {
    $_SESSION['error'] = "Invalid appointment details or unauthorized access.";
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}

// Ensure the lawyer has accepted this booking before payment
if ($appointment['status'] !== 'Accepted') {
    $_SESSION['error'] = "This consultation booking is not ready for payment. Current Status: " . htmlspecialchars($appointment['status']);
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}

// Check if already paid
try {
    $checkPaid = $conn->prepare("SELECT COUNT(*) FROM payments WHERE appointment_id = ?");
    $checkPaid->execute([$appt_id]);
    if ($checkPaid->fetchColumn() > 0) {
        $_SESSION['success'] = "This consultation has already been paid and approved.";
        header("Location: /legalconnect/user/dashboard.php");
        exit();
    }
} catch (PDOException $e) {}

// Generate mock transaction ID
$txn_id = 'TXN_' . strtoupper(uniqid());

// Handle payment processing
if (isset($_POST['process_payment'])) {
    $payment_method = sanitize($_POST['payment_method']);
    $amount = 300.00; // Fixed Consultation fee

    try {
        $conn->beginTransaction();

        // 1. Insert into Payments Table
        $ins = $conn->prepare("INSERT INTO payments (user_id, lawyer_id, appointment_id, amount, payment_method, transaction_id, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'Completed')");
        $ins->execute([$user_id, $appointment['lawyer_id'], $appt_id, $amount, $payment_method, $txn_id]);

        // 2. Update Appointment Status to 'Approved'
        $upd = $conn->prepare("UPDATE appointments SET status = 'Approved' WHERE appointment_id = ?");
        $upd->execute([$appt_id]);

        $conn->commit();

        $_SESSION['success'] = "Payment processed successfully! Your appointment is now approved. Transaction ID: " . $txn_id;
        header("Location: /legalconnect/user/dashboard.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_msg = "Transaction failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Checkout | Legal Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=4" rel="stylesheet">
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
                <h2 class="fw-bold text-dark mb-1">Billing & Payment Checkout</h2>
                <p class="text-muted mb-0">Secure online gateway checkouts to confirm Advocate consult scheduling.</p>
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
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Booking details checklist -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-file-invoice text-warning me-2"></i>Billing Summary</h5>
                    
                    <div class="p-3 bg-light rounded mb-4">
                        <div class="fw-bold text-dark mb-1">Advocate: Adv. <?php echo htmlspecialchars($appointment['lawyer_name']); ?></div>
                        <span class="badge bg-warning text-dark mb-3"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                        <div class="text-muted small mb-2"><i class="fa-solid fa-calendar me-2"></i>Date: <?php echo date('d M, Y', strtotime($appointment['appointment_date'])); ?></div>
                        <div class="text-muted small"><i class="fa-solid fa-clock me-2"></i>Time Slot: <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <span class="fw-bold text-dark fs-5">Amount Due:</span>
                        <span class="fw-bold text-success fs-4">&#8377;300.00</span>
                    </div>
                </div>
            </div>

            <!-- Right checkout options -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                    <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-shield-halved text-success me-2"></i>Payment Gateway</h4>
                    
                    <form method="POST" action="" id="paymentForm" onsubmit="return lcPaymentSubmit(this)" novalidate>
                        <!-- Payment Method Radio Buttons -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Payment Option</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check border p-3 rounded flex-grow-1">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="payCard" value="Credit/Debit Card" checked onclick="togglePaymentForms('card')">
                                    <label class="form-check-label fw-bold text-dark" for="payCard">
                                        <i class="fa-solid fa-credit-card text-primary me-1"></i>Card
                                    </label>
                                </div>
                                <div class="form-check border p-3 rounded flex-grow-1">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="payUPI" value="UPI" onclick="togglePaymentForms('upi')">
                                    <label class="form-check-label fw-bold text-dark" for="payUPI">
                                        <i class="fa-solid fa-mobile-screen-button text-success me-1"></i>UPI
                                    </label>
                                </div>
                                <div class="form-check border p-3 rounded flex-grow-1">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="payNet" value="Net Banking" onclick="togglePaymentForms('net')">
                                    <label class="form-check-label fw-bold text-dark" for="payNet">
                                        <i class="fa-solid fa-building-columns text-info me-1"></i>Net Banking
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Card Fields -->
                        <div id="cardFields" class="payment-method-fields mb-4">
                            <div class="mb-3">
                                <label for="card_num" class="form-label fw-bold text-muted small">Card Number</label>
                                <input type="text" class="form-control card-req" id="card_num" placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric" autocomplete="cc-number">
                                <div class="lc-feedback" id="fb-card-num"></div>
                            </div>
                            <div class="mb-3">
                                <label for="card_holder" class="form-label fw-bold text-muted small">Card Holder Name</label>
                                <input type="text" class="form-control card-req" id="card_holder" placeholder="Name as on card" autocomplete="cc-name">
                                <div class="lc-feedback" id="fb-card-holder"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="card_exp" class="form-label fw-bold text-muted small">Expiration Date</label>
                                    <input type="text" class="form-control card-req" id="card_exp" placeholder="MM/YY" maxlength="5" inputmode="numeric" autocomplete="cc-exp">
                                    <div class="lc-feedback" id="fb-card-exp"></div>
                                </div>
                                <div class="col-6">
                                    <label for="card_cvv" class="form-label fw-bold text-muted small">CVV Code</label>
                                    <input type="password" class="form-control card-req" id="card_cvv" placeholder="123" maxlength="3" inputmode="numeric" autocomplete="cc-csc">
                                    <div class="lc-feedback" id="fb-card-cvv"></div>
                                </div>
                            </div>
                            <!-- Hidden field carries raw digits (spaces stripped) to backend -->
                            <input type="hidden" id="card_num_raw" name="card_number_raw">
                        </div>

                        <!-- UPI Fields -->
                        <div id="upiFields" class="payment-method-fields mb-4" style="display: none;">
                            <div class="mb-3">
                                <label for="upi_id" class="form-label fw-bold text-muted small">VPA / UPI ID</label>
                                <input type="text" class="form-control" id="upi_id" placeholder="e.g. client@ybl">
                                <div class="form-text">Type your virtual payment address linked to GooglePay/PhonePe.</div>
                            </div>
                        </div>

                        <!-- Net Banking Fields -->
                        <div id="netFields" class="payment-method-fields mb-4" style="display: none;">
                            <div class="mb-3">
                                <label for="bank_name" class="form-label fw-bold text-muted small">Select Bank Account</label>
                                <select class="form-select" id="bank_name">
                                    <option value="">Choose bank...</option>
                                    <option value="SBI">State Bank of India</option>
                                    <option value="HDFC">HDFC Bank</option>
                                    <option value="ICICI">ICICI Bank</option>
                                    <option value="AXIS">Axis Bank</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-muted small mb-4">
                            <i class="fa-solid fa-lock text-success me-1"></i>Transactions are encrypted and simulated for sandbox testing.
                        </div>

                        <button type="submit" name="process_payment" class="btn btn-success w-100 rounded-pill py-3 fw-bold text-white fs-5 shadow-sm">
                            <i class="fa-solid fa-circle-check me-2"></i>Pay &#8377;300.00 Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Legal Connect Real-Time Validation -->
<script src="/legalconnect/assets/js/lc-validation.js"></script>
<script>
function togglePaymentForms(method) {
    const cardDiv = document.getElementById('cardFields');
    const upiDiv = document.getElementById('upiFields');
    const netDiv = document.getElementById('netFields');
    
    const cardInputs = document.querySelectorAll('.card-req');
    const upiInput = document.getElementById('upi_id');
    const netSelect = document.getElementById('bank_name');

    if (method === 'card') {
        cardDiv.style.display = 'block';
        upiDiv.style.display = 'none';
        netDiv.style.display = 'none';
        
        cardInputs.forEach(i => i.setAttribute('required', 'required'));
        upiInput.removeAttribute('required');
        netSelect.removeAttribute('required');
    } else if (method === 'upi') {
        cardDiv.style.display = 'none';
        upiDiv.style.display = 'block';
        netDiv.style.display = 'none';
        
        cardInputs.forEach(i => i.removeAttribute('required'));
        upiInput.setAttribute('required', 'required');
        netSelect.removeAttribute('required');
    } else if (method === 'net') {
        cardDiv.style.display = 'none';
        upiDiv.style.display = 'none';
        netDiv.style.display = 'block';
        
        cardInputs.forEach(i => i.removeAttribute('required'));
        upiInput.removeAttribute('required');
        netSelect.setAttribute('required', 'required');
    }
}
</script>
</body>
</html>
