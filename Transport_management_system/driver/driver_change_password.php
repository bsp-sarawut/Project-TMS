<?php
session_start();
require_once("config/condb.php"); // ปรับพาธให้ถูกต้อง

// ตรวจสอบว่าคนขับล็อกอินหรือยัง
if (!isset($_SESSION['driver_user'])) {
    header("Location: driver_signin.php");
    exit();
}

// ตรวจสอบว่าเป็นการเข้าสู่ระบบครั้งแรก
if ($_SESSION['first_login'] != 1) {
    header("Location: driver.php");
    exit();
}

// ดึงข้อมูลคนขับจากฐานข้อมูล
$driver_user = $_SESSION['driver_user'];
$stmt = $conn->prepare("SELECT * FROM driver WHERE driver_user = :driver_user");
$stmt->bindParam(':driver_user', $driver_user, PDO::PARAM_STR);
$stmt->execute();
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

// ถ้าไม่พบข้อมูลคนขับ
if (!$driver) {
    $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้";
    header("Location: driver_signin.php");
    exit();
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ตรวจสอบรหัสผ่านปัจจุบัน
    if (!password_verify($current_password, $driver['driver_password'])) {
        $_SESSION['error'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
    } 
    // ตรวจสอบรหัสผ่านใหม่และยืนยันรหัสผ่าน
    elseif ($new_password != $confirm_password) {
        $_SESSION['error'] = "รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน";
    } 
    // ตรวจสอบความยาวรหัสผ่านขั้นต่ำ
    elseif (strlen($new_password) < 8) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
    } 
    // ตรวจสอบรหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว
    elseif (!preg_match('/[0-9]/', $new_password)) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีตัวเลขอย่างน้อย 1 ตัว";
    }
    // ตรวจสอบรหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว
    elseif (!preg_match('/[A-Z]/', $new_password)) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว";
    }
    // ตรวจสอบรหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว
    elseif (!preg_match('/[a-z]/', $new_password)) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว";
    }
    // ตรวจสอบรหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว
    elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว (เช่น !@#$%^&*)";
    }
    // รหัสผ่านใหม่ต้องไม่เหมือนรหัสผ่านเดิม
    elseif ($current_password === $new_password) {
        $_SESSION['error'] = "รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม";
    } else {
        try {
            // เข้ารหัสรหัสผ่านใหม่
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // อัปเดตรหัสผ่านและสถานะ first_login
            $update_stmt = $conn->prepare("UPDATE driver SET driver_password = :driver_password, first_login = 0 WHERE driver_user = :driver_user");
            $update_stmt->bindParam(':driver_password', $hashed_password, PDO::PARAM_STR);
            $update_stmt->bindParam(':driver_user', $driver_user, PDO::PARAM_STR);
            
            if ($update_stmt->execute()) {
                // อัปเดตสถานะใน session
                $_SESSION['first_login'] = 0;
                $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จ!";
                header("Location: driver.php");
                exit();
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน กรุณาลองอีกครั้ง";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่านครั้งแรก</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="icon" type="image/x-icon" href="../Logo/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'IBM Plex Sans Thai', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .password-container {
            max-width: 500px;
            width: 100%;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #003087;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            color: #4a4a4a;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 12px;
        }
        .btn-primary {
            background-color: #003087;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0044cc;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .first-login-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .password-requirements {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #003087;
        }
        .password-requirements h6 {
            font-weight: 600;
            color: #003087;
            margin-bottom: 10px;
        }
        .password-requirements ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        .password-requirements li {
            margin-bottom: 5px;
        }
        .input-group-text {
            background-color: #f0f0f0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group .form-control {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        /* สีแสดงความเข้มแข็งของรหัสผ่าน */
        .password-strength {
            height: 5px;
            margin-top: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }
        .strength-medium {
            background-color: #ffc107;
            width: 50%;
        }
        .strength-strong {
            background-color: #28a745;
            width: 75%;
        }
        .strength-very-strong {
            background-color: #198754;
            width: 100%;
        }
        .password-strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่านครั้งแรก</h5>
            </div>
            <div class="card-body">
                <div class="first-login-alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>โปรดทราบ:</strong> คุณจำเป็นต้องเปลี่ยนรหัสผ่านในครั้งแรกที่เข้าสู่ระบบเพื่อความปลอดภัยของบัญชีของคุณ
                </div>
                
                <?php if (isset($_SESSION['error'])) { ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php } ?>
                
                <?php if (isset($_SESSION['success'])) { ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php } ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                        <div class="password-strength-text" id="password-strength-text"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                        <div id="password-match" class="mt-1 text-danger" style="font-size: 0.85rem;"></div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>บันทึกรหัสผ่านใหม่
                    </button>
                </form>
                
                <div class="password-requirements">
                    <h6><i class="fas fa-shield-alt me-2"></i>ข้อกำหนดรหัสผ่าน</h6>
                    <ul>
                        <li>รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</li>
                        <li>ต้องมีตัวอักษรตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว</li>
                        <li>ต้องมีตัวอักษรตัวพิมพ์เล็กอย่างน้อย 1 ตัว</li>
                        <li>ต้องมีตัวเลขอย่างน้อย 1 ตัว</li>
                        <li>ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว (เช่น ! @ # $ % ^ & *)</li>
                        <li>รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS, Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        // แสดง/ซ่อนรหัสผ่าน
        function addPasswordToggle(inputId) {
            const inputField = document.getElementById(inputId);
            const toggleContainer = document.createElement('div');
            toggleContainer.classList.add('input-group-append');
            
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.classList.add('btn', 'btn-outline-secondary');
            toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            toggleButton.style.borderTopLeftRadius = '0';
            toggleButton.style.borderBottomLeftRadius = '0';
            
            toggleButton.addEventListener('click', function() {
                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    inputField.type = 'password';
                    toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
            
            inputField.parentNode.appendChild(toggleButton);
        }
        
        // ตรวจสอบความแข็งแรงของรหัสผ่าน
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-strength-text');
            
            // ถ้ารหัสผ่านว่างเปล่า
            if (password.length === 0) {
                strengthBar.className = 'password-strength';
                strengthText.innerHTML = '';
                return;
            }
            
            // ตรวจสอบความยาวของรหัสผ่าน
            if (password.length >= 8) {
                strength += 1;
            }
            
            // ตรวจสอบว่ามีตัวเลขหรือไม่
            if (password.match(/[0-9]+/)) {
                strength += 1;
            }
            
            // ตรวจสอบว่ามีตัวอักษรตัวเล็กหรือไม่
            if (password.match(/[a-z]+/)) {
                strength += 1;
            }
            
            // ตรวจสอบว่ามีตัวอักษรตัวใหญ่หรือไม่
            if (password.match(/[A-Z]+/)) {
                strength += 1;
            }
            
            // ตรวจสอบว่ามีอักขระพิเศษหรือไม่
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) {
                strength += 1;
            }
            
            // แสดงผลความแข็งแรงของรหัสผ่าน
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.className = 'password-strength strength-weak';
                    strengthText.innerHTML = '<span class="text-danger">รหัสผ่านอ่อนแอเกินไป</span>';
                    break;
                case 2:
                    strengthBar.className = 'password-strength strength-medium';
                    strengthText.innerHTML = '<span class="text-warning">รหัสผ่านปานกลาง</span>';
                    break;
                case 3:
                    strengthBar.className = 'password-strength strength-strong';
                    strengthText.innerHTML = '<span class="text-success">รหัสผ่านแข็งแรง</span>';
                    break;
                case 4:
                case 5:
                    strengthBar.className = 'password-strength strength-very-strong';
                    strengthText.innerHTML = '<span class="text-success">รหัสผ่านแข็งแรงมาก!</span>';
                    break;
            }
        }
        
        // ตรวจสอบว่ารหัสผ่านใหม่และยืนยันรหัสผ่านตรงกันหรือไม่
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (confirmPassword === '') {
                matchText.innerHTML = '';
            } else if (newPassword === confirmPassword) {
                matchText.innerHTML = '<i class="fas fa-check me-1"></i> รหัสผ่านตรงกัน';
                matchText.className = 'mt-1 text-success';
            } else {
                matchText.innerHTML = '<i class="fas fa-times me-1"></i> รหัสผ่านไม่ตรงกัน';
                matchText.className = 'mt-1 text-danger';
            }
        }
        
        // เพิ่มปุ่มแสดง/ซ่อนรหัสผ่านสำหรับทุกช่องรหัสผ่าน
        window.addEventListener('DOMContentLoaded', function() {
            addPasswordToggle('current_password');
            addPasswordToggle('new_password');
            addPasswordToggle('confirm_password');
            
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // ตรวจสอบความแข็งแรงของรหัสผ่านเมื่อมีการพิมพ์
            newPasswordInput.addEventListener('keyup', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });
            
            // ตรวจสอบการตรงกันของรหัสผ่านเมื่อมีการพิมพ์
            confirmPasswordInput.addEventListener('keyup', checkPasswordMatch);
            
            // ตรวจสอบรหัสผ่านที่ยืนยันว่าตรงกันหรือไม่
            function validatePasswords() {
                if (confirmPasswordInput.value && confirmPasswordInput.value !== newPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('รหัสผ่านไม่ตรงกัน');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
            
            newPasswordInput.addEventListener('change', validatePasswords);
            confirmPasswordInput.addEventListener('keyup', validatePasswords);
            
            // ตรวจสอบความถูกต้องของรหัสผ่านก่อนส่งฟอร์ม
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = newPasswordInput.value;
                const hasUpperCase = /[A-Z]/.test(password);
                const hasLowerCase = /[a-z]/.test(password);
                const hasNumbers = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
                } else if (!hasUpperCase) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว');
                } else if (!hasLowerCase) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว');
                } else if (!hasNumbers) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว');
                } else if (!hasSpecial) {
                    e.preventDefault();
                    alert('รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว');
                }
            });
        });
    </script>
</body>
</html>