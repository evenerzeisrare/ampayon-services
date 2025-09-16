<?php
// Register a new user
function registerUser($username, $password, $email, $full_name, $phone, $address, $user_type) {
    $conn = getDBConnection();
    
    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        return false; // Username or email already exists
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $insert_sql = "INSERT INTO users 
                   (username, password, email, full_name, phone, address, user_type) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param(
        "sssssss", 
        $username, 
        $hashed_password, 
        $email, 
        $full_name, 
        $phone, 
        $address, 
        $user_type
    );
    
    $success = $insert_stmt->execute();
    $userId = $insert_stmt->insert_id;
    
    $insert_stmt->close();
    $check_stmt->close();
    $conn->close();
    
    return $success ? $userId : false;
}

// Login user
function loginUser($username, $password) {
    $conn = getDBConnection();
    $sql = "SELECT id, username, password, user_type FROM users WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $sql = "SELECT * FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = null;
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $user;
}

// Update user profile
function updateUserProfile($userId, $data) {
    $conn = getDBConnection();
    
    // Check if email is being changed and already exists
    if (isset($data['email'])) {
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $data['email'], $userId);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            $conn->close();
            return false; // Email already exists
        }
    }
    
    // Build update query
    $update_fields = [];
    $params = [];
    $types = '';
    
    if (isset($data['email'])) {
        $update_fields[] = "email = ?";
        $params[] = $data['email'];
        $types .= 's';
    }
    
    if (isset($data['full_name'])) {
        $update_fields[] = "full_name = ?";
        $params[] = $data['full_name'];
        $types .= 's';
    }
    
    if (isset($data['phone'])) {
        $update_fields[] = "phone = ?";
        $params[] = $data['phone'];
        $types .= 's';
    }
    
    if (isset($data['address'])) {
        $update_fields[] = "address = ?";
        $params[] = $data['address'];
        $types .= 's';
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $update_fields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= 's';
    }
    
    if (empty($update_fields)) {
        $conn->close();
        return false; // Nothing to update
    }
    
    $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $userId;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return $success;
}
?>