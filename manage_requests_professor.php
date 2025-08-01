// manag3 requests_professor
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];

// Αν έγινε υποβολή έγκρισης ή απόρριψης
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && in_array($action, ['accept', 'reject'])) {
        // Εύρεση αιτήματος
        $stmt = $pdo->prepare("
            SELECT tr.*, t.professor_id
            FROM thesis_requests tr
            JOIN topics t ON tr.topic_id = t.id
            WHERE tr.id = ? AND t.professor_id = ?
        ");
        $stmt->execute([$request_id, $professor_id]);
        $request = $stmt->fetch();

        if ($request) {
            if ($action === 'accept') {
                // Δημιουργία εγγραφής στην thesis_assignments
                $stmt = $pdo->prepare("
                    INSERT INTO thesis_assignments (student_id, title, description, pdf_filename, status, assigned_date, created_at)
                    SELECT ?, t.title, t.description, t.pdf_filename, 'Υπό Ανάθεση', NOW(), NOW()
                    FROM topics t
                    WHERE t.id = ?
                ");
                $stmt->execute([$request['student_id'], $request['topic_id']]);

                // Ενημέρωση αιτήματος σε accepted
                $stmt = $pdo->prepare("UPDATE thesis_requests SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$request_id]);
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE thesis_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$request_id]);
            }
        }
    }
}

// Λήψη εκκρεμών αιτημάτων για τον καθηγητή
$stmt = $pdo->prepare("
    SELECT tr.id AS request_id, tr.status, s.name AS student_name, t.title AS topic_title
    FROM thesis_requests tr
    JOIN topics t ON tr.topic_id = t.id
    JOIN users s ON tr.student_id = s.id
    WHERE t.professor_id = ? AND tr.status = 'pending'
");
$stmt->execute([$professor_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Αιτήματα Φοιτητών</title>
</head>
<body>

<p><a href="professor_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Εκκρεμή Αιτήματα Ανάθεσης</h2>

<?php if (count($requests) === 0): ?>
    <p>Δεν υπάρχουν εκκρεμή αιτήματα.</p>
<?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Φοιτητής</th>
            <th>Θέμα</th>
            <th>Ενέργεια</th>
        </tr>
        <?php foreach ($requests as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                <td><?php echo htmlspecialchars($r['topic_title']); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?php echo $r['request_id']; ?>">
                        <button type="submit" name="action" value="accept">Αποδοχή</button>
                        <button type="submit" name="action" value="reject">Απόρριψη</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
