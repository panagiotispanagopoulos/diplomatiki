// exam_schedule_student
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$student_id = $_SESSION['user_id'];

// Ανάκτηση διπλωματικής
$stmt = $pdo->prepare("SELECT * FROM thesis_assignments WHERE student_id = ?");
$stmt->execute([$student_id]);
$thesis = $stmt->fetch();

if (!$thesis || $thesis['status'] !== 'Υπό Εξέταση') {
    die("Η εργασία δεν είναι σε φάση 'Υπό Εξέταση'.");
}

$diploma_id = $thesis['id'];

// Έλεγχος αν υπάρχει ήδη ανακοίνωση
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE diploma_id = ?");
$stmt->execute([$diploma_id]);
$announcement = $stmt->fetch();

// Υποβολή φόρμας
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $mode = $_POST['mode'] ?? 'in_person';
    $link = trim($_POST['public_link'] ?? '');

    $datetime = "$date $time";

    if (!$date || !$time || !$location) {
        $error = "Συμπληρώστε όλα τα υποχρεωτικά πεδία.";
    } else {
        if ($announcement) {
            // Ενημέρωση
            $stmt = $pdo->prepare("UPDATE announcements SET presentation_date = ?, location = ?, mode = ?, public_link = ? WHERE diploma_id = ?");
            $stmt->execute([$datetime, $location, $mode, $link, $diploma_id]);
            $success = "Η ανακοίνωση ενημερώθηκε.";
        } else {
            // Δημιουργία
            $stmt = $pdo->prepare("INSERT INTO announcements (diploma_id, presentation_date, location, mode, public_link, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$diploma_id, $datetime, $location, $mode, $link]);
            $success = "Η ανακοίνωση καταχωρήθηκε.";
        }
        // Επανφόρτωση
        header("Location: exam_schedule.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καταχώρηση Εξέτασης</title>
</head>
<body>

<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Καταχώρηση Ημερομηνίας Εξέτασης</h2>

<?php if (isset($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php elseif (isset($_GET['success'])): ?>
    <p style="color:green;">Η καταχώρηση έγινε επιτυχώς.</p>
<?php endif; ?>

<form method="post">
    <label>Ημερομηνία:<br>
        <input type="date" name="date" value="<?php echo isset($announcement['presentation_date']) ? date('Y-m-d', strtotime($announcement['presentation_date'])) : ''; ?>" required>
    </label><br><br>

    <label>Ώρα:<br>
        <input type="time" name="time" value="<?php echo isset($announcement['presentation_date']) ? date('H:i', strtotime($announcement['presentation_date'])) : ''; ?>" required>
    </label><br><br>

    <label>Τοποθεσία:<br>
        <input type="text" name="location" value="<?php echo htmlspecialchars($announcement['location'] ?? ''); ?>" required>
    </label><br><br>

    <label>Τρόπος Εξέτασης:<br>
        <select name="mode">
            <option value="in_person" <?php if (($announcement['mode'] ?? '') === 'in_person') echo 'selected'; ?>>Δια ζώσης</option>
            <option value="online" <?php if (($announcement['mode'] ?? '') === 'online') echo 'selected'; ?>>Εξ αποστάσεως</option>
        </select>
    </label><br><br>

    <label>Σύνδεσμος (αν υπάρχει):<br>
        <input type="url" name="public_link" value="<?php echo htmlspecialchars($announcement['public_link'] ?? ''); ?>">
    </label><br><br>

    <button type="submit">Καταχώρηση</button>
</form>

<?php if ($announcement): ?>
    <h3>Τρέχουσα Ανακοίνωση</h3>
    <p><strong>Ημερομηνία:</strong> <?php echo date('d/m/Y H:i', strtotime($announcement['presentation_date'])); ?></p>
    <p><strong>Τοποθεσία:</strong> <?php echo htmlspecialchars($announcement['location']); ?></p>
    <p><strong>Τρόπος:</strong> <?php echo $announcement['mode'] === 'online' ? 'Εξ αποστάσεως' : 'Δια ζώσης'; ?></p>
    <?php if ($announcement['public_link']): ?>
        <p><strong>Σύνδεσμος:</strong> <a href="<?php echo htmlspecialchars($announcement['public_link']); ?>" target="_blank">Προβολή</a></p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
