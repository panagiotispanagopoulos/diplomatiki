<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$student_id = $_SESSION['user_id'];

// Βρίσκουμε τη διπλωματική του φοιτητή
$stmt = $pdo->prepare("SELECT * FROM diplomas WHERE student_id = ?");
$stmt->execute([$student_id]);
$diploma = $stmt->fetch();

if (!$diploma || $diploma['status'] !== 'under_review') {
    die("Δεν υπάρχει διπλωματική σε κατάσταση 'Υπό Εξέταση'.");
}

$diploma_id = $diploma['id'];

// Λήψη βαθμών
$stmt = $pdo->prepare("
    SELECT u.name AS professor_name, g.grade, g.criteria
    FROM grades g
    JOIN users u ON g.professor_id = u.id
    WHERE g.diploma_id = ?
");
$stmt->execute([$diploma_id]);
$grades = $stmt->fetchAll();

// Λήψη σχολίων
$stmt = $pdo->prepare("
    SELECT u.name AS professor_name, n.note
    FROM notes n
    JOIN users u ON n.professor_id = u.id
    WHERE n.diploma_id = ?
");
$stmt->execute([$diploma_id]);
$notes = $stmt->fetchAll();

// Υπολογισμός μέσου όρου
$avg_grade = null;
if (count($grades) > 0) {
    $total = array_sum(array_column($grades, 'grade'));
    $avg_grade = round($total / count($grades), 2);
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πρακτικό Εξέτασης</title>
</head>
<body>

<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Πρακτικό Εξέτασης Διπλωματικής</h2>

<?php if (count($grades) === 0): ?>
    <p>Δεν έχουν καταχωρηθεί ακόμη βαθμολογίες.</p>
<?php else: ?>
    <h3>Βαθμολογίες</h3>
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
    <p><strong>Μέσος Όρος:</strong> <?php echo $avg_grade; ?></p>
<?php endif; ?>

<?php if (count($notes) > 0): ?>
    <h3>Σχόλια Επιτροπής</h3>
    <ul>
        <?php foreach ($notes as $n): ?>
            <li><strong><?php echo htmlspecialchars($n['professor_name']); ?>:</strong> <?php echo htmlspecialchars($n['note']); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><strong>Κατάσταση:</strong> <?php echo htmlspecialchars($diploma['status']); ?></p>

</body>
</html>
