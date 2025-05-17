<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = [];
$orders = [];
$orderItems = [];

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user orders
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$activeTab = $_GET['tab'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Connect</title>
    <link rel="stylesheet" href="../css/user-account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="../images/connect-logo.png" alt="Connect Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="user-shop.php">Shop</a></li>
                    <li class="active"><a href="user-account.php">Account</a></li>
                    <li><a href="user-cart.php">Cart</a></li>
                </ul>
            </nav>
    </header>

    <main class="account-container">
        <div class="account-sidebar">
            <div class="user-profile">
                <div class="avatar">
                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                </div>
                <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                <p><?= htmlspecialchars($user['email']) ?></p>
            </div>
            
            <nav class="account-nav">
                <ul>
                    <li class="<?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                        <a href="user-account.php?tab=dashboard"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="<?= $activeTab === 'orders' ? 'active' : '' ?>">
                        <a href="user-account.php?tab=orders"><i class="fas fa-shopping-bag"></i> My Orders</a>
                    </li>
                    <li class="<?= $activeTab === 'profile' ? 'active' : '' ?>">
                        <a href="user-account.php?tab=profile"><i class="fas fa-user"></i> Profile</a>
                    </li>
                    <li>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <div class="account-content">
            <?php if ($activeTab === 'dashboard'): ?>
                <section class="dashboard-section">
                    <h2>Welcome Back, <?= htmlspecialchars($user['first_name']) ?>!</h2>
                    <p>Here's what's happening with your account.</p>
                    
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>Total Orders</h3>
                            <p><?= count($orders) ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock"></i>
                            <h3>Pending Orders</h3>
                            <p><?= count(array_filter($orders, fn($o) => $o['status'] === 'pending')) ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-truck"></i>
                            <h3>Orders Shipped</h3>
                            <p><?= count(array_filter($orders, fn($o) => $o['status'] === 'shipped')) ?></p>
                        </div>
                    </div>
                    
                    <div class="recent-orders">
                        <h3>Recent Orders</h3>
                        <?php if (count($orders) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                            <td><?= count($order['items']) ?></td>
                                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="status-badge <?= $order['status'] ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="user-account.php?tab=orders&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>You haven't placed any orders yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
            
            <?php elseif ($activeTab === 'orders'): ?>
                <section class="orders-section">
                    <h2>My Orders</h2>
                    
                    <?php if (isset($_GET['order_id'])): ?>
                        <?php 
                        $orderId = $_GET['order_id'];
                        $order = current(array_filter($orders, fn($o) => $o['id'] == $orderId));
                        ?>
                        
                        <?php if ($order): ?>
                            <div class="order-details">
                                <div class="order-header">
                                    <div>
                                        <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                        <p>Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?></p>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge <?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="order-summary">
                                    <div class="order-address">
                                        <h4>Shipping Address</h4>
                                        <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                                    </div>
                                    <div class="order-payment">
                                        <h4>Payment Method</h4>
                                        <p><?= htmlspecialchars($order['payment_method']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="order-items">
                                    <h4>Order Items</h4>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-item">
                                            <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                            <div class="item-details">
                                                <h5><?= htmlspecialchars($item['name']) ?></h5>
                                                <p>₱<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                            </div>
                                            <div class="item-total">
                                                ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-totals">
                                    <div class="totals-row">
                                        <span>Subtotal</span>
                                        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="totals-row">
                                        <span>Shipping</span>
                                        <span>Free</span>
                                    </div>
                                    <div class="totals-row grand-total">
                                        <span>Total</span>
                                        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                </div>
                                
                                <a href="user-account.php?tab=orders" class="btn btn-outline">Back to Orders</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">Order not found</div>
                            <a href="user-account.php?tab=orders" class="btn btn-outline">Back to Orders</a>
                        <?php endif; ?>
                    
                    <?php else: ?>
                        <?php if (count($orders) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                            <td><?= count($order['items']) ?></td>
                                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="status-badge <?= $order['status'] ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="account.php?tab=orders&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <h3>No Orders Yet</h3>
                                <p>You haven't placed any orders yet. Start shopping now!</p>
                                <a href="user-shop.php" class="btn btn-primary">Shop Now</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            
            <?php elseif ($activeTab === 'profile'): ?>
                <section class="profile-section">
                    <h2>My Profile</h2>
                    
                    <form action="update-profile.php" method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile">Mobile Number</label>
                            <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars($user['mobile']) ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                    
                    <div class="change-password">
                        <h3>Change Password</h3>
                        <form action="change-password.php" method="POST" class="password-form">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>