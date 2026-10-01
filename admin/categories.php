<?php
$page_title = "Manage Categories";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg = "";
$success_msg = "";

// Edit values fallback
$edit_mode = false;
$edit_id = "";
$edit_name = "";
$edit_desc = "";

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = "Category deleted successfully!";
        header("Location: /legalconnect/admin/categories.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error deleting category (It might be linked to active lawyers).";
    }
}

// Handle Add / Edit Submission
if (isset($_POST['save_category'])) {
    $cat_name = sanitize($_POST['category_name']);
    $cat_desc = sanitize($_POST['description']);
    $form_action = sanitize($_POST['form_action']);

    if (empty($cat_name) || empty($cat_desc)) {
        $error_msg = "All fields are required!";
    } else {
        try {
            if ($form_action === 'add') {
                $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$cat_name, $cat_desc]);
                $_SESSION['success'] = "Category added successfully!";
            } elseif ($form_action === 'edit') {
                $cat_id = (int)$_POST['category_id'];
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->execute([$cat_name, $cat_desc, $cat_id]);
                $_SESSION['success'] = "Category updated successfully!";
            }
            header("Location: /legalconnect/admin/categories.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch values for Edit Mode
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$edit_id]);
    $cat = $stmt->fetch();
    if ($cat) {
        $edit_name = $cat['category_name'];
        $edit_desc = $cat['description'];
    } else {
        $edit_mode = false;
    }
}

// Fetch all categories for table
$categories = [];
try {
    $categories = $conn->query("SELECT * FROM categories ORDER BY category_id DESC")->fetchAll();
    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading list: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Legal Categories | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
            <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
            <a class="nav-link" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
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
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Manage Legal Categories</h2>
                <p class="text-muted mb-0">Add and update categories to assist clients in searching specialized lawyers.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-dark small">Logged in as: <strong>Admin</strong></span>
                <div class="avatar-circle">A</div>
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
            <!-- Left Form Side -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid <?php echo $edit_mode ? 'fa-pen-to-square text-warning' : 'fa-circle-plus text-success'; ?> me-2"></i>
                        <?php echo $edit_mode ? "Modify Category" : "Add Practice Area"; ?>
                    </h5>
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="category_name" class="form-label fw-bold">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="e.g. Cyber Law" value="<?php echo htmlspecialchars($edit_name); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description Details</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Details about what cases fall under this practice area..."><?php echo htmlspecialchars($edit_desc); ?></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gold flex-grow-1" name="save_category">
                                <i class="fa-solid fa-save me-2"></i>Save Category
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="/legalconnect/admin/categories.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right List Side -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list text-warning me-2"></i>Active Categories</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%">Category Name</th>
                                    <th style="width: 55%">Description</th>
                                    <th style="width: 20%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                            <td class="text-muted small"><?php echo nl2br(htmlspecialchars($cat['description'])); ?></td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="/legalconnect/admin/categories.php?action=edit&id=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="/legalconnect/admin/categories.php?action=delete&id=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category? All lawyers specialized in this category will remain, but the category listing will be deleted.');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No categories added yet. Create one on the left.</td>
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
