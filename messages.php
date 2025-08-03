<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Εύρεση συνεργάτη (μόνο αν υπάρχει ανάθεση)
if ($user_role === 'student') {
    $stmt = $pdo->prepare("
        SELECT u.id AS partner_id, u.name
        FROM thesis_assignments ta
        JOIN topics t ON ta.title = t.title
        JOIN users u ON t.professor_id = u.id
        WHERE ta.student_id = ?
        LIMIT 1
    ");
} elseif ($user_role === 'professor') {
    $stmt = $pdo->prepare("
        SELECT u.id AS partner_id, u.name
        FROM thesis_assignments ta
        JOIN users u ON ta.student_id = u.id
        JOIN topics t ON ta.title = t.title
        WHERE t.professor_id = ?
        LIMIT 1
    ");
} else {
    die("Μη εξουσιοδοτημένη πρόσβαση.");
}

$stmt->execute([$user_id]);
$partner = $stmt->fetch();

if (!$partner) {
    die("Δεν υπάρχει συνεργασία για αποστολή μηνυμάτων.");
}

$partner_id = $partner['partner_id'];
$partner_name = $partner['name'];

// Αποστολή μηνύματος
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg !== '') {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $partner_id, $msg]);
    }
}

// Λήψη μηνυμάτων
$stmt = $pdo->prepare("
    SELECT m.*, u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY m.sent_at ASC
");
$stmt->execute([$user_id, $partner_id, $partner_id, $user_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Μηνύματα</title>
</head>
<body>
<h2>Συνομιλία με: <?php echo htmlspecialchars($partner_name); ?></h2>

<div style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:auto;">
    <?php foreach ($messages as $m): ?>
        <p><strong><?php echo htmlspecialchars($m['sender_name']); ?>:</strong> 
           <?php echo nl2br(htmlspecialchars($m['message'])); ?> 
           <em style="color:gray;">[<?php echo $m['sent_at']; ?>]</em></p>
    <?php endforeach; ?>
</div>

<form method="post">
    <textarea name="message" rows="4" cols="50" required></textarea><br>
    <button type="submit">Αποστολή</button>
</form>

<p><a href="<?php echo ($user_role === 'student') ? 'student_dashboard.php' : 'professor_dashboard.php'; ?>">← Επιστροφή στο dashboard</a></p>
</body>
</html>
