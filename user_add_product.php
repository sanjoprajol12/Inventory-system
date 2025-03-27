<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Retrieve user's full name and role from session
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; // Default to 'User' if not set
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'; // Default to 'user' if not set

// Handle form submission
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert new product record
        $sql = "INSERT INTO products (name, quantity, price) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sid", $name, $quantity, $price);
        $stmt->execute();

        $conn->commit();
        $success_message = "Product added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error adding product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Inventory System</div>
        <div class="nav-right">
            <span class="user-name">Welcome, <?php echo htmlspecialchars($full_name); ?> (<?php echo $user_role === 'admin' ? 'Admin' : 'User'; ?>)</span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <aside class="sidebar">
            <ul class="menu">
                <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="user_products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="user_transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                
            </ul>
        </aside>

        <main class="main-content">
            <h2>Add New Product</h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required>
                </div>
                <button type="submit" name="add_product" class="btn-submit">Add Product</button>
            </form>
        </main>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html> 