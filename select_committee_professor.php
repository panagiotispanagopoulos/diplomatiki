<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];
$message = '';

// 🔄 Αν στάλθηκε η φόρμα
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diploma_id = $_POST['diploma_id'] ?? null;
    $member1_id = $_POST['member1'] ?? null;
    $member2_id = $_POST['member2'] ?? null;

    if ($diploma_id && $member1_id && $member2_id && $member1_id !== $member2_id) {
        // Προσθήκη επιβλέποντα
        $stmt = $pdo->prepare("INSERT IGNORE INTO committee_members (diploma_id, professor_id, role, accepted_at) VALUES (?, ?, 'supervisor', NOW())");
        $stmt->execute([$diploma_id, $professor_id]);

        // Προσθήκη μέλους 1
        $stmt = $pdo->prepare("INSERT IGNORE INTO committee_members (diploma_id, professor_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$diploma_id, $member1_id]);

        // Προσθήκη μέλους 2
        $stmt->execute([$diploma_id, $member2_id]);

        $message = "✅ Η επιτροπή ορίστηκε επιτυχώς.";
    } else {
        $message = "⚠️ Συμπληρώστε σωστά τα μέλη (και να μην είναι ίδια).";
    }
}

// Φέρνουμε τις διπλωματικές που συνδέονται με topics του καθηγητή
$stmt = $pdo->prepare("
    SELECT d.id, d.status, s.name AS student_name, t.title AS topic_title
    FROM diplomas d
    JOIN topics t ON d.topic_id = t.id
    JOIN users s ON d.student_id = s.id
    WHERE t.professor_id = ? AND d.status IN ('active', 'Υπό Ανάθεση')
");
$stmt->execute([$professor_id]);
$diplomas = $stmt->fetchAll();

// Φέρνουμε όλους τους καθηγητές (εκτός του τρέχοντος)
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'professor' AND id != ?");
$stmt->execute([$professor_id]);
$other_professors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ορισμός Τριμελούς Επιτροπής</title>
</head>
<body>

<p style="text-align:right;"><a href="professor_dashboard.php">🔙 Επιστροφή</a></p>

<h1>Ορισμός Τριμελούς Επιτροπής</h1>

<?php if ($message): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
<?php endif; ?>

<?php if (count($diplomas) === 0): ?>
    <p>Δεν υπάρχουν διπλωματικές για τις οποίες μπορείτε να ορίσετε επιτροπή.</p>
<?php else: ?>
    <form method="post">
        <label>Επίλεξε διπλωματική:</label><br>
        <select name="diploma_id" required>
            <?php foreach ($diplomas as $d): ?>
                <option value="<?php echo $d['id']; ?>">
                    <?php echo htmlspecialchars($d['student_name'] . ' - ' . $d['topic_title']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Μέλος Επιτροπής 1:</label><br>
        <select name="member1" required>
            <?php foreach ($other_professors as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Μέλος Επιτροπής 2:</label><br>
        <select name="member2" required>
            <?php foreach ($other_professors as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">💼 Ορισμός Επιτροπής</button>
    </form>
<?php endif; ?>

</body>
</html>
