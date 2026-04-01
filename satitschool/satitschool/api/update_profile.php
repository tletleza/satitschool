<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

try {
    // 1. จัดการเปลี่ยนรหัสผ่าน (ถ้ามีการกรอกรหัสใหม่เข้ามา)
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'รหัสผ่านใหม่ไม่ตรงกัน']);
            exit;
        }
        
        // ถ้าไม่ใช่ Admin ต้องยืนยันรหัสเก่า
        if ($role !== 'admin') {
            if (empty($current_password)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกรหัสผ่านปัจจุบัน เพื่อยืนยันตัวตน']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $hash)) {
                echo json_encode(['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
                exit;
            }
        }
        
        // อัปเดตรหัสผ่านใหม่
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
    }
    
    // 2. จัดการอัปโหลดรูปโปรไฟล์
    $upload_dir = '../uploads/users/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['profile_picture']['tmp_name'];
        $name = basename($_FILES['profile_picture']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('user_') . '.' . $ext;
            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $profile_picture = 'uploads/users/' . $new_name;
                
                // Fetch old image to delete it later
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $old_image = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$profile_picture, $user_id]);
                
                if ($old_image && file_exists('../' . $old_image) && strpos($old_image, 'uploads/users/') === 0) {
                    unlink('../' . $old_image);
                }
                
                $_SESSION['profile_picture'] = $profile_picture; // อัปเดต Session ไปยังรูปใหม่
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ประเภทไฟล์ไม่รองรับ (jpg, png, webp เท่านั้น)']);
            exit;
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
