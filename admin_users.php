<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Retrieve user's full name and role from session
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; // Default to 'User' if not set
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'; // Default to 'user' if not set

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY full_name");

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete user record
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        $success_message = "User deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error deleting user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="admin_transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2><i class="fas fa-users"></i> User Management</h2>

        <!-- Button to Register New User -->
        <div class="add-user">
            <a href="register.php" class="btn-add">Register New User</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="user-list">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="actions">
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    // Add any necessary JavaScript here
</script>
</body>
</html>