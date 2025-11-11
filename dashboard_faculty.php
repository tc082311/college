<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'faculty') {
    header("Location: login.php"); exit;
}
$faculty_id = $_SESSION['user_id'];
include 'header.php';
?>
<div class="card">
    <h2>Faculty Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Faculty') ?></p>
    <ul class="menu">
        <li><a href="attendance.php">Mark / View Attendance</a></li>
        <li><a href="grades.php">Upload / View Grades</a></li>
        <li><a href="timetable.php">View Timetable</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<?php include 'footer.php'; ?>

