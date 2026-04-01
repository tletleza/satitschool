<?php
/**
 * ไฟล์ login.php: หน้ากาก (UI) สำหรับการเข้าสู่ระบบ
 * ทำหน้าที่แสดงฟอร์ม และตรวจสอบเบื้องต้นว่าผู้ใช้ล็อกอินอยู่แล้วหรือไม่
 */
session_start();

// ถ้าผู้ใช้ล็อกอินอยู่แล้ว (มีค่า user_id ใน Session) ให้ดีดไปหน้า Dashboard (index.php) ทันที
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | โรงเรียนสาธิตวิทยา</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #A82323; /* Primary Color */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background-image: linear-gradient(135deg, #A82323 0%, #8B1A1A 100%);
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        .login-card h3 {
            color: #A82323;
            font-weight: 700;
        }
        .form-control:focus {
            border-color: #6D9E51;
            box-shadow: 0 0 0 0.25rem rgba(109, 158, 81, 0.25);
        }
        .btn-custom {
            background-color: #A82323;
            color: #FEFFD3;
            border: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background-color: #8B1A1A;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 26, 26, 0.3);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <img src="favicon.png" alt="โลโก้โรงเรียนสาธิต" class="mb-3 shadow-sm rounded-circle shadow" style="width: 80px; height: 80px; object-fit: contain; background: white; padding: 5px;">
        <h3 class="mb-2 fw-bold text-dark">สาธิตวิทยา</h3>
        <p class="text-muted">เข้าสู่ระบบเพื่อจัดการข้อมูลโรงเรียน</p>
    </div>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- ส่งข้อมูลแบบ POST ไปยัง process_login.php -->
    <form action="process_login.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label fw-bold">ชื่อผู้ใช้งาน (Username)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="กรอกชื่อผู้ใช้ของคุณ" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label fw-bold">รหัสผ่าน (Password)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่านของคุณ" required>
            </div>
        </div>
        <button type="submit" class="btn btn-custom w-100 py-2"><i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ</button>
    </form>
</div>

</body>
</html>
