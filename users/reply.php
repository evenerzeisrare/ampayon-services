<?php
require_once '../includes/config.php'; // includes session_start()
require_once '../includes/db_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$parent_id = intval($_POST['parent_id'] ?? 0);
$message   = trim($_POST['message'] ?? '');
$sender_id = $_SESSION['user_id'] ?? null;

if (!$sender_id) {
    header("Location: ../login.php?error=not_logged_in");
    exit();
}

if ($message === '') {
    header("Location: messages.php?action=view&id={$parent_id}&error=empty_message");
    exit();
}

$conn = getDBConnection();

// ✅ Determine the root thread ID
$thread_id = $parent_id;
$stmt = $conn->prepare("SELECT id, parent_id, sender_id, receiver_id, service_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent_message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$parent_message) {
    $conn->close();
    header("Location: messages.php?action=inbox&error=invalid_thread");
    exit();
}

// If this message itself is a reply, use the original parent as the thread root
if (!empty($parent_message['parent_id'])) {
    $thread_id = $parent_message['parent_id'];
    // Fetch root message for accurate participants
    $stmt = $conn->prepare("SELECT id, sender_id, receiver_id, service_id FROM messages WHERE id = ?");
    $stmt->bind_param("i", $thread_id);
    $stmt->execute();
    $root_message = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($root_message) {
        $parent_message = $root_message;
    }
}

// ✅ Determine receiver (must be part of thread)
if ($parent_message['sender_id'] === $sender_id) {
    $receiver_id = $parent_message['receiver_id'];
} elseif ($parent_message['receiver_id'] === $sender_id) {
    $receiver_id = $parent_message['sender_id'];
} else {
    $conn->close();
    header("Location: messages.php?action=inbox&error=not_allowed");
    exit();
}

// ✅ For service-related threads, verify permissions for sellers
if (!empty($parent_message['service_id']) && ($_SESSION['user_type'] ?? '') === 'seller') {
    $service = getServiceById($parent_message['service_id']);
    if (!$service || !in_array($service['user_id'], [$sender_id, $receiver_id])) {
        $conn->close();
        header("Location: messages.php?action=view&id={$thread_id}&error=not_allowed");
        exit();
    }
}

// ✅ Insert reply linked to root thread
$stmt = $conn->prepare(
    "INSERT INTO messages (parent_id, sender_id, receiver_id, service_id, message, created_at)
     VALUES (?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param(
    "iiiis",
    $thread_id,
    $sender_id,
    $receiver_id,
    $parent_message['service_id'],
    $message
);

if ($stmt->execute()) {
    if (function_exists('sendMessageNotification')) {
        sendMessageNotification($receiver_id, $sender_id, $message, $parent_message['service_id']);
    }
    $stmt->close();
    $conn->close();
    // Redirect back to the root thread
    header("Location: messages.php?action=view&id={$thread_id}&sent=1#reply");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: messages.php?action=view&id={$thread_id}&error=failed");
    exit();
}
