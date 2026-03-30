<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

try {
    $stmt = $pdo->query("SELECT n.*, u.username as creator_name 
                         FROM news n 
                         JOIN users u ON n.created_by = u.id 
                         ORDER BY n.created_at DESC");
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $news]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
