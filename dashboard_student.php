<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php"); exit;
}
$student_id = $_SESSION['user_id'];
include 'includes/header.php';
?>
<div class="card">
    <h2>Student Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Student') ?></p>
    <ul class="menu">
        <li><a href="attendance.php">View Attendance</a></li>
        <li><a href="grades.php">View Grades</a></li>
        <li><a href="timetable.php">View Timetable</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<?php include 'includes/footer.php'; ?>
