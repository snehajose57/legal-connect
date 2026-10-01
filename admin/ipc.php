<?php
$page_title = "Manage IPC Sections";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg = "";
$success_msg = "";

// Edit values fallback
$edit_mode = false;
$edit_id = "";
$edit_section = "";
$edit_title = "";
$edit_desc = "";

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM ipc_sections WHERE ipc_id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = "IPC Section deleted successfully!";
        header("Location: /legalconnect/admin/ipc.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error deleting IPC section: " . $e->getMessage();
    }
}

// Handle Add / Edit Submission
if (isset($_POST['save_ipc'])) {
    $sec_num = sanitize($_POST['section_number']);
    $sec_title = sanitize($_POST['title']);
    $sec_desc = sanitize($_POST['description']);
    $form_action = sanitize($_POST['form_action']);

    if (empty($sec_num) || empty($sec_title) || empty($sec_desc)) {
        $error_msg = "All fields are required!";
    } else {
        try {
            if ($form_action === 'add') {
                $stmt = $conn->prepare("INSERT INTO ipc_sections (section_number, title, description) VALUES (?, ?, ?)");
                $stmt->execute([$sec_num, $sec_title, $sec_desc]);
                $_SESSION['success'] = "IPC Section added successfully!";
            } elseif ($form_action === 'edit') {
                $ipc_id = (int)$_POST['ipc_id'];
                $stmt = $conn->prepare("UPDATE ipc_sections SET section_number = ?, title = ?, description = ? WHERE ipc_id = ?");
                $stmt->execute([$sec_num, $sec_title, $sec_desc, $ipc_id]);
                $_SESSION['success'] = "IPC Section updated successfully!";
            }
            header("Location: /legalconnect/admin/ipc.php");
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
    $stmt = $conn->prepare("SELECT * FROM ipc_sections WHERE ipc_id = ?");
    $stmt->execute([$edit_id]);
    $ipc = $stmt->fetch();
    if ($ipc) {
        $edit_section = $ipc['section_number'];
        $edit_title = $ipc['title'];
        $edit_desc = $ipc['description'];
    } else {
        $edit_mode = false;
    }
}

// Fetch all IPC Sections
$ipc_sections = [];
try {
    $ipc_sections = $conn->query("SELECT * FROM ipc_sections ORDER BY ipc_id DESC")->fetchAll();
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
    <title>Manage IPC Sections | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
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
                <h2 class="fw-bold text-dark mb-1">Manage IPC Sections</h2>
                <p class="text-muted mb-0">Establish legal awareness records for client dashboards.</p>
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
                        <?php echo $edit_mode ? "Modify IPC Code" : "Add IPC Section"; ?>
                    </h5>
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="ipc_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="section_number" class="form-label fw-bold">Section Identifier</label>
                            <input type="text" class="form-control" id="section_number" name="section_number" required placeholder="e.g. Section 378" value="<?php echo htmlspecialchars($edit_section); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">IPC Title</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Theft" value="<?php echo htmlspecialchars($edit_title); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Legal Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Explain parameters, punishments, and fine provisions of this legal code..."><?php echo htmlspecialchars($edit_desc); ?></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gold flex-grow-1" name="save_ipc">
                                <i class="fa-solid fa-save me-2"></i>Save IPC Code
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="/legalconnect/admin/ipc.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right List Side -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-book text-warning me-2"></i>Active IPC Database</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%">Section</th>
                                    <th style="width: 25%">Title</th>
                                    <th style="width: 40%">Description</th>
                                    <th style="width: 15%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ipc_sections)): ?>
                                    <?php foreach ($ipc_sections as $ipc): ?>
                                        <tr>
                                            <td class="fw-bold text-warning"><?php echo htmlspecialchars($ipc['section_number']); ?></td>
                                            <td class="fw-bold text-dark small"><?php echo htmlspecialchars($ipc['title']); ?></td>
                                            <td class="text-muted small"><?php echo nl2br(htmlspecialchars(substr($ipc['description'], 0, 150))) . (strlen($ipc['description']) > 150 ? '...' : ''); ?></td>
                                            <td class="text-center">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="/legalconnect/admin/ipc.php?action=edit&id=<?php echo $ipc['ipc_id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="/legalconnect/admin/ipc.php?action=delete&id=<?php echo $ipc['ipc_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this IPC Section?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No IPC sections added yet. Create one on the left.</td>
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
