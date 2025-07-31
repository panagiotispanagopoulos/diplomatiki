<?php
session_start();
require 'config.php';

// Έλεγχος σύνδεσης και ρόλου
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$professor_name = $_SESSION['user_name'];

// Διαχείριση φόρμας δημιουργίας νέου θέματος
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_topic'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    // Διαχείριση ανέβασμα αρχείου PDF
    $pdf_filename = null;
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $tmp_name = $_FILES['pdf']['tmp_name'];
        $original_name = basename($_FILES['pdf']['name']);
        $pdf_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $original_name);
        move_uploaded_file($tmp_name, $upload_dir . $pdf_filename);
    }

    // Εισαγωγή στη βάση
    $stmt = $pdo->prepare("INSERT INTO topics (professor_id, title, description, pdf_filename, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$professor_id, $title, $description, $pdf_filename]);

    header("Location: professor_dashboard.php");
    exit;
}

// Φόρτωση θεμάτων καθηγητή
$stmt = $pdo->prepare("SELECT * FROM topics WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$topics = $stmt->fetchAll();

// Φόρτωση προφίλ καθηγητή
$stmt = $pdo->prepare("SELECT * FROM professor_profiles WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$profile = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Καθηγητή</title>
</head>
<body>

<p style="text-align:right;">
    <a href="logout.php" style="color:red; font-weight:bold;">Αποσύνδεση</a>
</p>

<h1>Καλωσόρισες, <?php echo htmlspecialchars($professor_name); ?>!</h1>

<h2>Το Προφίλ σας</h2>

<?php if (!isset($_GET['edit'])): ?>
    <p><strong>Διεύθυνση:</strong> <?php echo htmlspecialchars($profile['address'] ?? '—'); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? '—'); ?></p>
    <p><strong>Κινητό:</strong> <?php echo htmlspecialchars($profile['phone_mobile'] ?? '—'); ?></p>
    <p><strong>Σταθερό:</strong> <?php echo htmlspecialchars($profile['phone_landline'] ?? '—'); ?></p>
    <a href="professor_dashboard.php?edit=1">✏️ Επεξεργασία</a>
<?php else: ?>
    <form method="post" action="update_profile_professor.php">
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
        <a href="professor_dashboard.php" style="margin-left:20px;">Ακύρωση</a>
    </form>
<?php endif; ?>


<!-- Δημιουργία Θέματος -->
<h2>Δημιουργία Νέου Θέματος Διπλωματικής</h2>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="new_topic" value="1" />
    <label>Τίτλος: <input type="text" name="title" required></label><br><br>
    <label>Περιγραφή:<br>
        <textarea name="description" rows="5" cols="40" required></textarea>
    </label><br><br>
    <label>Αρχείο PDF (προαιρετικό): <input type="file" name="pdf" accept="application/pdf"></label><br><br>
    <button type="submit">Δημιουργία Θέματος</button>
</form>

<!-- Προβολή Θεμάτων -->
<h2>Τα Θέματά σας</h2>
<?php if (count($topics) === 0): ?>
    <p>Δεν έχετε αναρτήσει ακόμα κάποιο θέμα.</p>
<?php else: ?>
    <ul>
        <?php foreach ($topics as $topic): ?>
            <li>
                <strong><?php echo htmlspecialchars($topic['title']); ?></strong><br />
                <?php echo nl2br(htmlspecialchars($topic['description'])); ?><br />
                <?php if ($topic['pdf_filename']): ?>
                    <a href="uploads/<?php echo urlencode($topic['pdf_filename']); ?>" target="_blank">Προβολή PDF</a>
                <?php endif; ?>
            </li><br>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
