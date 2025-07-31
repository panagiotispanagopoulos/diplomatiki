<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$professor_id = $_SESSION['user_id'];

// Ανάγνωση δεδομένων από τη φόρμα
$address = trim($_POST['address'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone_mobile = trim($_POST['phone_mobile'] ?? '');
$phone_landline = trim($_POST['phone_landline'] ?? '');

// Αν όλα κενά, επέστρεψε
if ($address === '' && $email === '' && $phone_mobile === '' && $phone_landline === '') {
    header("Location: professor_dashboard.php");
    exit;
}

// Έλεγχος αν υπάρχει ήδη εγγραφή
$stmt = $pdo->prepare("SELECT id FROM professor_profiles WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $stmt = $pdo->prepare("UPDATE professor_profiles SET address = ?, email = ?, phone_mobile = ?, phone_landline = ? WHERE professor_id = ?");
    $stmt->execute([$address, $email, $phone_mobile, $phone_landline, $professor_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO professor_profiles (professor_id, address, email, phone_mobile, phone_landline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$professor_id, $address, $email, $phone_mobile, $phone_landline]);
}

header("Location: professor_dashboard.php");
exit;
?>
