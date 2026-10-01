<?php
require_once __DIR__ . '/../config/db.php';

// Access check: User must be logged in to participate in chats
if (!isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$current_role = $_SESSION['role'];
$current_session_id = (int)$_SESSION['user_id'];

header('Content-Type: application/json');

if (isset($_GET['action'])) {
    $action = sanitize($_GET['action']);

    if ($action === 'fetch_messages') {
        $user_id = (int)$_GET['user_id'];
        $lawyer_id = (int)$_GET['lawyer_id'];

        // Access validation: one of the IDs must match current session ID based on role
        if (($current_role === 'user' && $current_session_id !== $user_id) || 
            ($current_role === 'lawyer' && $current_session_id !== $lawyer_id)) {
            echo json_encode(['error' => 'Forbidden Access']);
            exit();
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM chats WHERE user_id = ? AND lawyer_id = ? ORDER BY date_time ASC");
            $stmt->execute([$user_id, $lawyer_id]);
            $messages = $stmt->fetchAll();

            $formatted = [];
            foreach ($messages as $msg) {
                $formatted[] = [
                    'chat_id' => $msg['chat_id'],
                    'sender_type' => $msg['sender_type'],
                    'message' => htmlspecialchars($msg['message']),
                    'time' => date('h:i A', strtotime($msg['date_time']))
                ];
            }
            echo json_encode(['success' => true, 'messages' => $formatted]);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'send_message') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit();
        }

        $user_id = (int)$_POST['user_id'];
        $lawyer_id = (int)$_POST['lawyer_id'];
        $message = trim($_POST['message']);

        // Access validation
        if (($current_role === 'user' && $current_session_id !== $user_id) || 
            ($current_role === 'lawyer' && $current_session_id !== $lawyer_id)) {
            echo json_encode(['error' => 'Forbidden Access']);
            exit();
        }

        if (empty($message)) {
            echo json_encode(['error' => 'Message is empty']);
            exit();
        }

        try {
            $stmt = $conn->prepare("INSERT INTO chats (user_id, lawyer_id, sender_type, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $lawyer_id, $current_role, $message]);
            echo json_encode(['success' => true]);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }
}

echo json_encode(['error' => 'Invalid Action']);
exit();
?>
