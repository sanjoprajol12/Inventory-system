<?php
require_once 'config.php';

try {
    // Create users table with role column
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);

    // Create default admin user if not exists
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $check_admin = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    
    if ($check_admin->num_rows == 0) {
        $sql = "INSERT INTO users (full_name, username, password, email, role) 
                VALUES ('System Admin', 'admin', ?, 'admin@example.com', 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $admin_password);
        $stmt->execute();
        echo "Admin user created successfully<br>";
    }

    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Products table created successfully<br>";
    } else {
        echo "Error creating products table: " . $conn->error . "<br>";
    }
    
    // No sample products to insert
    // You can comment out or remove the following lines
    /*
    $sql = "INSERT IGNORE INTO products (name, quantity, price) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($sample_products as $product) {
        $stmt->bind_param("sid", $product[0], $product[1], $product[2]);
        if ($stmt->execute()) {
            echo "Sample product '{$product[0]}' created successfully<br>";
        } else {
            echo "Error creating sample product '{$product[0]}': " . $stmt->error . "<br>";
        }
    }
    */
    
    echo "<br>Database setup completed!<br>";
    echo "You can now login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>