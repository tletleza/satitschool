<?php
require_once 'includes/check_auth.php';
require_once 'includes/db.php';

// เตรียม Roles ไว้สำหรับ User นักเรียน
try {
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (3, 'student', 'Student')");
} catch(Exception $e) {}

$msg = '';

// การรับคำสั่ง Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if ($student) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$student['user_id']]);
        $msg = "ลบข้อมูลนักเรียนสำเร็จ!";
    }
}

// การรับการ Submit แบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_student'])) {
    $id = $_POST['id'] ?? '';
    $student_code = $_POST['student_code'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'] ?: null; // ถ้าไม่ได้เลือกวันเกิดให้เป็น null
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $parent_phone = $_POST['parent_phone'];
    $classroom_id = $_POST['classroom_id'] ?: null;

    if (empty($msg)) {
        if ($id) {
            // จัดการอัปโหลดรูปภาพ
            $profile_picture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $new_name = uniqid('student_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], 'uploads/students/' . $new_name)) {
                        $profile_picture = 'uploads/students/' . $new_name;
                    }
                }
            }

            // โหมด Edit
            try {
                if ($profile_picture) {
                    // ดึงรูปเก่ามาลบ
                    $stmt = $pdo->prepare("SELECT profile_picture FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $old_pic = $stmt->fetchColumn();
                    if ($old_pic && file_exists($old_pic)) {
                        unlink($old_pic);
                    }
                
                    $stmt = $pdo->prepare("UPDATE students SET student_code=?, first_name=?, last_name=?, dob=?, gender=?, address=?, parent_phone=?, profile_picture=? WHERE id=?");
                    $stmt->execute([$student_code, $first_name, $last_name, $dob, $gender, $address, $parent_phone, $profile_picture, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE students SET student_code=?, first_name=?, last_name=?, dob=?, gender=?, address=?, parent_phone=? WHERE id=?");
                    $stmt->execute([$student_code, $first_name, $last_name, $dob, $gender, $address, $parent_phone, $id]);
                }
                
                // จัดการระดับชั้นเรียน
                if ($classroom_id) {
                    $check = $pdo->prepare("SELECT student_id FROM student_classrooms WHERE student_id = ?");
                    $check->execute([$id]);
                    if ($check->rowCount() > 0) {
                        $pdo->prepare("UPDATE student_classrooms SET classroom_id = ? WHERE student_id = ?")->execute([$classroom_id, $id]);
                    } else {
                        $pdo->prepare("INSERT INTO student_classrooms (student_id, classroom_id) VALUES (?, ?)")->execute([$id, $classroom_id]);
                    }
                } else {
                    $pdo->prepare("DELETE FROM student_classrooms WHERE student_id = ?")->execute([$id]);
                }
                
                $msg = "แก้ไขข้อมูลนักเรียนสำเร็จ!";
            } catch (Exception $e) {
                $msg = "ไม่สามารถแก้ไขได้ กรุณาตรวจสอบรหัสนักเรียนซ้ำ";
            }
        } else {
            // โหมด Add
            $stmt = $pdo->query("SELECT id FROM roles WHERE name='student'");
            $role_id = $stmt->fetchColumn() ?: 3;
            
            // สร้าง User account สำหรับนักเรียนก่อน
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$student_code, $password_hash, $role_id]);
                $user_id = $pdo->lastInsertId();
                
                // จัดการอัปโหลดรูปภาพ
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $new_name = uniqid('student_') . '.' . $ext;
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], 'uploads/students/' . $new_name)) {
                            $profile_picture = 'uploads/students/' . $new_name;
                        }
                    }
                }

                // นำ User ID ไปผูกและ Insert ลงตาราง Students
                $stmt = $pdo->prepare("INSERT INTO students (user_id, student_code, first_name, last_name, dob, gender, address, parent_phone, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_code, $first_name, $last_name, $dob, $gender, $address, $parent_phone, $profile_picture]);
                $new_student_id = $pdo->lastInsertId();
                
                // จัดการระดับชั้นเรียน
                if ($classroom_id) {
                    $pdo->prepare("INSERT INTO student_classrooms (student_id, classroom_id) VALUES (?, ?)")->execute([$new_student_id, $classroom_id]);
                }
                
                $msg = "เพิ่มข้อมูลนักเรียนสำเร็จ! (รหัสผ่านเริ่มต้นคือ 123456)";
            } catch (Exception $e) {
                $msg = "เกิดข้อผิดพลาด: รหัสนักเรียน (Username) นี้ถูกใช้งานแล้วในระบบ";
            }
        }
    }
}

// ดึงข้อมูลทั้งหมดมาแสดงในตาราง พร้อมกับระดับชั้นและห้องเรียน
$students_query = "
    SELECT s.*, c.id as classroom_id, c.room_name, g.level_name
    FROM students s
    LEFT JOIN student_classrooms sc ON s.id = sc.student_id
    LEFT JOIN classrooms c ON sc.classroom_id = c.id
    LEFT JOIN grades g ON c.grade_id = g.id
    ORDER BY s.id DESC
";
$students = $pdo->query($students_query)->fetchAll();

// ดึงข้อมูลห้องเรียนเพื่อนำไปใส่ใน Dropdown ตัวเลือก (1/1, 1/2...)
$classrooms_query = "
    SELECT c.id, c.room_name, g.level_name 
    FROM classrooms c 
    JOIN grades g ON c.grade_id = g.id 
    ORDER BY g.id ASC, c.room_name ASC
";
$classrooms_list = $pdo->query($classrooms_query)->fetchAll();
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-user-graduate me-2"></i> ข้อมูลนักเรียน</h3>
        <button class="btn btn-primary" style="background-color: var(--primary-color); border:none;" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="clearForm()">
            <i class="fas fa-user-plus me-1"></i> เพิ่มข้อมูลนักเรียน
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
                            <th width="70">รูปโปรไฟล์</th>
                            <th>รหัสประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ระดับชั้น/ห้อง</th>
                            <th>เพศ</th>
                            <th>เบอร์ผู้ปกครอง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td class="text-center">
                                <?php if($s['profile_picture']): ?>
                                    <img src="<?= htmlspecialchars($s['profile_picture']) ?>" class="rounded-circle shadow-sm border" style="width: 48px; height: 48px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle shadow-sm bg-light border d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                                        <i class="fas fa-user text-secondary fa-lg"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($s['student_code']) ?></strong></td>
                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td>
                                <?php if($s['level_name']): ?>
                                    <span class="badge bg-info text-dark shadow-sm"><?= htmlspecialchars($s['level_name']." (".$s['room_name'].")") ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['gender'] == 'M' ? '<span class="badge bg-primary">ชาย</span>' : ($s['gender'] == 'F' ? '<span class="badge bg-danger">หญิง</span>' : '<span class="badge bg-secondary">อื่นๆ</span>') ?></td>
                            <td><?= htmlspecialchars($s['parent_phone'] ?? '-') ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning mb-1" onclick='editStudent(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i> แก้ไข</button>
                                <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ยืนยันลบข้อมูลนักเรียนคนนี้? (บัญชีผู้ใช้สำหรับเข้าระบบจะถูกลบทิ้งด้วย)')"><i class="fas fa-trash"></i> ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($students)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูลนักเรียน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form สำหรับ Add / Edit -->
<div class="modal fade" id="studentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="students.php" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header card-custom-header">
        <h5 class="modal-title" id="modalTitle">เพิ่มข้อมูลนักเรียน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="s_id">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">รหัสนักเรียน (ใช้เป็น Username)</label>
                <input type="text" name="student_code" id="s_code" class="form-control" placeholder="เช่น 64001" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">เบอร์โทรผู้ปกครอง</label>
                <input type="text" name="parent_phone" id="s_phone" class="form-control" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">ชื่อ</label>
                <input type="text" name="first_name" id="s_fname" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">นามสกุล</label>
                <input type="text" name="last_name" id="s_lname" class="form-control" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">ระดับชั้นและห้องเรียน</label>
                <select name="classroom_id" id="s_classroom" class="form-select">
                    <option value="">-- ไม่ระบุชั้นเรียน --</option>
                    <?php foreach($classrooms_list as $cl): ?>
                    <option value="<?= $cl['id'] ?>">
                        <?= htmlspecialchars($cl['level_name']." (".$cl['room_name'].")") ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">เพศ</label>
                <select name="gender" id="s_gender" class="form-select">
                    <option value="M">ชาย</option>
                    <option value="F">หญิง</option>
                    <option value="Other">อื่นๆ</option>
                </select>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">วันเกิด</label>
                <input type="date" name="dob" id="s_dob" class="form-control">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">ที่อยู่ (Address)</label>
            <textarea name="address" id="s_address" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="mb-3 border rounded p-3 bg-light">
            <label class="form-label fw-bold"><i class="fas fa-image me-1 text-primary"></i> รูปประจำตัว (Profile Picture)</label>
            <input type="file" name="profile_picture" id="s_profile_picture" class="form-control bg-white" accept="image/jpeg, image/png, image/webp">
            <div id="current_s_pic" class="mt-2 text-muted small fw-bold"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" name="submit_student" class="btn btn-primary" style="background-color: var(--primary-color); border:none;"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
      </div>
    </form>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'เพิ่มข้อมูลนักเรียนใหม่';
    document.getElementById('s_id').value = '';
    document.getElementById('s_code').value = '';
    document.getElementById('s_fname').value = '';
    document.getElementById('s_lname').value = '';
    document.getElementById('s_phone').value = '';
    document.getElementById('s_classroom').value = '';
    document.getElementById('s_gender').value = 'M';
    document.getElementById('s_dob').value = '';
    document.getElementById('s_address').value = '';
    document.getElementById('current_s_pic').innerHTML = '';
    // อนุญาตให้แก้ไข Username ได้หากเป็นการเพิ่มใหม่
    document.getElementById('s_code').readOnly = false;
}

function editStudent(s) {
    document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลนักเรียน';
    document.getElementById('s_id').value = s.id;
    document.getElementById('s_code').value = s.student_code;
    document.getElementById('s_fname').value = s.first_name;
    document.getElementById('s_lname').value = s.last_name;
    document.getElementById('s_phone').value = s.parent_phone;
    document.getElementById('s_classroom').value = s.classroom_id || '';
    document.getElementById('s_gender').value = s.gender || 'M';
    document.getElementById('s_dob').value = s.dob || '';
    document.getElementById('s_address').value = s.address || '';
    
    if(s.profile_picture) {
        document.getElementById('current_s_pic').innerHTML = `<img src="${s.profile_picture}" class="rounded shadow-sm me-2 border" style="width: 45px; height: 45px; object-fit: cover;"> อัปโหลดรูปใหม่เพื่อเปลี่ยนแปลง`;
    } else {
        document.getElementById('current_s_pic').innerHTML = 'ยังไม่มีรูปภาพประจำตัว';
    }
    
    // ห้ามแก้ไข Username เมื่ออยู่ในโหมด Edit เพื่อป้องกันการผิดพลาดของ Relation DB
    document.getElementById('s_code').readOnly = true; 
    
    var modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
