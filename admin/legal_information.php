<?php
$page_title = "Manage Legal Information";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg   = "";
$success_msg = "";

$edit_mode               = false;
$edit_id                 = "";
$edit_category           = "";
$edit_title              = "";
$edit_short_desc         = "";
$edit_desc               = "";
$edit_procedure          = "";
$edit_docs               = "";
$edit_related_ipc        = "";
$edit_info_type          = "topic";
$edit_status             = "Active";

// Handle Toggle Status Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $toggle_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE legal_information SET status = IF(status='Active','Inactive','Active') WHERE legal_info_id = ?");
        $stmt->execute([$toggle_id]);
        $_SESSION['success'] = "Legal Information status updated!";
        header("Location: /legalconnect/admin/legal_information.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error toggling status: " . $e->getMessage();
    }
}

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM legal_information WHERE legal_info_id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = "Legal Information item deleted successfully!";
        header("Location: /legalconnect/admin/legal_information.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error deleting record: " . $e->getMessage();
    }
}

// Handle Save (Add / Edit) Submission
if (isset($_POST['save_legal_info'])) {
    $category        = sanitize($_POST['category']);
    $title           = sanitize($_POST['title']);
    $short_desc      = sanitize($_POST['short_description']);
    $desc            = sanitize($_POST['description']);
    $procedure       = sanitize($_POST['procedure_details']);
    $docs            = sanitize($_POST['documents_required']);
    $related_ipc     = sanitize($_POST['related_ipc_section']);
    $info_type       = sanitize($_POST['info_type']);
    $status          = sanitize($_POST['status']);
    $form_action     = sanitize($_POST['form_action']);

    if (empty($category) || empty($title) || empty($short_desc) || empty($desc)) {
        $error_msg = "Category, Title, Short Description and Detailed Description are required!";
    } else {
        try {
            if ($form_action === 'add') {
                $stmt = $conn->prepare("INSERT INTO legal_information 
                    (category, title, short_description, description, procedure_details, documents_required, related_ipc_section, info_type, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category, $title, $short_desc, $desc, $procedure, $docs, $related_ipc, $info_type, $status]);
                $_SESSION['success'] = "Legal Information added successfully!";
            } elseif ($form_action === 'edit') {
                $info_id = (int)$_POST['info_id'];
                $stmt = $conn->prepare("UPDATE legal_information SET 
                    category = ?, title = ?, short_description = ?, description = ?, procedure_details = ?, documents_required = ?, related_ipc_section = ?, info_type = ?, status = ?
                    WHERE legal_info_id = ?");
                $stmt->execute([$category, $title, $short_desc, $desc, $procedure, $docs, $related_ipc, $info_type, $status, $info_id]);
                $_SESSION['success'] = "Legal Information updated successfully!";
            }
            header("Location: /legalconnect/admin/legal_information.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch values for Edit Mode
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $edit_id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM legal_information WHERE legal_info_id = ?");
    $stmt->execute([$edit_id]);
    $item = $stmt->fetch();
    if ($item) {
        $edit_category    = $item['category'];
        $edit_title       = $item['title'];
        $edit_short_desc  = $item['short_description'];
        $edit_desc        = $item['description'];
        $edit_procedure   = $item['procedure_details'];
        $edit_docs        = $item['documents_required'];
        $edit_related_ipc = $item['related_ipc_section'];
        $edit_info_type   = $item['info_type'];
        $edit_status      = $item['status'];
    } else {
        $edit_mode = false;
    }
}

// Search and List Fetching
$search_q = isset($_GET['search']) ? trim($_GET['search']) : '';
$list_items = [];
try {
    $sql = "SELECT * FROM legal_information";
    $params = [];
    if ($search_q !== '') {
        $sql .= " WHERE title LIKE ? OR category LIKE ? OR short_description LIKE ? OR related_ipc_section LIKE ?";
        $term = '%' . $search_q . '%';
        $params = [$term, $term, $term, $term];
    }
    $sql .= " ORDER BY legal_info_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $list_items = $stmt->fetchAll();

    $pending_users   = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error fetching legal information: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Legal Information | Admin | Legal Connect</title>
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
            <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Admin Panel</h4>
        </div>
        <nav class="nav flex-column py-3">
            <a class="nav-link" href="/legalconnect/admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a class="nav-link" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
            <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
            <a class="nav-link active" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
            <a class="nav-link" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients <span class="badge bg-danger ms-auto"><?php echo $pending_users; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
            <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
            <a class="nav-link" href="/legalconnect/admin/monitoring.php"><i class="fa-solid fa-chart-line"></i> Monitoring &amp; Audit</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <!-- Header -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Legal Information Management</h2>
                <p class="text-muted mb-0">Add, edit, search, activate/deactivate, and manage legal guidance topics, FAQs, and scenarios.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
                <div class="avatar-circle">A</div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($error_msg !== ""): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
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
            <!-- Add / Edit Form -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-pen-to-square text-warning' : 'fa-circle-plus text-warning'; ?> me-2"></i>
                        <?php echo $edit_mode ? 'Edit Legal Information' : 'Add New Legal Information'; ?>
                    </h5>

                    <form action="/legalconnect/admin/legal_information.php" method="POST">
                        <input type="hidden" name="form_action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="info_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Legal Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php 
                                $cats = ['Criminal Law', 'Civil Law', 'Family Law', 'Property Law', 'Consumer Law', 'Cyber Law', 'Labour Law', 'Motor / Accident Law'];
                                foreach ($cats as $c):
                                ?>
                                    <option value="<?php echo $c; ?>" <?php if ($edit_category === $c) echo 'selected'; ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Information Type</label>
                            <select name="info_type" class="form-select" required>
                                <option value="topic" <?php if ($edit_info_type === 'topic') echo 'selected'; ?>>Legal Topic / Concept</option>
                                <option value="faq" <?php if ($edit_info_type === 'faq') echo 'selected'; ?>>Common Legal Question (FAQ)</option>
                                <option value="situation" <?php if ($edit_info_type === 'situation') echo 'selected'; ?>>"What Should I Do?" Scenario</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Title / Heading</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. What is an FIR? / I received a legal notice" value="<?php echo htmlspecialchars($edit_title); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Short Description / Summary</label>
                            <textarea name="short_description" class="form-control" rows="2" placeholder="Brief summary displayed on cards..." required><?php echo htmlspecialchars($edit_short_desc); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Detailed Explanation</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Full explanation in simple, ordinary language..." required><?php echo htmlspecialchars($edit_desc); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Basic Procedure / Steps (Optional)</label>
                            <textarea name="procedure_details" class="form-control" rows="3" placeholder="Step 1... Step 2..."><?php echo htmlspecialchars($edit_procedure); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Documents Required (Optional)</label>
                            <input type="text" name="documents_required" class="form-control" placeholder="e.g. ID proof, complaint copy, bill receipts" value="<?php echo htmlspecialchars($edit_docs); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Related IPC Section / Law (Optional)</label>
                            <input type="text" name="related_ipc_section" class="form-control" placeholder="e.g. IPC Section 302 / Section 420" value="<?php echo htmlspecialchars($edit_related_ipc); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Publication Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php if ($edit_status === 'Active') echo 'selected'; ?>>Active (Visible to Clients)</option>
                                <option value="Inactive" <?php if ($edit_status === 'Inactive') echo 'selected'; ?>>Inactive (Hidden)</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_legal_info" class="btn btn-gold flex-grow-1 fw-bold">
                                <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $edit_mode ? 'Update Record' : 'Save Record'; ?>
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="/legalconnect/admin/legal_information.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List Table -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list text-warning me-2"></i>Legal Information Records (<?php echo count($list_items); ?>)</h5>
                        
                        <form action="/legalconnect/admin/legal_information.php" method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search title/category..." value="<?php echo htmlspecialchars($search_q); ?>">
                            <button type="submit" class="btn btn-sm btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Category &amp; Type</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($list_items)): ?>
                                    <?php foreach ($list_items as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($row['category']); ?></div>
                                                <span class="badge bg-light text-muted border" style="font-size:0.7rem;"><?php echo strtoupper($row['info_type']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 240px;"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <small class="text-muted text-truncate d-block" style="max-width: 240px; font-size: 0.75rem;"><?php echo htmlspecialchars($row['short_description']); ?></small>
                                            </td>
                                            <td>
                                                <a href="/legalconnect/admin/legal_information.php?action=toggle&id=<?php echo $row['legal_info_id']; ?>" 
                                                   class="badge rounded-pill <?php echo $row['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?> text-decoration-none" title="Click to toggle status">
                                                    <?php echo $row['status']; ?>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <a href="/legalconnect/admin/legal_information.php?action=edit&id=<?php echo $row['legal_info_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="/legalconnect/admin/legal_information.php?action=delete&id=<?php echo $row['legal_info_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this legal information record?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No legal information records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
