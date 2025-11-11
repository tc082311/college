<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'];
$msg = '';

// Faculty: mark attendance; Student: view own; HOD: view reports
if ($role == 'faculty' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark'])) {
    $subject_id = intval($_POST['subject_id']);
    $date = $_POST['date'] ?: date('Y-m-d');
    $present_ids = $_POST['present'] ?? []; // array of student ids

    // fetch students in department (simple approach: students with same department as faculty)
    // remove previous marks for this subject/date
    $del = $conn->prepare("DELETE FROM attendance WHERE subject_id = ? AND date = ?");
    $del->bind_param("is",$subject_id,$date);
    $del->execute();
    $del->close();

    // insert marks for every student passed as present; for simplicity insert Present for present, Absent for others
    // get all students
    $students = $conn->query("SELECT user_id FROM users WHERE role='student'");
    while ($st = $students->fetch_assoc()) {
        $sid = $st['user_id'];
        $status = in_array($sid, $present_ids) ? 'Present' : 'Absent';
        $ins = $conn->prepare("INSERT INTO attendance (student_id,subject_id,date,status) VALUES (?,?,?,?)");
        $ins->bind_param("iiss",$sid,$subject_id,$date,$status);
        $ins->execute();
        $ins->close();
    }
    $msg = "Attendance saved for $date.";
}

// Display logic
include 'header.php';
?>
<div class="card">
    <h2>Attendance</h2>
    <?php if($msg): ?><div class="info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if ($role == 'faculty'): 
        // faculty can choose a subject they teach
        $faculty_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT subject_id,subject_name FROM subjects WHERE faculty_id = ?");
        $stmt->bind_param("i",$faculty_id);
        $stmt->execute();
        $subRes = $stmt->get_result();
    ?>
        <h3>Mark Attendance</h3>
        <form method="post">
            <label>Subject</label>
            <select name="subject_id" required>
                <?php while($r = $subRes->fetch_assoc()): ?>
                    <option value="<?= $r['subject_id'] ?>"><?= htmlspecialchars($r['subject_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Date</label>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>">
            <h4>Students</h4>
            <div class="students">
                <?php 
                $students = $conn->query("SELECT user_id,name FROM users WHERE role='student' ORDER BY name");
                while($s = $students->fetch_assoc()): ?>
                    <div class="chk">
                        <label><input type="checkbox" name="present[]" value="<?= $s['user_id'] ?>"> <?= htmlspecialchars($s['name']) ?></label>
                    </div>
                <?php endwhile; ?>
            </div>
            <button type="submit" name="mark">Save Attendance</button>
        </form>
    <?php elseif ($role == 'student'): 
        // show student's attendance
        $sid = $_SESSION['user_id'];
        $res = $conn->prepare("SELECT a.date,s.subject_name,a.status FROM attendance a JOIN subjects s ON a.subject_id=s.subject_id WHERE a.student_id = ? ORDER BY a.date DESC");
        $res->bind_param("i",$sid);
        $res->execute();
        $att = $res->get_result();
    ?>
        <h3>Your Attendance</h3>
        <table class="table">
            <tr><th>Date</th><th>Subject</th><th>Status</th></tr>
            <?php while($r = $att->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['date'] ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['status'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: 
        // HOD view - summary
        $summary = $conn->query("SELECT s.subject_name, a.date, SUM(a.status='Present') AS presents, COUNT(*) AS total FROM attendance a JOIN subjects s ON a.subject_id=s.subject_id GROUP BY a.subject_id,a.date ORDER BY a.date DESC");
    ?>
        <h3>Attendance Reports</h3>
        <table class="table">
            <tr><th>Date</th><th>Subject</th><th>Presents</th><th>Total</th><th>%</th></tr>
            <?php while($r = $summary->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['date'] ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['presents'] ?></td>
                    <td><?= $r['total'] ?></td>
                    <td><?= $r['total'] ? round(($r['presents']/$r['total'])*100,2) : 0 ?>%</td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>

