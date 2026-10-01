<?php
$page_title = "Chat Support with Lawyer";
require_once __DIR__ . '/../config/db.php';

// Access Control
check_login('user');

$user_id = $_SESSION['user_id'];
$error_msg = "";

// Fetch active lawyers (who user has scheduled appointments with)
$lawyers = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT l.lawyer_id, l.name, l.specialization, l.profile_image 
                            FROM lawyers l 
                            JOIN appointments a ON l.lawyer_id = a.lawyer_id 
                            WHERE a.user_id = ? AND a.status != 'Cancelled'
                            ORDER BY l.name ASC");
    $stmt->execute([$user_id]);
    $lawyers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error loading chat contacts: " . $e->getMessage();
}

// Check if a lawyer is pre-selected
$selected_lawyer_id = isset($_GET['lawyer_id']) ? (int)$_GET['lawyer_id'] : null;
$selected_lawyer = null;
if ($selected_lawyer_id) {
    // If not in appointments list, we still allow starting a chat if they clicked from lawyers list
    // (This is nice UX since they might want to ask a quick pre-consultation detail!)
    try {
        $stmt = $conn->prepare("SELECT lawyer_id, name, specialization, profile_image FROM lawyers WHERE lawyer_id = ? AND status = 'Approved'");
        $stmt->execute([$selected_lawyer_id]);
        $selected_lawyer = $stmt->fetch();
    } catch (PDOException $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Lawyer | Legal Connect</title>
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
            <a class="nav-link active" href="/legalconnect/user/chat.php"><i class="fa-solid fa-comments"></i> Chat Support</a>
            <a class="nav-link" href="/legalconnect/user/reports.php"><i class="fa-solid fa-chart-pie"></i> Client Reports</a>
            <a class="nav-link" href="/legalconnect/user/notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a class="nav-link" href="/legalconnect/user/profile.php"><i class="fa-solid fa-circle-user"></i> Profile Settings</a>
            <hr class="border-secondary mx-3">
            <a class="nav-link text-danger" href="/legalconnect/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        </nav>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden; padding: 25px;">
        <!-- Top Title Bar -->
        <header class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Advocate Chat Channels</h2>
                <p class="text-muted mb-0">Consult live with your verified legal advocates.</p>
            </div>
        </header>

        <!-- Message Box split screen layout -->
        <div class="row g-3 flex-grow-1 overflow-hidden" style="min-height: 0;">
            <!-- Left Side Contacts column -->
            <div class="col-md-4 h-100 overflow-y-auto">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments text-warning me-2"></i>Active Channels</h5>
                    <div class="list-group list-group-flush">
                        <?php if (!empty($lawyers)): ?>
                            <?php foreach ($lawyers as $lawyer_item): ?>
                                <a href="/legalconnect/user/chat.php?lawyer_id=<?php echo $lawyer_item['lawyer_id']; ?>" class="list-group-item list-group-item-action border-0 px-2 py-3 rounded mb-2 d-flex align-items-center gap-3 <?php echo ($selected_lawyer_id === $lawyer_item['lawyer_id']) ? 'bg-light border-start border-warning border-3' : ''; ?>">
                                    <?php if ($lawyer_item['profile_image'] && $lawyer_item['profile_image'] !== 'default_lawyer.png'): ?>
                                        <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer_item['profile_image']); ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="avatar-circle bg-warning text-dark"><?php echo substr($lawyer_item['name'], 0, 1); ?></div>
                                    <?php endif; ?>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-dark mb-0">Adv. <?php echo htmlspecialchars($lawyer_item['name']); ?></div>
                                        <small class="text-muted text-truncate d-block"><?php echo htmlspecialchars($lawyer_item['specialization']); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-comments-slash fs-2 mb-2 text-muted"></i>
                                <div class="small text-muted">No active chat channels. Schedule a consultation first!</div>
                                <a href="/legalconnect/user/lawyers.php" class="btn btn-sm btn-gold mt-3 rounded-pill px-3">Search Lawyers</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side Messaging Panel -->
            <div class="col-md-8 h-100">
                <?php if ($selected_lawyer): ?>
                    <div class="chat-container h-100">
                        <div class="chat-header">
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($selected_lawyer['profile_image'] && $selected_lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($selected_lawyer['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle bg-warning text-dark"><?php echo substr($selected_lawyer['name'], 0, 1); ?></div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="fw-bold text-light mb-0">Adv. <?php echo htmlspecialchars($selected_lawyer['name']); ?></h6>
                                    <small class="text-white-50"><?php echo htmlspecialchars($selected_lawyer['specialization']); ?></small>
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
                                <input type="hidden" id="chatUserId" value="<?php echo $user_id; ?>">
                                <input type="hidden" id="chatLawyerId" value="<?php echo $selected_lawyer['lawyer_id']; ?>">
                                <input type="text" class="form-control rounded-pill px-4" id="chatMsgInput" required placeholder="Type message here..." autocomplete="off">
                                <button type="submit" class="btn btn-warning rounded-circle d-flex align-items-center justify-content-center text-dark" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-paper-plane fs-5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center h-100 d-flex flex-column justify-content-center">
                        <i class="fa-solid fa-comments-text text-muted mb-3" style="font-size: 4rem;"></i>
                        <h4 class="fw-bold text-dark">No Chat Selected</h4>
                        <p class="text-muted mx-auto" style="max-width: 400px;">Select an advocate channel from the left sidebar directory to initialize secure consulting chats.</p>
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

<?php if ($selected_lawyer): ?>
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
                            const isMe = (msg.sender_type === 'user');
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
