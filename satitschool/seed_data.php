<?php
require_once 'includes/db.php';

set_time_limit(600);
header('Content-Type: text/html; charset=utf-8');

$today_l = date('l'); 

echo "<h3>เริ่มการสร้างข้อมูล (Data Seeder - Fix Version)...</h3>";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE attendances;");
    $pdo->exec("TRUNCATE TABLE academic_records;");
    $pdo->exec("TRUNCATE TABLE schedules;");
    $pdo->exec("TRUNCATE TABLE subjects;");
    $pdo->exec("TRUNCATE TABLE student_classrooms;");
    $pdo->exec("TRUNCATE TABLE classrooms;");
    $pdo->exec("TRUNCATE TABLE students;");
    $pdo->exec("TRUNCATE TABLE teachers;");
    $pdo->exec("TRUNCATE TABLE academic_years;");
    $pdo->exec("TRUNCATE TABLE grades;");
    $pdo->exec("DELETE FROM users WHERE username != 'admin';");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->beginTransaction();

    $pdo->exec("INSERT IGNORE INTO roles (id, name) VALUES (1, 'admin'), (2, 'teacher'), (3, 'student')");
    $pdo->exec("INSERT INTO academic_years (year, term, is_current) VALUES (2567, 1, 1)");
    $ay_id = $pdo->lastInsertId();

    $pwd = password_hash('123456', PASSWORD_DEFAULT);

    $fn_m = ['นราวิชญ์', 'ภานุพงศ์', 'ธนากร', 'ธีรเทพ', 'วริศ', 'กฤษฎา', 'ปิยบุตร', 'สิริศักดิ์', 'พงศธร', 'อธิวัฒน์'];
    $fn_f = ['พรพิมล', 'วริศรา', 'สโรชา', 'อัญชลี', 'สิริยากร', 'เบญจวรรณ', 'นิศากร', 'ภาวิณี', 'รัตนาพร', 'วิลาสินี'];
    $ln = ['รุ่งเรือง', 'เจริญยิ่ง', 'ศิริวัฒนา', 'พูนผล', 'แสงสว่าง', 'วงศ์ประเสริฐ', 'อิ่มสบาย', 'มิตรสัมพันธ์'];

    // Teachers
    $t_ids = [];
    for ($i=1; $i<=20; $i++) {
        $u = "teacher" . str_pad($i, 2, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$u, $pwd, 2]);
        $uid = $pdo->lastInsertId();
        $is_m = rand(0,1);
        $fn = $is_m ? $fn_m[array_rand($fn_m)] : $fn_f[array_rand($fn_f)];
        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, first_name, last_name, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $fn, $ln[array_rand($ln)], "08".rand(10000000,99999999)]);
        $t_ids[] = $pdo->lastInsertId();
    }

    // Grades & Classrooms
    $c_ids = [];
    for ($g=1; $g<=6; $g++) {
        $stmt = $pdo->prepare("INSERT INTO grades (level_name) VALUES (?)");
        $stmt->execute(["มัธยมศึกษาปีที่ $g"]);
        $gid = $pdo->lastInsertId();
        for ($r=1; $r<=2; $r++) {
            $stmt = $pdo->prepare("INSERT INTO classrooms (grade_id, room_name, advisor_id, academic_year_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$gid, "$g/$r", $t_ids[array_rand($t_ids)], $ay_id]);
            $c_ids[$g][] = $pdo->lastInsertId();
        }
    }

    // Students
    $s_ids = [];
    $idx = 1;
    $dist = [6=>30, 5=>50, 4=>30, 3=>40, 2=>40, 1=>40];
    foreach($dist as $lv => $count){
        foreach($c_ids[$lv] as $rid) {
            // แบ่งนักเรียนลงห้องให้เท่าๆ กัน
            $students_per_room = $count / 2;
            for($i=1; $i<=$students_per_room; $i++) {
                $u = "student" . str_pad($idx, 3, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
                $stmt->execute([$u, $pwd, 3]);
                $uid = $pdo->lastInsertId();
                $is_m = rand(0,1);
                $fn = $is_m ? $fn_m[array_rand($fn_m)] : $fn_f[array_rand($fn_f)];
                $code = "ST".str_pad($idx, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO students (user_id, student_code, first_name, last_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$uid, $code, $fn, $ln[array_rand($ln)]]);
                $sid = $pdo->lastInsertId();
                $s_ids[] = $sid;
                $stmt = $pdo->prepare("INSERT INTO student_classrooms (student_id, classroom_id, roll_number) VALUES (?, ?, ?)");
                $stmt->execute([$sid, $rid, $i]);
                $idx++;
            }
        }
    }

    // Subjects
    $subs = [['ค101','คณิตศาสตร์',1.5],['ว101','วิทยาศาสตร์',1.5],['อ101','อังกฤษ',1.0]];
    $sub_ids = [];
    foreach($subs as $s){
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, name, credit) VALUES (?, ?, ?)");
        $stmt->execute($s);
        $sub_ids[] = $pdo->lastInsertId();
    }

    // Schedules (Force TODAY)
    $sch_ids = [];
    foreach($c_ids as $lv => $rooms){
        foreach($rooms as $rid){
            foreach($sub_ids as $sub_i){
                $stmt = $pdo->prepare("INSERT INTO schedules (classroom_id, subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time) 
                                     VALUES (?, ?, ?, ?, ?, '09:00:00', '11:00:00')");
                $stmt->execute([$rid, $sub_i, $t_ids[array_rand($t_ids)], $ay_id, $today_l]);
                $sch_ids[] = $pdo->lastInsertId();
            }
        }
    }

    // สุ่มคะแนน/เกรด และ เวลาเรียน
    foreach ($s_ids as $sid) {
        // ให้คะแนนนักเรียนตามแต่ละวิชา
        foreach ($sub_ids as $su_id) {
            $score = rand(40, 99);
            if ($score >= 80) $grade = '4';
            elseif ($score >= 70) $grade = '3';
            elseif ($score >= 60) $grade = '2';
            elseif ($score >= 50) $grade = '1';
            else $grade = '0';
            $stmt = $pdo->prepare("INSERT INTO academic_records (student_id, subject_id, academic_year_id, total_score, grade, recorded_by) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$sid, $su_id, $ay_id, $score, $grade]);
        }
        
        // ให้เวลาเรียนแบบสุ่ม
        $sampled_schs = array_rand(array_flip($sch_ids), 2);
        foreach ((array)$sampled_schs as $sc_id) {
            $r = rand(1, 10);
            $stat = ($r <= 8) ? 'present' : (($r == 9) ? 'late' : 'absent');
            $stmt = $pdo->prepare("INSERT INTO attendances (student_id, schedule_id, date, status, recorded_by) VALUES (?, ?, CURDATE(), ?, 1)");
            $stmt->execute([$sid, $sc_id, $stat]);
        }
    }

    $pdo->commit();
    echo "<h2 style='color:green;'>ข้อมูลพร้อมแล้ว! (ทุกห้องมีตารางสอนวันนี้: $today_l) และย้าย jQuery เรียบร้อย</h2>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "<h2 style='color:red;'>Error: " . $e->getMessage() . "</h2>";
}
?>
