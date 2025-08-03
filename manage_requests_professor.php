<?php
session_start();
require 'config.php';

// Έλεγχος σύνδεσης και ρόλου
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];

// Υποβολή αποδοχής ή απόρριψης
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
                // Ανάθεση διπλωματικής
                $stmt = $pdo->prepare("
                    INSERT INTO thesis_assignments 
                    (student_id, title, description, pdf_filename, status, assigned_date, created_at)
                    SELECT ?, t.title, t.description, t.pdf_filename, 'Υπό Ανάθεση', NOW(), NOW()
                    FROM topics t
                    WHERE t.id = ?
                ");
                $stmt->execute([$request['student_id'], $request['topic_id']]);

                // Ενημέρωση αιτήματος
                $stmt = $pdo->prepare("UPDATE thesis_requests SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$request_id]);
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE thesis_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$request_id]);
            }
        }
    }
}

// Λήψη εκκρεμών αιτημάτων + πληροφοριών φοιτητή
$stmt = $pdo->prepare("
    SELECT 
        tr.id AS request_id, tr.status, 
        s.name AS student_name, s.id AS student_id, s.email,
        t.title AS topic_title,
        sp.gpa, sp.total_courses, sp.passed_courses, sp.entry_year
    FROM thesis_requests tr
    JOIN topics t ON tr.topic_id = t.id
    JOIN users s ON tr.student_id = s.id
    LEFT JOIN student_profiles sp ON sp.student_id = s.id
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
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #aaa; padding: 10px; }
        th { background-color: #eee; }
        .hidden { display: none; }
        .toggle-btn {
            padding: 5px 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
        }
        .info-box {
            background-color: #f9f9f9;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
    <script>
        function toggle(id) {
            const box = document.getElementById(id);
            if (box) box.classList.toggle('hidden');
        }
    </script>
</head>
<body>

<h1>Αιτήματα Ανάθεσης Διπλωματικών</h1>
<p><a href="professor_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<?php if (count($requests) === 0): ?>
    <p>Δεν υπάρχουν εκκρεμή αιτήματα.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Φοιτητής</th>
            <th>Θέμα</th>
            <th>Ενέργειες</th>
        </tr>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['student_name']); ?></td>
            <td><?php echo htmlspecialchars($r['topic_title']); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?php echo $r['request_id']; ?>">
                    <button type="submit" name="action" value="accept">✅ Αποδοχή</button>
                    <button type="submit" name="action" value="reject">❌ Απόρριψη</button>
                </form>
                <button class="toggle-btn" onclick="toggle('info_<?php echo $r['request_id']; ?>')">📋 Πληροφορίες</button>
                <div class="info-box hidden" id="info_<?php echo $r['request_id']; ?>">
                    <strong>Email:</strong> <?php echo htmlspecialchars($r['email']); ?><br>
                    <strong>Μέσος Όρος:</strong> <?php echo $r['gpa'] !== null ? htmlspecialchars($r['gpa']) : '—'; ?><br>
                    <strong>Μαθήματα Περασμένα:</strong> <?php echo (int)$r['passed_courses']; ?> /
                    <?php echo (int)$r['total_courses']; ?><br>
                    <strong>Έτος Εισαγωγής:</strong> <?php echo $r['entry_year'] ?? '—'; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
