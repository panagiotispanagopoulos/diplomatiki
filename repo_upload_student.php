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
    die("Η εργασία σας δεν είναι σε κατάσταση 'Υπό Εξέταση'.");
}

$success = null;
$error = null;

// Υποβολή συνδέσμου
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $link = trim($_POST['repository_link'] ?? '');

    if (filter_var($link, FILTER_VALIDATE_URL)) {
        $stmt = $pdo->prepare("UPDATE diplomas SET repository_link = ? WHERE id = ?");
        $stmt->execute([$link, $diploma['id']]);
        $success = "Ο σύνδεσμος καταχωρήθηκε επιτυχώς.";
        $diploma['repository_link'] = $link; // Ενημέρωση τοπικά για εμφάνιση
    } else {
        $error = "Παρακαλώ εισάγετε έγκυρο URL.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Αποθετήριο Νημερτής</title>
</head>
<body>

<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Καταχώρηση Συνδέσμου προς Νημερτή</h2>

<?php if ($success): ?>
    <p style="color:green;"><?php echo $success; ?></p>
<?php elseif ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">
    <label>Σύνδεσμος:<br>
        <input type="url" name="repository_link" value="<?php echo htmlspecialchars($diploma['repository_link'] ?? ''); ?>" style="width: 100%;" required>
    </label><br><br>
    <button type="submit">Αποθήκευση</button>
</form>

<?php if (!empty($diploma['repository_link'])): ?>
    <p><strong>Τρέχων σύνδεσμος:</strong>
        <a href="<?php echo htmlspecialchars($diploma['repository_link']); ?>" target="_blank">
            <?php echo htmlspecialchars($diploma['repository_link']); ?>
        </a>
    </p>
<?php endif; ?>

</body>
</html>
