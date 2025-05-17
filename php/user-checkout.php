<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$cartItems = [];
$subtotal = 0;
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'];
    $shippingAddress = $_POST['shipping_address'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, payment_method, shipping_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $subtotal, $paymentMethod, $shippingAddress]);
        $orderId = $pdo->lastInsertId();
        
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        
        header("Location: user-thank-you.php?order_id=$orderId");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Connect</title>
    <link rel="stylesheet" href="../css/user-checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <main class="checkout-container">
        <h1>Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (count($cartItems) > 0): ?>
            <form action="user-checkout.php" method="POST" class="checkout-form">
                <div class="checkout-columns">
                    <div class="checkout-details">
                        <section class="shipping-address">
                            <h2>Shipping Address</h2>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="shipping_address">Address</label>
                                <textarea id="shipping_address" name="shipping_address" rows="4" required></textarea>
                            </div>
                        </section>
                        
                        <section class="payment-method">
                            <h2>Payment Method</h2>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Credit Card" checked>
                                    <i class="fas fa-credit-card"></i>
                                    <span>Credit Card</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="PayPal">
                                    <i class="fab fa-paypal"></i>
                                    <span>PayPal</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="GCash">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>GCash</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Cash on Delivery">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Cash on Delivery</span>
                                </label>
                            </div>
                        </section>
                    </div>
                    
                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        <div class="order-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <span class="item-quantity"><?= $item['quantity'] ?> ×</span>
                                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                    <span class="item-price">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-totals">
                            <div class="totals-row">
                                <span>Subtotal</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="totals-row">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <div class="totals-row grand-total">
                                <span>Total</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Place Order</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>There are no items to checkout</p>
                <a href="user-shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>