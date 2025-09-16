<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$page_title = 'Services';
$categories = getCategories();

// Get search parameters
$search_query = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? null;

// Get services based on search or category
if (!empty($search_query)) {
    $services = searchServices($search_query, $category_id);
    $page_title = 'Search Results for "' . htmlspecialchars($search_query) . '"';
} elseif ($category_id) {
    $services = searchServices('', $category_id);
    $category = array_filter($categories, function($cat) use ($category_id) {
        return $cat['id'] == $category_id;
    });
    $category = reset($category);
    $page_title = $category ? $category['name'] : 'Services';
} else {
    $services = getFeaturedServices();
}
?>

<section class="featured-listings">
    <div class="container">
        <div class="section-title">
            <h2><?php echo $page_title; ?></h2>
        </div>
        
        <div class="search-bar mb-2">
            <form action="" method="GET" class="search-form">
                <div class="search-inputs">
                    <input type="text" name="q" placeholder="Search for services..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>
        
        <?php if (empty($services)): ?>
            <div class="text-center py-2">
                <p>No services found. Try a different search.</p>
                <a href="<?php echo SITE_URL; ?>/services/" class="btn">Browse All Services</a>
            </div>
        <?php else: ?>
            <div class="listing-grid">
                <?php foreach ($services as $service): ?>
                    <div class="listing-card">
                        <div class="listing-img">
                            <img src="<?php echo $service['image_path'] ? htmlspecialchars($service['image_path']) : SITE_URL . '/assets/images/service-default.jpg'; ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
                        </div>
                        <div class="listing-details">
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <div class="listing-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($service['location']); ?></span>
                                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($service['contact_number']); ?></span>
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category_name']); ?></span>
                            </div>
                            <div class="listing-description">
                                <p><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                            </div>
                            <div class="listing-actions">
                                <a href="view.php?id=<?php echo $service['id']; ?>" class="contact-btn">View Details</a>
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
        <?php endif; ?>
    </div>
</section>

<?php
require_once '../includes/footer.php';
?>