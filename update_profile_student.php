// update_profile_student
<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$student_id = $_SESSION['user_id'];

// Ανάγνωση δεδομένων από τη φόρμα
$address = trim($_POST['address'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_mobile = trim($_POST['phone_mobile'] ?? '');
$phone_landline = trim($_POST['phone_landline'] ?? '');

// Έλεγχος: Αν όλα τα πεδία είναι κενά, αγνόησέ το
if ($address === '' && $email === '' && $phone_mobile === '' && $phone_landline === '') {
    header("Location: student_dashboard.php");
    exit;
}

// Έλεγχος αν υπάρχει ήδη εγγραφή για τον φοιτητή
$stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_id = ?");
$stmt->execute([$student_id]);
$existing = $stmt->fetchColumn();

if ($existing) {
    // Ενημέρωση υπάρχοντος προφίλ
    $stmt = $pdo->prepare("UPDATE student_profiles SET address = ?, email = ?, phone_mobile = ?, phone_landline = ? WHERE student_id = ?");
    $stmt->execute([$address, $email, $phone_mobile, $phone_landline, $student_id]);
} else {
    // Δημιουργία νέου προφίλ
    $stmt = $pdo->prepare("INSERT INTO student_profiles (student_id, address, email, phone_mobile, phone_landline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $address, $email, $phone_mobile, $phone_landline]);
}

// Επιστροφή στο dashboard
header("Location: student_dashboard.php");
exit;
