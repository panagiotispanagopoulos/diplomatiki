// view_final_report_student
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$student_id = $_SESSION['user_id'];

// Βρίσκουμε τη διπλωματική που έχει ολοκληρωθεί
$stmt = $pdo->prepare("SELECT * FROM diplomas WHERE student_id = ? AND status = 'completed'");
$stmt->execute([$student_id]);
$diploma = $stmt->fetch();

if (!$diploma) {
    die("Δεν υπάρχει περατωμένη διπλωματική εργασία.");
}

$diploma_id = $diploma['id'];

// Επιμέρους βαθμολογίες
$stmt = $pdo->prepare("
    SELECT u.name AS professor_name, g.grade, g.criteria
    FROM grades g
    JOIN users u ON g.professor_id = u.id
    WHERE g.diploma_id = ?
");
$stmt->execute([$diploma_id]);
$grades = $stmt->fetchAll();

// Παρατηρήσεις
$stmt = $pdo->prepare("
    SELECT u.name AS professor_name, n.note
    FROM notes n
    JOIN users u ON n.professor_id = u.id
    WHERE n.diploma_id = ?
");
$stmt->execute([$diploma_id]);
$notes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Τελικό Πρακτικό</title>
</head>
<body>

<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Τελικό Πρακτικό Διπλωματικής</h2>

<p><strong>Κατάσταση:</strong> Περατωμένη</p>

<?php if (!empty($diploma['final_grade'])): ?>
    <p><strong>Τελικός Βαθμός:</strong> <?php echo $diploma['final_grade']; ?></p>
<?php else: ?>
    <p>Δεν έχει καταχωρηθεί τελικός βαθμός.</p>
<?php endif; ?>

<?php if (!empty($diploma['repository_link'])): ?>
    <p><strong>Αποθετήριο:</strong> <a href="<?php echo htmlspecialchars($diploma['repository_link']); ?>" target="_blank">
        <?php echo htmlspecialchars($diploma['repository_link']); ?>
    </a></p>
<?php endif; ?>

<?php if (count($grades) > 0): ?>
    <h3>Αναλυτικές Βαθμολογίες</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Καθηγητής</th>
            <th>Βαθμός</th>
            <th>Κριτήρια</th>
        </tr>
        <?php foreach ($grades as $g): ?>
            <tr>
                <td><?php echo htmlspecialchars($g['professor_name']); ?></td>
                <td><?php echo $g['grade']; ?></td>
                <td><?php echo htmlspecialchars($g['criteria']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if (count($notes) > 0): ?>
    <h3>Παρατηρήσεις</h3>
    <ul>
        <?php foreach ($notes as $n): ?>
            <li><strong><?php echo htmlspecialchars($n['professor_name']); ?>:</strong> <?php echo htmlspecialchars($n['note']); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
