<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hod') {
    header("Location: login.php"); exit;
}
include 'header.php';
?>
<div class="card">
    <h2>HOD / Admin Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'HOD') ?></p>
    <ul class="menu">
        <li><a href="admin_manage.php">Manage Users & Subjects</a></li>
        <li><a href="timetable.php">Manage / View Timetable</a></li>
        <li><a href="attendance.php">View Attendance Reports</a></li>
        <li><a href="grades.php">View Grades / Reports</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<?php include 'footer.php'; ?>

