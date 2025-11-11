<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$role = $_SESSION['role'];
$msg = '';

// HOD can create or update timetable entries
if ($role == 'hod' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $day = $_POST['day'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    $subject_id = intval($_POST['subject_id']);
    $faculty_id = intval($_POST['faculty_id']);
    if ($day && $time_slot && $subject_id) {
        $ins = $conn->prepare("INSERT INTO timetable (day,time_slot,subject_id,faculty_id) VALUES (?,?,?,?)");
        $ins->bind_param("ssii",$day,$time_slot,$subject_id,$faculty_id);
        $ins->execute();
        $ins->close();
        $msg = "Timetable slot added.";
    } else $msg = "Fill required fields.";
}

include 'header.php';
?>
<div class="card">
    <h2>Timetable</h2>
    <?php if($msg): ?><div class="info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if ($role == 'hod'): 
        $subjects = $conn->query("SELECT subject_id,subject_name FROM subjects ORDER BY subject_name");
        $faculties = $conn->query("SELECT user_id,name FROM users WHERE role='faculty' ORDER BY name");
    ?>
        <h3>Create Timetable Slot</h3>
        <form method="post" class="inline">
            <select name="day" required>
                <option value="">Day</option>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                <option>Thursday</option><option>Friday</option><option>Saturday</option>
            </select>
            <input type="text" name="time_slot" placeholder="09:00-10:00" required>
            <select name="subject_id" required>
                <option value="">Subject</option>
                <?php while($s = $subjects->fetch_assoc()): ?>
                    <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="faculty_id">
                <option value="">Faculty (optional)</option>
                <?php while($f = $faculties->fetch_assoc()): ?>
                    <option value="<?= $f['user_id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="create">Add Slot</button>
        </form>
    <?php endif; ?>

    <h3>Full Timetable</h3>
    <?php
    $tt = $conn->query("SELECT t.id,t.day,t.time_slot,s.subject_name,u.name as faculty FROM timetable t JOIN subjects s ON t.subject_id=s.subject_id LEFT JOIN users u ON t.faculty_id=u.user_id ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.time_slot");
    ?>
    <table class="table">
        <tr><th>Day</th><th>Time</th><th>Subject</th><th>Faculty</th></tr>
        <?php while($r = $tt->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['day']) ?></td>
                <td><?= htmlspecialchars($r['time_slot']) ?></td>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><?= htmlspecialchars($r['faculty'] ?? 'TBA') ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php include 'footer.php'; ?>

