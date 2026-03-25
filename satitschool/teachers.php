<?php
require_once 'includes/check_auth.php';
require_once 'includes/db.php';

// อัปเดตโครงสร้างฐานข้อมูลแบบอัตโนมัติ (เพิ่มคอลัมน์รหัสครู และ หมวดวิชา ถ้ายังไม่มี)
try {
    $pdo->query("SELECT teacher_code FROM teachers LIMIT 1");
} catch(Exception $e) {
    // ถ้าไม่มีจะทำการเพิ่ม
    $pdo->exec("ALTER TABLE teachers ADD COLUMN teacher_code VARCHAR(50) UNIQUE AFTER user_id, ADD COLUMN department VARCHAR(100) AFTER phone");
}

// สร้าง Roles หากเพิ่งลง Database ใหม่และยังไม่มีขัอมูล (ทำให้ User สร้างบัญชีได้)
try {
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'admin', 'Administrator'), (2, 'teacher', 'Teacher'), (3, 'student', 'Student')");
} catch(Exception $e) {}

$msg = '';

// การจัดการ Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT user_id, profile_pic FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    $t = $stmt->fetch();
    
    if ($t) {
        // ลบรูปภาพจากระบบ
        if ($t['profile_pic'] && file_exists($t['profile_pic'])) {
            @unlink($t['profile_pic']);
        }
        // ลบ User (คอลัมน์ Teacher จะถูก Cascade ลบอัตโนมัติ)
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$t['user_id']]);
        $msg = "ลบข้อมูลครูสำเร็จ!";
    }
}

// การจัดการ Add / Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_teacher'])) {
    $id = $_POST['id'] ?? '';
    $teacher_code = $_POST['teacher_code'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $line_id = $_POST['line_id'];
    
    // ความปลอดภัยการ Upload รูปภาพ
    $profile_pic = $_POST['existing_pic'] ?? '';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        
        $file_info = pathinfo($_FILES['profile_pic']['name']);
        $ext = strtolower($file_info['extension']);
        $mime = mime_content_type($_FILES['profile_pic']['tmp_name']);
        
        // ตรวจ Extension, MIME Type และ ขนาด (ห้ามเกิน 2MB)
        if (in_array($ext, $allowed_ext) && in_array($mime, $allowed_mime) && $_FILES['profile_pic']['size'] <= 2097152) {
            $upload_dir = 'uploads/teachers/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            // Random ชื่อไฟล์เพื่อป้องกันการโดนเดาชื่อหรือ execute shell
            $new_name = uniqid('t_', true) . '.' . $ext;
            $destination = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                // ถ้าอัปเดต ต้องลบไฟล์เก่าทิ้ง
                if ($profile_pic && file_exists($profile_pic)) unlink($profile_pic); 
                $profile_pic = $destination;
            }
        } else {
            $msg = "รูปไม่ถูกต้อง ตรวจสอบว่าเป็นไฟล์ (jpg/png) และขนาดไม่เกิน 2MB";
        }
    }

    if (empty($msg) || strpos($msg, 'สำเร็จ') !== false || $msg == '') {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE teachers SET teacher_code=?, first_name=?, last_name=?, phone=?, department=?, line_id=?, profile_pic=? WHERE id=?");
            try {
                $stmt->execute([$teacher_code, $first_name, $last_name, $phone, $department, $line_id, $profile_pic, $id]);
                $msg = "แก้ไขข้อมูลสำเร็จ!";
            } catch (Exception $e) {
                $msg = "ไม่สามารถแก้ไขได้ กรุณาตรวจสอบรหัสประจำตัวซ้ำ";
            }
        } else {
            // Add
            // 1. นำ Role ID = 2 สำหรับ Teacher
            $stmt = $pdo->query("SELECT id FROM roles WHERE name='teacher'");
            $role_id = $stmt->fetchColumn() ?: 2;
            
            // 2. Insert User (Default รหัสผ่าน 123456)
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$teacher_code, $password_hash, $role_id]);
                $user_id = $pdo->lastInsertId();
                
                // 3. Insert Teacher
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, teacher_code, first_name, last_name, phone, department, line_id, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $teacher_code, $first_name, $last_name, $phone, $department, $line_id, $profile_pic]);
                $msg = "เพิ่มข้อมูลสำเร็จ! (รหัสผ่านเริ่มต้นคือ 123456)";
            } catch (Exception $e) {
                $msg = "เกิดข้อผิดพลาด: รหัสประจำตัว (Username) นี้ถูกใช้งานแล้ว";
            }
        }
    }
}

// Fetch ข้อมูลครูมาแสดง
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY id DESC")->fetchAll();
?>

<!-- นำหน้า Header โครงสร้างเว็บมาใช้ -->
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-chalkboard-teacher me-2"></i> ข้อมูลครูผู้สอน</h3>
        <button class="btn btn-primary" style="background-color: var(--primary-color); border:none;" data-bs-toggle="modal" data-bs-target="#teacherModal" onclick="clearForm()">
            <i class="fas fa-user-plus me-1"></i> เพิ่มข้อมูลครู
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm">
            <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="card-custom-header">
                        <tr>
                            <th>รูป</th>
                            <th>รหัสประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>หมวดวิชา</th>
                            <th>เบอร์โทร</th>
                            <th>Line ID</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t): ?>
                        <tr>
                            <td>
                                <?php if($t['profile_pic'] && file_exists($t['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($t['profile_pic']) ?>" class="rounded-circle shadow-sm" width="50" height="50" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center text-white shadow-sm" style="width:50px; height:50px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($t['teacher_code'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td><?= htmlspecialchars($t['department'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($t['phone']) ?></td>
                            <td><?= htmlspecialchars($t['line_id']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning mb-1" onclick='editTeacher(<?= json_encode($t) ?>)'><i class="fas fa-edit"></i> แก้ไข</button>
                                <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ยืนยันการลบข้อมูลบุคลากรนี้? (บัญชีเข้าสู่ระบบของผู้ใช้นี้จะถูกลบไปด้วย)')"><i class="fas fa-trash"></i> ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($teachers)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลรายชื่อครู</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="teacherModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="teachers.php" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header card-custom-header">
        <h5 class="modal-title" id="modalTitle">เพิ่มข้อมูลครู</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="t_id">
        <input type="hidden" name="existing_pic" id="t_existing_pic">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">รหัสประจำตัว (ใช้เป็น Username)</label>
                <input type="text" name="teacher_code" id="t_code" class="form-control" placeholder="เช่น T1001" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">หมวดวิชา</label>
                <input type="text" name="department" id="t_dept" class="form-control" placeholder="เช่น วิทยาศาสตร์" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">ชื่อ</label>
                <input type="text" name="first_name" id="t_fname" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">นามสกุล</label>
                <input type="text" name="last_name" id="t_lname" class="form-control" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                <input type="text" name="phone" id="t_phone" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Line ID</label>
                <input type="text" name="line_id" id="t_line" class="form-control">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">อัปโหลดรูปโปรไฟล์ (ไม่เกิน 2MB รองรับ jpg, png, gif)</label>
            <input type="file" name="profile_pic" class="form-control" accept="image/*">
            <small class="text-danger mt-1 d-block" id="t_pic_help"></small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" name="submit_teacher" class="btn btn-primary" style="background-color: var(--primary-color); border:none;"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
      </div>
    </form>
  </div>
</div>

<script>
// ฟังก์ชันสำหรับเคลียร์โมดอลตอนกดยืนเพิ่มใหม่
function clearForm() {
    document.getElementById('modalTitle').innerText = 'เพิ่มข้อมูลครูใหม่';
    document.getElementById('t_id').value = '';
    document.getElementById('t_existing_pic').value = '';
    document.getElementById('t_code').value = '';
    document.getElementById('t_fname').value = '';
    document.getElementById('t_lname').value = '';
    document.getElementById('t_dept').value = '';
    document.getElementById('t_phone').value = '';
    document.getElementById('t_line').value = '';
    document.getElementById('t_pic_help').innerText = '';
    document.getElementById('t_code').readOnly = false;
}

// ฟังก์ชันดึงข้อมูลใส่โมดอลตอนกดแก้ไข
function editTeacher(t) {
    document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลครู';
    document.getElementById('t_id').value = t.id;
    document.getElementById('t_existing_pic').value = t.profile_pic || '';
    document.getElementById('t_code').value = t.teacher_code || '';
    document.getElementById('t_fname').value = t.first_name;
    document.getElementById('t_lname').value = t.last_name;
    document.getElementById('t_dept').value = t.department || '';
    document.getElementById('t_phone').value = t.phone || '';
    document.getElementById('t_line').value = t.line_id || '';
    
    // ป้องกันการแก้ไข Username ป้องกันตาราง Users พัง
    document.getElementById('t_code').readOnly = true; 
    
    if (t.profile_pic) {
        document.getElementById('t_pic_help').innerText = '* มีรูปภาพประวัติอยู่แล้ว หาไม่ต้องการเปลี่ยนไม่ต้องกดเลือกไฟล์ใหม่';
    } else {
        document.getElementById('t_pic_help').innerText = '';
    }
    var modal = new bootstrap.Modal(document.getElementById('teacherModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
