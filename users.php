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

// Handle adding a new user
if ($user_role === 'admin' && isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert new user record
        $sql = "INSERT INTO users (full_name, username, password, email, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $full_name, $username, $password, $email, $role);
        $stmt->execute();

        $conn->commit();
        $success_message = "User added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error adding user: " . $e->getMessage();
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
            <li><a href="home.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="stock.php"><i class="fas fa-warehouse"></i> Stock</a></li>
            <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2><i class="fas fa-users"></i> User Management</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
            <h3>Add New User</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn-submit">Add User</button>
            </form>
        <?php endif; ?>

        <h3>Existing Users</h3>
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