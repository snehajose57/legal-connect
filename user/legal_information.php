<?php
$page_title = "Legal Information";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id   = $_SESSION['user_id'];
$error_msg = "";

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_filter   = isset($_GET['category']) ? trim($_GET['category']) : '';
$type_filter  = isset($_GET['type']) ? trim($_GET['type']) : '';
$section_id   = isset($_GET['ipc_id']) ? (int)$_GET['ipc_id'] : 0;
$info_id      = isset($_GET['info_id']) ? (int)$_GET['info_id'] : 0;

// Default categories
$categories_list = [
    ['name' => 'Criminal Law', 'icon' => 'fa-gavel', 'desc' => 'IPC sections, FIR, Bail, Criminal complaints, offences, and criminal procedures.'],
    ['name' => 'Civil Law', 'icon' => 'fa-scale-balanced', 'desc' => 'Civil disputes, contracts, money recovery, legal notices, and civil procedures.'],
    ['name' => 'Family Law', 'icon' => 'fa-people-roof', 'desc' => 'Marriage, divorce, maintenance, child custody, and family disputes.'],
    ['name' => 'Property Law', 'icon' => 'fa-house-lock', 'desc' => 'Property ownership, disputes, transfers, inheritance, and land issues.'],
    ['name' => 'Consumer Law', 'icon' => 'fa-cart-shopping', 'desc' => 'Consumer rights, defective products, refund problems, and consumer complaints.'],
    ['name' => 'Cyber Law', 'icon' => 'fa-shield-halved', 'desc' => 'Online fraud, hacking, cyber harassment, digital misuse, and complaints.'],
    ['name' => 'Labour Law', 'icon' => 'fa-briefcase', 'desc' => 'Unpaid salary, employee rights, termination, and workplace disputes.'],
    ['name' => 'Motor / Accident Law', 'icon' => 'fa-car-burst', 'desc' => 'Road accidents, insurance, compensation, and accident procedures.']
];

// Fetch results
$legal_info_results = [];
$ipc_results        = [];
$active_modal_item  = null;
$active_modal_type  = ''; // 'info' or 'ipc'

try {
    // 1. Search or filter legal_information
    $info_sql = "SELECT * FROM legal_information WHERE status = 'Active'";
    $info_params = [];

    if ($search_query !== '') {
        $info_sql .= " AND (title LIKE ? OR short_description LIKE ? OR description LIKE ? OR category LIKE ? OR related_ipc_section LIKE ? OR procedure_details LIKE ?)";
        $term = '%' . $search_query . '%';
        $info_params = [$term, $term, $term, $term, $term, $term];
    }

    if ($cat_filter !== '') {
        $info_sql .= " AND category = ?";
        $info_params[] = $cat_filter;
    }

    if ($type_filter !== '') {
        $info_sql .= " AND info_type = ?";
        $info_params[] = $type_filter;
    }

    $info_sql .= " ORDER BY legal_info_id ASC";
    $info_stmt = $conn->prepare($info_sql);
    $info_stmt->execute($info_params);
    $legal_info_results = $info_stmt->fetchAll();

    // 2. Search IPC Sections (reusing existing ipc_sections table)
    $ipc_sql = "SELECT * FROM ipc_sections";
    $ipc_params = [];

    if ($search_query !== '') {
        $ipc_sql .= " WHERE section_number LIKE ? OR title LIKE ? OR description LIKE ?";
        $term = '%' . $search_query . '%';
        $ipc_params = [$term, $term, $term];
    }
    $ipc_sql .= " ORDER BY ipc_id ASC";
    $ipc_stmt = $conn->prepare($ipc_sql);
    $ipc_stmt->execute($ipc_params);
    $ipc_results = $ipc_stmt->fetchAll();

    // Fetch active modal item if requested via query param
    if ($info_id > 0) {
        $item_stmt = $conn->prepare("SELECT * FROM legal_information WHERE legal_info_id = ?");
        $item_stmt->execute([$info_id]);
        $active_modal_item = $item_stmt->fetch();
        if ($active_modal_item) {
            $active_modal_type = 'info';
        }
    } elseif ($section_id > 0) {
        $ipc_modal_stmt = $conn->prepare("SELECT * FROM ipc_sections WHERE ipc_id = ?");
        $ipc_modal_stmt->execute([$section_id]);
        $active_modal_item = $ipc_modal_stmt->fetch();
        if ($active_modal_item) {
            $active_modal_type = 'ipc';
        }
    }

} catch (PDOException $e) {
    $error_msg = "Error loading legal information: " . $e->getMessage();
}

// Group legal info by type for default display
$topics_list     = [];
$faqs_list       = [];
$situations_list = [];

foreach ($legal_info_results as $row) {
    if ($row['info_type'] === 'faq') {
        $faqs_list[] = $row;
    } elseif ($row['info_type'] === 'situation') {
        $situations_list[] = $row;
    } else {
        $topics_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Information | Legal Connect</title>
    <meta name="description" content="Explore basic legal guidance, legal topics, IPC sections, common procedures and legal Q&A.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
    <style>
        .category-card {
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            background: #ffffff;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.08);
            color: inherit;
            border-color: #f59e0b;
        }
        .category-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 14px;
        }
        .info-card {
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 14px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .info-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
        }
        .search-banner {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            border-radius: 20px;
            padding: 32px 28px;
            color: #ffffff;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .badge-cat {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .situation-pill {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            background: #ffffff;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: #1e293b;
        }
        .situation-pill:hover {
            background: #f8fafc;
            border-color: #f59e0b;
            color: #d97706;
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
            <a class="nav-link" href="/legalconnect/user/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/user/lawyers.php"><i class="fa-solid fa-user-tie"></i> Search Lawyers</a>
            <a class="nav-link active" href="/legalconnect/user/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
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
        <!-- Search & Welcome Header Banner -->
        <div class="search-banner mb-4">
            <h2 class="fw-bold text-white mb-2"><i class="fa-solid fa-book-open text-warning me-2"></i>Legal Information</h2>
            <p class="text-white-50 mb-4">Explore legal topics, IPC sections, common legal procedures and basic legal guidance.</p>
            
            <form action="/legalconnect/user/legal_information.php" method="GET" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="q" class="form-control border-0 ps-0 fs-6" 
                               placeholder="Search legal information... (e.g. murder, theft, FIR, bail, divorce, property, salary problem)" 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-gold btn-lg w-100 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                    </button>
                </div>
            </form>
            <?php if ($search_query !== '' || $cat_filter !== '' || $type_filter !== ''): ?>
                <div class="mt-3 d-flex align-items-center gap-2">
                    <span class="small text-white-50">Active Filters:</span>
                    <?php if ($search_query !== ''): ?>
                        <span class="badge bg-warning text-dark me-1">Search: "<?php echo htmlspecialchars($search_query); ?>"</span>
                    <?php endif; ?>
                    <?php if ($cat_filter !== ''): ?>
                        <span class="badge bg-info text-dark me-1">Category: <?php echo htmlspecialchars($cat_filter); ?></span>
                    <?php endif; ?>
                    <a href="/legalconnect/user/legal_information.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-0">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Search / Filter Active Results View -->
        <?php if ($search_query !== '' || $cat_filter !== ''): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-warning me-2"></i>Search &amp; Filter Results</h4>
                
                <!-- Matching IPC Sections -->
                <?php if (!empty($ipc_results)): ?>
                    <h6 class="fw-bold text-uppercase text-muted small border-bottom pb-2 mb-3"><i class="fa-solid fa-book-bookmark text-warning me-1"></i> Matching IPC Sections (<?php echo count($ipc_results); ?>)</h6>
                    <div class="row g-3 mb-4">
                        <?php foreach ($ipc_results as $ipc): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border shadow-sm p-3 rounded-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1"><?php echo htmlspecialchars($ipc['section_number']); ?></span>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#ipcModal<?php echo $ipc['ipc_id']; ?>">Read</button>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ipc['title']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($ipc['description'], 0, 110)) . (strlen($ipc['description']) > 110 ? '...' : ''); ?></p>
                                </div>
                            </div>

                            <!-- IPC Detail Modal -->
                            <div class="modal fade" id="ipcModal<?php echo $ipc['ipc_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <div class="modal-header border-0 bg-light rounded-top-4">
                                            <div>
                                                <span class="badge bg-warning text-dark fw-bold mb-1"><?php echo htmlspecialchars($ipc['section_number']); ?></span>
                                                <h5 class="modal-title fw-bold text-dark"><?php echo htmlspecialchars($ipc['title']); ?></h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <h6 class="fw-bold text-dark">IPC Section Description</h6>
                                            <p class="text-secondary mb-4"><?php echo nl2br(htmlspecialchars($ipc['description'])); ?></p>
                                            
                                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1">Need personalized legal advice?</h6>
                                                    <small class="text-muted">Ask our certified advocates or book a 1-on-1 consultation.</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                                    <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Matching Legal Info Topics/FAQs/Situations -->
                <?php if (!empty($legal_info_results)): ?>
                    <h6 class="fw-bold text-uppercase text-muted small border-bottom pb-2 mb-3"><i class="fa-solid fa-file-contract text-warning me-1"></i> Matching Legal Topics &amp; Guidance (<?php echo count($legal_info_results); ?>)</h6>
                    <div class="row g-3">
                        <?php foreach ($legal_info_results as $item): ?>
                            <div class="col-md-6">
                                <div class="card h-100 border p-3 rounded-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge badge-cat px-2 py-1"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <?php if ($item['related_ipc_section']): ?>
                                            <span class="badge bg-light text-dark border small"><i class="fa-solid fa-bookmark me-1 text-warning"></i><?php echo htmlspecialchars($item['related_ipc_section']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($item['short_description']); ?></p>
                                    <div class="mt-auto">
                                        <button class="btn btn-sm btn-gold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#infoModal<?php echo $item['legal_info_id']; ?>">View Full Information</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Detail Modal -->
                            <div class="modal fade" id="infoModal<?php echo $item['legal_info_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <div class="modal-header border-0 bg-light rounded-top-4">
                                            <div>
                                                <span class="badge badge-cat mb-1"><?php echo htmlspecialchars($item['category']); ?></span>
                                                <h5 class="modal-title fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <h6 class="fw-bold text-dark mb-2">Overview</h6>
                                            <p class="text-secondary"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                                            
                                            <?php if (!empty($item['procedure_details'])): ?>
                                                <h6 class="fw-bold text-dark mt-4 mb-2"><i class="fa-solid fa-list-ol text-warning me-2"></i>Basic Procedure / Steps</h6>
                                                <div class="bg-light p-3 rounded-3 text-secondary mb-3">
                                                    <?php echo nl2br(htmlspecialchars($item['procedure_details'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($item['documents_required'])): ?>
                                                <h6 class="fw-bold text-dark mt-3 mb-2"><i class="fa-solid fa-folder-open text-warning me-2"></i>Commonly Required Documents</h6>
                                                <p class="text-secondary small mb-3"><?php echo htmlspecialchars($item['documents_required']); ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($item['related_ipc_section'])): ?>
                                                <div class="alert alert-warning border-0 d-flex align-items-center gap-2 py-2 px-3 mb-4">
                                                    <i class="fa-solid fa-scale-balanced fs-5"></i>
                                                    <div>
                                                        <strong>Applicable Law / IPC Section:</strong> <?php echo htmlspecialchars($item['related_ipc_section']); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mt-4">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1">Have specific questions about your situation?</h6>
                                                    <small class="text-muted">Connect with our advocates for legal guidance.</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                                    <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($ipc_results) && empty($legal_info_results)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-magnifying-glass fs-1 text-muted opacity-50 mb-3"></i>
                        <h5 class="fw-bold text-dark mb-1">No matching legal information found</h5>
                        <p class="text-muted mb-3">Try searching with broader terms or ask our advocates directly.</p>
                        <a href="/legalconnect/user/questions.php" class="btn btn-gold rounded-pill px-4">Ask a Lawyer</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 1. BROWSE LEGAL CATEGORIES -->
        <div class="mb-5">
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-layer-group text-warning me-2"></i>Areas of Law &amp; Categories</h4>
            <p class="text-muted mb-3">Select a legal domain to browse curated topics and procedures.</p>
            <div class="row g-3">
                <?php foreach ($categories_list as $cat): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="/legalconnect/user/legal_information.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card shadow-sm">
                            <div class="category-icon">
                                <i class="fa-solid <?php echo $cat['icon']; ?>"></i>
                            </div>
                            <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($cat['name']); ?></h6>
                            <small class="text-muted d-block" style="font-size: 0.78rem; line-height: 1.3;"><?php echo htmlspecialchars($cat['desc']); ?></small>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 2. IPC SECTION SEARCH & BROWSE -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-book-bookmark text-warning me-2"></i>IPC Sections Directory</h4>
                    <p class="text-muted small mb-0">Search Indian Penal Code sections by number (e.g. 302, 378, 420) or offense keyword.</p>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2">Existing IPC Database</span>
            </div>

            <div class="row g-3">
                <?php foreach (array_slice($ipc_results, 0, 6) as $ipc): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-warning text-dark fw-bold"><?php echo htmlspecialchars($ipc['section_number']); ?></span>
                                <button class="btn btn-sm btn-outline-dark rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#ipcSecModal<?php echo $ipc['ipc_id']; ?>">View Details</button>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ipc['title']); ?></h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($ipc['description'], 0, 90)) . (strlen($ipc['description']) > 90 ? '...' : ''); ?></p>
                        </div>
                    </div>

                    <!-- Modal for IPC item -->
                    <div class="modal fade" id="ipcSecModal<?php echo $ipc['ipc_id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header border-0 bg-light rounded-top-4">
                                    <div>
                                        <span class="badge bg-warning text-dark fw-bold mb-1"><?php echo htmlspecialchars($ipc['section_number']); ?></span>
                                        <h5 class="modal-title fw-bold text-dark"><?php echo htmlspecialchars($ipc['title']); ?></h5>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <h6 class="fw-bold text-dark">Section Provisions &amp; Explanation</h6>
                                    <p class="text-secondary mb-4"><?php echo nl2br(htmlspecialchars($ipc['description'])); ?></p>

                                    <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Require assistance on this offense?</h6>
                                            <small class="text-muted">Ask our legal team or consult a legal advocate.</small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                            <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. BASIC LEGAL TOPICS -->
        <div class="mb-5">
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-scale-unbalanced-flip text-warning me-2"></i>Basic Legal Topics &amp; Concepts</h4>
            <p class="text-muted mb-3">Clear explanations of essential legal terms, rights, and procedures.</p>
            
            <div class="row g-3">
                <?php foreach ($topics_list as $topic): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="info-card h-100 p-3 shadow-sm d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-cat px-2 py-1"><?php echo htmlspecialchars($topic['category']); ?></span>
                                <?php if (!empty($topic['related_ipc_section'])): ?>
                                    <small class="text-muted"><i class="fa-solid fa-bookmark me-1 text-warning"></i><?php echo htmlspecialchars($topic['related_ipc_section']); ?></small>
                                <?php endif; ?>
                            </div>
                            <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($topic['title']); ?></h6>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($topic['short_description']); ?></p>
                            <div class="mt-auto">
                                <button class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#topicModal<?php echo $topic['legal_info_id']; ?>">Read Topic Details</button>
                            </div>
                        </div>
                    </div>

                    <!-- Topic Modal -->
                    <div class="modal fade" id="topicModal<?php echo $topic['legal_info_id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header border-0 bg-light rounded-top-4">
                                    <div>
                                        <span class="badge badge-cat mb-1"><?php echo htmlspecialchars($topic['category']); ?></span>
                                        <h5 class="modal-title fw-bold text-dark"><?php echo htmlspecialchars($topic['title']); ?></h5>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <h6 class="fw-bold text-dark mb-2">Overview &amp; Meaning</h6>
                                    <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($topic['description'])); ?></p>
                                    
                                    <?php if (!empty($topic['procedure_details'])): ?>
                                        <h6 class="fw-bold text-dark mt-4 mb-2"><i class="fa-solid fa-list-check text-warning me-2"></i>Basic Procedure</h6>
                                        <div class="bg-light p-3 rounded-3 text-secondary mb-3">
                                            <?php echo nl2br(htmlspecialchars($topic['procedure_details'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($topic['documents_required'])): ?>
                                        <h6 class="fw-bold text-dark mt-3 mb-2"><i class="fa-solid fa-folder-closed text-warning me-2"></i>Required Documents</h6>
                                        <p class="text-secondary small mb-3"><?php echo htmlspecialchars($topic['documents_required']); ?></p>
                                    <?php endif; ?>

                                    <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mt-4">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Need help applying this to your situation?</h6>
                                            <small class="text-muted">Ask our certified advocates or book a consultation.</small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                            <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 4. "WHAT SHOULD I DO?" SECTION -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-circle-question text-warning me-2"></i>"What Should I Do?" Guided Scenarios</h4>
            <p class="text-muted mb-4">Select your current situation to view step-by-step guidance, applicable laws, and recommended actions.</p>
            
            <div class="row g-3">
                <?php foreach ($situations_list as $sit): ?>
                    <div class="col-md-6">
                        <div class="situation-pill shadow-sm cursor-pointer" data-bs-toggle="modal" data-bs-target="#sitModal<?php echo $sit['legal_info_id']; ?>">
                            <div>
                                <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-circle-right text-warning me-2"></i><?php echo htmlspecialchars($sit['title']); ?></div>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($sit['short_description']); ?></small>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted"></i>
                        </div>
                    </div>

                    <!-- Situation Modal -->
                    <div class="modal fade" id="sitModal<?php echo $sit['legal_info_id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header border-0 bg-light rounded-top-4">
                                    <div>
                                        <span class="badge badge-cat mb-1"><?php echo htmlspecialchars($sit['category']); ?></span>
                                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-shield-cat text-warning me-2"></i>Situation: <?php echo htmlspecialchars($sit['title']); ?></h5>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <h6 class="fw-bold text-dark mb-2">1. Basic Explanation</h6>
                                    <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($sit['description'])); ?></p>
                                    
                                    <h6 class="fw-bold text-dark mt-4 mb-2">2. General Steps to Take</h6>
                                    <div class="bg-light p-3 rounded-3 text-secondary mb-3">
                                        <?php echo nl2br(htmlspecialchars($sit['procedure_details'])); ?>
                                    </div>

                                    <?php if (!empty($sit['related_ipc_section'])): ?>
                                        <h6 class="fw-bold text-dark mt-3 mb-2">3. Relevant Law / Section</h6>
                                        <span class="badge bg-warning text-dark px-3 py-2 fw-bold mb-3"><?php echo htmlspecialchars($sit['related_ipc_section']); ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($sit['documents_required'])): ?>
                                        <h6 class="fw-bold text-dark mt-3 mb-2">4. Commonly Required Documents</h6>
                                        <p class="text-secondary small mb-3"><?php echo htmlspecialchars($sit['documents_required']); ?></p>
                                    <?php endif; ?>

                                    <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between mt-4">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Ready for professional legal assistance?</h6>
                                            <small class="text-muted">Our advocates can help you navigate this situation.</small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                            <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5. COMMON LEGAL QUESTIONS (FAQs) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-comments-question text-warning me-2"></i>Common Legal Questions</h4>
            <p class="text-muted mb-4">Frequently asked questions and general legal answers.</p>

            <div class="accordion accordion-flush" id="faqAccordion">
                <?php foreach ($faqs_list as $index => $faq): ?>
                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="headingFaq<?php echo $faq['legal_info_id']; ?>">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq<?php echo $faq['legal_info_id']; ?>">
                                <span class="badge badge-cat me-2"><?php echo htmlspecialchars($faq['category']); ?></span>
                                <?php echo htmlspecialchars($faq['title']); ?>
                            </button>
                        </h2>
                        <div id="collapseFaq<?php echo $faq['legal_info_id']; ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body bg-light p-4">
                                <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($faq['description'])); ?></p>
                                
                                <?php if (!empty($faq['procedure_details'])): ?>
                                    <h6 class="fw-bold text-dark mt-3 mb-1">Recommended Action Steps:</h6>
                                    <div class="small text-secondary mb-3"><?php echo nl2br(htmlspecialchars($faq['procedure_details'])); ?></div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                                    <span class="small text-muted">For personalized advice on your case:</span>
                                    <div class="d-flex gap-2">
                                        <a href="/legalconnect/user/questions.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Ask a Lawyer</a>
                                        <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">Book Consultation</a>
                                    </div>
                                </div>
                            </div>
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
