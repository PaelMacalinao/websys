<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get all products
$products = [];
$categories = [];
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

try {
    // Get all distinct categories for the filter
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build the product query with optional filters
    $query = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category_filter)) {
        $query .= " AND category = ?";
        $params[] = $category_filter;
    }
    
    $query .= " ORDER BY date_added DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching products: " . $e->getMessage();
}

// Add to cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    try {
        // Check if product exists and has enough stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || $product['stock_quantity'] < $quantity) {
            $error = "Product not available or insufficient stock";
        } else {
            // Check if product already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cart_item) {
                // Update quantity
                $new_quantity = $cart_item['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $cart_item['id']]);
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            }
            
            $success = "Product added to cart successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | Connect</title>
    <link rel="stylesheet" href="../css/user-shop.css">
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
                    <li class="active"><a href="user-shop.php">Shop</a></li>
                    <li><a href="user-account.php">Account</a></li>
                    <li><a href="user-cart.php">Cart</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="shop-container">
        <div class="container">
            <h1 class="shop-title">Products</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="shop-controls">
                <form method="get" class="search-filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="filter-box">
                        <select id="category" name="category" class="category-filter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" <?= $category_filter === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply</button>
                    </div>
                </form>
            </div>
            
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No Products Found</h3>
                        <p>We couldn't find any products matching your criteria.</p>
                        <a href="user-shop.php" class="btn btn-primary">Reset Filters</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="../images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
                                <p class="product-price">₱<?= number_format($product['price'], 2) ?></p>
                                
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <p class="stock-available">In Stock (<?= $product['stock_quantity'] ?> available)</p>
                                <?php else: ?>
                                    <p class="stock-out">Out of Stock</p>
                                <?php endif; ?>
                                
                                <button class="view-details-btn" onclick="showProductDetails(<?= htmlspecialchars(json_encode($product)) ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                
                                <form method="post" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <div class="quantity-control">
                                            <label for="quantity-<?= $product['id'] ?>">Qty:</label>
                                            <div class="quantity-input-group">
                                                <button type="button" class="quantity-btn minus" onclick="adjustQuantity(this, -1)">-</button>
                                                <input type="number" id="quantity-<?= $product['id'] ?>" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>">
                                                <button type="button" class="quantity-btn plus" onclick="adjustQuantity(this, 1)">+</button>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="add_to_cart" class="btn btn-primary add-to-cart-btn">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-disabled" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Product Details Modal -->
    <div class="modal" id="productDetailsModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div class="modal-body">
                <div class="modal-product-image">
                    <img id="modalProductImage" src="" alt="">
                </div>
                <div class="modal-product-info">
                    <h2 id="modalProductName"></h2>
                    <p class="modal-product-category" id="modalProductCategory"></p>
                    <p class="modal-product-price" id="modalProductPrice"></p>
                    <p class="modal-product-stock" id="modalProductStock"></p>
                    <div class="modal-product-description">
                        <h3>Description</h3>
                        <p id="modalProductDescription"></p>
                    </div>
                    <form method="post" class="modal-add-to-cart-form">
                        <input type="hidden" id="modalProductId" name="product_id" value="">
                        <div class="modal-quantity-control">
                            <label for="modalQuantity">Qty:</label>
                            <div class="quantity-input-group">
                                <button type="button" class="quantity-btn minus" onclick="adjustModalQuantity(-1)">-</button>
                                <input type="number" id="modalQuantity" name="quantity" value="1" min="1">
                                <button type="button" class="quantity-btn plus" onclick="adjustModalQuantity(1)">+</button>
                            </div>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary add-to-cart-btn">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        // Highlight the active filter
        document.addEventListener('DOMContentLoaded', function() {
            const categoryFilter = document.getElementById('category');
            if (categoryFilter.value) {
                categoryFilter.classList.add('active-filter');
            }
            
            categoryFilter.addEventListener('change', function() {
                if (this.value) {
                    this.classList.add('active-filter');
                } else {
                    this.classList.remove('active-filter');
                }
            });
        });

        // Product details modal functionality
        function showProductDetails(product) {
            const modal = document.getElementById('productDetailsModal');
            document.getElementById('modalProductName').textContent = product.name;
            document.getElementById('modalProductCategory').textContent = product.category;
            document.getElementById('modalProductPrice').textContent = '₱' + parseFloat(product.price).toFixed(2);
            document.getElementById('modalProductStock').textContent = product.stock_quantity > 0 
                ? 'In Stock (' + product.stock_quantity + ' available)' 
                : 'Out of Stock';
            document.getElementById('modalProductStock').className = product.stock_quantity > 0 
                ? 'modal-product-stock stock-available' 
                : 'modal-product-stock stock-out';
            document.getElementById('modalProductDescription').textContent = product.description || 'No description available';
            document.getElementById('modalProductId').value = product.id;
            document.getElementById('modalQuantity').max = product.stock_quantity;
            document.getElementById('modalProductImage').src = '../images/products/' + product.image;
            document.getElementById('modalProductImage').alt = product.name;
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('productDetailsModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Quantity adjustment functions
        function adjustQuantity(button, change) {
            const input = button.parentElement.querySelector('input[type=number]');
            let newValue = parseInt(input.value) + change;
            if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
            if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
            input.value = newValue;
        }

        function adjustModalQuantity(change) {
            const input = document.getElementById('modalQuantity');
            let newValue = parseInt(input.value) + change;
            if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
            if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
            input.value = newValue;
        }
    </script>
</body>
</html>