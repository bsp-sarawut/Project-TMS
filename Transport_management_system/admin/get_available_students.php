<?php
require_once 'config/condb.php';

if (isset($_GET['queue_id'])) {
    $queue_id = $_GET['queue_id'];
    $stmt = $conn->prepare("
        SELECT s.stu_ID, s.stu_name, s.stu_lastname 
        FROM students s
        WHERE s.stu_ID NOT IN (
            SELECT student_id FROM queue_student WHERE queue_id = ?
        )
        ORDER BY s.stu_name
    ");
    $stmt->execute([$queue_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        echo "<option value='{$student['stu_ID']}'>{$student['stu_name']} {$student['stu_lastname']}</option>";
    }
}
?>