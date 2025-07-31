<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];
$message = '';

// 📝 Επεξεργασία υποβολής
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diploma_id = $_POST['diploma_id'] ?? null;
    $grade = $_POST['grade'] ?? null;
    $criteria = trim($_POST['criteria'] ?? '');

    if ($diploma_id && is_numeric($grade) && $criteria !== '') {
        // Αποθήκευση βαθμού
        $stmt = $pdo->prepare("INSERT INTO grades (diploma_id, professor_id, grade, criteria) VALUES (?, ?, ?, ?)");
        $stmt->execute([$diploma_id, $professor_id, $grade, $criteria]);
        $message = "✅ Η αξιολόγηση καταχωρήθηκε.";
    } else {
        $message = "⚠️ Συμπληρώστε όλα τα πεδία σωστά.";
    }
}

// 📥 Φέρνουμε όλες τις διπλωματικές που συμμετέχει ο καθηγητής και δεν έχει ήδη βαθμολογήσει
$stmt = $pdo->prepare("
    SELECT ta.id, ta.title, u.name AS student_name
    FROM thesis_assignments ta
    JOIN committee_members cm ON cm.diploma_id = ta.id
    JOIN users u ON ta.student_id = u.id
    WHERE cm.professor_id = ?
    AND ta.status = 'Υπό Εξέταση'
    AND ta.id NOT IN (
        SELECT diploma_id FROM grades WHERE professor_id = ?
    )
");
$stmt->execute([$professor_id, $professor_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καταχώρηση Αξιολόγησης</title>
</head>
<body>

<p style="text-align:right;"><a href="professor_dashboard.php">🔙 Επιστροφή</a></p>

<h1>Καταχώρηση Πρακτικού Αξιολόγησης</h1>

<?php if ($message): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
<?php endif; ?>

<?php if (count($assignments) === 0): ?>
    <p>Δεν υπάρχουν διπλωματικές για αξιολόγηση ή τις έχετε ήδη αξιολογήσει.</p>
<?php else: ?>
    <form method="post">
        <label>Επιλογή Διπλωματικής:</label><br>
        <select name="diploma_id" required>
            <?php foreach ($assignments as $a): ?>
                <option value="<?php echo $a['id']; ?>">
                    <?php echo htmlspecialchars($a['student_name'] . ' - ' . $a['title']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Βαθμός (0 - 10):</label><br>
        <input type="number" name="grade" step="0.1" min="0" max="10" required><br><br>

        <label>Κριτήρια αξιολόγησης:</label><br>
        <textarea name="criteria" rows="4" cols="50" required></textarea><br><br>

        <button type="submit">✅ Υποβολή Αξιολόγησης</button>
    </form>
<?php endif; ?>

</body>
</html>
