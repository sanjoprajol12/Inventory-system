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

if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete product record
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        $conn->commit();
        $success_message = "Product deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error deleting product: " . $e->getMessage();
    }
}

// Fetch all products
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
   :root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #4cc9f0;
    --warning-color: #f72585;
    --text-color: #2b2d42;
    --text-light: #8d99ae;
    --bg-light: #f8f9fa;
    --white: #ffffff;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: var(--bg-light);
    color: var(--text-color);
    min-height: 100vh;
}

.navbar {
    background-color: var(--white);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
}

.nav-brand {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.nav-right {
    display: flex;
    align-items: center;
}

.user-name {
    margin-right: 1rem;
}

.logout-btn {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.sidebar {
    width: 200px;
    position: fixed;
    top: 60px; /* Adjust based on navbar height */
    left: 0;
    background-color: var(--white);
    height: calc(100vh - 60px);
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.menu {
    list-style: none;
    padding: 1rem;
}

.menu li {
    margin: 1rem 0;
}

.menu a {
    text-decoration: none;
    color: var(--text-color);
    padding: 0.5rem;
    display: block;
    border-radius: 5px;
}

.menu a.active {
    background-color: var(--primary-color);
    color: var(--white);
}

.main-content {
    margin-left: 220px; /* Adjust based on sidebar width */
    padding: 2rem;
}

.alert {
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 5px;
}

.alert-success {
    background-color: var(--success-color);
    color: var(--white);
}

.alert-danger {
    background-color: var(--warning-color);
    color: var(--white);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: var(--secondary-color);
    color: var(--white);
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-delete {
    background-color: var(--warning-color);
    color: var(--white);
    border: none;
    padding: 0.5rem;
    border-radius: 5px;
    cursor: pointer;
}

.btn-add {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.btn-add:hover {
    background-color: var(--secondary-color);
}
    </style>
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
            <h2>Product Management</h2>
            
            <!-- Add New Product Button -->
            <div class="add-product">
                <a href="user_add_product.php" class="btn-add">Add New Product</a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rs <?php echo number_format($item['price']); ?></td>
                            <td>Rs <?php echo number_format($item['quantity'] * $item['price']); ?></td>
                            <td class="actions">
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>

</html>