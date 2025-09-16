<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Barangay Services Directory logo showing two hands shaking, representing community connection">
                    <h1><?php echo SITE_NAME; ?></h1>
                </div>
                
                <nav>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/services" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services' ? 'active' : ''; ?>">Services</a></li>
                        <?php if (isLoggedIn()): ?>
                            <?php if ($_SESSION['user_type'] == 'seller'): ?>
                                <li><a href="<?php echo SITE_URL; ?>/services/manage.php">My Services</a></li>
                            <?php endif; ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>/users/messages.php">
                                    Messages
                                    <?php if (isset($unread_count) && $unread_count > 0): ?>
                                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><a href="<?php echo SITE_URL; ?>/users/profile.php">Profile</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/register.php">Register</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </nav>
                
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <style>
        .notification-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: 5px;
            display: inline-block;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }
        
        nav ul li a {
            position: relative;
            display: flex;
            align-items: center;
        }
    </style>
</body>
</html>