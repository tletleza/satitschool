<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

// เฉพาะ Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.role_id, r.name as role_name, u.status, u.last_login 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         ORDER BY u.id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
