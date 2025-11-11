<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hod') {
    header("Location: login.php"); exit;
}

// Basic actions: create subjects, list users and subjects, create user (HOD can create)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_subject'])) {
        $subject_name = trim($_POST['subject_name']);
        $department = trim($_POST['department']);
        if ($subject_name) {
            $ins = $conn->prepare("INSERT INTO subjects (subject_name,department,faculty_id) VALUES (?,?,NULL)");
            $ins->bind_param("ss",$subject_name,$department);
            $ins->execute();
            $msg = "Subject added.";
            $ins->close();
        } else $msg = "Subject name required.";
    }
    if (isset($_POST['create_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $department = trim($_POST['department']);
        if ($name && $email && $password) {
            $hash = password_hash($password,PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name,email,password,role,department) VALUES (?,?,?,?,?)");
            $ins->bind_param("sssss",$name,$email,$hash,$role,$department);
            if ($ins->execute()) $msg = "User created.";
            else $msg = "Failed to create user.";
            $ins->close();
        } else $msg = "Fill required fields.";
    }
}

// fetch users & subjects
$users = $conn->query("SELECT user_id,name,email,role,department,created_at FROM users ORDER BY user_id DESC");
$subjects = $conn->query("SELECT s.subject_id,s.subject_name,s.department,u.name AS faculty_name FROM subjects s LEFT JOIN users u ON s.faculty_id = u.user_id ORDER BY s.subject_id DESC");

include 'includes/header.php';
?>
<div class="card">
    <h2>Admin / HOD Management</h2>
    <?php if($msg): ?><div class="info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <h3>Create Subject</h3>
    <form method="post" class="inline">
        <input type="text" name="subject_name" placeholder="Subject name" required>
        <input type="text" name="department" placeholder="Department">
        <button type="submit" name="create_subject">Add Subject</button>
    </form>

    <h3>Create User</h3>
    <form method="post" class="inline">
        <input type="text" name="name" placeholder="Full name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role">
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="hod">HOD</option>
        </select>
        <input type="text" name="department" placeholder="Department">
        <button type="submit" name="create_user">Create User</button>
    </form>

    <h3>Subjects</h3>
    <table class="table">
        <tr><th>ID</th><th>Subject</th><th>Department</th><th>Faculty</th></tr>
        <?php while($s = $subjects->fetch_assoc()): ?>
            <tr>
                <td><?= $s['subject_id'] ?></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= htmlspecialchars($s['department']) ?></td>
                <td><?= htmlspecialchars($s['faculty_name'] ?? 'Unassigned') ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h3>Users</h3>
    <table class="table">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Created</th></tr>
        <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['department']) ?></td>
                <td><?= $u['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
