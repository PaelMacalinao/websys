<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check admin access
checkAdminAccess();

try {
    // Get inventory statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_products,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity < 10 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock_quantity >= 10 THEN 1 ELSE 0 END) as in_stock
        FROM products
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get inventory data with recent movement history
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            c.name as category_name,
            il.created_at as last_movement,
            il.change_type as last_movement_type,
            il.quantity_change as last_quantity_change,
            COALESCE(oi.total_sold, 0) as total_sold,
            COALESCE(il_sum.total_restocked, 0) as total_restocked
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN (
            SELECT product_id, SUM(quantity) as total_sold 
            FROM order_items 
            GROUP BY product_id
        ) oi ON p.id = oi.product_id
        LEFT JOIN (
            SELECT product_id, MAX(id) as last_log_id
            FROM inventory_log
            GROUP BY product_id
        ) il_last ON p.id = il_last.product_id
        LEFT JOIN inventory_log il ON il_last.last_log_id = il.id
        LEFT JOIN (
            SELECT product_id, SUM(CASE WHEN change_type = 'restock' THEN quantity_change ELSE 0 END) as total_restocked
            FROM inventory_log
            GROUP BY product_id
        ) il_sum ON p.id = il_sum.product_id
        ORDER BY p.stock_quantity ASC, p.name ASC
    ");
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle inventory updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        try {
            $pdo->beginTransaction();

            $product_id = $_POST['product_id'];
            $new_quantity = (int)$_POST['stock_quantity'];
            $old_quantity = (int)$_POST['old_quantity'];
            $quantity_change = $new_quantity - $old_quantity;
            $change_type = $_POST['change_type'];
            $notes = $_POST['notes'] ?? '';

            if ($new_quantity < 0) {
                throw new Exception("Stock quantity cannot be negative");
            }

            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $product_id]);

            // Log the change in inventory_log
            $stmt = $pdo->prepare("
                INSERT INTO inventory_log (
                    product_id, quantity_change, old_quantity, new_quantity, 
                    change_type, changed_by, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $product_id,
                $quantity_change,
                $old_quantity,
                $new_quantity,
                $change_type,
                $_SESSION['user_id'],
                $notes
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Inventory updated successfully";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Get low stock and out of stock counts
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN stock_quantity < 10 THEN 1 ELSE 0 END) as low_stock_count,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count
        FROM products");
    $stmt->execute();
    $stockCounts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lowStockCount = $stockCounts['low_stock_count'] ?? 0;
    $outOfStockCount = $stockCounts['out_of_stock_count'] ?? 0;

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $inventory = [];
    $lowStockCount = 0;
    $outOfStockCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Connect Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Inventory Management</h1>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Low Stock Items</h3>
                        <div class="card-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $lowStockCount; ?></div>
                    <p class="card-subtitle">Products with less than 10 units</p>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Out of Stock</h3>
                        <div class="card-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $outOfStockCount; ?></div>
                    <p class="card-subtitle">Products with 0 units</p>
                </div>
            </div>            <div class="data-table">
                <div class="table-header">
                    <h2 class="table-title">Inventory Status</h2>
                    <div class="table-actions">
                        <button class="btn btn-outline" id="toggleFilters">
                            <i class="fas fa-filter"></i> Filters
                        </button>
                        <button class="btn btn-primary" onclick="exportInventory()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="filters-panel" id="filtersPanel" style="display: none;">
                    <div class="filter-group">
                        <label>Category:</label>
                        <select id="categoryFilter" class="filter-select">
                            <option value="">All Categories</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
                            while ($category = $stmt->fetch()) {
                                echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="out">Out of Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="in">In Stock</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="inventoryTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>                                <td>
                                    <div class="product-info-cell">
                                        <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td>
                                    <div class="stock-info">
                                        <span class="stock-quantity"><?php echo $item['stock_quantity']; ?></span>
                                        <?php if ($item['last_movement']): ?>
                                        <div class="movement-info">
                                            <small class="movement-type <?php echo $item['last_movement_type']; ?>">
                                                <?php 
                                                    $changeType = ucfirst($item['last_movement_type']);
                                                    $changeAmount = $item['last_quantity_change'] > 0 ? '+' . $item['last_quantity_change'] : $item['last_quantity_change'];
                                                    echo "$changeType ($changeAmount)";
                                                ?>
                                            </small>
                                            <small class="movement-date">
                                                <?php echo date('M j, g:ia', strtotime($item['last_movement'])); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>                                <td class="status-cell">
                                    <?php 
                                        $statusClass = 'in-stock';
                                        $statusText = 'In Stock';
                                        
                                        if ($item['stock_quantity'] == 0) {
                                            $statusClass = 'out-of-stock';
                                            $statusText = 'Out of Stock';
                                        } elseif ($item['stock_quantity'] < 10) {
                                            $statusClass = 'low-stock';
                                            $statusText = 'Low Stock';
                                        }
                                        
                                        $totalSold = (int)$item['total_sold'];
                                        $totalRestocked = (int)$item['total_restocked'];
                                    ?>
                                    <div class="status-wrapper">
                                        <div class="status-badge <?php echo $statusClass; ?>">
                                            <span class="status-dot"></span>
                                            <span class="status-text"><?php echo $statusText; ?></span>
                                        </div>
                                        <?php if ($totalSold > 0 || $totalRestocked > 0): ?>
                                        <div class="movement-stats">
                                            <?php if ($totalSold > 0): ?>
                                            <div class="stat-item sold">
                                                <i class="fas fa-shopping-cart"></i>
                                                <span class="stat-text"><?php echo $totalSold; ?> sold</span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($totalRestocked > 0): ?>
                                            <div class="stat-item restocked">
                                                <i class="fas fa-box"></i>
                                                <span class="stat-text"><?php echo $totalRestocked; ?> restocked</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td><td class="table-actions-cell">
                                    <div class="action-buttons">
                                        <button class="action-btn" 
                                            data-id="<?php echo htmlspecialchars($item['id']); ?>"
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                            data-stock="<?php echo htmlspecialchars($item['stock_quantity']); ?>"
                                            onclick="openModalWithData(this)"
                                            title="Edit Stock">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view-history" 
                                            data-id="<?php echo htmlspecialchars($item['id']); ?>"
                                            onclick="viewHistory(this)"
                                            title="View History">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Inventory Update Modal -->
    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Inventory</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="inventoryForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" id="productId">
                    <input type="hidden" name="old_quantity" id="oldQuantity">

                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" id="productName" class="form-input" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Current Stock</label>
                        <input type="text" id="currentStock" class="form-input" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stock_quantity">New Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-input" required min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="change_type">Change Type</label>
                        <select id="change_type" name="change_type" class="form-input" required>
                            <option value="restock">Restock</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="return">Return</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" 
                            placeholder="Enter any notes about this stock update"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Stock Movement History</h2>
                <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="history-list" id="historyList">
                    <!-- History items will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    function openModalWithData(button) {
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const stock = button.getAttribute('data-stock');
        
        document.getElementById('inventoryModal').style.display = 'block';
        document.getElementById('productId').value = id;
        document.getElementById('productName').value = name;
        document.getElementById('currentStock').value = stock;
        document.getElementById('oldQuantity').value = stock;
        document.getElementById('stock_quantity').value = stock;
    }

    function closeModal() {
        document.getElementById('inventoryModal').style.display = 'none';
    }

    async function viewHistory(button) {
        const productId = button.getAttribute('data-id');
        const historyModal = document.getElementById('historyModal');
        const historyList = document.getElementById('historyList');
        
        try {
            const response = await fetch(`get_inventory_history.php?product_id=${productId}`);
            const data = await response.json();
            
            let historyHTML = '<div class="history-items">';
            data.forEach(item => {
                const changeClass = item.quantity_change > 0 ? 'positive' : 'negative';
                const changeIcon = item.quantity_change > 0 ? '↑' : '↓';
                historyHTML += `
                    <div class="history-item">
                        <div class="history-header">
                            <span class="history-type ${item.change_type.toLowerCase()}">${item.change_type}</span>
                            <span class="history-date">${formatDate(item.created_at)}</span>
                        </div>
                        <div class="history-details">
                            <span class="quantity-change ${changeClass}">
                                ${changeIcon} ${Math.abs(item.quantity_change)} units
                            </span>
                            <span class="stock-levels">
                                ${item.old_quantity} → ${item.new_quantity}
                            </span>
                        </div>
                        ${item.notes ? `<div class="history-notes">${item.notes}</div>` : ''}
                    </div>
                `;
            });
            historyHTML += '</div>';
            
            historyList.innerHTML = historyHTML;
            historyModal.style.display = 'block';
        } catch (error) {
            console.error('Error fetching history:', error);
        }
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').style.display = 'none';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    document.getElementById('toggleFilters').addEventListener('click', function() {
        const filtersPanel = document.getElementById('filtersPanel');
        filtersPanel.style.display = filtersPanel.style.display === 'none' ? 'flex' : 'none';
    });

    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', filterTable);
    });

    function filterTable() {
        const categoryValue = document.getElementById('categoryFilter').value;
        const statusValue = document.getElementById('statusFilter').value;
        const rows = document.getElementById('inventoryTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let row of rows) {
            let showRow = true;
            const categoryCell = row.cells[2].textContent.trim();
            const stockValue = parseInt(row.cells[3].textContent);
            const statusCell = row.cells[4].textContent.trim().toLowerCase();

            if (categoryValue && !categoryCell.includes(categoryValue)) showRow = false;
            if (statusValue) {
                if (statusValue === 'out' && stockValue !== 0) showRow = false;
                if (statusValue === 'low' && (stockValue === 0 || stockValue >= 10)) showRow = false;
                if (statusValue === 'in' && stockValue < 10) showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        }
    }

    function exportInventory() {
        const table = document.getElementById('inventoryTable');
        let csv = [];
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].getElementsByTagName('td');
            if (i === 0) { // Headers
                const headers = rows[i].getElementsByTagName('th');
                for (let j = 0; j < headers.length - 1; j++) { // Skip Actions column
                    row.push(headers[j].textContent);
                }
            } else {
                for (let j = 0; j < cols.length - 1; j++) { // Skip Actions column
                    row.push(cols[j].textContent.trim());
                }
            }
            csv.push(row.join(','));
        }

        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `inventory_${formatDate(new Date())}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('inventoryModal')) {
            closeModal();
        }
        if (event.target == document.getElementById('historyModal')) {
            closeHistoryModal();
        }
    }
    </script>
</body>
</html>
