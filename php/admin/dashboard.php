<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check if user is logged in and is an admin
checkAdminAccess();

// Initialize variables with default values
$stats = [
    'total_products' => 0,
    'total_users' => 0,
    'total_orders' => 0,
    'total_sales' => 0
];
$recent_orders = [];
$low_stock = ['count' => 0];

// Get dashboard statistics
try {
    // Get overall statistics
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders) as total_sales");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);    // Get recent orders with user details and order items
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as order_items
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        GROUP BY o.id
        ORDER BY o.order_date DESC 
        LIMIT 5");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Handle order status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        try {
            $pdo->beginTransaction();
            
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            
            // Record status change in history
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $new_status, $_SESSION['user_id'], "Status updated by admin"]);
            
            $pdo->commit();
            $_SESSION['success'] = "Order status updated successfully!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
        }
    }

    // Get low stock products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10");
    $stmt->execute();
    $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly sales data for the past 6 months
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as total_sales
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get category distribution
    $stmt = $pdo->prepare("
        SELECT 
            c.name as category_name,
            COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id, c.name
        ORDER BY product_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Connect</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Dashboard</h1>
                <div class="header-actions">
                    <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
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

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Total Sales</h3>
                        <div class="card-icon success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo formatCurrency($stats['total_sales']); ?></div>
                    <p class="card-subtitle">Total revenue from all orders</p>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Products</h3>
                        <div class="card-icon info">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $stats['total_products']; ?></div>
                    <p class="card-subtitle">Total products in inventory</p>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Users</h3>
                        <div class="card-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $stats['total_users']; ?></div>
                    <p class="card-subtitle">Registered customers</p>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Orders</h3>
                        <div class="card-icon warning">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $stats['total_orders']; ?></div>
                    <p class="card-subtitle">Total orders placed</p>
                </div>
            </div>

            <div class="dashboard-analytics-grid">
                <div class="analytics-card">
                    <div class="card-header">
                        <h3>Sales Over Time</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-header">
                        <h3>Product Categories</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="data-table">
                <div class="table-header">
                    <h2 class="table-title">Recent Orders</h2>
                    <div class="table-actions">
                        <a href="orders.php" class="btn btn-outline">View All Orders</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>                        <tbody>                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-state-content">
                                        <i class="fas fa-shopping-cart"></i>
                                        <p>No recent orders found</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="customer-info">
                                            <div><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                            <small><?php echo htmlspecialchars($order['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                    <td>
                                        <form method="POST" class="status-update-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="new_status" class="status-select <?php echo strtolower($order['status']); ?>" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_order_status" value="1">
                                        </form>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td class="table-actions-cell">
                                        <button type="button" class="action-btn view-details" onclick="viewOrderDetails('<?php echo htmlspecialchars(json_encode($order)); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>                        </tbody>
                    </table>
                </div>
            </div>
        </main>    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="order-info">
                    <div class="order-header">
                        <h3>Order #<span id="modalOrderId"></span></h3>
                        <p>Placed on <span id="modalOrderDate"></span></p>
                    </div>
                    <div class="customer-details">
                        <h4>Customer Information</h4>
                        <p><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                        <p><strong>Email:</strong> <span id="modalCustomerEmail"></span></p>
                    </div>
                    <div class="order-items">
                        <h4>Order Items</h4>
                        <div id="modalOrderItems"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/admin.js"></script>
    <script>
    // Initialize charts when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
    });    // Order Details Modal Functions
    function viewOrderDetails(orderData) {
        const order = JSON.parse(orderData);
        document.getElementById('modalOrderId').textContent = String(order.id).padStart(6, '0');
        document.getElementById('modalOrderDate').textContent = new Date(order.created_at).toLocaleString();
        document.getElementById('modalCustomerName').textContent = order.first_name + ' ' + order.last_name;
        document.getElementById('modalCustomerEmail').textContent = order.email;
        document.getElementById('modalOrderItems').textContent = order.order_items;
        
        document.getElementById('orderDetailsModal').style.display = 'block';
    }

    // Close modal when clicking the close button or outside the modal
    document.querySelector('.close-modal').onclick = function() {
        document.getElementById('orderDetailsModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('orderDetailsModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function initializeCharts() {
        // Sales Chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            // Process PHP monthly sales data for chart
            const salesData = <?php echo json_encode($monthly_sales); ?>;
            const months = salesData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short' });
            });
            const sales = salesData.map(item => parseFloat(item.total_sales));

            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Monthly Sales',
                        data: sales,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Categories Chart
        const categoriesCtx = document.getElementById('categoriesChart');
        if (categoriesCtx) {
            // Process PHP category data for chart
            const categoryData = <?php echo json_encode($category_stats); ?>;
            const categoryNames = categoryData.map(item => item.category_name);
            const productCounts = categoryData.map(item => item.product_count);

            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        data: productCounts,
                        backgroundColor: [
                            '#4f46e5', // Primary
                            '#10b981', // Success
                            '#f59e0b', // Warning
                            '#ef4444', // Danger
                            '#6b7280'  // Secondary
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    </script>
</body>
</html>
