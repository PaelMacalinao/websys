<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check admin access
checkAdminAccess();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();

            switch ($_POST['action']) {
                case 'edit':
                    $updateFields = [];
                    $params = [];

                    // Validate email
                    if (!empty($_POST['email'])) {
                        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Invalid email format");
                        }
                        $updateFields[] = "email = ?";
                        $params[] = $_POST['email'];
                    }

                    // Handle password update
                    if (!empty($_POST['password'])) {
                        if (strlen($_POST['password']) < 6) {
                            throw new Exception("Password must be at least 6 characters long");
                        }
                        $updateFields[] = "password = ?";
                        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    }

                    // Update role if provided
                    if (!empty($_POST['role'])) {
                        if (!in_array($_POST['role'], ['user', 'admin'])) {
                            throw new Exception("Invalid role specified");
                        }
                        $updateFields[] = "role = ?";
                        $params[] = $_POST['role'];
                    }

                    if (empty($updateFields)) {
                        throw new Exception("No fields to update");
                    }

                    $params[] = $_POST['user_id'];  // Add user ID for WHERE clause
                    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $_SESSION['success'] = "User updated successfully";
                    break;

                case 'delete':
                    // Prevent deleting self
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        throw new Exception("You cannot delete your own account");
                    }

                    // Check if user is the last admin
                    if ($_POST['role'] === 'admin') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                        $stmt->execute();
                        $adminCount = $stmt->fetchColumn();

                        if ($adminCount <= 1) {
                            throw new Exception("Cannot delete the last admin account");
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $_SESSION['success'] = "User deleted successfully";
                    break;
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: users.php');
        exit();
    }
}

// Get all users with additional info
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id) as total_spent,
        (SELECT MAX(order_date) FROM orders WHERE user_id = u.id) as last_order_date
    FROM users u 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Connect Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">User Management</h1>
            </div>

            <?php if ($error = getError()): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success = getSuccess()): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <div class="data-table">
                <div class="table-header">
                    <h2 class="table-title">Users</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['order_count'] > 0): ?>
                                        <span title="Last order: <?php echo formatDate($user['last_order_date']); ?>">
                                            <?php echo $user['order_count']; ?> order(s)
                                        </span>
                                    <?php else: ?>
                                        No orders
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $user['total_spent'] ? formatCurrency($user['total_spent']) : '₱0.00'; ?>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td class="table-actions-cell">
                                    <button class="action-btn" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="action-btn" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Edit User</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="userId">

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" class="form-input" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Leave blank to keep current password"
                               minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">Role</label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(action, user = null) {
        document.getElementById('userModal').style.display = 'block';
        
        if (user) {
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
        }
    }

    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
        document.getElementById('userForm').reset();
    }

    function deleteUser(userId, userRole) {
        const message = userRole === 'admin' 
            ? 'Are you sure you want to delete this admin account?' 
            : 'Are you sure you want to delete this user?';
            
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="role" value="${userRole}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('userModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>
