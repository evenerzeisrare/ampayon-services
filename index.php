<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$page_title = 'Home';
$categories = getCategories();
$featured_services = getFeaturedServices(6);
?>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-content">
        <h2>Connecting You to Local Services</h2>
        <p>Find the best services in your barangay with our comprehensive directory. Supporting local businesses has never been easier.</p>
        
        <form action="search.php" method="GET" class="search-bar">
            <input type="text" name="q" placeholder="Search for services...">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
</section>

<!-- Services Section -->
<section class="services" id="services">
    <div class="container">
        <div class="section-title">
            <h2>Service Categories</h2>
        </div>
        
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-img">
                        <img src="<?php echo SITE_URL; ?>/assets/images/category-<?php echo $category['id']; ?>.jpg" alt="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    <div class="category-info">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <a href="services/?category=<?php echo $category['id']; ?>" class="btn">View Listings</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Listings -->
<section class="featured-listings" id="listings">
    <div class="container">
        <div class="section-title">
            <h2>Featured Listings</h2>
        </div>
        
        <div class="listing-grid">
            <?php foreach ($featured_services as $service): ?>
                <div class="listing-card">
                    <div class="listing-img">
                        <img src="<?php echo $service['image_path'] ? htmlspecialchars($service['image_path']) : SITE_URL . '/assets/images/service-default.jpg'; ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
                    </div>
                    <div class="listing-details">
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <div class="listing-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($service['location']); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($service['contact_number']); ?></span>
                        </div>
                        <div class="listing-description">
                            <p><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                        </div>
                        <div class="listing-actions">
                            <a href="services/view.php?id=<?php echo $service['id']; ?>" class="contact-btn">View Details</a>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-2">
            <a href="services/" class="btn btn-outline">View All Listings</a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about" id="about">
    <div class="container">
        <div class="section-title">
            <h2>About Our Directory</h2>
        </div>
        
        <div class="about-content">
            <div class="about-img">
                <img src="<?php echo SITE_URL; ?>/assets/images/about.jpg" alt="Barangay community helping each other in a friendly neighborhood setting">
            </div>
            <div class="about-text">
                <h3>Supporting Our Community</h3>
                <p>The Ampayon Services was created to bridge the gap between local service providers and residents. Our goal is to make it easy for community members to find the services they need while supporting small businesses within the barangay.</p>
                <p>This platform showcases the diverse range of talents and services available right in your neighborhood. From essential goods to specialized skills, we connect you with trustworthy providers.</p>
                <p>All listings are verified by our barangay officials to ensure quality and reliability. We believe in community-powered solutions that benefit everyone.</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn mt-1">Join Our Community</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>