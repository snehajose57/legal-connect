<?php
$page_title = "Provide Advocate Feedback";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

$lawyer_id = isset($_GET['lawyer_id']) ? (int)$_GET['lawyer_id'] : null;
$lawyer = null;

if ($lawyer_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM lawyers WHERE lawyer_id = ? AND status = 'Approved'");
        $stmt->execute([$lawyer_id]);
        $lawyer = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

if (!$lawyer) {
    $_SESSION['error'] = "Invalid lawyer selected for feedback.";
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}

// Security Verification: Client must have at least one booking (Approved or Completed) with this advocate
try {
    $checkBooking = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ? AND lawyer_id = ? AND status IN ('Approved', 'Completed')");
    $checkBooking->execute([$user_id, $lawyer_id]);
    $bookingCount = $checkBooking->fetchColumn();

    if ($bookingCount == 0) {
        $_SESSION['error'] = "You can only leave reviews for advocates you have scheduled appointments with.";
        header("Location: /legalconnect/user/dashboard.php");
        exit();
    }
} catch (PDOException $e) {}

// Handle Submission
if (isset($_POST['submit_feedback'])) {
    $rating = (int)$_POST['rating'];
    $comments = sanitize($_POST['comments']);

    if ($rating < 1 || $rating > 5) {
        $error_msg = "Please select a rating between 1 and 5 stars!";
    } else {
        try {
            $ins = $conn->prepare("INSERT INTO feedbacks (user_id, lawyer_id, rating, comments) VALUES (?, ?, ?, ?)");
            $ins->execute([$user_id, $lawyer_id, $rating, $comments]);

            $_SESSION['success'] = "Thank you! Your feedback has been posted to Advocate " . $lawyer['name'] . "'s profile.";
            header("Location: /legalconnect/user/dashboard.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error submitting feedback: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | Legal Connect</title>
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
                <h2 class="fw-bold text-dark mb-1">Advocate Feedback & Ratings</h2>
                <p class="text-muted mb-0">Help other clients find the right counsel by reviewing your consultation experience.</p>
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
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Feedback Form details row -->
        <div class="row g-4 justify-content-center">
            <!-- Left Advocate Brief Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-4 rounded-4 bg-white h-100">
                    <div class="bg-light p-4 rounded-4 mb-3">
                        <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                            <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-3 shadow" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-circle avatar-large mx-auto text-dark bg-warning mb-2"><?php echo substr($lawyer['name'], 0, 1); ?></div>
                        <?php endif; ?>
                        <h5 class="fw-bold mb-1 mt-2 text-dark">Adv. <?php echo htmlspecialchars($lawyer['name']); ?></h5>
                        <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                    </div>
                    <div class="text-start small text-muted">
                        <i class="fa-solid fa-circle-info text-warning me-1"></i>Your rating score affects the average review level displayed on the lawyer directory.
                    </div>
                </div>
            </div>

            <!-- Right form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white h-100">
                    <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Review Consultation</h4>
                    
                    <form method="POST" action="">
                        <!-- Interactive Star Selectors -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Assign Stars Rating</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="Excellent (5 stars)"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="Good (4 stars)"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="Average (3 stars)"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="Fair (2 stars)"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="Poor (1 star)"><i class="fa-solid fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="comments" class="form-label fw-bold">Comments & Experience Notes</label>
                            <textarea class="form-control" id="comments" name="comments" rows="5" required placeholder="Type comments regarding clarity of legal advice, timing, communication skills, etc..."></textarea>
                        </div>

                        <button type="submit" name="submit_feedback" class="btn btn-gold w-100 rounded-pill py-3 fw-bold text-dark shadow-sm">
                            <i class="fa-solid fa-paper-plane me-2"></i>Submit Review
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
