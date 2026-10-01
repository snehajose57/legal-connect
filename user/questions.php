<?php
$page_title = "Ask Legal Questions";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Fetch approved lawyers list for dropdown
$lawyers = [];
try {
    $stmt = $conn->query("SELECT lawyer_id, name, specialization FROM lawyers WHERE status = 'Approved' ORDER BY name ASC");
    $lawyers = $stmt->fetchAll();
} catch (PDOException $e) {}

// Handle Submission
if (isset($_POST['submit_question'])) {
    $lawyer_id = (int)$_POST['lawyer_id'];
    $subject = sanitize($_POST['subject']);
    $question_text = sanitize($_POST['question_text']);

    if (empty($lawyer_id) || empty($subject) || empty($question_text)) {
        $error_msg = "All fields are required!";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO legal_questions (user_id, lawyer_id, subject, question, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $lawyer_id, $subject, $question_text]);
            
            $_SESSION['success'] = "Legal question submitted to Advocate successfully!";
            header("Location: /legalconnect/user/questions.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch user questions history
$questions = [];
try {
    $stmt = $conn->prepare("SELECT q.*, l.name as lawyer_name, l.specialization 
                            FROM legal_questions q 
                            JOIN lawyers l ON q.lawyer_id = l.lawyer_id 
                            WHERE q.user_id = ? 
                            ORDER BY q.created_at DESC");
    $stmt->execute([$user_id]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error reading questions history: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Legal Questions | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/user/questions.php"><i class="fa-solid fa-circle-question"></i> Legal Q&amp;A</a>
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
                <h2 class="fw-bold text-dark mb-1">Legal Support Q&A</h2>
                <p class="text-muted mb-0">Ask verified lawyers legal doubts and receive professional counseling responses.</p>
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
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Form Formulate Question -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-pen-clip text-warning me-2"></i>Ask a Question</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="lawyer_id" class="form-label fw-bold">Select Lawyer</label>
                            <select class="form-select" id="lawyer_id" name="lawyer_id" required>
                                <option value="">Select legal advisor...</option>
                                <?php foreach ($lawyers as $lawyer): ?>
                                    <option value="<?php echo $lawyer['lawyer_id']; ?>">Adv. <?php echo htmlspecialchars($lawyer['name']); ?> (<?php echo htmlspecialchars($lawyer['specialization']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-bold">Subject / Main Query</label>
                            <input type="text" class="form-control" id="subject" name="subject" required placeholder="e.g. Property boundary issues with neighbors">
                        </div>

                        <div class="mb-4">
                            <label for="question_text" class="form-label fw-bold">Question Details</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="6" required placeholder="Outline details of your case or inquiry here for review..."></textarea>
                        </div>

                        <button type="submit" name="submit_question" class="btn btn-gold w-100 rounded-pill">
                            <i class="fa-solid fa-paper-plane me-2"></i>Submit Question
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Question history list -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>My Question Logs</h5>
                    
                    <?php if (!empty($questions)): ?>
                        <div class="accordion" id="qHistoryAccordion">
                            <?php foreach ($questions as $index => $q): ?>
                                <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden">
                                    <h2 class="accordion-header" id="heading<?php echo $q['question_id']; ?>">
                                        <button class="accordion-button collapsed fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $q['question_id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $q['question_id']; ?>">
                                            <div class="w-100 d-flex justify-content-between align-items-center pe-3">
                                                <div>
                                                    <span class="badge bg-dark text-warning me-2">#Q-<?php echo $q['question_id']; ?></span>
                                                    <?php echo htmlspecialchars($q['subject']); ?>
                                                </div>
                                                <span class="badge badge-status <?php echo $q['status'] === 'Answered' ? 'badge-approved' : 'badge-pending'; ?>"><?php echo $q['status']; ?></span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $q['question_id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $q['question_id']; ?>" data-bs-parent="#qHistoryAccordion">
                                        <div class="accordion-body bg-light border-top">
                                            <div class="mb-3 small">
                                                <strong class="text-dark">Assigned Lawyer:</strong> Adv. <?php echo htmlspecialchars($q['lawyer_name']); ?> (<?php echo htmlspecialchars($q['specialization']); ?>)<br>
                                                <strong class="text-dark">Date Asked:</strong> <?php echo date('d M, Y h:i A', strtotime($q['created_at'])); ?>
                                            </div>
                                            <div class="mb-3">
                                                <h6 class="fw-bold text-dark mb-1">Your Question:</h6>
                                                <p class="bg-white p-3 rounded border text-muted small mb-0"><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                                            </div>
                                            
                                            <?php if ($q['status'] === 'Answered'): ?>
                                                <div>
                                                    <h6 class="fw-bold text-success mb-1"><i class="fa-solid fa-reply me-1"></i>Lawyer's Advice:</h6>
                                                    <p class="bg-white p-3 rounded border text-dark small mb-0"><?php echo nl2br(htmlspecialchars($q['response'])); ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning py-2 small mb-0 mt-3">
                                                    <i class="fa-solid fa-circle-info me-1"></i>Waiting for Advocate to post their response. Check back soon.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-clipboard-question fs-1 text-muted mb-2"></i>
                            <div>You have not submitted any questions yet. Use the form to make a submission.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
