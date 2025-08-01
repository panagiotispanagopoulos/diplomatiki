// professor_dashboard
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

// Φόρμα νέου θέματος
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_topic'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

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

    $stmt = $pdo->prepare("INSERT INTO topics (professor_id, title, description, pdf_filename, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$professor_id, $title, $description, $pdf_filename]);

    header("Location: professor_dashboard.php");
    exit;
}

// Προφίλ
$stmt = $pdo->prepare("SELECT * FROM professor_profiles WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$profile = $stmt->fetch();

// Θέματα
$stmt = $pdo->prepare("SELECT * FROM topics WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$topics = $stmt->fetchAll();

// Φοιτητές με διπλωματικές
$stmt = $pdo->prepare("
    SELECT d.id AS diploma_id, d.status, u.name AS student_name, u.id AS student_id,
           t.title AS thesis_title, cm.role
    FROM committee_members cm
    JOIN diplomas d ON cm.diploma_id = d.id
    JOIN users u ON d.student_id = u.id
    JOIN topics t ON d.topic_id = t.id
    WHERE cm.professor_id = ?
");
$stmt->execute([$professor_id]);
$assigned = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καθηγητής - Dashboard</title>
    <style>
        body { font-family: Arial; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .hidden { display: none; }
        .toggle-btn { cursor: pointer; background: #e0e0e0; padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
    <script>
        function toggle(id) {
            const el = document.getElementById(id);
            el.classList.toggle('hidden');
        }
    </script>
</head>
<body>

<p style="text-align:right;">
    <a href="logout.php" style="color:red; font-weight:bold;">Αποσύνδεση</a>
</p>

<h1>Καλωσόρισες, <?php echo htmlspecialchars($professor_name); ?>!</h1>

<!-- Προφίλ -->
<h2>Το Προφίλ σας</h2>
<?php if (!isset($_GET['edit'])): ?>
    <p><strong>Διεύθυνση:</strong> <?php echo htmlspecialchars($profile['address'] ?? '—'); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? '—'); ?></p>
    <p><strong>Κινητό:</strong> <?php echo htmlspecialchars($profile['phone_mobile'] ?? '—'); ?></p>
    <p><strong>Σταθερό:</strong> <?php echo htmlspecialchars($profile['phone_landline'] ?? '—'); ?></p>
    <a href="professor_dashboard.php?edit=1">✏️ Επεξεργασία</a>
<?php else: ?>
    <form method="post" action="update_profile_professor.php">
        <label>Διεύθυνση:<br><input type="text" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>"></label><br><br>
        <label>Email:<br><input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>"></label><br><br>
        <label>Κινητό:<br><input type="text" name="phone_mobile" value="<?php echo htmlspecialchars($profile['phone_mobile'] ?? ''); ?>"></label><br><br>
        <label>Σταθερό:<br><input type="text" name="phone_landline" value="<?php echo htmlspecialchars($profile['phone_landline'] ?? ''); ?>"></label><br><br>
        <button type="submit">💾 Αποθήκευση</button>
        <a href="professor_dashboard.php" style="margin-left:20px;">Ακύρωση</a>
    </form>
<?php endif; ?>

<!-- Θέματα -->
<h2>Δημιουργία Νέου Θέματος</h2>
<button onclick="toggle('new-topic-form')">➕ Δημιουργία Θέματος</button>
<div id="new-topic-form" class="hidden">
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="new_topic" value="1" />
        <label>Τίτλος: <input type="text" name="title" required></label><br><br>
        <label>Περιγραφή:<br><textarea name="description" rows="5" cols="40" required></textarea></label><br><br>
        <label>Αρχείο PDF: <input type="file" name="pdf" accept="application/pdf"></label><br><br>
        <button type="submit">✅ Υποβολή</button>
    </form>
</div>

<h2>Τα Θέματά σας</h2>
<button onclick="toggle('topics-section')">📂 Προβολή Θεμάτων</button>
<div id="topics-section" class="hidden">
<?php if (count($topics) === 0): ?>
    <p>Δεν έχετε καταχωρίσει θέματα ακόμα.</p>
<?php else: ?>
    <ul>
        <?php foreach ($topics as $topic): ?>
            <li>
                <strong><?php echo htmlspecialchars($topic['title']); ?></strong><br />
                <?php echo nl2br(htmlspecialchars($topic['description'])); ?><br />
                <?php if ($topic['pdf_filename']): ?>
                    <a href="uploads/<?php echo urlencode($topic['pdf_filename']); ?>" target="_blank">📄 Προβολή PDF</a>
                <?php endif; ?>
            </li><br>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
</div>

<!-- Διπλωματικές -->
<h2>Οι Φοιτητές σας</h2>
<button onclick="toggle('student-section')">📚 Προβολή Διπλωματικών</button>
<div id="student-section" class="hidden">
    <?php if (count($assigned) === 0): ?>
        <p>Δεν έχετε αναλάβει διπλωματικές ακόμα.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Φοιτητής</th>
                <th>Θέμα</th>
                <th>Κατάσταση</th>
                <th>Ρόλος</th>
                <th>Ενέργειες</th>
            </tr>
            <?php foreach ($assigned as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td><?php echo htmlspecialchars($row['thesis_title']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td>
                    <a href="view_drafts.php?diploma_id=<?php echo $row['diploma_id']; ?>">📄 Πρόχειρο</a> |
                    <a href="submit_evaluation.php?diploma_id=<?php echo $row['diploma_id']; ?>">📝 Αξιολόγηση</a>
                    <?php if ($row['status'] === 'Περατωμένη'): ?>
                        | <a href="view_final_report.php?diploma_id=<?php echo $row['diploma_id']; ?>">📘 Τελικό Πρακτικό</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
