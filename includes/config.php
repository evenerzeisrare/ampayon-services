<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_services');

// Site configuration
define('SITE_NAME', 'AMPAYON SERVICES');
define('SITE_URL', 'http://localhost/barangay-services');

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include other required files
require_once 'db_functions.php';
require_once 'auth_functions.php';
?>