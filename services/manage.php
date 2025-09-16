<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || $_SESSION['user_type'] != 'seller') {
    header("Location: " . SITE_URL);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$service_id = $_GET['id'] ?? 0;
$categories = getCategories();
$errors = [];
$success = false;

// Handle actions
if ($action == 'add' || $action == 'edit') {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        
        // Validate input
        if (empty($title)) {
            $errors['title'] = 'Title is required';
        }
        
        if (empty($category_id) || !array_filter($categories, function($cat) use ($category_id) {
            return $cat['id'] == $category_id;
        })) {
            $errors['category_id'] = 'Please select a valid category';
        }
        
        if (empty($description)) {
            $errors['description'] = 'Description is required';
        }
        
        if (empty($location)) {
            $errors['location'] = 'Location is required';
        }
        
        if (empty($contact_number)) {
            $errors['contact_number'] = 'Contact number is required';
        }
        
        // Handle file upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $errors['image'] = 'Only JPG, PNG, and GIF images are allowed';
            } else {
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = SITE_URL . '/assets/uploads/' . $file_name;
                } else {
                    $errors['image'] = 'Failed to upload image';
                }
            }
        } elseif ($action == 'edit' && empty($_FILES['image']['name'])) {
            // Keep existing image if not uploading new one
            $service = getServiceById($service_id);
            $image_path = $service['image_path'];
        }
        
        // If no errors, save service
        if (empty($errors)) {
            $service_data = [
                'category_id' => $category_id,
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'contact_number' => $contact_number,
                'image_path' => $image_path
            ];
            
            if ($action == 'add') {
                $result = addService($service_data, $user_id);
                if ($result) {
                    $success = true;
                    header("Location: manage.php?action=edit&id=$result");
                    exit();
                } else {
                    $errors['general'] = 'Failed to add service. Please try again.';
                }
            } else {
                $result = updateService($service_id, $service_data);
                if ($result) {
                    $success = true;
                } else {
                    $errors['general'] = 'Failed to update service. Please try again.';
                }
            }
        }
    }
    
    // For edit action, load existing service data
    if ($action == 'edit' && empty($_POST)) {
        $service = getServiceById($service_id);
        if (!$service || $service['user_id'] != $user_id) {
            header("Location: manage.php");
            exit();
        }
    }
} elseif ($action == 'delete') {
    $service = getServiceById($service_id);
    if ($service && $service['user_id'] == $user_id) {
        deleteService($service_id);
    }
    header("Location: manage.php");
    exit();
}

// Set page title
$page_title = 'Manage Services';
if ($action == 'add') $page_title = 'Add New Service';
if ($action == 'edit') $page_title = 'Edit Service';

require_once '../includes/header.php';
?>

<?php if ($action == 'list'): ?>
    <section class="featured-listings">
        <div class="container">
            <div class="section-title">
                <h2>My Services</h2>
                <a href="manage.php?action=add" class="btn">Add New Service</a>
            </div>
            
            <?php $services = getUserServices($user_id); ?>
            
            <?php if (empty($services)): ?>
                <div class="text-center py-2">
                    <p>You haven't added any services yet.</p>
                    <a href="manage.php?action=add" class="btn">Add Your First Service</a>
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
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category_name']); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($service['location']); ?></span>
                                </div>
                                <div class="listing-description">
                                    <p><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                </div>
                                <div class="listing-actions">
                                    <a href="view.php?id=<?php echo $service['id']; ?>" class="contact-btn">View</a>
                                    <a href="manage.php?action=edit&id=<?php echo $service['id']; ?>" class="btn-outline">Edit</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <section class="contact">
        <div class="container">
            <div class="section-title">
                <h2><?php echo $page_title; ?></h2>
                <a href="manage.php" class="btn-outline">Back to My Services</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Service <?php echo $action == 'add' ? 'added' : 'updated'; ?> successfully!</p>
                </div>
            <?php elseif (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($errors['general']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="contact-form">
                <form action="manage.php?action=<?php echo $action; ?><?php echo $action == 'edit' ? '&id=' . $service_id : ''; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Service Title</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? $service['title'] ?? ''); ?>" required>
                        <?php if (isset($errors['title'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['title']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) || (isset($service['category_id']) && $service['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['category_id']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($_POST['description'] ?? $service['description'] ?? ''); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['description']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? $service['location'] ?? ''); ?>" required>
                        <?php if (isset($errors['location'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['location']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['contact_number'] ?? $service['contact_number'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_number'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['contact_number']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Service Image</label>
                        <input type="file" id="image" name="image" class="form-control">
                        <?php if (isset($errors['image'])): ?>
                            <small class="text-danger"><?php echo htmlspecialchars($errors['image']); ?></small>
                        <?php endif; ?>
                        <?php if ($action == 'edit' && !empty($service['image_path'])): ?>
                            <div class="current-image mt-1">
                                <p>Current Image:</p>
                                <img src="<?php echo htmlspecialchars($service['image_path']); ?>" alt="Current service image" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="submit-btn"><?php echo ucfirst($action); ?> Service</button>
                </form>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>