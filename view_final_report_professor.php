// view_final_report_professor
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];
$message = '';

// Αν έγινε POST για τελική καταχώρηση
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize'])) {
    $diploma_id = $_POST['diploma_id'];

    // Υπολογισμός μέσου όρου
    $stmt = $pdo->prepare("SELECT AVG(grade) AS final_grade FROM grades WHERE diploma_id = ?");
    $stmt->execute([$diploma_id]);
    $final = $stmt->fetch();

    // Ενημέρωση βαθμού & κατάστασης
    $stmt = $pdo->prepare("UPDATE diplomas SET final_grade = ?, status = 'completed' WHERE id = ?");
    $stmt->execute([$final['final_grade'], $diploma_id]);

    $message = "✅ Η διπλωματική καταχωρήθηκε ως περατωμένη.";
}

// Φέρνουμε τις διπλωματικές στις οποίες συμμετέχει
$stmt = $pdo->prepare("
    SELECT d.id, d.title, d.status, u.name AS student_name,
           (SELECT COUNT(*) FROM committee_members WHERE diploma_id = d.id) AS total_members,
           (SELECT COUNT(*) FROM grades WHERE diploma_id = d.id) AS submitted_evaluations,
           (SELECT role FROM committee_members WHERE diploma_id = d.id AND professor_id = ?) AS my_role
    FROM diplomas d
    JOIN users u ON d.student_id = u.id
    WHERE d.status = 'Υπό Εξέταση'
      AND d.id IN (SELECT diploma_id FROM committee_members WHERE professor_id = ?)
");
$stmt->execute([$professor_id, $professor_id]);
$diplomas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Προβολή Τελικού Πρακτικού</title>
</head>
<body>

<p style="text-align:right;"><a href="professor_dashboard.php">🔙 Επιστροφή</a></p>

<h1>Τελικό Πρακτικό Διπλωματικής</h1>

<?php if ($message): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
<?php endif; ?>

<?php if (count($diplomas) === 0): ?>
    <p>Δεν υπάρχουν διαθέσιμες διπλωματικές για τελική προβολή.</p>
<?php else: ?>
    <?php foreach ($diplomas as $d): ?>
        <hr>
        <h3><?php echo htmlspecialchars($d['student_name'] . ' - ' . $d['title']); ?></h3>
        <p><strong>Κατάσταση:</strong> <?php echo htmlspecialchars($d['status']); ?></p>

        <h4>Αξιολογήσεις:</h4>
        <ul>
        <?php
            $stmt = $pdo->prepare("
                SELECT g.grade, g.criteria, u.name
                FROM grades g
                JOIN users u ON g.professor_id = u.id
                WHERE g.diploma_id = ?
            ");
            $stmt->execute([$d['id']]);
            $grades = $stmt->fetchAll();

            foreach ($grades as $g) {
                echo "<li><strong>" . htmlspecialchars($g['name']) . ":</strong> Βαθμός: " . htmlspecialchars($g['grade']) . "<br>Κριτήρια: " . nl2br(htmlspecialchars($g['criteria'])) . "</li><br>";
            }
        ?>
        </ul>

        <p><strong>Υποβολές:</strong> <?php echo $d['submitted_evaluations']; ?> / <?php echo $d['total_members']; ?></p>

        <?php if ($d['submitted_evaluations'] == $d['total_members'] && $d['my_role'] === 'supervisor'): ?>
            <form method="post" onsubmit="return confirm('Είστε βέβαιοι για την περάτωση;');">
                <input type="hidden" name="diploma_id" value="<?php echo $d['id']; ?>">
                <button type="submit" name="finalize">🏁 Περάτωση Διπλωματικής</button>
            </form>
        <?php else: ?>
            <?php if ($d['my_role'] === 'supervisor'): ?>
                <p><em>Περιμένετε την υποβολή όλων των αξιολογήσεων.</em></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
