<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . SITE_URL . "/services/");
    exit();
}

$service_id = intval($_GET['id']);
$service = getServiceById($service_id);

if (!$service) {
    header("Location: " . SITE_URL . "/services/");
    exit();
}

$page_title = htmlspecialchars($service['title']);
$is_owner = isLoggedIn() && $_SESSION['user_id'] == $service['user_id'];

// Determine if user can message
$can_message = true;
if (isLoggedIn() && $_SESSION['user_type'] === 'seller') {
    // Sellers can only message others about services they do not own
    if ($_SESSION['user_id'] == $service['user_id']) {
        $can_message = false;
    }
}
?>

<section class="featured-listings">
    <div class="container">
        <div class="listing-card single-listing">
            <div class="listing-img">
                <img src="<?php echo $service['image_path'] ? htmlspecialchars($service['image_path']) : SITE_URL . '/assets/images/service-default.jpg'; ?>" alt="<?php echo $page_title; ?>">
            </div>
            <div class="listing-details">
                <h1><?php echo $page_title; ?></h1>
                <div class="listing-meta">
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($service['full_name'] ?? 'Unknown'); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($service['location'] ?? 'Unknown'); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($service['contact_number'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($service['email'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category_name'] ?? 'Uncategorized'); ?></span>
                </div>
                
                <div class="listing-description">
                    <p><?php echo nl2br(htmlspecialchars($service['description'] ?? '')); ?></p>
                </div>

                <?php if (isLoggedIn() && !$is_owner): ?>
                    <section class="product-message-box">
                        <h3>Contact Seller About This Service</h3>

                        <?php if (!$can_message && $_SESSION['user_type'] === 'seller'): ?>
                            <div class="alert alert-warning">
                                You can only reply to messages for your own services.
                            </div>
                        <?php else: ?>
                            <form action="../users/send_messages.php" method="POST">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $service['user_id']; ?>">
                                <div class="form-group">
                                    <textarea name="message" class="form-control" placeholder="Your message about <?php echo $page_title; ?>..." required></textarea>
                                </div>
                                <button type="submit" class="submit-btn">Send Message</button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($is_owner): ?>
                    <div class="owner-actions mt-2">
                        <a href="manage.php?action=edit&id=<?php echo $service['id']; ?>" class="btn">Edit Service</a>
                        <a href="manage.php?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to delete this service?')">Delete Service</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .product-message-box {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-top: 25px;
        border: 1px solid #eee;
    }
    .product-message-box h3 {
        color: var(--primary-color);
        margin-bottom: 15px;
    }
    .alert-warning {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        padding: 12px;
        border-radius: 5px;
        color: #856404;
        margin-bottom: 15px;
    }
</style>

<?php
require_once '../includes/footer.php';
?>
