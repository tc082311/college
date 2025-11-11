<?php
require 'db.php';
session_start();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT user_id,name,password,role FROM users WHERE email = ?");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['name'] = $row['name'];
                if ($row['role']=='student') header("Location: dashboard_student.php");
                elseif ($row['role']=='faculty') header("Location: dashboard_faculty.php");
                else header("Location: dashboard_hod.php");
                exit;
            } else $err = "Invalid credentials.";
        } else $err = "User not found.";
        $stmt->close();
    } else $err = "Please enter email and password.";
}

include 'includes/header.php';
?>
<div class="card">
    <h2>Login</h2>
    <?php if($err): ?><div class="error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Register</a></p>
</div>
<?php include 'includes/footer.php'; ?>
