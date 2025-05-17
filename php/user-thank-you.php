<?php
session_start();
require 'config.php';

if (!isset($_GET['order_id'])) {
    header('Location: user-shop.php');
    exit;
}

$orderId = $_GET['order_id'];
$order = null;
$orderItems = [];
$error = null;

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Order not found");
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Connect</title>
    <link rel="stylesheet" href="../css/user-thank-you.css">
</head>
<body>
    <div class="receipt-container">
        <?php if ($error): ?>
            <div class="error-message">
                <p><?= htmlspecialchars($error) ?></p>
                <a href="user-shop.php" class="btn">Return to Shop</a>
            </div>
        <?php elseif ($order): ?>
            <div class="confirmation-image">
                <img src="../images/connect-reciept.png" alt="Order Confirmation">
            </div>
            
            <div class="confirmation-details">
                <h2 class="section-title">Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <span><?= $item['quantity'] ?> × <?= htmlspecialchars($item['name']) ?></span>
                            <span>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="receipt-totals">
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
                
                <h2 class="section-title">Customer Information</h2>
                <div class="customer-info">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'Not specified') ?></p>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address'] ?? 'Not specified') ?></p>
                    <p><strong>Order Date:</strong> <?= isset($order['created_at']) ? date('F j, Y \a\t g:i A', strtotime($order['created_at'])) : 'Not available' ?></p>
                </div>
            </div>
            
            <div class="continue-shopping">
                <a href="user-shop.php" class="btn">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>