<?php
session_start();
require 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$productId = $_GET['id'];
$product = [];
$relatedProducts = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: index.php');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
    $stmt->execute([$product['category'], $productId]);
    $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error fetching product details: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Connect</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .back-button:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <main>
        <div class="container" style="margin-top: 20px;">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <!-- Product Details Section -->
        <section class="product-details">
            <div class="container">
                <div class="product-details-grid">
                    <!-- Product Images -->
                    <div class="product-images">
                        <div class="main-image">
                            <img src="../images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="product-info">
                        <h1><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="product-meta">
                            <span class="category"><?= htmlspecialchars($product['category']) ?></span>
                            <?php if ($product['is_featured']): ?>
                                <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="price-section">
                            <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                            <p class="stock-status <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <?= $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                            </p>
                        </div>
                        
                        <div class="product-description">
                            <h3>Description</h3>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                        
                        <div class="product-actions">
                            <div class="quantity-selector">
                                <button class="quantity-btn minus">-</button>
                                <input type="number" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="quantity-input">
                                <button class="quantity-btn plus">+</button>
                            </div>
                            <a href="register.php">
                                <button class="btn btn-primary add-to-cart" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                                    Register to Add Items to your Cart
                                </button>
                            </a>
                        </div>
                        
                        <div class="product-meta-details">
                            <p><strong>Product Number:</strong> CONNECT-<?= str_pad($product['id'], 4, '0', STR_PAD_LEFT) ?></p>
                            <p><strong>Date Added:</strong> <?= date('F j, Y', strtotime($product['date_added'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <section class="related-products">
            <div class="container">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php foreach($relatedProducts as $related): ?>
                    <div class="product-card">
                        <img src="../images/products/<?= htmlspecialchars($related['image']) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
                        <h3><?= htmlspecialchars($related['name']) ?></h3>
                        <p class="price">₱<?= number_format($related['price'], 2) ?></p>
                        <a href="index-product.php?id=<?= $related['id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script>
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);
                
                if (button.classList.contains('minus') && value > 1) {
                    input.value = value - 1;
                } else if (button.classList.contains('plus') && value < <?= $product['stock_quantity'] ?>) {
                    input.value = value + 1;
                }
            });
        });
    </script>
</body>
</html>