<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];

// Φέρνουμε όλα τα thesis_assignments όπου είναι μέλος επιτροπής
$stmt = $pdo->prepare("
    SELECT ta.*, u.name AS student_name 
    FROM thesis_assignments ta
    JOIN users u ON ta.student_id = u.id
    WHERE ta.id IN (
        SELECT diploma_id FROM committee_members WHERE professor_id = ?
    )
    AND ta.status = 'Υπό Εξέταση'
    AND ta.pdf_filename IS NOT NULL
");
$stmt->execute([$professor_id]);
$drafts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πρόχειρες Εργασίες Φοιτητών</title>
</head>
<body>

<p style="text-align:right;"><a href="professor_dashboard.php">🔙 Επιστροφή</a></p>

<h1>Πρόχειρες Εργασίες Φοιτητών</h1>

<?php if (count($drafts) === 0): ?>
    <p>Δεν υπάρχουν διαθέσιμες πρόχειρες εργασίες.</p>
<?php else: ?>
    <ul>
        <?php foreach ($drafts as $d): ?>
            <li>
                <strong><?php echo htmlspecialchars($d['student_name']); ?></strong><br>
                <em>Τίτλος:</em> <?php echo htmlspecialchars($d['title']); ?><br>
                <a href="uploads/<?php echo urlencode($d['pdf_filename']); ?>" target="_blank">📄 Προβολή Πρόχειρου</a>
            </li>
            <br>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
