<?php
require_once 'config/condb.php';

if (isset($_GET['queue_id'])) {
    $queue_id = $_GET['queue_id'];
    $stmt = $conn->prepare("SELECT s.stu_ID, s.stu_name, s.stu_lastname 
                            FROM queue_student qs 
                            JOIN students s ON qs.student_id = s.stu_ID 
                            WHERE qs.queue_id = ?");
    $stmt->execute([$queue_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($students) > 0) {
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>{$student['stu_ID']}</td>";
            echo "<td>{$student['stu_name']} {$student['stu_lastname']}</td>";
            echo "<td>";
            echo "<button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editStudentModal' 
                          onclick='setEditStudent({$queue_id}, \"{$student['stu_ID']}\", \"{$student['stu_name']} {$student['stu_lastname']}\")'>แก้ไข</button>";
            echo "<form method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='queue_id' value='{$queue_id}'>";
            echo "<input type='hidden' name='student_id' value='{$student['stu_ID']}'>";
            echo "<button type='submit' name='delete_student' class='btn btn-danger btn-sm' onclick='return confirm(\"ยืนยันการลบนักเรียน?\")'>ลบ</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3'>ไม่มีนักเรียน</td></tr>";
    }
}
?>