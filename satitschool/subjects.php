<?php
require_once 'includes/check_auth.php';
require_once 'includes/db.php';

$msg = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
        $msg = "ลบข้อมูลวิชาเรียบร้อยแล้ว!";
    } catch(Exception $e) {
        $msg = "ไม่สามารถลบได้ เนื่องจากวิชานี้ถูกผูกการใช้งานในตารางสอนหรือบันทึกผลการเรียนแล้ว";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_subject'])) {
    $id = $_POST['id'] ?? '';
    $subject_code = $_POST['subject_code'];
    $name = $_POST['name'];
    $credit = $_POST['credit'];
    $description = $_POST['description'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_code=?, name=?, credit=?, description=? WHERE id=?");
        try {
            $stmt->execute([$subject_code, $name, $credit, $description, $id]);
            $msg = "แก้ไขข้อมูลวิชาสำเร็จ!";
        } catch (Exception $e) {
            $msg = "ไม่สามารถแก้ไขได้ กรุณาตรวจสอบรหัสวิชาซ้ำ";
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, name, credit, description) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$subject_code, $name, $credit, $description]);
            $msg = "เพิ่มรายวิชาสำเร็จ!";
        } catch (Exception $e) {
            $msg = "เกิดข้อผิดพลาด: รหัสวิชานี้มีอยู่ในระบบแล้ว";
        }
    }
}

// โหลดข้อมูลรายวิชามาโชว์ในตาราง
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_code ASC")->fetchAll();
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-book me-2"></i> ข้อมูลรายวิชา</h3>
        <button class="btn btn-primary" style="background-color: var(--primary-color); border:none;" data-bs-toggle="modal" data-bs-target="#subjectModal" onclick="clearForm()">
            <i class="fas fa-plus-circle me-1"></i> เพิ่มรายวิชา
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
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>หน่วยกิต</th>
                            <th>คำอธิบายรายวิชา</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['subject_code']) ?></strong></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars($s['credit']) ?> หน่วยกิต</span></td>
                            <td><?= htmlspecialchars($s['description'] ?: '-') ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning mb-1" onclick='editSubject(<?= json_encode($s) ?>)'><i class="fas fa-edit"></i> แก้ไข</button>
                                <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ยืนยันลบข้อมูลรายวิชานี้? (ระวัง: หากลบแล้วตารางเรียนที่เกี่ยวข้องอาจได้รับผลกระทบ)')"><i class="fas fa-trash"></i> ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($subjects)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลรายวิชาในระบบ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="subjectModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="subjects.php" class="modal-content">
      <div class="modal-header card-custom-header">
        <h5 class="modal-title" id="modalTitle">เพิ่มข้อมูลรายวิชา</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="sub_id">
        
        <div class="mb-3">
            <label class="form-label fw-bold">รหัสวิชา</label>
            <input type="text" name="subject_code" id="sub_code" class="form-control" placeholder="เช่น ท10101" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ชื่อวิชา</label>
            <input type="text" name="name" id="sub_name" class="form-control" placeholder="เช่น ภาษาไทยพื้นฐาน" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">หน่วยกิต</label>
            <input type="number" step="0.5" name="credit" id="sub_credit" class="form-control" value="1.0" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">คำอธิบายรายวิชา (ถ้ามี)</label>
            <textarea name="description" id="sub_desc" class="form-control" rows="3" placeholder="วิชานี้เรียนเกี่ยวกับ..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" name="submit_subject" class="btn btn-primary" style="background-color: var(--primary-color); border:none;"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
      </div>
    </form>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'เพิ่มรายวิชาใหม่';
    document.getElementById('sub_id').value = '';
    document.getElementById('sub_code').value = '';
    document.getElementById('sub_name').value = '';
    document.getElementById('sub_credit').value = '1.0';
    document.getElementById('sub_desc').value = '';
    document.getElementById('sub_code').readOnly = false;
}

function editSubject(s) {
    document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลวิชา';
    document.getElementById('sub_id').value = s.id;
    document.getElementById('sub_code').value = s.subject_code;
    document.getElementById('sub_name').value = s.name;
    document.getElementById('sub_credit').value = s.credit;
    document.getElementById('sub_desc').value = s.description || '';
    
    document.getElementById('sub_code').readOnly = true; 
    
    var modal = new bootstrap.Modal(document.getElementById('subjectModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
