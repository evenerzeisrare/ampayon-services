<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL);
    exit();
}

$page_title = 'Register';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $user_type = $_POST['user_type'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
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
    
    // Validate user type
    if (empty($user_type) || !in_array($user_type, ['seller', 'buyer'])) {
        $errors['user_type'] = 'Please select a valid user type';
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $userId = registerUser($username, $password, $email, $full_name, $phone, $address, $user_type);
        
        if ($userId) {
            $success = true;
            
            // Auto-login after registration
            loginUser($username, $password);
            header("Location: " . SITE_URL . "/users/profile.php");
            exit();
        } else {
            $errors['general'] = 'Registration failed. Username or email may already exist.';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="contact">
    <div class="container">
        <div class="section-title">
            <h2>Create an Account</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>Registration successful! You can now login.</p>
            </div>
        <?php elseif (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <p><?php echo htmlspecialchars($errors['general']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="contact-form">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="form-group">
                    <label for="user_type">I want to:</label>
                    <select id="user_type" name="user_type" class="form-control" required>
                        <option value="">Select account type</option>
                        <option value="seller" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'selected' : ''; ?>>Sell Services</option>
                        <option value="buyer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'buyer') ? 'selected' : ''; ?>>Find Services</option>
                    </select>
                    <?php if (isset($errors['user_type'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['user_type']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['username']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['full_name']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    <?php if (isset($errors['phone'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['phone']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['address']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <?php if (isset($errors['password'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['password']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['confirm_password']); ?></small>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="submit-btn">Register</button>
                
                <p class="text-center mt-1">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>