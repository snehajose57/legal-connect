<?php
$page_title = "Book Appointment";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Validate lawyer selection
$lawyer_id = isset($_GET['lawyer_id']) ? (int)$_GET['lawyer_id'] : null;
$lawyer = null;

if ($lawyer_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM lawyers WHERE lawyer_id = ? AND status = 'Approved'");
        $stmt->execute([$lawyer_id]);
        $lawyer = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Error reading advocate data: " . $e->getMessage();
    }
}

if (!$lawyer) {
    $_SESSION['error'] = "Invalid lawyer selected or lawyer is not approved.";
    header("Location: /legalconnect/user/lawyers.php");
    exit();
}

// Fetch all existing booked slots for this lawyer (non-cancelled)
$booked_slots = [];
try {
    $slots_stmt = $conn->prepare(
        "SELECT appointment_date, appointment_time FROM appointments 
         WHERE lawyer_id = ? AND status NOT IN ('Cancelled', 'Rejected')
         ORDER BY appointment_date, appointment_time"
    );
    $slots_stmt->execute([$lawyer_id]);
    $raw_slots = $slots_stmt->fetchAll();
    // Group by date -> list of times
    foreach ($raw_slots as $row) {
        $d = $row['appointment_date'];
        $t = substr($row['appointment_time'], 0, 5); // HH:MM
        if (!isset($booked_slots[$d])) $booked_slots[$d] = [];
        $booked_slots[$d][] = $t;
    }
} catch (PDOException $e) {
    $error_msg = "Error reading availability: " . $e->getMessage();
}

// The 8 available time slots (HH:MM format)
$all_slots = ['09:00','10:00','11:00','12:00','14:00','15:00','16:00','17:00'];

// Fully booked dates = dates where all 8 slots are taken
$fully_booked_dates = [];
foreach ($booked_slots as $date => $times) {
    if (count(array_intersect($all_slots, $times)) >= count($all_slots)) {
        $fully_booked_dates[] = $date;
    }
}

// Handle Form Submission
if (isset($_POST['book_btn'])) {
    $appt_date = sanitize($_POST['appointment_date']);
    $appt_time = sanitize($_POST['appointment_time']);

    $today = date('Y-m-d');
    if ($appt_date < $today) {
        $error_msg = "Cannot book appointments in the past!";
    } else {
        try {
            // Strict single-client-per-slot check (any non-cancelled/rejected booking blocks the slot)
            $check = $conn->prepare(
                "SELECT COUNT(*) FROM appointments 
                 WHERE lawyer_id = ? AND appointment_date = ? AND appointment_time = ? 
                 AND status NOT IN ('Cancelled', 'Rejected')"
            );
            $check->execute([$lawyer_id, $appt_date, $appt_time]);
            $is_booked = $check->fetchColumn();

            if ($is_booked > 0) {
                $error_msg = "This time slot is already booked for Adv. " . htmlspecialchars($lawyer['name']) . ". Please select another slot.";
            } else {
                $insert = $conn->prepare(
                    "INSERT INTO appointments (user_id, lawyer_id, appointment_date, appointment_time, status) 
                     VALUES (?, ?, ?, ?, 'Pending')"
                );
                $insert->execute([$user_id, $lawyer_id, $appt_date, $appt_time]);

                $_SESSION['success'] = "Your consultation request has been submitted! Once the advocate accepts, you can proceed with payment.";
                header("Location: /legalconnect/user/dashboard.php");
                exit();
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Pass data to JS
$booked_slots_json       = json_encode($booked_slots);
$fully_booked_dates_json = json_encode($fully_booked_dates);
$today_js                = date('Y-m-d');
$current_hour_js         = (int)date('H');
$current_min_js          = (int)date('i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Legal Consultation | Legal Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
    <style>
        /* Slot availability legend */
        .slot-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.82rem; margin-top: 8px; }
        .slot-legend span { display: flex; align-items: center; gap: 5px; }
        .dot { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
        .dot-available  { background: #198754; }
        .dot-booked     { background: #dc3545; }
        .dot-passed     { background: #adb5bd; }

        /* Date input highlight when fully booked dates */
        #date-status-msg {
            font-size: 0.85rem;
            margin-top: 6px;
            min-height: 22px;
        }

        /* Style disabled options visually */
        select option:disabled { color: #aaa; font-style: italic; }

        /* Slot count badge */
        #slots-available-badge {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* Submit button disabled state */
        #book-submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Calendar date highlight (not supported in all browsers, graceful) */
        input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; }
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
            <a class="nav-link active" href="/legalconnect/user/lawyers.php"><i class="fa-solid fa-user-tie"></i> Search Lawyers</a>
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
                <h2 class="fw-bold text-dark mb-1">Book Consultation</h2>
                <p class="text-muted mb-0">Choose an available date and time to schedule your appointment.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Client Account</span>
                <div class="avatar-circle"><?php echo substr($_SESSION['name'], 0, 1); ?></div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Booking details row -->
        <div class="row g-4 justify-content-center">

            <!-- Selected Lawyer Details Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-4 rounded-4 bg-white h-100">
                    <div class="bg-light p-4 rounded-4 position-relative mb-4">
                        <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                            <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>"
                                 alt="Profile" class="rounded-circle border border-white border-3 shadow"
                                 style="width: 110px; height: 110px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-circle avatar-large mx-auto text-dark bg-warning mb-2"><?php echo substr($lawyer['name'], 0, 1); ?></div>
                        <?php endif; ?>
                        <h5 class="fw-bold mb-1 mt-2 text-dark">Adv. <?php echo htmlspecialchars($lawyer['name']); ?></h5>
                        <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                    </div>

                    <div class="text-start mb-4">
                        <h6 class="fw-bold text-dark mb-2">Qualifications & Background</h6>
                        <p class="text-muted small mb-2"><i class="fa-solid fa-graduation-cap text-warning me-2"></i><?php echo htmlspecialchars($lawyer['qualification']); ?></p>
                        <p class="text-muted small mb-2"><i class="fa-solid fa-briefcase text-warning me-2"></i><?php echo htmlspecialchars($lawyer['experience']); ?>+ Years of Practice</p>
                        <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot text-warning me-2"></i><?php echo htmlspecialchars($lawyer['address']); ?></p>
                    </div>

                    <!-- Availability Legend -->
                    <div class="border rounded-3 p-3 bg-light text-start mb-3">
                        <div class="fw-bold text-dark small mb-2"><i class="fa-solid fa-circle-info text-warning me-1"></i>Slot Legend</div>
                        <div class="slot-legend">
                            <span><span class="dot dot-available"></span>Available</span>
                            <span><span class="dot dot-booked"></span>Booked</span>
                            <span><span class="dot dot-passed"></span>Passed</span>
                        </div>
                    </div>

                    <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                        <i class="fa-solid fa-arrow-left me-1"></i>Select Different Lawyer
                    </a>
                </div>
            </div>

            <!-- Booking form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white h-100">
                    <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-calendar-days text-warning me-2"></i>Schedule Consultation</h4>
                    <p class="text-muted mb-4">Consultation Fee: <strong class="text-success fs-5">&#8377;300.00</strong> <span class="text-muted small">(paid after advocate accepts)</span></p>

                    <form method="POST" action="" id="bookingForm">

                        <!-- Date Picker -->
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label fw-bold">
                                <i class="fa-solid fa-calendar-check text-warning me-1"></i>Select Date
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                                <input type="date" class="form-control" id="appointment_date"
                                       name="appointment_date" required
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div id="date-status-msg" class="text-muted"></div>
                        </div>

                        <!-- Time Slot Picker -->
                        <div class="mb-4">
                            <label for="appointment_time" class="form-label fw-bold d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-clock text-warning me-1"></i>Select Time Slot</span>
                                <span id="slots-available-badge" class="badge bg-light text-muted border" style="display:none;"></span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-clock"></i></span>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <option value="">— Pick a date first —</option>
                                    <option value="09:00:00" data-hour="9"  data-label="09:00 AM – 10:00 AM">09:00 AM – 10:00 AM</option>
                                    <option value="10:00:00" data-hour="10" data-label="10:00 AM – 11:00 AM">10:00 AM – 11:00 AM</option>
                                    <option value="11:00:00" data-hour="11" data-label="11:00 AM – 12:00 PM">11:00 AM – 12:00 PM</option>
                                    <option value="12:00:00" data-hour="12" data-label="12:00 PM – 01:00 PM">12:00 PM – 01:00 PM</option>
                                    <option value="14:00:00" data-hour="14" data-label="02:00 PM – 03:00 PM">02:00 PM – 03:00 PM</option>
                                    <option value="15:00:00" data-hour="15" data-label="03:00 PM – 04:00 PM">03:00 PM – 04:00 PM</option>
                                    <option value="16:00:00" data-hour="16" data-label="04:00 PM – 05:00 PM">04:00 PM – 05:00 PM</option>
                                    <option value="17:00:00" data-hour="17" data-label="05:00 PM – 06:00 PM">05:00 PM – 06:00 PM</option>
                                </select>
                            </div>
                            <div id="slot-hint" class="text-muted small mt-1" style="display:none;">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                <span id="slot-hint-text"></span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" name="book_btn" id="book-submit-btn"
                                class="btn btn-gold w-100 rounded-pill py-3 fw-bold text-dark fs-5 shadow-sm" disabled>
                            <i class="fa-solid fa-paper-plane me-2"></i>Submit Booking Request
                        </button>
                        <p class="text-muted small text-center mt-2 mb-0">
                            <i class="fa-solid fa-lock me-1"></i>Your slot will be confirmed after advocate acceptance.
                        </p>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Data from PHP ──────────────────────────────────────────────────────────
const BOOKED_SLOTS       = <?php echo $booked_slots_json; ?>;
const FULLY_BOOKED_DATES = <?php echo $fully_booked_dates_json; ?>;
const TODAY              = "<?php echo $today_js; ?>";
const CURRENT_HOUR       = <?php echo $current_hour_js; ?>;
const CURRENT_MIN        = <?php echo $current_min_js; ?>;

// All defined slot hours (must match option data-hour)
const ALL_SLOT_HOURS = [9, 10, 11, 12, 14, 15, 16, 17];

const dateInput    = document.getElementById('appointment_date');
const timeSelect   = document.getElementById('appointment_time');
const submitBtn    = document.getElementById('book-submit-btn');
const dateMsg      = document.getElementById('date-status-msg');
const slotBadge    = document.getElementById('slots-available-badge');
const slotHint     = document.getElementById('slot-hint');
const slotHintText = document.getElementById('slot-hint-text');

// ── Main function: refresh slots when date changes ─────────────────────────
function refreshSlots() {
    const selectedDate = dateInput.value;
    if (!selectedDate) {
        resetSlots("— Pick a date first —");
        return;
    }

    const isToday         = (selectedDate === TODAY);
    const bookedToday     = BOOKED_SLOTS[selectedDate] || [];  // array of "HH:MM"
    const isFullyBooked   = FULLY_BOOKED_DATES.includes(selectedDate);

    // Reset all options
    const options = timeSelect.querySelectorAll('option[data-hour]');
    let availableCount = 0;
    let bookedCount    = 0;
    let passedCount    = 0;

    options.forEach(opt => {
        const hour     = parseInt(opt.dataset.hour);
        const label    = opt.dataset.label;
        const slotKey  = hour.toString().padStart(2, '0') + ':00'; // "09:00", "10:00" …

        const isBooked = bookedToday.includes(slotKey);
        const isPassed = isToday && (hour < CURRENT_HOUR || (hour === CURRENT_HOUR && CURRENT_MIN >= 0));

        opt.disabled = false;
        opt.style.color = '';

        if (isBooked) {
            opt.disabled = true;
            opt.textContent = label + '  ✗ Booked';
            opt.style.color = '#dc3545';
            bookedCount++;
        } else if (isPassed) {
            opt.disabled = true;
            opt.textContent = label + '  ✗ Passed';
            opt.style.color = '#adb5bd';
            passedCount++;
        } else {
            opt.textContent = label + '  ✓';
            opt.style.color = '#198754';
            availableCount++;
        }
    });

    // Reset time select to placeholder
    timeSelect.value = '';

    // Update badge
    slotBadge.style.display = '';
    if (availableCount > 0) {
        slotBadge.textContent  = availableCount + ' slot' + (availableCount > 1 ? 's' : '') + ' available';
        slotBadge.className    = 'badge bg-success-subtle text-success border border-success-subtle';
    } else {
        slotBadge.textContent = 'No slots available';
        slotBadge.className   = 'badge bg-danger-subtle text-danger border border-danger-subtle';
    }

    // Date status message
    if (isFullyBooked || availableCount === 0) {
        dateMsg.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i>All slots are booked for this date. Please choose another day.</span>';
    } else if (isToday) {
        dateMsg.innerHTML = '<span class="text-warning fw-semibold"><i class="fa-solid fa-clock me-1"></i>Today selected — past time slots are disabled.</span>';
    } else {
        dateMsg.innerHTML = '<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>' + availableCount + ' slot(s) available on this date.</span>';
    }

    // Hint below select
    if (bookedCount > 0 || passedCount > 0) {
        slotHint.style.display = '';
        let hints = [];
        if (bookedCount) hints.push(bookedCount + ' booked');
        if (passedCount) hints.push(passedCount + ' passed');
        slotHintText.textContent = hints.join(', ') + ' — shown as disabled options.';
    } else {
        slotHint.style.display = 'none';
    }

    updateSubmitButton();
}

// ── Enable submit only when a valid (non-disabled) time is selected ─────────
function updateSubmitButton() {
    const selected = timeSelect.value;
    if (!selected || !dateInput.value) {
        submitBtn.disabled = true;
        return;
    }
    const selectedOpt = timeSelect.querySelector('option[value="' + selected + '"]');
    submitBtn.disabled = !!(selectedOpt && selectedOpt.disabled);
}

function resetSlots(placeholder) {
    const options = timeSelect.querySelectorAll('option[data-hour]');
    options.forEach(opt => {
        opt.disabled    = false;
        opt.textContent = opt.dataset.label;
        opt.style.color = '';
    });
    timeSelect.value     = '';
    timeSelect.options[0].textContent = placeholder;
    slotBadge.style.display = 'none';
    slotHint.style.display  = 'none';
    dateMsg.innerHTML       = '';
    submitBtn.disabled      = true;
}

// ── Event Listeners ────────────────────────────────────────────────────────
dateInput.addEventListener('change', refreshSlots);
timeSelect.addEventListener('change', updateSubmitButton);

// Prevent selecting dates that are fully booked by adding a live message
// (browsers don't support disabling individual dates natively, but we warn)
dateInput.addEventListener('input', () => {
    const d = dateInput.value;
    if (FULLY_BOOKED_DATES.includes(d)) {
        dateMsg.innerHTML = '<span class="text-danger fw-semibold"><i class="fa-solid fa-calendar-xmark me-1"></i>This date is fully booked! Please select another date.</span>';
    }
});

// Initial state
resetSlots('— Pick a date first —');
</script>
</body>
</html>
