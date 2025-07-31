<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'secretariat') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['json_file'])) {
    die("Δεν υποβλήθηκε αρχείο.");
}

$jsonFile = $_FILES['json_file']['tmp_name'];
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (!$data || !is_array($data)) {
    die("Το αρχείο δεν είναι έγκυρο JSON.");
}

$inserted = 0;
$skipped = 0;

foreach ($data as $user) {
    $name = trim($user['name'] ?? '');
    $email = trim($user['email'] ?? '');
    $role = trim($user['role'] ?? '');
    $password = $user['password'] ?? 'default123';

    // Έλεγχος αν όλα υπάρχουν
    if ($name === '' || $email === '' || $role === '') {
        $skipped++;
        continue;
    }

    // Προαιρετικά: hashing αν το site σου το απαιτεί
    // $password = password_hash($password, PASSWORD_DEFAULT);

    // Έλεγχος αν υπάρχει ήδη το email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetchColumn() > 0) {
        $skipped++;
        continue;
    }

    // Εισαγωγή
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at)
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $password, $role]);
    $inserted++;
}

echo "✅ Εισαγωγή Ολοκληρώθηκε.<br>";
echo "➕ Νέοι χρήστες: $inserted<br>";
echo "⚠️ Παραλήφθηκαν: $skipped<br>";

echo "<p><a href='secretariat_dashboard.php'>Επιστροφή στο Dashboard</a></p>";
?>
