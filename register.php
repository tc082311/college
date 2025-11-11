<?php
require 'db.php';
session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $department = trim($_POST['department'] ?? '');

    if (!$name || !$email || !$password) $errors[] = "All fields required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if (strlen($password) < 6) $errors[] = "Password must be 6+ characters.";

    if (empty($errors)) {
        // check email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name,email,password,role,department) VALUES (?,?,?,?,?)");
            $ins->bind_param("sssss",$name,$email,$hash,$role,$department);
            if ($ins->execute()) {
                $_SESSION['user_id'] = $ins->insert_id;
                $_SESSION['role'] = $role;
                // redirect based on role
                if ($role == 'student') header("Location: dashboard_student.php");
                elseif ($role == 'faculty') header("Location: dashboard_faculty.php");
                else header("Location: dashboard_hod.php");
                exit;
            } else {
                $errors[] = "Registration failed.";
            }
        }
        $stmt->close();
    }
}
include 'header.php';
?>
<div class="card">
    <h2>Register</h2>
    <?php if($errors): foreach($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; endif; ?>

    <form method="post">
        <label>Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label>Password</label>
        <input type="password" name="password" required>
        <label>Role</label>
        <select name="role">
            <option value="student" <?= (($_POST['role'] ?? '')=='student')?'selected':'' ?>>Student</option>
            <option value="faculty" <?= (($_POST['role'] ?? '')=='faculty')?'selected':'' ?>>Faculty</option>
            <option value="hod" <?= (($_POST['role'] ?? '')=='hod')?'selected':'' ?>>HOD</option>
        </select>
        <label>Department</label>
        <input type="text" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>
<?php include 'footer.php'; ?>

