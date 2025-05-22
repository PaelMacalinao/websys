<?php
session_start();
require_once '../config.php';
require_once 'admin_functions.php';

// Check admin access
checkAdminAccess();

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            il.*,
            u.username as changed_by_user
        FROM inventory_log il
        JOIN users u ON il.changed_by = u.id
        WHERE il.product_id = ?
        ORDER BY il.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$_GET['product_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($history);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
