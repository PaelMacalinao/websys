<?php
session_start();
require 'config.php';

$featuredProducts = [];
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_featured = 1 LIMIT 4");
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching featured products: " . $e->getMessage();
}

$newArrivals = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY date_added DESC LIMIT 4");
    $newArrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching new arrivals: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Connect</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="../images/connect-logo.png" alt="Connect Logo">
            </div>
            <nav class="main-nav">
                <ul>
                    <li class="active"><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero"></section>

        <!-- Featured Products -->
        <section class="featured-products">
            <div class="container">
                <h2>Featured Products</h2>
                <div class="product-grid">
                    <?php foreach($featuredProducts as $product): ?>
                    <div class="product-card">
                        <img src="../images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                        <a href="index-product.php?id=<?= $product['id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- New Arrivals -->
        <section id="new-arrivals" class="new-arrivals">
            <div class="container">
                <h2>New Arrivals</h2>
                <div class="product-grid">
                    <?php foreach($newArrivals as $product): ?>
                    <div class="product-card">
                        <img src="../images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="price">₱<?= number_format($product['price'], 2) ?></p>
                        <a href="index-product.php?id=<?= $product['id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- About Us -->
        <section id="about" class="about-section">
            <div class="container">
                <h2>About Connect</h2>
                <p>Connect is your premier online destination for quality products at affordable prices. Founded in 2025, we bring you the best selection of everyday essentials with just a few clicks. Our team is dedicated to providing exceptional customer service and a seamless shopping experience. We carefully curate our products to ensure both quality and value for our customers. At Connect, your satisfaction is our top priority, and we're constantly working to improve your shopping journey.</p>
                <div class="about-features">
                    <div class="feature">
                        <i class="fas fa-shipping-fast"></i>
                        <h3>Fast Shipping</h3>
                        <p>Get your orders delivered within 3-5 business days</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-headset"></i>
                        <h3>24/7 Support</h3>
                        <p>Our customer service team is always ready to help</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-undo"></i>
                        <h3>Easy Returns</h3>
                        <p>30-day hassle-free return policy</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta">
            <div class="container">
                <h2>Why Join Our Connect Community?</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <i class="fas fa-percentage"></i>
                        <h3>Exclusive Discounts</h3>
                        <p>Get members-only deals and early access to sales</p>
                    </div>
                    <div class="benefit-card">
                        <i class="fas fa-gift"></i>
                        <h3>Special Rewards</h3>
                        <p>Earn points with every purchase redeemable for perks</p>
                    </div>
                    <div class="benefit-card">
                        <i class="fas fa-bolt"></i>
                        <h3>Early Access</h3>
                        <p>Be first to shop new arrivals and limited editions</p>
                    </div>
                    <div class="benefit-card">
                        <i class="fas fa-user-shield"></i>
                        <h3>Personalized Service</h3>
                        <p>Get tailored recommendations and priority support</p>
                    </div>
                </div>
                <a href="register.php" class="btn btn-primary">Register Now!</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Connect</h3>
                <p>Address:<br>Palawan, Philippines</p>
            </div>
        
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul class="footer-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#new-arrivals">New Arrivals</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </div>
        
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p class="contact-info">
                    <i class="bi bi-telephone-fill"></i> +1 223 719 02 11
                </p>
                <p class="contact-info">
                    <i class="bi bi-envelope-fill"></i> connect@ecommerce.com
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    
        <div class="footer-bottom">
            <p>&copy; Connect Ltd <?= date('Y') ?></p>
        </div>
    </footer>
</body>
</html>