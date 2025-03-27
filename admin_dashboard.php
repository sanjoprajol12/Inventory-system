<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$full_name = $_SESSION['full_name'];

// Check if user_role is set
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'; // Default to 'user' if not set
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; // Default to 'User' if not set

// Fetch dashboard statistics
$stats = [
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'total_value' => $conn->query("SELECT SUM(quantity * price) as total FROM products")->fetch_assoc()['total'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < 10")->fetch_assoc()['count']
];

// Fetch recent products
$recent_products = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Navbar Styles */
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
            gap: 1rem;
        }

        .user-name {
            color: var(--text-color);
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background-color: var(--warning-color);
            color: var(--white);
            border-radius: 6px;
            text-decoration: none;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            opacity: 0.9;
        }

        /* Layout */
        .container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background-color: var(--white);
            padding: 2rem 0;
            position: fixed;
            height: calc(100vh - 70px);
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 0.5rem;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .menu a:hover, .menu a.active {
            background-color: rgba(67, 97, 238, 0.1);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }

        .menu i {
            margin-right: 0.75rem;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .card h3 {
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .card p {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
        }

        /* Recent Products Table */
        .recent-products {
            background-color: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .recent-products h2 {
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            color: var(--text-light);
            background-color: var(--bg-light);
        }

        tbody tr:hover {
            background-color: var(--bg-light);
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status.low {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }

        .status.good {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit, .btn-delete {
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn-edit {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-delete {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .close {
            cursor: pointer;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-footer {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
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
                <li><a href="admin_home.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin_transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <?php if ($user_role === 'admin'): ?>
                   
                <?php endif; ?>
            </ul>
        </aside>

        <main class="main-content">
            <!-- Admin-specific content -->
            <?php if ($user_role === 'admin'): ?>
                <div class="admin-section">
                    <h2>Admin Dashboard</h2>
                    <!-- Admin-specific statistics or controls -->
                </div>
            <?php endif; ?>

            <!-- Common content for all users -->
            <div class="dashboard-cards">
                <div class="card">
                    <i class="fas fa-box"></i>
                    <h3>Total Products</h3>
                    <p><?php echo $stats['total_products']; ?></p>
                </div>
                <div class="card">
                <i class="fas fa-rupee-sign"></i>
                    <h3>Stock Value</h3>
                    <p>Rs <?php echo number_format($stats['total_value']); ?></p>
                </div>
                <div class="card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Low Stock Items</h3>
                    <p><?php echo $stats['low_stock']; ?></p>
                </div>
            </div>

            <div class="recent-products">
                <h2>Recent Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $recent_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td>Rs <?php echo number_format($product['price']); ?></td>
                                <td>Rs <?php echo number_format($product['quantity'] * $product['price']); ?></td>
                                <td>
                                    <span class="status <?php echo $product['quantity'] < 10 ? 'low' : 'good'; ?>">
                                        <?php echo $product['quantity'] < 10 ? 'Low Stock' : 'In Stock'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button onclick="editProduct(<?php echo $product['id']; ?>)" class="btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <span class="close">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" id="productId">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" step="0.01" id="price" name="price" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-delete" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-edit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function editProduct(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('productId').value = product.id;
                    document.getElementById('name').value = product.name;
                    document.getElementById('quantity').value = product.quantity;
                    document.getElementById('price').value = product.price;
                    modal.style.display = 'block';
                });
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                fetch(`delete_product.php?id=${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting product');
                        }
                    });
            }
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        closeBtn.onclick = closeModal;

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        document.getElementById('editForm').onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('productId').value;
            const formData = new FormData(this);

            fetch(`update_product.php?id=${id}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating product');
                }
            });
        }
    </script>
</body>
</html> 