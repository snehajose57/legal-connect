<?php
$page_title = "Provide Legal Support Q&A";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('lawyer');

$lawyer_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Handle Response Submission
if (isset($_POST['submit_response'])) {
    $q_id = (int)$_POST['question_id'];
    $response_txt = sanitize($_POST['response_text']);

    if (empty($response_txt)) {
        $error_msg = "Response description cannot be empty!";
    } else {
        try {
            // Verify ownership
            $check = $conn->prepare("SELECT lawyer_id FROM legal_questions WHERE question_id = ?");
            $check->execute([$q_id]);
            $owner = $check->fetchColumn();

            if ($owner && (int)$owner === $lawyer_id) {
                $stmt = $conn->prepare("UPDATE legal_questions SET response = ?, status = 'Answered' WHERE question_id = ?");
                $stmt->execute([$response_txt, $q_id]);
                $_SESSION['success'] = "Response submitted and saved to the client dashboard.";
                header("Location: /legalconnect/lawyer/support.php");
                exit();
            } else {
                $error_msg = "Access Denied: Invalid question record.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch questions
$questions = [];
try {
    $stmt = $conn->prepare("SELECT q.*, u.name as client_name FROM legal_questions q JOIN users u ON q.user_id = u.user_id WHERE q.lawyer_id = ? ORDER BY q.created_at DESC");
    $stmt->execute([$lawyer_id]);
    $questions = $stmt->fetchAll();
    
    // Count pending bookings for badge
    $pending_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_count->execute([$lawyer_id]);
    $count_pending = $pending_count->fetchColumn();

    // Count pending Q&A for badge
    $pending_qa = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_qa->execute([$lawyer_id]);
    $count_pending_qa = $pending_qa->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading inquiries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provide Legal Support Q&A | Legal Connect</title>
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
            <a class="nav-link" href="/legalconnect/lawyer/requests.php"><i class="fa-solid fa-calendar-days"></i> Client Requests <span class="badge bg-danger ms-auto"><?php echo $count_pending; ?></span></a>
            <a class="nav-link active" href="/legalconnect/lawyer/support.php"><i class="fa-solid fa-circle-question"></i> Support Q&A <span class="badge bg-danger ms-auto"><?php echo $count_pending_qa; ?></span></a>
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
                <h2 class="fw-bold text-dark mb-1">Legal Support Q&A Portal</h2>
                <p class="text-muted mb-0">Review submitted legal doubts and questions from registered clients.</p>
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
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Questions List -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clipboard-question text-warning me-2"></i>Inquiries Box</h5>
            
            <?php if (!empty($questions)): ?>
                <div class="accordion" id="qAccordion">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="accordion-item mb-3 border rounded shadow-sm overflow-hidden">
                            <h2 class="accordion-header" id="heading<?php echo $q['question_id']; ?>">
                                <button class="accordion-button collapsed fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $q['question_id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $q['question_id']; ?>">
                                    <div class="w-100 d-flex justify-content-between align-items-center pe-3">
                                        <div>
                                            <span class="badge bg-dark text-warning me-2">#Q-<?php echo $q['question_id']; ?></span>
                                            <?php echo htmlspecialchars($q['subject']); ?>
                                            <span class="text-muted fw-normal small ms-2">from <?php echo htmlspecialchars($q['client_name']); ?></span>
                                        </div>
                                        <span class="badge badge-status <?php echo $q['status'] === 'Answered' ? 'badge-approved' : 'badge-pending'; ?>"><?php echo $q['status']; ?></span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $q['question_id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $q['question_id']; ?>" data-bs-parent="#qAccordion">
                                <div class="accordion-body bg-light border-top">
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-dark mb-1">User Question Description:</h6>
                                        <p class="bg-white p-3 rounded border text-muted mb-0"><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                                        <small class="text-muted d-block mt-1">Asked on: <?php echo date('d M, Y h:i A', strtotime($q['created_at'])); ?></small>
                                    </div>

                                    <?php if ($q['status'] === 'Answered'): ?>
                                        <div class="mt-4">
                                            <h6 class="fw-bold text-success mb-1"><i class="fa-solid fa-reply-all me-1"></i>Your Submitted Response:</h6>
                                            <p class="bg-white p-3 rounded border text-dark mb-0"><?php echo nl2br(htmlspecialchars($q['response'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-4 card p-3 border-0 shadow-sm bg-white">
                                            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-pen-nib text-warning me-1"></i>Formulate Response</h6>
                                            <form method="POST" action="">
                                                <input type="hidden" name="question_id" value="<?php echo $q['question_id']; ?>">
                                                <div class="mb-3">
                                                    <textarea class="form-control" name="response_text" rows="5" required placeholder="Type legal advice, clarifications, or consultation instructions here..."></textarea>
                                                </div>
                                                <div class="text-end">
                                                    <button type="submit" name="submit_response" class="btn btn-gold px-4">
                                                        <i class="fa-solid fa-paper-plane me-2"></i>Send Response
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-clipboard-check fs-2 text-muted mb-2"></i>
                    <div>You do not have any submitted legal inquiries.</div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
