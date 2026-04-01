<?php
require_once 'includes/db.php';

echo "<h3>Fixing Teacher Codes and Departments...</h3>";

try {
    $stmt = $pdo->query("SELECT id, user_id FROM teachers");
    $teachers = $stmt->fetchAll();

    $depts = ['ภาษาไทย', 'คณิตศาสตร์', 'วิทยาศาสตร์', 'ภาษาอังกฤษ', 'สังคมศึกษา', 'สุขศึกษา', 'ศิลปะ', 'การงานอาชีพ', 'คอมพิวเตอร์', 'ประวัติศาสตร์'];

    foreach ($teachers as $index => $t) {
        $id = $t['id'];
        $uid = $t['user_id'];
        
        $teacher_code = "T" . str_pad($index + 1001, 4, '0', STR_PAD_LEFT);
        
        // Match user's username to teacher_code
        $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$teacher_code, $uid]);
        
        // Update teacher details
        $dept = $depts[$index % 10];
        $pdo->prepare("UPDATE teachers SET teacher_code = ?, department = ? WHERE id = ?")
            ->execute([$teacher_code, $dept, $id]);
            
        echo "Updated Teacher ID $id to $teacher_code ($dept)<br>";
    }

    echo "<h2 style='color:green;'>All Teacher Data Fixed!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Error: " . $e->getMessage() . "</h2>";
}
