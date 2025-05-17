<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cartId => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cartId, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                $error = "Error updating cart: " . $e->getMessage();
            }
        }
    }
    header('Location: user-cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $cartId = (int)$_GET['remove'];
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $_SESSION['user_id']]);
        header('Location: user-cart.php');
        exit;
    } catch (PDOException $e) {
        $error = "Error removing item: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Connect</title>
    <link rel="stylesheet" href="../css/user-cart.css">
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
                    <li><a href="user-account.php">Account</a></li>
                    <li class="active"><a href="user-cart.php">Cart</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="cart-container">
        <h1>Shopping Cart</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (count($cartItems) > 0): ?>
            <form action="user-cart.php" method="POST" class="cart-form">
                <div class="cart-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr class="cart-item">
                                    <td class="product-info">
                                        <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                        <div>
                                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                                            <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                <p class="stock-warning">Only <?= $item['stock_quantity'] ?> available</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="price">₱<?= number_format($item['price'], 2) ?></td>
                                    <td class="quantity">
                                        <input type="number" name="quantities[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>">
                                    </td>
                                    <td class="subtotal">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    <td class="action">
                                        <a href="user-cart.php?remove=<?= $item['id'] ?>" class="remove-item"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-actions">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='user-shop.php'">Continue Shopping</button>
                    <button type="submit" name="update_cart" class="btn btn-outline">Quantity Update</button>
                </div>
                
                <div class="cart-summary">
                    <h3>Cart Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row grand-total">
                        <span>Total</span>
                        <span>₱<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <a href="user-checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet</p>
                <a href="user-shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>