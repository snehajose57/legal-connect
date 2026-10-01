<?php
$page_title = "Client Chat Support";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('lawyer');

$lawyer_id = $_SESSION['user_id'];
$error_msg = "";

// Fetch active clients (who have booked appointments with this lawyer)
$clients = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT u.user_id, u.name, u.email, u.profile_image 
                            FROM users u 
                            JOIN appointments a ON u.user_id = a.user_id 
                            WHERE a.lawyer_id = ? 
                            ORDER BY u.name ASC");
    $stmt->execute([$lawyer_id]);
    $clients = $stmt->fetchAll();

    // Fetch counts for badges
    $pending_count = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_count->execute([$lawyer_id]);
    $count_pending = $pending_count->fetchColumn();

    $pending_qa = $conn->prepare("SELECT COUNT(*) FROM legal_questions WHERE lawyer_id = ? AND status = 'Pending'");
    $pending_qa->execute([$lawyer_id]);
    $count_pending_qa = $pending_qa->fetchColumn();
} catch (PDOException $e) {
    $error_msg = "Error loading contacts: " . $e->getMessage();
}

// Check if a client is pre-selected
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$selected_client = null;
if ($selected_user_id) {
    foreach ($clients as $c) {
        if ($c['user_id'] === $selected_user_id) {
            $selected_client = $c;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Chat Support | Legal Connect</title>
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
            <a class="nav-link" href="/legalconnect/lawyer/support.php"><i class="fa-solid fa-circle-question"></i> Support Q&A <span class="badge bg-danger ms-auto"><?php echo $count_pending_qa; ?></span></a>
            <a class="nav-link active" href="/legalconnect/lawyer/chat.php"><i class="fa-solid fa-comments"></i> Client Chat</a>
            <a class="nav-link" href="/legalconnect/lawyer/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Details</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden; padding: 25px;">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Client Consultation Chat</h2>
                <p class="text-muted mb-0">Consult directly with clients who hold active schedules.</p>
            </div>
        </header>

        <!-- Message Box split screen layout -->
        <div class="row g-3 flex-grow-1 overflow-hidden" style="min-height: 0;">
            <!-- Left Side Contacts column -->
            <div class="col-md-4 h-100 overflow-y-auto">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-warning me-2"></i>Active Clients</h5>
                    <div class="list-group list-group-flush">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <a href="/legalconnect/lawyer/chat.php?user_id=<?php echo $client['user_id']; ?>" class="list-group-item list-group-item-action border-0 px-2 py-3 rounded mb-2 d-flex align-items-center gap-3 <?php echo ($selected_user_id === $client['user_id']) ? 'bg-light border-start border-warning border-3' : ''; ?>">
                                    <?php if ($client['profile_image'] && $client['profile_image'] !== 'default_user.png'): ?>
                                        <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($client['profile_image']); ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="avatar-circle"><?php echo substr($client['name'], 0, 1); ?></div>
                                    <?php endif; ?>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($client['name']); ?></div>
                                        <small class="text-muted text-truncate d-block"><?php echo htmlspecialchars($client['email']); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-users-slash fs-2 mb-2 text-muted"></i>
                                <div class="small">No active clients.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side Messaging Panel -->
            <div class="col-md-8 h-100">
                <?php if ($selected_client): ?>
                    <div class="chat-container h-100">
                        <div class="chat-header">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($selected_client['profile_image'] && $selected_client['profile_image'] !== 'default_user.png'): ?>
                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($selected_client['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle bg-warning text-dark"><?php echo substr($selected_client['name'], 0, 1); ?></div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="fw-bold text-light mb-0"><?php echo htmlspecialchars($selected_client['name']); ?></h6>
                                    <small class="text-white-50"><?php echo htmlspecialchars($selected_client['email']); ?></small>
                                </div>
                            </div>
                            <span class="badge bg-success rounded-pill px-3">Secure Connection</span>
                        </div>

                        <!-- Chat Messages Feed -->
                        <div class="chat-messages" id="chatMessagesBox">
                            <div class="text-center my-auto py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                                Loading chat history...
                            </div>
                        </div>

                        <!-- Message Inputs -->
                        <div class="chat-input-area">
                            <form id="sendMessageForm" class="d-flex gap-2">
                                <input type="hidden" id="chatUserId" value="<?php echo $selected_client['user_id']; ?>">
                                <input type="hidden" id="chatLawyerId" value="<?php echo $lawyer_id; ?>">
                                <input type="text" class="form-control rounded-pill px-4" id="chatMsgInput" required placeholder="Type legal advice or message here..." autocomplete="off">
                                <button type="submit" class="btn btn-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-paper-plane fs-5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fa-solid fa-comments-text text-muted mb-3" style="font-size: 4rem;"></i>
                        <h4 class="fw-bold text-dark">No Client Selected</h4>
                        <p class="text-muted mx-auto" style="max-width: 400px;">Select a registered client from the left directory to initialize secure, live consulting sessions.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php if ($selected_client): ?>
<script>
$(document).ready(function() {
    const userId = $('#chatUserId').val();
    const lawyerId = $('#chatLawyerId').val();
    const messagesBox = $('#chatMessagesBox');
    
    let lastMsgCount = 0;

    // Load messages
    function fetchChatLog() {
        $.ajax({
            url: '/legalconnect/api/chat_handler.php',
            type: 'GET',
            data: {
                action: 'fetch_messages',
                user_id: userId,
                lawyer_id: lawyerId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.messages.length === 0) {
                        html = '<div class="text-center py-5 text-muted small">No messages exchanged yet. Start the conversation!</div>';
                    } else {
                        response.messages.forEach(function(msg) {
                            const isMe = (msg.sender_type === 'lawyer');
                            const bubbleClass = isMe ? 'message-sent' : 'message-received';
                            html += `
                                <div class="message-bubble ${bubbleClass}">
                                    <div>${msg.message}</div>
                                    <span class="message-time">${msg.time}</span>
                                </div>
                            `;
                        });
                    }
                    
                    messagesBox.html(html);

                    // Auto scroll to bottom only on new messages
                    if (response.messages.length !== lastMsgCount) {
                        messagesBox.animate({ scrollTop: messagesBox.prop("scrollHeight") }, 300);
                        lastMsgCount = response.messages.length;
                    }
                } else {
                    messagesBox.html(`<div class="alert alert-danger py-2 small">${response.error}</div>`);
                }
            },
            error: function() {
                messagesBox.html('<div class="alert alert-danger py-2 small">AJAX Error: failed to load chat logs.</div>');
            }
        });
    }

    // Call once
    fetchChatLog();

    // Poll every 2 seconds
    const intervalId = setInterval(fetchChatLog, 2000);

    // Clean interval when leaving page
    $(window).on('beforeunload', function() {
        clearInterval(intervalId);
    });

    // Send Message
    $('#sendMessageForm').on('submit', function(e) {
        e.preventDefault();
        const msgText = $('#chatMsgInput').val().trim();
        if (msgText === '') return;

        $('#chatMsgInput').val('');

        $.ajax({
            url: '/legalconnect/api/chat_handler.php?action=send_message',
            type: 'POST',
            data: {
                user_id: userId,
                lawyer_id: lawyerId,
                message: msgText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    fetchChatLog();
                } else {
                    alert('Error: ' + response.error);
                }
            }
        });
    });
});
</script>
<?php endif; ?>
</body>
</html>
