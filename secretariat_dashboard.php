<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secretariat') {
    header("Location: login.php");
    exit;
}

function formatDateDiff($startDate) {
    if (!$startDate) return "—";
    $start = new DateTime($startDate);
    $now = new DateTime();
    $interval = $start->diff($now);
    return $interval->format('%m μήνες και %d ημέρες');
}

// Φόρτωση διπλωματικών με κατάσταση 'active' ή 'under_review'
$stmt = $pdo->prepare("SELECT d.*, t.title AS topic_title, u.name AS student_name 
                       FROM diplomas d
                       JOIN topics t ON d.topic_id = t.id
                       JOIN users u ON d.student_id = u.id
                       WHERE d.status IN ('active', 'under_review')");
$stmt->execute();
$diplomas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Γραμματείας</title>
</head>
<body>

<p style="text-align:right;">
    <a href="logout.php" style="color:red; font-weight:bold;">Αποσύνδεση</a>
</p>

<h1>Καλώς ήρθατε, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>

<hr>
<h2>1) Προβολή Διπλωματικών Εργασιών</h2>
<?php if (!$diplomas): ?>
    <p>Δεν υπάρχουν διπλωματικές σε κατάσταση "Ενεργή" ή "Υπό Εξέταση".</p>
<?php else: ?>
    <?php foreach ($diplomas as $diploma): ?>
        <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
            <h3>Φοιτητής: <?php echo htmlspecialchars($diploma['student_name']); ?></h3>
            <p><strong>Θέμα:</strong> <?php echo htmlspecialchars($diploma['topic_title']); ?></p>
            <p><strong>Κατάσταση:</strong> <?php echo $diploma['status'] === 'active' ? 'Ενεργή' : 'Υπό Εξέταση'; ?></p>
            <p><strong>Ανάθεση πριν:</strong> <?php echo formatDateDiff($diploma['start_date']); ?></p>

            <h4>Μέλη Τριμελούς:</h4>
            <?php
            $stmt = $pdo->prepare("SELECT u.name, cm.role 
                                   FROM committee_members cm 
                                   JOIN users u ON cm.professor_id = u.id 
                                   WHERE cm.diploma_id = ?");
            $stmt->execute([$diploma['id']]);
            $members = $stmt->fetchAll();
            if ($members):
                echo "<ul>";
                foreach ($members as $m) {
                    echo "<li>" . htmlspecialchars($m['name']) . " (" . htmlspecialchars($m['role']) . ")</li>";
                }
                echo "</ul>";
            else:
                echo "<p>Δεν έχουν οριστεί μέλη.</p>";
            endif;
            ?>

            <!-- Διαχείριση -->
            <h4>Διαχείριση:</h4>
            <?php if ($diploma['status'] === 'active'): ?>
                <form method="post" action="update_diploma_status.php">
                    <input type="hidden" name="diploma_id" value="<?php echo $diploma['id']; ?>">
                    <label>Αρ. Πρωτοκόλλου ΓΣ: <input type="text" name="gs_code" required></label><br>
                    <label>Έτος ΓΣ: <input type="number" name="gs_year" required></label><br>
                    <button type="submit" name="action" value="confirm_assignment">Καταχώρηση Ανάθεσης</button>
                    <br><br>
                    <label>Λόγος Ακύρωσης:<br><textarea name="cancel_reason" required></textarea></label><br>
                    <button type="submit" name="action" value="cancel_assignment">Ακύρωση Ανάθεσης</button>
                </form>
            <?php elseif ($diploma['status'] === 'under_review'): ?>
                <?php
                // Έλεγχος αν υπάρχουν βαθμοί και σύνδεσμος νημερτή
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE diploma_id = ?");
                $stmt->execute([$diploma['id']]);
                $grades_exist = $stmt->fetchColumn() > 0;

                if ($diploma['repository_link'] && $grades_exist):
                ?>
                    <form method="post" action="update_diploma_status.php">
                        <input type="hidden" name="diploma_id" value="<?php echo $diploma['id']; ?>">
                        <button type="submit" name="action" value="complete_diploma">Καταχώρηση ως Περατωμένη</button>
                    </form>
                <?php else: ?>
                    <p>Απαιτείται σύνδεσμος προς Νημερτή και βαθμοί για την ολοκλήρωση.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<hr>
<h2>2) Εισαγωγή Δεδομένων (JSON)</h2>
<form method="post" action="import_json.php" enctype="multipart/form-data">
    <label>Αρχείο JSON:<br><input type="file" name="json_file" accept=".json" required></label><br><br>
    <button type="submit">Μεταφόρτωση και Εισαγωγή</button>
</form>

</body>
</html>
