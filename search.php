<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$page_title = 'Search Results';
$query = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    $conn = getDBConnection();
    $sql = "SELECT s.*, c.name as category_name 
            FROM services s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.title LIKE ? OR s.description LIKE ? OR c.name LIKE ?";
    $likeQuery = "%$query%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
    $conn->close();
}
?>

<section class="search-results">
    <div class="container">
        <div class="section-title">
            <h2>Search Results for: "<?php echo htmlspecialchars($query); ?>"</h2>
        </div>
        
        <?php if (empty($results)): ?>
            <p>No services found.</p>
        <?php else: ?>
            <div class="listing-grid">
                <?php foreach ($results as $service): ?>
                    <div class="listing-card">
                        <div class="listing-img">
                            <img src="<?php echo $service['image_path'] ?: SITE_URL.'/assets/images/service-default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($service['title']); ?>">
                        </div>
                        <div class="listing-details">
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                            <a href="services/view.php?id=<?php echo $service['id']; ?>" class="contact-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
