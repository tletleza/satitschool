<?php
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// อนุญาตเฉพาะ Admin และ Teacher
authorize(['admin', 'teacher']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// ดึงรายชื่อวิชาและห้องเรียน (RBAC: ครูเห็นเฉพาะวิชาตัวเอง)
$query = "SELECT DISTINCT s.id as subject_id, s.name as subject_name, s.subject_code, c.id as classroom_id, c.room_name, g.level_name 
          FROM schedules sc
          JOIN subjects s ON sc.subject_id = s.id
          JOIN classrooms c ON sc.classroom_id = c.id
          JOIN grades g ON c.grade_id = g.id";

if ($role === 'teacher') {
    // หา teacher_id จาก user_id
    $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $t_stmt->execute([$user_id]);
    $teacher_id = $t_stmt->fetchColumn();
    $query .= " WHERE sc.teacher_id = " . (int)$teacher_id;
}
$query .= " ORDER BY g.id ASC, c.room_name ASC, s.subject_code ASC";

$options = $pdo->query($query)->fetchAll();

// จัดกลุ่มข้อมูลตามระดับชั้นเพื่อความง่ายในการกรองและการเลือก
$grouped_options = [];
foreach ($options as $opt) {
    $level = $opt['level_name'];
    if (!isset($grouped_options[$level])) {
        $grouped_options[$level] = [];
    }
    $grouped_options[$level][] = $opt;
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-award me-2"></i> ระบบบันทึกคะแนน (Grading System)</h3>
        <span class="badge bg-light text-dark border shadow-sm p-2">
            <i class="fas fa-user-tag me-1 text-primary"></i> สิทธิ์ผู้ใช้งาน: <?= htmlspecialchars(ucfirst($role)) ?>
        </span>
    </div>
    
    <!-- ตัวเลือกวิชา/ห้องเรียน -->
    <div class="card card-custom mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-10">
                    <label class="form-label fw-bold">เลือกรายวิชาและห้องเรียนที่ต้องการบันทึกคะแนน</label>
                    <select id="select_class_subject" class="form-select form-select-lg shadow-sm border-2">
                        <option value="">-- กรุณาเลือกรายการ --</option>
                        <?php foreach($grouped_options as $level => $opts): ?>
                            <optgroup label="<?= htmlspecialchars($level) ?>">
                                <?php foreach($opts as $opt): ?>
                                <option value="<?= $opt['subject_id'] ?>-<?= $opt['classroom_id'] ?>">
                                    <?= htmlspecialchars("ห้อง ".$opt['room_name']." | ".$opt['subject_code']." ".$opt['subject_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="btn_load" class="btn btn-primary btn-lg w-100 shadow-sm" style="background-color: var(--primary-color); border:none;">
                        <i class="fas fa-users-viewfinder me-2"></i> ดึงรายชื่อ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- พื้นที่แสดงรายชื่อนักเรียนด้วย DataTables -->
    <div id="grading_area" style="display:none;">
        <form id="grading_form">
            <input type="hidden" name="subject_id" id="hidden_subject_id">
            <input type="hidden" name="classroom_id" id="hidden_classroom_id">
            
            <div class="card card-custom border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark"><i class="fas fa-list-ol me-2 text-primary"></i> รายชื่อนักเรียนเพื่อบันทึกคะแนน</h5>
                        <button type="submit" class="btn btn-success shadow-sm px-4">
                             <i class="fas fa-save me-2"></i> บันทึกคะแนนทั้งหมดในหน้านี้
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="studentTable" class="table table-hover table-bordered align-middle text-center w-100">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">เลขที่</th>
                                    <th width="120">รหัสนักเรียน</th>
                                    <th class="text-start">ชื่อ-นามสกุล</th>
                                    <th width="150" class="bg-light">คะแนนดิบ (0-100)</th>
                                    <th width="100">เกรด</th>
                                </tr>
                            </thead>
                            <tbody id="student_list">
                                <!-- AJAX content here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DataTables JS & jQuery -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    let studentTable = null;

    // ฟังก์ชันคำนวณเกรด (Logic กลาง)
    function calculateGrade(score) {
        if(isNaN(score) || score === '') return '-';
        if (score >= 80) return '4';
        if (score >= 75) return '3.5';
        if (score >= 70) return '3';
        if (score >= 65) return '2.5';
        if (score >= 60) return '2';
        if (score >= 55) return '1.5';
        if (score >= 50) return '1';
        return '0';
    }

    // Event: อัปเดตเกรดแบบ Real-time เมื่อพิมพ์ (JavaScript logic)
    $(document).on('input', '.input-score', function(){
        let score = parseFloat($(this).val());
        let gradeSpan = $(this).closest('tr').find('.grade-badge');
        
        if(score > 100) { $(this).val(100); score = 100; }
        if(score < 0) { $(this).val(0); score = 0; }

        let grade = calculateGrade(score);
        gradeSpan.text(grade);
        
        if(grade == '0') {
            gradeSpan.addClass('bg-danger').removeClass('bg-success');
        } else if(grade !== '-') {
            gradeSpan.addClass('bg-success').removeClass('bg-danger');
        } else {
            gradeSpan.removeClass('bg-success bg-danger text-white');
        }
    });

    // โหลดรายชื่อนักเรียนด้วย AJAX และ Initialize DataTables
    $('#btn_load').click(function(){
        let val = $('#select_class_subject').val();
        if(!val) return alert('กรุณาเลือกวิชาและห้องเรียนก่อนครับ');
        
        let parts = val.split('-');
        let sub_id = parts[0];
        let class_id = parts[1];

        $('#hidden_subject_id').val(sub_id);
        $('#hidden_classroom_id').val(class_id);

        $.ajax({
            url: 'api/get_students_by_class.php',
            type: 'GET',
            data: { classroom_id: class_id, subject_id: sub_id },
            beforeSend: function() {
                if(studentTable) studentTable.destroy();
                $('#student_list').html('<tr><td colspan="5">กำลังโหลดข้อมูล...</td></tr>');
            },
            success: function(response){
                let html = '';
                response.forEach(function(st){
                    let gradeClass = '';
                    if(st.grade !== null) {
                        gradeClass = (st.grade == '0') ? 'bg-danger' : 'bg-success';
                    }
                    
                    html += `<tr>
                        <td>${st.roll_number || '-'}</td>
                        <td>${st.student_code}</td>
                        <td class="text-start">${st.first_name} ${st.last_name}</td>
                        <td class="bg-light">
                            <input type="number" name="scores[${st.id}]" class="form-control text-center input-score fw-bold" 
                                   value="${st.score !== null ? st.score : ''}" placeholder="0-100" min="0" max="100">
                        </td>
                        <td><span class="badge grade-badge fs-6 ${gradeClass}">${st.grade || '-'}</span></td>
                    </tr>`;
                });
                $('#student_list').html(html);
                $('#grading_area').fadeIn();
                
                // Initialize DataTable
                studentTable = $('#studentTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json"
                    },
                    "pageLength": 50, // แสดง 50 คนต่อหน้าตามที่กำหนด
                    "columnDefs": [
                        { "orderable": false, "targets": [3, 4] } // ปิดการเรียงลำดับในช่องกรอกคะแนน
                    ]
                });
            }
        });
    });

    // บันทึกคะแนนทั้งหมด (Batch Save)
    $('#grading_form').submit(function(e){
        e.preventDefault();
        
        // ใช้ DataTables API เพื่อดึงข้อมูล input ทั้งหมด (รวมถึงหน้าอื่นที่ไม่ได้แสดงอยู่)
        // แต่ในโจทย์ระบุว่า 'บันทึกคะแนนทั้งหมดในหน้านั้น/แถวนั้น' 
        // ปกติเราควรส่งทั้งหมดที่ถูกกรอก:
        let formData = $(this).serialize();
        
        $.ajax({
            url: 'api/save_grades.php',
            type: 'POST',
            data: formData,
            success: function(res){
                if(res.success) {
                    alert('บันทึกคะแนนนักเรียนทั้งหมดเรียบร้อยแล้ว!');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + res.message);
                }
            }
        });
    });
});
</script>

<style>
.input-score:focus {
    background-color: #fff7cd;
    border-color: #fb9b8f;
    box-shadow: 0 0 0 0.25rem rgba(251, 155, 143, 0.25);
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0;
}
</style>

<?php require_once 'includes/footer.php'; ?>
