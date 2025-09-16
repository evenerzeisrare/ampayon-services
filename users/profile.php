<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

$user = getCurrentUser();
$page_title = 'My Profile';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $data = [
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
        'address' => $address
    ];
    
    // Only update password if provided
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        } else {
            $data['password'] = $password;
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate full name
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    }
    
    // Validate address
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        if (updateUserProfile($user['id'], $data)) {
            $success = true;
            $user = getCurrentUser(); // Refresh user data
        } else {
            $errors['general'] = 'Failed to update profile. Please try again.';
        }
    }
}
?>

<section class="contact">
    <div class="container">
        <div class="section-title">
            <h2>My Profile</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>Profile updated successfully!</p>
            </div>
        <?php elseif (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <p><?php echo htmlspecialchars($errors['general']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="contact-form">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['full_name']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    <?php if (isset($errors['phone'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['phone']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['address']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="user_type">Account Type</label>
                    <input type="text" id="user_type" class="form-control" 
                           value="<?php echo ucfirst($user['user_type']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <?php if (isset($errors['password'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['password']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['confirm_password']); ?></small>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="submit-btn">Update Profile</button>
            </form>
        </div>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>