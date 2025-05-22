<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check admin access
checkAdminAccess();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();

            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([
                        trim($_POST['name']),
                        trim($_POST['description'])
                    ]);
                    $_SESSION['success'] = "Category added successfully";
                    break;

                case 'edit':
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([
                        trim($_POST['name']),
                        trim($_POST['description']),
                        $_POST['category_id']
                    ]);
                    $_SESSION['success'] = "Category updated successfully";
                    break;

                case 'delete':
                    // Check if category has products
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                    $stmt->execute([$_POST['category_id']]);
                    $count = $stmt->fetchColumn();

                    if ($count > 0) {
                        throw new Exception("Cannot delete category: It contains $count product(s)");
                    }

                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$_POST['category_id']]);
                    $_SESSION['success'] = "Category deleted successfully";
                    break;
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: categories.php');
        exit();
    }
}

// Get all categories with product counts
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        COUNT(p.id) as product_count,
        MAX(p.date_added) as last_product_added
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id, c.name, c.description, c.created_at, c.updated_at
    ORDER BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Connect Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Category Management</h1>
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
                    <h2 class="table-title">Categories</h2>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openModal('add')">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <span class="badge">
                                        <?php echo $category['product_count']; ?> product(s)
                                    </span>
                                </td>
                                <td><?php echo formatDate($category['updated_at']); ?></td>
                                <td class="table-actions-cell">
                                    <button class="action-btn" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($category['product_count'] == 0): ?>
                                    <button class="action-btn" onclick="deleteCategory(<?php echo $category['id']; ?>)">
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

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add Category</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="categoryForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="category_id" id="categoryId">

                    <div class="form-group">
                        <label class="form-label" for="name">Category Name</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(action, category = null) {
        document.getElementById('categoryModal').style.display = 'block';
        document.getElementById('formAction').value = action;
        
        if (action === 'edit' && category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description;
        } else {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('categoryForm').reset();
        }
    }

    function closeModal() {
        document.getElementById('categoryModal').style.display = 'none';
    }

    function deleteCategory(categoryId) {
        if (confirm('Are you sure you want to delete this category?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" value="${categoryId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('categoryModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>
