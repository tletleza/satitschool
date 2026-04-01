<?php
/**
 * ไฟล์ my_grades.php: หน้าแสดงผลการเรียนสำหรับนักเรียน
 * จุดเด่นคือการคำนวณเกรดเฉลี่ย (GPA) แบบถ่วงน้ำหนักหน่วยกิต 
 * และการจัดอันดับลำดับที่ (Rank) ในห้องเรียนแบบอัตโนมัติ
 */
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// เฉพาะ Student เท่านั้น (Admin/Teacher ดูผ่านหน้าอื่น)
authorize(['student']);

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลนักเรียนและห้องเรียนปัจจุบัน
$stmt = $pdo->prepare("
    SELECT s.id as student_id, s.student_code, s.first_name, s.last_name, c.room_name, g.level_name, sc.classroom_id
    FROM students s
    JOIN student_classrooms sc ON s.id = sc.student_id
    JOIN classrooms c ON sc.classroom_id = c.id
    JOIN grades g ON c.grade_id = g.id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$grades = [];
$gpa = '0.00';
$total_credits = 0;
$rank_in_class = '-';
$total_students_in_class = 0;

if ($student) {
    // 1. ดึงเกรดทุกวิชา
    $stmt = $pdo->prepare("
        SELECT sub.subject_code, sub.name as subject_name, sub.credit, ar.midterm_score, ar.final_score, ar.grade
        FROM academic_records ar
        JOIN subjects sub ON ar.subject_id = sub.id
        WHERE ar.student_id = ?
        ORDER BY sub.subject_code ASC
    ");
    $stmt->execute([$student['student_id']]);
    $grades = $stmt->fetchAll();

    // 2. คำนวณ GPA ของนักเรียนคนนี้
    $total_grade_points = 0;
    foreach ($grades as $g) {
        if ($g['grade'] !== null) {
            $total_credits += $g['credit'];
            $total_grade_points += ($g['grade'] * $g['credit']);
        }
    }
    if ($total_credits > 0) {
        $gpa = number_format($total_grade_points / $total_credits, 2);
    }

    // 3. คำนวณอันดับในห้อง (Ranking in Class)
    // ดึง GPA ของทุกคนในห้องมาเปรียบเทียบ
    $rank_stmt = $pdo->prepare("
        SELECT 
            s.id,
            SUM(ar.grade * sub.credit) / SUM(sub.credit) as student_gpa
        FROM students s
        JOIN student_classrooms sc ON s.id = sc.student_id
        JOIN academic_records ar ON s.id = ar.student_id
        JOIN subjects sub ON ar.subject_id = sub.id
        WHERE sc.classroom_id = ?
        GROUP BY s.id
        ORDER BY student_gpa DESC
    ");
    $rank_stmt->execute([$student['classroom_id']]);
    $class_ranking = $rank_stmt->fetchAll();
    
    $total_students_in_class = count($class_ranking);
    $current_rank = 1;
    foreach ($class_ranking as $r) {
        // ใช้การเปรียบเทียบ float แบบปัดเศษเพื่อความแม่นยำ
        if (round($r['student_gpa'], 4) > round((float)$gpa, 4)) {
            $current_rank++;
        }
    }
    $rank_in_class = $current_rank;
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #2980B9, #6DD5FA); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-star fa-2x opacity-75"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-white-50 small text-uppercase fw-bold">เกรดเฉลี่ย (GPA)</h6>
                        <h2 class="mb-0 fw-bold"><?= $gpa ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #11998E, #38EF7D); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-book-open fa-2x opacity-75"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-white-50 small text-uppercase fw-bold">หน่วยกิตสะสม</h6>
                        <h2 class="mb-0 fw-bold"><?= number_format($total_credits, 1) ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #F39C12, #F1C40F); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-trophy fa-2x opacity-75"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-white-50 small text-uppercase fw-bold">อันดับในห้อง</h6>
                        <h2 class="mb-0 fw-bold"><?= $rank_in_class ?> <small class="fs-6 opacity-75">/ <?= $total_students_in_class ?></small></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="120">รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th class="text-center" width="100">หน่วยกิต</th>
                            <th class="text-center" width="150">คะแนนเก็บ</th>
                            <th class="text-center" width="150">คะแนนปลายภาค</th>
                            <th class="text-center" width="100">เกรด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grades)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">ยังไม่มีข้อมูลผลการเรียน</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($grades as $g): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($g['subject_code']) ?></td>
                            <td><?= htmlspecialchars($g['subject_name']) ?></td>
                            <td class="text-center"><?= number_format($g['credit'], 1) ?></td>
                            <td class="text-center text-muted"><?= $g['midterm_score'] ?></td>
                            <td class="text-center text-muted"><?= $g['final_score'] ?></td>
                            <td class="text-center">
                                <span class="badge fs-6 <?= ($g['grade'] == '0' ? 'bg-danger' : 'bg-success') ?> px-3">
                                    <?= $g['grade'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
