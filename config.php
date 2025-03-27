<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "inventory_system";

try {
    // Create connection without database first
    $conn = new mysqli($host, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    if ($conn->query($sql) === TRUE) {
        // Select the database
        $conn->select_db($database);
    } else {
        die("Error creating database: " . $conn->error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-specific-password');
define('SMTP_FROM', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Inventory System');
?> 