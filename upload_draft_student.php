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
    die("Η εργασία σας δεν είναι σε κατάσταση 'Υπό Εξέταση'.");
}

// Ανέβασμα αρχείου
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['draft_pdf'])) {
    $file = $_FILES['draft_pdf'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $file['tmp_name'];
        $name = basename($file['name']);
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        if (strtolower($ext) !== 'pdf') {
            $error = "Μόνο αρχεία PDF επιτρέπονται.";
        } else {
            // Μετονομασία αρχείου
            $new_filename = "draft_student_" . $student_id . "_" . time() . ".pdf";
            $upload_path = "uploads/" . $new_filename;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                // Ενημέρωση βάσης
                $stmt = $pdo->prepare("UPDATE thesis_assignments SET pdf_filename = ? WHERE id = ?");
                $stmt->execute([$new_filename, $thesis['id']]);
                $success = "Το αρχείο ανέβηκε επιτυχώς.";
            } else {
                $error = "Αποτυχία μεταφοράς αρχείου.";
            }
        }
    } else {
        $error = "Σφάλμα κατά το ανέβασμα του αρχείου.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ανάρτηση Πρόχειρης Εργασίας</title>
</head>
<body>

<p><a href="student_dashboard.php">← Επιστροφή στο Dashboard</a></p>

<h2>Ανάρτηση Πρόχειρης Εργασίας</h2>

<?php if (isset($success)): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php elseif (isset($error)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Επιλέξτε αρχείο PDF:<br>
        <input type="file" name="draft_pdf" accept="application/pdf" required>
    </label><br><br>
    <button type="submit">Ανάρτηση</button>
</form>

<?php if ($thesis['pdf_filename']): ?>
    <h3>Τρέχον Πρόχειρο:</h3>
    <a href="uploads/<?php echo htmlspecialchars($thesis['pdf_filename']); ?>" target="_blank">Προβολή Αρχείου</a>
<?php endif; ?>

</body>
</html>
