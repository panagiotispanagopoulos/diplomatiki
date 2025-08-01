// student_dashboard
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Φόρτωση στοιχείων διπλωματικής
$stmt = $pdo->prepare("SELECT * FROM thesis_assignments WHERE student_id = ?");
$stmt->execute([$student_id]);
$thesis = $stmt->fetch();

// Προφίλ φοιτητή
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE student_id = ?");
$stmt->execute([$student_id]);
$profile = $stmt->fetch();

function formatDateDiff($startDate) {
    $start = new DateTime($startDate);
    $now = new DateTime();
    $interval = $start->diff($now);
    return $interval->format('%m μήνες και %d ημέρες');
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Φοιτητή</title>
</head>
<body>

<p style="text-align:right;">
    <a href="logout.php" style="color:red; font-weight:bold;">Αποσύνδεση</a>
</p>

<h1>Καλώς ήρθες, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>

<!-- Προβολή θέματος -->
<h2>Η Διπλωματική σου</h2>
<?php if (!$thesis): ?>
    <p>Δεν έχεις ακόμα κάποια διπλωματική εργασία.</p>
<?php else: ?>
    <p><strong>Θέμα:</strong> <?php echo htmlspecialchars($thesis['title']); ?></p>
    <p><strong>Περιγραφή:</strong><br><?php echo nl2br(htmlspecialchars($thesis['description'])); ?></p>
    <?php if ($thesis['pdf_filename']): ?>
        <p><a href="uploads/<?php echo $thesis['pdf_filename']; ?>" target="_blank">Προβολή PDF Περιγραφής</a></p>
    <?php endif; ?>
    <p><strong>Κατάσταση:</strong> <?php echo $thesis['status']; ?></p>
    <?php if ($thesis['assigned_date']): ?>
        <p>Ανάθεση πριν από: <?php echo formatDateDiff($thesis['assigned_date']); ?></p>
    <?php endif; ?>

    <!-- Εμφάνιση μελών τριμελούς -->
    <h3>Τριμελής Επιτροπή</h3>
    <?php
    $stmt = $pdo->prepare("SELECT u.name, cm.role FROM committee_members cm JOIN users u ON cm.professor_id = u.id WHERE cm.diploma_id = ?");
    $stmt->execute([$thesis['id']]);
    $members = $stmt->fetchAll();

    if ($members):
        echo "<ul>";
        foreach ($members as $m) {
            echo "<li>" . htmlspecialchars($m['name']) . " (" . htmlspecialchars($m['role']) . ")</li>";
        }
        echo "</ul>";
    else:
        echo "<p>Δεν έχουν οριστεί ακόμα μέλη.</p>";
    endif;
    ?>
<?php endif; ?>

<h2>Το Προφίλ σου</h2>

<?php if (!isset($_GET['edit'])): ?>
    <p><strong>Διεύθυνση:</strong> <?php echo htmlspecialchars($profile['address'] ?? '—'); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? '—'); ?></p>
    <p><strong>Κινητό:</strong> <?php echo htmlspecialchars($profile['phone_mobile'] ?? '—'); ?></p>
    <p><strong>Σταθερό:</strong> <?php echo htmlspecialchars($profile['phone_landline'] ?? '—'); ?></p>
    <a href="student_dashboard.php?edit=1">✏️ Επεξεργασία</a>
<?php else: ?>
    <form method="post" action="update_profile.php">
        <label>Διεύθυνση:<br>
            <input type="text" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
        </label><br><br>
        <label>Email:<br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">
        </label><br><br>
        <label>Κινητό:<br>
            <input type="text" name="phone_mobile" value="<?php echo htmlspecialchars($profile['phone_mobile'] ?? ''); ?>">
        </label><br><br>
        <label>Σταθερό:<br>
            <input type="text" name="phone_landline" value="<?php echo htmlspecialchars($profile['phone_landline'] ?? ''); ?>">
        </label><br><br>
        <button type="submit">💾 Αποθήκευση</button>
        <a href="student_dashboard.php" style="margin-left: 20px;">Ακύρωση</a>
    </form>
<?php endif; ?>


<!-- Διαχείριση Διπλωματικής -->
<h2>Διαχείριση Διπλωματικής</h2>
<?php
if (!$thesis) {
    echo "<p>Δεν μπορείς να διαχειριστείς διπλωματική χωρίς ανάθεση.</p>";
} else {
    switch ($thesis['status']) {
        case 'Υπό Ανάθεση':
            echo "<p><a href='select_committee.php'>Πρόσκληση μελών τριμελούς</a></p>";
            break;
        case 'Υπό Εξέταση':
            echo "<p><a href='upload_draft.php'>Ανάρτηση Πρόχειρου Κειμένου</a></p>";
            echo "<p><a href='exam_schedule.php'>Καταχώρηση Ημερομηνίας Εξέτασης</a></p>";
            echo "<p><a href='repo_upload.php'>Σύνδεσμος προς Νημερτή</a></p>";
            echo "<p><a href='view_evaluation.php'>Προβολή Πρακτικού Εξέτασης</a></p>";
            break;
        case 'Περατωμένη':
            echo "<p>Η διπλωματική σου έχει ολοκληρωθεί. Μπορείς να δεις το τελικό πρακτικό.</p>";
            echo "<p><a href='view_final_report.php'>Προβολή Πρακτικού</a></p>";
            break;
        default:
            echo "<p>Η κατάσταση δεν υποστηρίζεται ακόμη.</p>";
    }
}
?>

</body>
</html>
