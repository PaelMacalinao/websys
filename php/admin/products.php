<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check admin access
checkAdminAccess();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();

            switch ($_POST['action']) {
                case 'add':
                    // Validate input
                    if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['category_id'])) {
                        throw new Exception("Name, price, and category are required");
                    }

                    if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
                        throw new Exception("Price must be a positive number");
                    }

                    if (!is_numeric($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0) {
                        throw new Exception("Stock quantity must be a non-negative number");
                    }

                    // Insert product
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            name, description, price, category_id, 
                            stock_quantity, image, is_featured
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['name']),
                        trim($_POST['description']),
                        $_POST['price'],
                        $_POST['category_id'],
                        $_POST['stock_quantity'],
                        $_POST['image'],
                        isset($_POST['is_featured']) ? 1 : 0
                    ]);
                    
                    $productId = $pdo->lastInsertId();

                    // Log initial inventory
                    if ($_POST['stock_quantity'] > 0) {
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_log (
                                product_id, quantity_change, old_quantity,
                                new_quantity, change_type, changed_by, notes
                            ) VALUES (?, ?, 0, ?, 'restock', ?, 'Initial stock')
                        ");
                        $stmt->execute([
                            $productId,
                            $_POST['stock_quantity'],
                            $_POST['stock_quantity'],
                            $_SESSION['user_id']
                        ]);
                    }

                    $pdo->commit();
                    $_SESSION['success'] = "Product added successfully";
                    break;

                case 'edit':
                    // Validate input
                    if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['category_id']) || empty($_POST['product_id'])) {
                        throw new Exception("Name, price, category, and product ID are required");
                    }

                    // Get current stock quantity
                    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                    $stmt->execute([$_POST['product_id']]);
                    $currentProduct = $stmt->fetch();
                    
                    if (!$currentProduct) {
                        throw new Exception("Product not found");
                    }

                    $oldQuantity = $currentProduct['stock_quantity'];
                    $newQuantity = $_POST['stock_quantity'];

                    // Update product
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, description = ?, price = ?, 
                            category_id = ?, stock_quantity = ?, 
                            image = ?, is_featured = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        trim($_POST['name']),
                        trim($_POST['description']),
                        $_POST['price'],
                        $_POST['category_id'],
                        $newQuantity,
                        $_POST['image'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        $_POST['product_id']
                    ]);

                    // Log inventory change if quantity changed
                    if ($newQuantity != $oldQuantity) {
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_log (
                                product_id, quantity_change, old_quantity,
                                new_quantity, change_type, changed_by, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['product_id'],
                            $newQuantity - $oldQuantity,
                            $oldQuantity,
                            $newQuantity,
                            'adjustment',
                            $_SESSION['user_id'],
                            'Stock adjusted through product edit'
                        ]);
                    }

                    $pdo->commit();
                    $_SESSION['success'] = "Product updated successfully";
                    break;

                case 'delete':
                    if (empty($_POST['product_id'])) {
                        throw new Exception("Product ID is required");
                    }

                    // Check if product has any orders
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM order_items 
                        WHERE product_id = ?
                    ");
                    $stmt->execute([$_POST['product_id']]);
                    $orderCount = $stmt->fetchColumn();

                    if ($orderCount > 0) {
                        throw new Exception("Cannot delete product: It is associated with $orderCount order(s)");
                    }

                    // Delete product and let cascading take care of inventory log
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$_POST['product_id']]);

                    $pdo->commit();
                    $_SESSION['success'] = "Product deleted successfully";
                    break;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: products.php');
        exit();
    }
}

// Get products with category names and inventory status
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        c.name as category_name,
        (SELECT COUNT(*) FROM order_items WHERE product_id = p.id) as total_orders,
        (SELECT SUM(quantity) FROM order_items WHERE product_id = p.id) as total_units_sold
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.date_added DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for the form
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Connect Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Product Management</h1>
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
                    <h2 class="table-title">Products</h2>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openModal('add')">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Orders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="../images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $product['stock_quantity'] == 0 ? 'out' : ($product['stock_quantity'] < 10 ? 'low' : 'in'); ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="order-count" title="Total units sold: <?php echo $product['total_units_sold'] ?? 0; ?>">
                                        <?php echo $product['total_orders'] ?? 0; ?>
                                    </span>
                                </td>
                                <td class="table-actions-cell">
                                    <button class="action-btn" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add Product</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="product_id" id="productId">

                    <div class="form-group">
                        <label class="form-label" for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-input" required>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="price">Price</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="image">Image</label>
                        <input type="text" id="image" name="image" class="form-input" required>
                        <small>Enter the image filename (e.g., product.jpg)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_featured" id="is_featured">
                            Featured Product
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(action, product = null) {
        document.getElementById('productModal').style.display = 'block';
        document.getElementById('formAction').value = action;
        
        if (action === 'edit' && product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('price').value = product.price;
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('image').value = product.image;
            document.getElementById('is_featured').checked = product.is_featured == 1;
        } else {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('productForm').reset();
        }
    }

    function closeModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('productModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>
