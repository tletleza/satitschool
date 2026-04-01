<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

if ($_SESSION['role'] === 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['news_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$created_by = $_SESSION['user_id'];

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit;
}

$upload_dir = '../uploads/news/';
$image_url = null;

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['image']['tmp_name'];
    $name = basename($_FILES['image']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $new_name = uniqid('news_') . '.' . $ext;
        if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
            $image_url = 'uploads/news/' . $new_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to write uploaded file. Ensure uploads/news is writable.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp']);
        exit;
    }
}

try {
    if ($id) {
        // Update existing
        if ($image_url) {
            // Fetch old image to delete it later
            $stmt = $pdo->prepare("SELECT image_url FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$title, $content, $image_url, $id]);
            
            if ($old_image && file_exists('../' . $old_image)) {
                unlink('../' . $old_image);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $id]);
        }
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO news (title, content, image_url, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $image_url, $created_by]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
