<?php
$page_title = "Browse Lawyers";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$error_msg = "";
$search_query = "";
$specialization_filter = "";

// Fetch categories for filter dropdown
$categories = [];
try {
    $categories = $conn->query("SELECT category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// Build dynamic search/filter query
$sql = "SELECT l.*, COALESCE(AVG(f.rating), 0.0) as avg_rating, COUNT(f.feedback_id) as total_reviews 
        FROM lawyers l 
        LEFT JOIN feedbacks f ON l.lawyer_id = f.lawyer_id 
        WHERE l.status = 'Approved'";

$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = sanitize($_GET['search']);
    $sql .= " AND (l.name LIKE ? OR l.qualification LIKE ? OR l.address LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if (isset($_GET['specialization']) && !empty($_GET['specialization'])) {
    $specialization_filter = sanitize($_GET['specialization']);
    $sql .= " AND l.specialization = ?";
    $params[] = $specialization_filter;
}

$sql .= " GROUP BY l.lawyer_id ORDER BY avg_rating DESC, l.experience DESC";

// Fetch Lawyers list
$lawyers = [];
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $lawyers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search & Browse Lawyers | Legal Connect</title>
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
                <h2 class="fw-bold text-dark mb-1">Search & Find Lawyers</h2>
                <p class="text-muted mb-0">Browse approved legal practitioners, check experience levels, and book consultations.</p>
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

        <!-- Search and Filter Form -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-lg-5 col-md-5 col-sm-12">
                        <label class="form-label fw-bold text-muted small">Search Keyword</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search lawyer name, qualifications, office..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <label class="form-label fw-bold text-muted small">Practice Specialization</label>
                        <select class="form-select" name="specialization">
                            <option value="">All Categories...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($cat === $specialization_filter) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-12 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-gold flex-grow-1"><i class="fa-solid fa-filter me-2"></i>Filter</button>
                            <?php if (!empty($search_query) || !empty($specialization_filter)): ?>
                                <a href="/legalconnect/user/lawyers.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Directory Profiles List -->
        <div class="row g-4">
            <?php if (!empty($lawyers)): ?>
                <?php foreach ($lawyers as $lawyer): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card feature-card p-0 text-center overflow-hidden h-100 d-flex flex-column justify-content-between">
                            <div class="bg-dark p-4 position-relative">
                                <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-3 shadow" style="width: 90px; height: 90px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle avatar-large mx-auto text-dark bg-warning"><?php echo substr($lawyer['name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body p-4 flex-grow-1">
                                <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($lawyer['name']); ?></h5>
                                <span class="badge bg-warning text-dark mb-3"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                                
                                <div class="mb-3 text-warning">
                                    <?php 
                                    $stars = round($lawyer['avg_rating']); 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $stars) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star text-muted"></i>';
                                    }
                                    ?>
                                    <span class="text-muted small ms-1">(<?php echo $lawyer['total_reviews']; ?> reviews)</span>
                                </div>

                                <div class="text-start bg-light p-3 rounded mb-3 small">
                                    <p class="mb-1 text-muted"><strong class="text-dark">Degree:</strong> <?php echo htmlspecialchars($lawyer['qualification']); ?></p>
                                    <p class="mb-1 text-muted"><strong class="text-dark">Experience:</strong> <?php echo htmlspecialchars($lawyer['experience']); ?>+ Years</p>
                                    <p class="mb-0 text-muted text-truncate"><strong class="text-dark">Office:</strong> <?php echo htmlspecialchars($lawyer['address']); ?></p>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-white border-0 p-4 pt-0">
                                <div class="d-flex gap-2">
                                    <!-- Booking Button -->
                                    <a href="/legalconnect/user/book.php?lawyer_id=<?php echo $lawyer['lawyer_id']; ?>" class="btn btn-warning text-dark flex-grow-1 rounded-pill btn-sm fw-bold">
                                        <i class="fa-solid fa-calendar-check me-1"></i>Book Session
                                    </a>
                                    <!-- Chat Button -->
                                    <a href="/legalconnect/user/chat.php?lawyer_id=<?php echo $lawyer['lawyer_id']; ?>" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center btn-sm" style="width: 36px; height: 36px;" title="Chat Now">
                                        <i class="fa-solid fa-comments"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-white shadow-sm rounded-4 max-width-md mx-auto" style="max-width: 500px;">
                        <i class="fa-solid fa-users-slash fs-1 text-muted mb-3"></i>
                        <h5>No Advocates Found</h5>
                        <p class="text-muted mb-0">No active verified lawyers matched your search query. Try broadening your keywords or removing filters.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
