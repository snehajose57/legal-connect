<?php
$page_title = "Manage Lawyers";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('admin');

$error_msg = "";
$success_msg = "";

// Handle Lawyer Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitize($_GET['action']);
    $lawyer_id = (int) $_GET['id'];

    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE lawyers SET status = 'Approved' WHERE lawyer_id = ?");
            $stmt->execute([$lawyer_id]);
            $_SESSION['success'] = "Lawyer approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE lawyers SET status = 'Rejected' WHERE lawyer_id = ?");
            $stmt->execute([$lawyer_id]);
            $_SESSION['success'] = "Lawyer registration rejected.";
        } elseif ($action === 'block') {
            $stmt = $conn->prepare("UPDATE lawyers SET status = 'Blocked' WHERE lawyer_id = ?");
            $stmt->execute([$lawyer_id]);
            $_SESSION['success'] = "Lawyer blocked successfully.";
        } elseif ($action === 'unblock') {
            $stmt = $conn->prepare("UPDATE lawyers SET status = 'Approved' WHERE lawyer_id = ?");
            $stmt->execute([$lawyer_id]);
            $_SESSION['success'] = "Lawyer account restored successfully.";
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM lawyers WHERE lawyer_id = ?");
            $stmt->execute([$lawyer_id]);
            $_SESSION['success'] = "Lawyer deleted from system database.";
        }

        header("Location: /legalconnect/admin/lawyers.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Operation failed: " . $e->getMessage();
    }
}

// Search Filter
$search_query = "";
$params = [];
$sql = "SELECT * FROM lawyers";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = sanitize($_GET['search']);
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR specialization LIKE ? OR qualification LIKE ?";
    $params = ["%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"];
}

$sql .= " ORDER BY created_at DESC";

// Fetch Lawyers
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $lawyers = $stmt->fetchAll();

    $pending_users = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'Pending'")->fetchColumn();
    $pending_lawyers = $conn->query("SELECT COUNT(*) FROM lawyers WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error listing lawyers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lawyer Registrations | Legal Connect</title>
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
                <h4 class="text-white fw-bold mb-0"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Admin
                    Panel</h4>
            </div>
            <nav class="nav flex-column py-3">
                <a class="nav-link" href="/legalconnect/admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <a class="nav-link" href="/legalconnect/admin/categories.php"><i class="fa-solid fa-list-check"></i> Practice Areas</a>
                <a class="nav-link" href="/legalconnect/admin/ipc.php"><i class="fa-solid fa-book-bookmark"></i> IPC Sections</a>
                <a class="nav-link" href="/legalconnect/admin/legal_information.php"><i class="fa-solid fa-book-open"></i> Legal Information</a>
                <a class="nav-link" href="/legalconnect/admin/users.php"><i class="fa-solid fa-users"></i> Manage Clients <span class="badge bg-danger ms-auto"><?php echo $pending_users; ?></span></a>
                <a class="nav-link active" href="/legalconnect/admin/lawyers.php"><i class="fa-solid fa-user-tie"></i> Manage Lawyers <span class="badge bg-danger ms-auto"><?php echo $pending_lawyers; ?></span></a>
                <a class="nav-link" href="/legalconnect/admin/appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
                <a class="nav-link" href="/legalconnect/admin/monitoring.php"><i class="fa-solid fa-chart-line"></i> Monitoring &amp; Audit</a>
                <hr class="border-secondary mx-3">
                <a class="nav-link text-danger" href="/legalconnect/logout.php"><i
                        class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
            </nav>
        </aside>

        <!-- Main Content Panel -->
        <main class="main-content">
            <!-- Top Title Bar -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Manage Lawyers Directory</h2>
                    <p class="text-muted mb-0">Review qualifications, view bar certificate documents, and approve or
                        reject registrations.</p>
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
                    <i
                        class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search Card -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-9 col-sm-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i
                                        class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" name="search"
                                    placeholder="Search lawyers by name, specialty, or credentials..."
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-gold w-100"><i
                                        class="fa-solid fa-filter me-2"></i>Search</button>
                                <?php if (!empty($search_query)): ?>
                                    <a href="/legalconnect/admin/lawyers.php" class="btn btn-outline-secondary"
                                        title="Clear Search"><i class="fa-solid fa-rotate-left"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Directory Table Card -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-graduation-cap text-warning me-2"></i>Lawyer
                    Registry</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Photo</th>
                                <th>Name & Spec</th>
                                <th>Qualifications</th>
                                <th>Experience</th>
                                <th>Cert / License</th>
                                <th>Status</th>
                                <th class="text-center">Action Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lawyers)): ?>
                                <?php foreach ($lawyers as $lawyer): ?>
                                    <tr>
                                        <td>
                                            <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                                                <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>"
                                                    alt="Profile" class="rounded-circle"
                                                    style="width: 45px; height: 45px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="avatar-circle bg-warning text-dark">
                                                    <?php echo substr($lawyer['name'], 0, 1); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($lawyer['name']); ?>
                                            </div>
                                            <span
                                                class="badge bg-light text-dark border small mt-1"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($lawyer['qualification']); ?></div>
                                            <small class="text-muted"><i
                                                    class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($lawyer['phone']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($lawyer['experience']); ?> Years</td>
                                        <td>
                                            <a href="/legalconnect/uploads/certificates/<?php echo htmlspecialchars($lawyer['certificate']); ?>"
                                                target="_blank" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                <i class="fa-solid fa-file-pdf me-1"></i>View License
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-status <?php
                                            if ($lawyer['status'] === 'Pending')
                                                echo 'badge-pending';
                                            elseif ($lawyer['status'] === 'Approved')
                                                echo 'badge-approved';
                                            elseif ($lawyer['status'] === 'Blocked')
                                                echo 'badge-blocked';
                                            else
                                                echo 'badge-rejected';
                                            ?>"><?php echo $lawyer['status']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($lawyer['status'] === 'Pending'): ?>
                                                    <a href="/legalconnect/admin/lawyers.php?action=approve&id=<?php echo $lawyer['lawyer_id']; ?>"
                                                        class="btn btn-sm btn-success" title="Approve Lawyer Account">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </a>
                                                    <a href="/legalconnect/admin/lawyers.php?action=reject&id=<?php echo $lawyer['lawyer_id']; ?>"
                                                        class="btn btn-sm btn-danger" title="Reject Lawyer Registration">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </a>
                                                <?php else: ?>
                                                    <?php if ($lawyer['status'] === 'Blocked'): ?>
                                                        <a href="/legalconnect/admin/lawyers.php?action=unblock&id=<?php echo $lawyer['lawyer_id']; ?>"
                                                            class="btn btn-sm btn-outline-success" title="Unblock Lawyer">
                                                            <i class="fa-solid fa-circle-check"></i> Unblock
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/legalconnect/admin/lawyers.php?action=block&id=<?php echo $lawyer['lawyer_id']; ?>"
                                                            class="btn btn-sm btn-outline-danger" title="Block Lawyer">
                                                            <i class="fa-solid fa-ban"></i> Block
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="/legalconnect/admin/lawyers.php?action=delete&id=<?php echo $lawyer['lawyer_id']; ?>"
                                                        class="btn btn-sm btn-danger" title="Delete Profile"
                                                        onclick="return confirm('Are you sure you want to delete this lawyer profile?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Details Modal Trigger -->
                                                <button class="btn btn-sm btn-dark" data-bs-toggle="modal"
                                                    data-bs-target="#modal<?php echo $lawyer['lawyer_id']; ?>">
                                                    <i class="fa-solid fa-eye"></i> Info
                                                </button>
                                            </div>

                                            <!-- Bootstrap Details Modal -->
                                            <div class="modal fade" id="modal<?php echo $lawyer['lawyer_id']; ?>" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title fw-bold"><i
                                                                    class="fa-solid fa-user-tie text-warning me-2"></i>Profile
                                                                Details</h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <div class="text-center mb-4">
                                                                <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                                                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>"
                                                                        alt="Profile" class="rounded-circle border"
                                                                        style="width: 100px; height: 100px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div
                                                                        class="avatar-circle avatar-large bg-warning text-dark mb-2">
                                                                        <?php echo substr($lawyer['name'], 0, 1); ?></div>
                                                                <?php endif; ?>
                                                                <h4 class="fw-bold text-dark mt-2 mb-0">
                                                                    <?php echo htmlspecialchars($lawyer['name']); ?></h4>
                                                                <span
                                                                    class="badge bg-warning text-dark mt-1"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-6">
                                                                    <div class="fw-bold text-muted small">QUALIFICATION</div>
                                                                    <div class="text-dark">
                                                                        <?php echo htmlspecialchars($lawyer['qualification']); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="fw-bold text-muted small">EXPERIENCE</div>
                                                                    <div class="text-dark">
                                                                        <?php echo htmlspecialchars($lawyer['experience']); ?>
                                                                        Years</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="fw-bold text-muted small">EMAIL</div>
                                                                    <div class="text-dark text-truncate">
                                                                        <?php echo htmlspecialchars($lawyer['email']); ?></div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="fw-bold text-muted small">PHONE</div>
                                                                    <div class="text-dark">
                                                                        <?php echo htmlspecialchars($lawyer['phone']); ?></div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="fw-bold text-muted small">OFFICE ADDRESS</div>
                                                                    <div class="text-dark bg-light p-2 rounded small">
                                                                        <?php echo nl2br(htmlspecialchars($lawyer['address'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">No lawyer accounts registered yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>