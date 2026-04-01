<?php
require_once 'includes/db.php';

set_time_limit(600);
header('Content-Type: text/html; charset=utf-8');

echo "<h3>เริ่มการสร้างข้อมูล (10 Teachers, 60 Students, Weekly Schedule)...</h3>";

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

    // 1. Roles & Academic Year
    $pdo->exec("INSERT IGNORE INTO roles (id, name) VALUES (1, 'admin'), (2, 'teacher'), (3, 'student')");
    $pdo->exec("INSERT INTO academic_years (year, term, is_current) VALUES (2567, 1, 1)");
    $ay_id = $pdo->lastInsertId();

    $admin_id = $pdo->query("SELECT id FROM users WHERE username='admin' LIMIT 1")->fetchColumn() ?: 1;
    $pwd = password_hash('123456', PASSWORD_DEFAULT);

    $fn_m = ['นราวิชญ์', 'ภานุพงศ์', 'ธนากร', 'ธีรเทพ', 'วริศ', 'กฤษฎา', 'ปิยบุตร', 'สิริศักดิ์', 'พงศธร', 'อธิวัฒน์'];
    $fn_f = ['พรพิมล', 'วริศรา', 'สโรชา', 'อัญชลี', 'สิริยากร', 'เบญจวรรณ', 'นิศากร', 'ภาวิณี', 'รัตนาพร', 'วิลาสินี'];
    $ln = ['รุ่งเรือง', 'เจริญยิ่ง', 'ศิริวัฒนา', 'พูนผล', 'แสงสว่าง', 'วงศ์ประเสริฐ', 'อิ่มสบาย', 'มิตรสัมพันธ์'];

    // 2. Teachers (Exactly 10)
    $t_ids = [];
    $depts = ['ภาษาไทย', 'คณิตศาสตร์', 'วิทยาศาสตร์', 'ภาษาอังกฤษ', 'สังคมศึกษา', 'สุขศึกษา', 'ศิลปะ', 'การงานอาชีพ', 'คอมพิวเตอร์', 'ประวัติศาสตร์'];
    for ($i=1; $i<=10; $i++) {
        $teacher_code = "T" . str_pad($i + 1000, 4, '0', STR_PAD_LEFT); // T1001, T1002...
        $u = $teacher_code; // ใช้ teacher_code เป็น username
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$u, $pwd, 2]);
        $uid = $pdo->lastInsertId();
        
        $fn = ($i % 2 == 0) ? $fn_m[($i-1)%10] : $fn_f[($i-1)%10];
        $dept = $depts[$i-1];
        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, teacher_code, first_name, last_name, phone, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $teacher_code, $fn, $ln[array_rand($ln)], "08".rand(10000000,99999999), $dept]);
        $t_ids[] = $pdo->lastInsertId();
    }

    // 3. Subjects (10 Unique Subjects)
    $subs_data = [
        ['TH101', 'ภาษาไทย', 1.5, 'ศึกษาหลักภาษา การอ่าน การเขียน และวรรณคดีไทย'],
        ['MA101', 'คณิตศาสตร์', 1.5, 'เน้นการคำนวณ ตรรกศาสตร์ และเรขาคณิตพื้นฐาน'],
        ['SC101', 'วิทยาศาสตร์', 1.5, 'เรียนรู้กระบวนการทางวิทยาศาสตร์ ฟิสิกส์ เคมี และชีววิทยาพื้นฐาน'],
        ['EN101', 'ภาษาอังกฤษ', 1.0, 'พัฒนาทักษะการฟัง พูด อ่าน เขียน ภาษาอังกฤษเบื้องต้น'],
        ['SO101', 'สังคมศึกษา', 1.0, 'ศึกษาศาสนา วัฒนธรรม หน้าที่พลเมือง และวิถีชีวิตในสังคม'],
        ['HE101', 'สุขศึกษา', 0.5, 'เรียนรู้เรื่องสุขภาพ โภชนาการ และการดูแลตนเอง'],
        ['AR101', 'ศิลปะ', 0.5, 'พื้นฐานงานทัศนศิลป์ การวาดเขียน และความคิดสร้างสรรค์'],
        ['OC101', 'การงานอาชีพ', 0.5, 'ทักษะพื้นฐานในการทำงานอาชีพ และงานบ้าน'],
        ['CO101', 'คอมพิวเตอร์', 1.0, 'ฝึกการใช้งานโปรแกรมพื้นฐาน และการเขียนโปรแกรมเบื้องต้น'],
        ['HI101', 'ประวัติศาสตร์', 0.5, 'ศึกษาประวัติความเป็นมาของชาติไทยและเหตุการณ์สำคัญ']
    ];
    $sub_ids = [];
    foreach($subs_data as $s){
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, name, credit, description) VALUES (?, ?, ?, ?)");
        $stmt->execute($s);
        $sub_ids[] = $pdo->lastInsertId();
    }

    // 4. Grades & Classrooms (6 Classrooms: M.1/1 - M.6/1)
    $c_ids = [];
    for ($g = 1; $g <= 6; $g++) {
        $stmt = $pdo->prepare("INSERT INTO grades (level_name) VALUES (?)");
        $stmt->execute(["มัธยมศึกษาปีที่ $g"]);
        $gid = $pdo->lastInsertId();

        $building = "6";
        $floor = ceil($g / 2); // 1, 1, 2, 2, 3, 3
        $room_no = ($g % 2 == 0) ? "2" : "1"; // Alternating 1 and 2

        $stmt = $pdo->prepare("INSERT INTO classrooms (grade_id, room_name, building, floor, room_number, advisor_id, academic_year_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$gid, "$g/1", $building, $floor, $room_no, $t_ids[$g - 1], $ay_id]); // Each room has a different advisor
        $c_ids[] = $pdo->lastInsertId();
    }

    // 5. Students (Exactly 60: 10 per Classroom)
    $s_ids = [];
    $student_idx = 1;
    foreach($c_ids as $room_id) {
        for($i=1; $i<=10; $i++) {
            $u = "student" . str_pad($student_idx, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
            $stmt->execute([$u, $pwd, 3]);
            $uid = $pdo->lastInsertId();
            
            $fn = ($student_idx % 2 == 0) ? $fn_m[array_rand($fn_m)] : $fn_f[array_rand($fn_f)];
            $code = "ST".str_pad($student_idx, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO students (user_id, student_code, first_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uid, $code, $fn, $ln[array_rand($ln)]]);
            $sid = $pdo->lastInsertId();
            $s_ids[] = ['sid' => $sid, 'rid' => $room_id];
            
            $stmt = $pdo->prepare("INSERT INTO student_classrooms (student_id, classroom_id, roll_number) VALUES (?, ?, ?)");
            $stmt->execute([$sid, $room_id, $i]);
            $student_idx++;
        }
    }

    // 6. Weekly Schedule (Mon-Fri) - Conflict-Free Rotation
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $periods = [
        ['08:30:00', '10:30:00'],
        ['10:30:00', '12:30:00'],
        ['13:30:00', '15:30:00']
    ];
    $sch_ids = [];

    foreach ($days as $day_idx => $day_name) {
        foreach ($periods as $period_idx => $times) {
            foreach ($c_ids as $room_idx => $room_id) {
                // Algorithm to rotate teachers: (day + period + room) % 10
                $teacher_idx = ($day_idx + $period_idx + $room_idx) % 10;
                $t_id = $t_ids[$teacher_idx];
                $sub_id = $sub_ids[$teacher_idx]; // Assign subject 1:1 with teacher for simplicity

                $stmt = $pdo->prepare("INSERT INTO schedules (classroom_id, subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$room_id, $sub_id, $t_id, $ay_id, $day_name, $times[0], $times[1]]);
                $sch_ids[] = $pdo->lastInsertId();
            }
        }
    }

    // 7. Academic Records & Mock Attendance
    // Filter out only students in their relevant records
    foreach ($s_ids as $s_info) {
        $sid = $s_info['sid'];
        $rid = $s_info['rid'];
        
        // Get subjects for this student's room from schedules
        $stmt = $pdo->prepare("SELECT DISTINCT subject_id FROM schedules WHERE classroom_id = ?");
        $stmt->execute([$rid]);
        $st_subs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($st_subs as $su_id) {
            $score = rand(45, 95);
            $grade = '0';
            if ($score >= 80) $grade = '4';
            elseif ($score >= 75) $grade = '3.5';
            elseif ($score >= 70) $grade = '3';
            elseif ($score >= 65) $grade = '2.5';
            elseif ($score >= 60) $grade = '2';
            elseif ($score >= 55) $grade = '1.5';
            elseif ($score >= 50) $grade = '1';

            $stmt = $pdo->prepare("INSERT INTO academic_records (student_id, subject_id, academic_year_id, midterm_score, final_score, grade, recorded_by) 
                                 VALUES (?, ?, ?, 0, ?, ?, ?)");
            $stmt->execute([$sid, $su_id, $ay_id, $score, $grade, $admin_id]);
        }
        
        // Random Attendance for today (if weekday)
        $today_l = date('l');
        $stmt = $pdo->prepare("SELECT id FROM schedules WHERE classroom_id = ? AND day_of_week = ?");
        $stmt->execute([$rid, $today_l]);
        $today_schs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($today_schs as $sc_id) {
            $r = rand(1, 10);
            $stat = ($r <= 8) ? 'present' : (($r == 9) ? 'late' : 'absent');
            $stmt = $pdo->prepare("INSERT INTO attendances (student_id, schedule_id, date, status, recorded_by) VALUES (?, ?, CURDATE(), ?, ?)");
            $stmt->execute([$sid, $sc_id, $stat, $admin_id]);
        }
    }

    $pdo->commit();
    echo "<h2 style='color:green;'>Data Refreshed Successfully!</h2>";
    echo "<ul>
            <li>Teachers: 10</li>
            <li>Students: 60</li>
            <li>Rooms: 6 (M.1/1 - M.6/1)</li>
            <li>Weekly Schedule: Mon-Fri Created (90 periods total)</li>
          </ul>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "<h2 style='color:red;'>Error Seeding Data: " . $e->getMessage() . "</h2>";
}
