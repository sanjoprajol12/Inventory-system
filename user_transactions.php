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

// Create transactions table if not exists
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
$conn->query($sql);

// Handle new transaction
if (isset($_POST['add_transaction'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $type = $_POST['type'];
    $unit_price = $_POST['unit_price'];
    $total_amount = $quantity * $unit_price;

    // Fetch current stock level
    $product = $conn->query("SELECT quantity FROM products WHERE id = $product_id")->fetch_assoc();
    $current_stock = $product['quantity'];

    // Validate stock level for "Stock Out"
    if ($type == 'out' && $quantity > $current_stock) {
        $error_message = "Error: Not enough stock available for this transaction.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Add transaction record
            $sql = "INSERT INTO transactions (product_id, quantity, type, unit_price, total_amount, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisddi", $product_id, $quantity, $type, $unit_price, $total_amount, $_SESSION['user_id']);
            $stmt->execute();

            // Update product quantity
            $quantity_change = ($type == 'in') ? $quantity : -$quantity;
            $sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $quantity_change, $product_id);
            $stmt->execute();

            $conn->commit();
            $success_message = "Transaction recorded successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error recording transaction: " . $e->getMessage();
        }
    }
}

// Handle clear transactions
if (isset($_POST['clear_transactions'])) {
    $sql = "DELETE FROM transactions";
    $conn->query($sql);
    $success_message = "All transactions cleared successfully!";
}

// Handle individual transaction deletion
if (isset($_POST['delete_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    $sql = "DELETE FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transaction_id);
    if ($stmt->execute()) {
        $success_message = "Transaction deleted successfully!";
    } else {
        $error_message = "Error deleting transaction.";
    }
}

// Fetch all products for dropdown
$products = $conn->query("SELECT id, name, quantity FROM products ORDER BY name");

// Fetch recent transactions
$transactions = $conn->query("
    SELECT t.*, p.name as product_name, u.full_name as created_by_name
    FROM transactions t
    JOIN products p ON t.product_id = p.id
    JOIN users u ON t.created_by = u.id
    ORDER BY t.transaction_date DESC
    LIMIT 50
");

// Calculate total value
$total_value = $conn->query("
    SELECT 
        SUM(CASE WHEN type = 'in' THEN total_amount ELSE -total_amount END) as net_value
    FROM transactions
")->fetch_assoc()['net_value'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Add the updated CSS here */
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
            font-weight: 500;
        }

        .logout-btn {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: var(--secondary-color);
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary-color);
        }

        .sidebar {
            width: 220px;
            position: fixed;
            top: 60px;
            left: 0;
            background-color: var(--white);
            height: calc(100vh - 60px);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            padding: 1rem 0;
            transition: transform 0.3s;
        }

        .menu {
            list-style: none;
            padding: 0;
        }

        .menu li {
            margin: 1rem 0;
        }

        .menu a {
            text-decoration: none;
            color: var(--text-color);
            padding: 0.5rem 1rem;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }

        .menu a:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .menu a.active {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            padding-top: 1rem;
        }

        h2 {
            margin-bottom: 1rem;
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
            font-weight: bold;
        }

        .alert-success {
            background-color: var(--success-color);
            color: var(--white);
        }

        .alert-danger {
            background-color: var(--warning-color);
            color: var(--white);
        }

        .transaction-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .transaction-form, .transaction-list {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .summary-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .summary-card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .btn-submit, .btn-clear {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 1rem;
        }

        .btn-submit:hover, .btn-clear:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-clear {
            background-color: var(--warning-color);
        }

        .btn-clear:hover {
            background-color: #d32f2f;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group select:focus, .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 600;
        }

        td {
            background-color: var(--white);
        }

        tr:nth-child(even) td {
            background-color: var(--bg-light);
        }

        .type-in {
            color: #4caf50;
        }

        .type-out {
            color: #f44336;
        }

        @media (max-width: 768px) {
            .transaction-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
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
        <div class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<div class="container">
    <aside class="sidebar">
        <ul class="menu">
            <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="user_products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="user_transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2><i class="fas fa-exchange-alt"></i> Transaction Management</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Net Value</h3>
                <p>Rs <?php echo number_format($total_value, 2); ?></p>
            </div>
        </div>

        <div class="transaction-container">
            <div class="transaction-form">
                <h3>New Transaction</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Product</label>
                        <select name="product_id" required>
                            <option value="">Select Product</option>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    (Current Stock: <?php echo $product['quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="in">Purchase</option>
                            <option value="out">Sales</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Unit Price</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0.01" required>
                    </div>
                    <button type="submit" name="add_transaction" class="btn-submit">Record Transaction</button>
                </form>
            </div>

            <div class="transaction-list">
                <h3>Recent Transactions</h3>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <form method="POST" action="">
                        <button type="submit" name="clear_transactions" class="btn-clear">Clear All Transactions</button>
                    </form>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                <td class="type-<?php echo $transaction['type']; ?>">
                                    <?php echo ucfirst($transaction['type']); ?>
                                </td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td>Rs <?php echo number_format($transaction['unit_price']); ?></td>
                                <td>Rs <?php echo number_format($transaction['total_amount']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <button type="submit" name="delete_transaction" class="btn-clear">Clear</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

document.querySelector('select[name="product_id"]').addEventListener('change', function() {
    var productId = this.value;
    if (productId) {
        fetch('get_product_price.php?product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('unit_price').value = data.unit_price;
            })
            .catch(error => console.error('Error fetching product price:', error));
    } else {
        document.getElementById('unit_price').value = '';
    }
});
</script>
</body>
</html>