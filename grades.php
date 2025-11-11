<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'];
$msg = '';

// Faculty: upload grades; Student: view grades; HOD: view all
if ($role == 'faculty' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $subject_id = intval($_POST['subject_id']);
    $student_id = intval($_POST['student_id']);
    $marks = intval($_POST['marks']);
    $gradeLetter = $_POST['grade'] ?? '';

    // upsert marks
    $stmt = $conn->prepare("SELECT grade_id FROM grades WHERE student_id=? AND subject_id=?");
    $stmt->bind_param("ii",$student_id,$subject_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $up = $conn->prepare("UPDATE grades SET marks=?, grade=?, created_at=CURRENT_TIMESTAMP WHERE student_id=? AND subject_id=?");
        $up->bind_param("isii",$marks,$gradeLetter,$student_id,$subject_id);
        $up->execute();
        $up->close();
    } else {
        $stmt->close();
        $ins = $conn->prepare("INSERT INTO grades (student_id,subject_id,marks,grade) VALUES (?,?,?,?)");
        $ins->bind_param("iiis",$student_id,$subject_id,$marks,$gradeLetter);
        $ins->execute();
        $ins->close();
    }
    $msg = "Grade saved.";
}

include 'header.php';
?>
<div class="card">
    <h2>Grades</h2>
    <?php if($msg): ?><div class="info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if ($role == 'faculty'): 
        $faculty_id = $_SESSION['user_id'];
        $substmt = $conn->prepare("SELECT subject_id,subject_name FROM subjects WHERE faculty_id = ?");
        $substmt->bind_param("i",$faculty_id);
        $substmt->execute();
        $subjects = $substmt->get_result();
    ?>
        <h3>Upload Grade</h3>
        <form method="post">
            <label>Subject</label>
            <select name="subject_id" required>
                <?php while($s = $subjects->fetch_assoc()): ?>
                    <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Student</label>
            <select name="student_id" required>
                <?php $students = $conn->query("SELECT user_id,name FROM users WHERE role='student' ORDER BY name"); while($st = $students->fetch_assoc()): ?>
                    <option value="<?= $st['user_id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Marks</label>
            <input type="number" name="marks" required>
            <label>Grade</label>
            <input type="text" name="grade">
            <button type="submit" name="upload">Save Grade</button>
        </form>

        <h3>Your Subject Grades</h3>
        <?php
        // show grades uploaded by this faculty (join subjects)
        $q = $conn->prepare("SELECT g.grade_id,g.marks,g.grade,u.name AS student,s.subject_name,g.created_at FROM grades g JOIN users u ON g.student_id=u.user_id JOIN subjects s ON g.subject_id=s.subject_id WHERE s.faculty_id = ? ORDER BY g.created_at DESC");
        $q->bind_param("i",$faculty_id);
        $q->execute();
        $grades = $q->get_result();
        ?>
        <table class="table">
            <tr><th>Student</th><th>Subject</th><th>Marks</th><th>Grade</th><th>Date</th></tr>
            <?php while($r = $grades->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['student']) ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['marks'] ?></td>
                    <td><?= htmlspecialchars($r['grade']) ?></td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

    <?php elseif ($role == 'student'):
        $sid = $_SESSION['user_id'];
        $res = $conn->prepare("SELECT s.subject_name,g.marks,g.grade,g.created_at FROM grades g JOIN subjects s ON g.subject_id=s.subject_id WHERE g.student_id = ? ORDER BY g.created_at DESC");
        $res->bind_param("i",$sid);
        $res->execute();
        $grades = $res->get_result();
    ?>
        <h3>Your Grades</h3>
        <table class="table">
            <tr><th>Subject</th><th>Marks</th><th>Grade</th><th>Date</th></tr>
            <?php while($r = $grades->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['marks'] ?></td>
                    <td><?= htmlspecialchars($r['grade']) ?></td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

    <?php else:
        // HOD - show all grades
        $all = $conn->query("SELECT g.grade_id,u.name AS student,s.subject_name,g.marks,g.grade,g.created_at FROM grades g JOIN users u ON g.student_id=u.user_id JOIN subjects s ON g.subject_id=s.subject_id ORDER BY g.created_at DESC");
    ?>
        <h3>All Grades</h3>
        <table class="table">
            <tr><th>Student</th><th>Subject</th><th>Marks</th><th>Grade</th><th>Date</th></tr>
            <?php while($r = $all->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['student']) ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['marks'] ?></td>
                    <td><?= htmlspecialchars($r['grade']) ?></td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>

