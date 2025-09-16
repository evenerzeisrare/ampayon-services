<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL);
    exit();
}

$page_title = 'Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        if (loginUser($username, $password)) {
            header("Location: " . SITE_URL);
            exit();
        } else {
            $errors['general'] = 'Invalid username or password';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="contact">
    <div class="container">
        <div class="section-title">
            <h2>Login to Your Account</h2>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <p><?php echo htmlspecialchars($errors['general']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="contact-form">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['username']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <?php if (isset($errors['password'])): ?>
                        <small class="text-danger"><?php echo htmlspecialchars($errors['password']); ?></small>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="submit-btn">Login</button>
                
                <p class="text-center mt-1">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </form>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>