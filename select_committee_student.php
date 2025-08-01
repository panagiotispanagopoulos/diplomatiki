// select_committee_student
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Έλεγχος διπλωματικής
$stmt = $pdo->prepare("SELECT id, status FROM thesis_assignments WHERE student_id = ?");
$stmt->execute([$student_id]);
$thesis = $stmt->fetch();

if (!$thesis || $thesis['status'] !== 'Υπό Ανάθεση') {
    die("Δεν μπορείτε να προσκαλέσετε μέλη σε αυτή τη φάση.");
}

$diploma_id = $thesis['id'];

// Εισαγωγή πρόσκλησης
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_id = $_POST['professor_id'] ?? null;
    $role = $_POST['role'] ?? null;

    if ($professor_id && in_array($role, ['supervisor', 'member'])) {
        // Έλεγχος αν υπάρχει ήδη το ίδιο μέλος
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM committee_members WHERE diploma_id = ? AND professor_id = ?");
        $stmt->execute([$diploma_id, $professor_id]);

        if ($stmt->fetchColumn() == 0) {
            // Έλεγχος ρόλων (μόνο 1 supervisor, max 2 μέλη)
            $stmt = $pdo->prepare("SELECT role FROM committee_members WHERE diploma_id = ?");
            $stmt->execute([$diploma_id]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $supervisors = array_filter($roles, fn($r) => $r === 'supervisor');
            $members = array_filter($roles, fn($r) => $r === 'member');

            if ($role === 'supervisor' && count($supervisors) >= 1) {
                $error = "Έχετε ήδη ορίσει επιβλέποντα.";
            } elseif ($role === 'member' && count($members) >= 2) {
                $error = "Μπορείτε να προσθέσετε έως 2 μέλη.";
            } else {
                // Εισαγωγή
                $stmt = $pdo->prepare("INSERT INTO committee_members (diploma_id, professor_id, role) VALUES (?, ?, ?)");
                $stmt->execute([$diploma_id, $professor_id, $role]);
                $success = "Ο/Η καθηγητής/τρια προσκλήθηκε.";
            }
        } else {
            $error = "Ο χρήστης αυτός έχει ήδη προσκληθεί.";
        }
    } else {
        $error = "Λανθασμένα στοιχεία.";
    }
}

// Λίστα καθηγητών
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'professor'");
$professors = $stmt->fetchAll();

// Ήδη προσκεκλημένοι
$stmt = $pdo->prepare("SELECT u.name, cm.role FROM committee_members cm JOIN users u ON cm.professor_id = u.id WHERE cm.diploma_id = ?");
$stmt->execute([$diploma_id]);
$existing = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πρόσκληση Τριμελούς</title>
</head>
<body>
<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Πρόσκληση Μελών Τριμελούς Επιτροπής</h2>

<?php if (isset($success)): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php if (isset($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post">
    <label>Καθηγητής:<br>
        <select name="professor_id" required>
            <option value="">-- Επιλέξτε --</option>
            <?php foreach ($professors as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Ρόλος:
        <select name="role" required>
            <option value="supervisor">Επιβλέπων</option>
            <option value="member">Μέλος</option>
        </select>
    </label><br><br>

    <button type="submit">Πρόσκληση</button>
</form>

<h3>Ήδη προσκεκλημένοι:</h3>
<?php if ($existing): ?>
    <ul>
        <?php foreach ($existing as $e): ?>
            <li><?php echo htmlspecialchars($e['name']) . " (" . htmlspecialchars($e['role']) . ")"; ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Δεν έχουν οριστεί ακόμα μέλη.</p>
<?php endif; ?>

</body>
</html>
