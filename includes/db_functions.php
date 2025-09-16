<?php
// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Get all categories
function getCategories() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM categories";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    $conn->close();
    return $categories;
}

// Get featured services
function getFeaturedServices($limit = 6) {
    $conn = getDBConnection();
    $sql = "SELECT s.*, u.full_name, u.phone, c.name as category_name 
            FROM services s 
            JOIN users u ON s.user_id = u.id 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.status = 'active' 
            ORDER BY s.created_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $services;
}

// Search services
function searchServices($query, $category = null) {
    $conn = getDBConnection();
    
    if ($category) {
        $sql = "SELECT s.*, u.full_name, u.phone, c.name as category_name 
                FROM services s 
                JOIN users u ON s.user_id = u.id 
                JOIN categories c ON s.category_id = c.id 
                WHERE s.status = 'active' 
                AND (s.title LIKE ? OR s.description LIKE ?) 
                AND s.category_id = ?";
        
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $category);
    } else {
        $sql = "SELECT s.*, u.full_name, u.phone, c.name as category_name 
                FROM services s 
                JOIN users u ON s.user_id = u.id 
                JOIN categories c ON s.category_id = c.id 
                WHERE s.status = 'active' 
                AND (s.title LIKE ? OR s.description LIKE ?)";
        
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $services = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $services;
}

// Get service by ID
function getServiceById($id) {
    $conn = getDBConnection();
    $sql = "SELECT s.*, u.full_name, u.phone, u.email, c.name as category_name 
            FROM services s 
            JOIN users u ON s.user_id = u.id 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = null;
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $service;
}

// Get services by user
function getUserServices($userId) {
    $conn = getDBConnection();
    $sql = "SELECT s.*, c.name as category_name 
            FROM services s 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $services;
}

// Add new service
function addService($data, $userId) {
    $conn = getDBConnection();
    $sql = "INSERT INTO services 
            (user_id, category_id, title, description, location, contact_number, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iisssss", 
        $userId, 
        $data['category_id'], 
        $data['title'], 
        $data['description'], 
        $data['location'], 
        $data['contact_number'], 
        $data['image_path']
    );
    
    $success = $stmt->execute();
    $serviceId = $stmt->insert_id;
    
    $stmt->close();
    $conn->close();
    return $success ? $serviceId : false;
}

// Update service
function updateService($id, $data) {
    $conn = getDBConnection();
    $sql = "UPDATE services SET 
            category_id = ?, 
            title = ?, 
            description = ?, 
            location = ?, 
            contact_number = ?, 
            image_path = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssi", 
        $data['category_id'], 
        $data['title'], 
        $data['description'], 
        $data['location'], 
        $data['contact_number'], 
        $data['image_path'], 
        $id
    );
    
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}

// Delete service
function deleteService($id) {
    $conn = getDBConnection();
    $sql = "DELETE FROM services WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}

// Store a new message
function saveMessage($senderId, $receiverId, $serviceId, $messageText) {
    $conn = getDBConnection();
    $sql = "INSERT INTO messages (sender_id, receiver_id, service_id, message, sent_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $senderId, $receiverId, $serviceId, $messageText);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}
// Email notification function
function sendMessageNotification($receiver_id, $sender_id, $message, $service_id = null) {
    $conn = getDBConnection();

    // Get receiver email and username
    $stmt = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $receiver = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get sender username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $sender_id);
    $stmt->execute();
    $sender = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $conn->close();

    if ($receiver && $sender) {
        $to = $receiver['email'];
        $subject = "New message from " . $sender['username'];

        $body = "Hello " . $receiver['username'] . ",\n\n";
        $body .= "You have received a new message from " . $sender['username'] . ":\n\n";
        $body .= wordwrap($message, 70) . "\n\n";
        $body .= "To view the message, log in here: " . SITE_URL . "/users/messages.php";

        // Send the email (use PHPMailer in production)
        @mail($to, $subject, $body);
    }
}


?>