<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$page_title = 'Contact Us';
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate input
    if (empty($name)) $errors['name'] = 'Name is required';
    if (empty($email)) $errors['email'] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
    if (empty($message)) $errors['message'] = 'Message is required';

    if (empty($errors)) {
        // Save to database
        $conn = getDBConnection();
        $sql = "INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $message);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}
?>

<section class="contact">
    <div class="container">
        <div class="section-title">
            <h2>Contact Us</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Thank you! We'll respond soon.</div>
        <?php elseif (!empty($errors)): ?>
            <div class="alert alert-danger">Please fix the errors below.</div>
        <?php endif; ?>
        
        <form method="POST" class="contact-form">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <small class="text-danger"><?= $errors['name'] ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <small class="text-danger"><?= $errors['email'] ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <small class="text-danger"><?= $errors['message'] ?></small>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="submit-btn">Send Message</button>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>