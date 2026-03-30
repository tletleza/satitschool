<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$user_id = $_POST['user_id'];
$role_id = $_POST['role_id'];
$status = $_POST['status'];
$new_password = trim($_POST['new_password'] ?? '');

// ป้องกัน Admin เผลอลดสิทธิ์ตัวเอง (ID 1 หรือ Username admin)
if ($user_id == 1 || $_SESSION['user_id'] == $user_id) {
    if ($role_id != 1) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนสิทธิ์ Administrator ของตนเองได้']);
        exit;
    }
}

try {
    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET role_id = ?, status = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$role_id, $status, $hashed, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role_id = ?, status = ? WHERE id = ?");
        $stmt->execute([$role_id, $status, $user_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
