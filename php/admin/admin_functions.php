<?php
// Include main config
require_once '../config.php';

// Check if user is logged in and is an admin
function checkAdminAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../login.php');
        exit();
    }
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Get error message if exists and clear it
function getError() {
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);
    return $error;
}

// Get success message if exists and clear it
function getSuccess() {
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['success']);
    return $success;
}

// Log inventory changes
function logInventoryChange($pdo, $product_id, $quantity_change, $old_quantity, $change_type, $notes = '') {
    $stmt = $pdo->prepare("
        INSERT INTO inventory_log (
            product_id, 
            quantity_change, 
            old_quantity, 
            new_quantity, 
            change_type, 
            changed_by, 
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $product_id,
        $quantity_change,
        $old_quantity,
        $old_quantity + $quantity_change,
        $change_type,
        $_SESSION['user_id'],
        $notes
    ]);
}

// Get stock status label
function getStockStatusLabel($quantity) {
    if ($quantity <= 0) {
        return ['Out of Stock', 'danger'];
    } elseif ($quantity < 10) {
        return ['Low Stock', 'warning'];
    } else {
        return ['In Stock', 'success'];
    }
}

// Update product stock
function updateProductStock($pdo, $product_id, $quantity_change, $change_type, $notes = '') {
    try {
        $pdo->beginTransaction();
        
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$product_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            throw new Exception("Product not found");
        }
        
        $old_quantity = $current['stock_quantity'];
        $new_quantity = $old_quantity + $quantity_change;
        
        if ($new_quantity < 0) {
            throw new Exception("Cannot reduce stock below 0");
        }
        
        // Update stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $product_id]);
        
        // Log the change
        logInventoryChange($pdo, $product_id, $quantity_change, $old_quantity, $change_type, $notes);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
