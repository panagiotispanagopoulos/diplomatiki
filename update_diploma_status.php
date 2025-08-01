// update_diploma_status
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secretariat') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Μη έγκυρη πρόσβαση.");
}

$diploma_id = $_POST['diploma_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$diploma_id || !$action) {
    die("Λείπουν απαιτούμενα δεδομένα.");
}

$secretariat_id = $_SESSION['user_id'];

switch ($action) {
    case 'confirm_assignment':
        $gs_code = trim($_POST['gs_code'] ?? '');
        $gs_year = trim($_POST['gs_year'] ?? '');

        if ($gs_code === '' || $gs_year === '') {
            die("Πρέπει να συμπληρώσετε τον αριθμό και το έτος ΓΣ.");
        }

        // Λήψη στοιχείων από thesis_assignments
        $stmt = $pdo->prepare("SELECT * FROM thesis_assignments WHERE id = ?");
        $stmt->execute([$diploma_id]);
        $thesis = $stmt->fetch();

        if (!$thesis) {
            die("Δεν βρέθηκε η διπλωματική.");
        }

        $topic_id = null;

        // Προσπάθεια εύρεσης topic από topics table (με βάση τον student_id)
        $stmt = $pdo->prepare("SELECT topic_id FROM diplomas WHERE student_id = ?");
        $stmt->execute([$thesis['student_id']]);
        $topic_id = $stmt->fetchColumn();

        // Αν δεν υπάρχει topic από πριν, δοκιμάζουμε μέσω thesis_assignments
        if (!$topic_id) {
            $stmt = $pdo->prepare("SELECT topic_id FROM topics WHERE professor_id = (SELECT professor_id FROM committee_members WHERE diploma_id = ? LIMIT 1)");
            $stmt->execute([$diploma_id]);
            $topic_id = $stmt->fetchColumn() ?: 1; // Προεπιλογή fallback topic_id
        }

        // Δημιουργία εγγραφής στο diplomas αν δεν υπάρχει
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM diplomas WHERE student_id = ?");
        $stmt->execute([$thesis['student_id']]);
        $diploma_exists = $stmt->fetchColumn();

        if (!$diploma_exists) {
            $stmt = $pdo->prepare("INSERT INTO diplomas (topic_id, student_id, status, start_date, created_at)
                                   VALUES (?, ?, 'active', ?, NOW())");
            $stmt->execute([
                $topic_id,
                $thesis['student_id'],
                $thesis['assigned_date'] ?? date('Y-m-d')
            ]);
        }

        // Καταγραφή σημείωσης
        $note_text = "Ανάθεση διπλωματικής με απόφαση ΓΣ {$gs_code}/{$gs_year}.";
        $stmt = $pdo->prepare("INSERT INTO notes (diploma_id, professor_id, note) VALUES (?, ?, ?)");
        $stmt->execute([$diploma_id, $secretariat_id, $note_text]);

        echo "✅ Η ανάθεση καταχωρήθηκε επιτυχώς.";
        break;

    case 'cancel_assignment':
        $gs_code = trim($_POST['gs_code'] ?? '');
        $gs_year = trim($_POST['gs_year'] ?? '');
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');

        if ($gs_code === '' || $gs_year === '' || $cancel_reason === '') {
            die("Πρέπει να συμπληρώσετε όλα τα πεδία για την ακύρωση.");
        }

        $stmt = $pdo->prepare("UPDATE diplomas SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$diploma_id]);

        $note_text = "Ακύρωση διπλωματικής με απόφαση ΓΣ {$gs_code}/{$gs_year}. Λόγος: {$cancel_reason}";
        $stmt = $pdo->prepare("INSERT INTO notes (diploma_id, professor_id, note) VALUES (?, ?, ?)");
        $stmt->execute([$diploma_id, $secretariat_id, $note_text]);

        echo "⚠️ Η διπλωματική ακυρώθηκε επιτυχώς.";
        break;

    case 'complete_diploma':
        $stmt = $pdo->prepare("UPDATE diplomas SET status = 'completed' WHERE id = ?");
        $stmt->execute([$diploma_id]);

        $note_text = "Η διπλωματική καταχωρήθηκε ως περατωμένη από τη Γραμματεία.";
        $stmt = $pdo->prepare("INSERT INTO notes (diploma_id, professor_id, note) VALUES (?, ?, ?)");
        $stmt->execute([$diploma_id, $secretariat_id, $note_text]);

        echo "🏁 Η διπλωματική καταχωρήθηκε ως περατωμένη.";
        break;

    default:
        echo "Άγνωστη ενέργεια.";
        exit;
}
