<?php
    session_start();
    require_once 'config/condb.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            width: 100%;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        .alert {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>ลงทะเบียน</h1>
    <h3 class="mb-4">กรุณากรอกข้อมูล</h3>
        <hr>
    <?php if (isset($_SESSION["error"])) {?>
        <div class="alert alert-danger" role="alert"><?php echo $_SESSION["error"];unset($_SESSION["error"]);?></div>
    <?php }?>

    <?php if (isset($_SESSION["warning"])) {?>
        <div class="alert alert-warning" role="alert"><?php echo $_SESSION["warning"];unset($_SESSION["warning"]);?></div>
    <?php }?>

    <?php if (isset($_SESSION["success"])) {?>
        <div class="alert alert-success" role="alert"><?php echo $_SESSION["success"];unset($_SESSION["success"]);?></div>
    <?php }?>

    <form action="stu_signup_db.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="stu_username" class="form-label">Username</label>
            <input type="text" class="form-control" name="stu_username" required>
        </div>

        <div class="mb-3">
            <label for="stu_password" class="form-label">Password</label>
            <input type="password" class="form-control" name="stu_password" required>
        </div>

        <div class="mb-3">
            <label for="stu_year" class="form-label">ปีการศึกษา</label>
            <select class="form-select" name="stu_year" required>
                <option value="" selected>เลือกปีการศึกษา</option>
                <?php
                    $current_year = date("Y") + 543;
                    for ($i = $current_year - 4; $i <= $current_year; $i++) {
                        echo "<option value='$i'>$i</option>";
                    }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_license" class="form-label">รหัสนักศึกษา</label>
            <input type="text" class="form-control" name="stu_license" required>
        </div>

        <div class="mb-3">
            <label for="stu_name" class="form-label">ชื่อ</label>
            <input type="text" class="form-control" name="stu_name" required>
        </div>

        <div class="mb-3">
            <label for="stu_lastname" class="form-label">นามสกุล</label>
            <input type="text" class="form-control" name="stu_lastname" required>
        </div>

        <div class="mb-3">
            <label for="stu_tel" class="form-label">เบอร์โทร</label>
            <input type="tel" class="form-control" name="stu_tel" required>
        </div>


        <div class="mb-3">
            <label for="stu_faculty" class="form-label">คณะ</label>
            <select class="form-select" name="stu_faculty" id="stu_faculty" onchange="updateMajors()" required>
                <option value="" selected>เลือกคณะ</option>
                <option value="วิศวกรรมศาสตร์">วิศวกรรมศาสตร์</option>
                <option value="เทคโนโลยี">เทคโนโลยี</option>
                <option value="บริหารธุรกิจ">บริหารธุรกิจ</option>
                <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                <option value="บัญชี">บัญชี</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_major" class="form-label">สาขา</label>
            <select class="form-select" name="stu_major" id="stu_major" required>
                <option value="" selected>เลือกสาขา</option>
            </select>
        </div>
        <div class="form-group">
            <label for="stu_img">รูปภาพประจำตัว</label>
            <input type="file" class="form-control" id="stu_img" name="stu_img" accept="image/*">
        </div>
    <button type="submit" name="signup" class="btn btn-primary mt-3">สมัครสมาชิก</button>
</form>
    <p class="footer mt-3">เป็นสมาชิกแล้ว? <a href="index.php">เข้าสู่ระบบ</a></p>
</div>

<script>
    const majorsByFaculty = {
        "วิศวกรรมศาสตร์": ["สาขาวิชาวิศวกรรมเครื่องกล", "สาขาวิชาวิศวกรรมไฟฟ้า", "สาขาวิชาวิศวกรรมอุตสาหการ"],
        "เทคโนโลยี": ["สาขาวิชาเทคโนโลยีการจัดการอุตสาหกรรม"],
        "บริหารธุรกิจ": ["สาขาวิชาการบัญชี", "สาขาวิชาคอมพิวเตอร์ธุรกิจ", "สาขาวิชาการจัดการทั่วไป"],
        "วิทยาศาสตร์": ["สาขาวิชาเทคโนโลยีสารสนเทศ", "สาขาวิชาเทคโนโลยีคอมพิวเตอร์"],
        "บัญชี": ["สาขาวิชาการบัญชี"]
    };

    function updateMajors() {
        const facultySelect = document.getElementById("stu_faculty");
        const majorSelect = document.getElementById("stu_major");
        const selectedFaculty = facultySelect.value;

        majorSelect.innerHTML = '<option value="" selected>เลือกสาขา</option>';

        if (majorsByFaculty[selectedFaculty]) {
            majorsByFaculty[selectedFaculty].forEach(major => {
                const option = document.createElement("option");
                option.value = major;
                option.textContent = major;
                majorSelect.appendChild(option);
            });
        }
    }
</script>
</body>
</html>
