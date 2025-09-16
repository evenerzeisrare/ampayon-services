<?php
require_once '../includes/config.php';
require_once '../includes/db_functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; // 'seller' or 'buyer'
$action    = $_GET['action'] ?? 'inbox';
$message_id = intval($_GET['id'] ?? 0);
$errors    = [];
$success   = false;

/* ---------- Handle sending/reply ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id  = intval($_POST['parent_id'] ?? 0);
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : null;
    $message    = trim($_POST['message'] ?? '');

    if ($message === '') {
        $errors['message'] = 'Message cannot be empty.';
    }

    if (!$errors) {
        $conn = getDBConnection();

        // Validate parent thread and determine receiver
        if ($parent_id) {
            $stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->bind_param("i", $parent_id);
            $stmt->execute();
            $parent_msg = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$parent_msg) {
                $conn->close();
                header("Location: messages.php?action=inbox&error=invalid_thread");
                exit();
            }

            $receiver_id = ($parent_msg['sender_id'] == $user_id)
                ? $parent_msg['receiver_id']
                : $parent_msg['sender_id'];

            if ($parent_msg['service_id'] && $user_type === 'seller') {
                $service = getServiceById($parent_msg['service_id']);
                if (!$service || !in_array($service['user_id'], [$user_id, $receiver_id])) {
                    $conn->close();
                    header("Location: messages.php?action=view&id={$parent_id}&error=not_allowed");
                    exit();
                }
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO messages 
            (parent_id, sender_id, receiver_id, service_id, message, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iiiis", $parent_id, $user_id, $receiver_id, $service_id, $message);

        if ($stmt->execute()) {
            if (function_exists('sendMessageNotification')) {
                sendMessageNotification($receiver_id, $user_id, $message, $service_id);
            }
            $stmt->close();
            $conn->close();
            $success = true;
            header("Location: messages.php?action=" . ($parent_id ? "view&id={$parent_id}" : "inbox") . "&sent=1");
            exit();
        } else {
            $errors['general'] = "Failed to send message.";
        }
        $stmt->close();
        $conn->close();
    }
}

/* ---------- Fetch messages ---------- */
$conn = getDBConnection();
$messages = [];

// Inbox
if ($action == 'inbox') {
    $sql = "SELECT m.*, u.full_name AS sender_name, s.title AS service_title
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN services s ON m.service_id = s.id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

// Sent
} elseif ($action == 'sent') {
    $sql = "SELECT m.*, u.full_name AS receiver_name, s.title AS service_title
            FROM messages m
            JOIN users u ON m.receiver_id = u.id
            LEFT JOIN services s ON m.service_id = s.id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

// View conversation
} elseif ($action == 'view' && $message_id) {
    $thread_id = $message_id;

    // Find root thread ID
    $root_stmt = $conn->prepare("SELECT parent_id FROM messages WHERE id = ?");
    $root_stmt->bind_param("i", $message_id);
    $root_stmt->execute();
    $root_result = $root_stmt->get_result()->fetch_assoc();
    $root_stmt->close();

    if ($root_result && $root_result['parent_id'] != 0) {
        $thread_id = $root_result['parent_id'];
    }

    // Mark thread as read
    $update = $conn->prepare(
        "UPDATE messages 
         SET is_read = 1 
         WHERE (id = ? OR parent_id = ?) AND receiver_id = ?"
    );
    $update->bind_param("iii", $thread_id, $thread_id, $user_id);
    $update->execute();
    $update->close();

    // Fetch thread messages
    $sql = "SELECT m.*, u.full_name AS sender_name, s.title AS service_title
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN services s ON m.service_id = s.id
            WHERE m.id = ? OR m.parent_id = ?
            ORDER BY m.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $thread_id, $thread_id);
}

if (isset($stmt)) {
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Unread count
$unread_stmt = $conn->prepare(
    "SELECT COUNT(*) AS unread_count 
     FROM messages 
     WHERE receiver_id = ? AND is_read = 0"
);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread_count'] ?? 0;
$unread_stmt->close();
$conn->close();

// Title
$page_title = ucfirst($action) . ' Messages' . ($unread_count > 0 ? " ($unread_count)" : "");
if ($action == 'view' && !empty($messages)) {
    $page_title = 'Conversation';
}

require_once '../includes/header.php';
?>

<section class="featured-listings">
    <div class="container">
        <div class="section-title">
            <h2><?= htmlspecialchars($page_title) ?></h2>
            <div class="message-actions">
                <a href="<?= SITE_URL; ?>/users/messages.php?action=inbox"
                   class="btn <?= $action == 'inbox' ? 'active' : ''; ?>">
                   Inbox <?= $unread_count > 0 ? "<span class='badge'>$unread_count</span>" : ""; ?>
                </a>
                <a href="<?= SITE_URL; ?>/users/messages.php?action=sent"
                   class="btn <?= $action == 'sent' ? 'active' : ''; ?>">Sent</a>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">Message sent successfully!</div>
        <?php endif; ?>

        <?php if ($action == 'view'): ?>
            <?php if (empty($messages)): ?>
                <p>No messages found in this conversation.</p>
            <?php else: ?>
                <div class="message-view">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $display_name = $msg['sender_id'] == $user_id
                            ? ($msg['receiver_name'] ?? $msg['sender_name'] ?? 'Unknown User')
                            : ($msg['sender_name'] ?? 'Unknown User');
                        ?>
                        <div class="message-bubble <?= $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <div class="message-header">
                                <strong><?= htmlspecialchars($display_name ?? 'Unknown'); ?></strong>
                                <small><?= date('M j, Y g:i A', strtotime($msg['created_at'])); ?></small>
                                <?php if (!empty($msg['service_title'])): ?>
                                    | <i class="fas fa-tag"></i> <?= htmlspecialchars($msg['service_title']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($msg['message'] ?? '')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="message-reply" id="reply">
                        <form action="send_messages.php" method="POST">
                            <input type="hidden" name="parent_id" 
                                   value="<?= $messages[0]['parent_id'] ?: $messages[0]['id']; ?>">
                            <input type="hidden" name="receiver_id"
                                   value="<?= $messages[0]['sender_id'] == $user_id 
                                       ? $messages[0]['receiver_id'] 
                                       : $messages[0]['sender_id']; ?>">
                            <?php if (!empty($messages[0]['service_id'])): ?>
                                <input type="hidden" name="service_id" 
                                       value="<?= $messages[0]['service_id']; ?>">
                            <?php endif; ?>
                            <textarea name="message" class="form-control" placeholder="Type your reply..." required></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <small class="text-danger"><?= htmlspecialchars($errors['message']); ?></small>
                            <?php endif; ?>
                            <button type="submit" class="submit-btn">Send Reply</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="messages-list">
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $name = $action == 'inbox'
                        ? ($msg['sender_name'] ?? 'Unknown Sender')
                        : ($msg['receiver_name'] ?? 'Unknown Receiver');
                    $threadId = $msg['parent_id'] ? $msg['parent_id'] : $msg['id'];
                    ?>
                    <div class="message-item <?= (!$msg['is_read'] && $msg['receiver_id'] == $user_id) ? 'unread' : ''; ?>">
                        <div>
                            <h4><?= $action == 'inbox' ? 'From: ' : 'To: '; echo htmlspecialchars($name ?? 'Unknown'); ?></h4>
                            <p><?= htmlspecialchars(substr($msg['message'] ?? '', 0, 100)); ?>...</p>
                            <small><?= date('M j, Y g:i A', strtotime($msg['created_at'])); ?></small>
                        </div>
                        <div>
                            <a href="messages.php?action=view&id=<?= $threadId; ?>" class="btn">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.message-bubble {
    padding: 12px 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    max-width: 70%;
}
.message-bubble.sent {
    background: #e3f2fd;
    margin-left: auto;
}
.message-bubble.received {
    background: #f1f1f1;
    margin-right: auto;
}
.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}
.message-content {
    margin-top: 5px;
}
.badge {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.8rem;
    margin-left: 5px;
}
.unread {
    border-left: 4px solid #3498db;
    background-color: #f8f9fa;
}
.message-item {
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.message-reply {
    margin-top: 20px;
}
</style>
