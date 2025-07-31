<?php
$host = 'localhost';
$db   = 'diplomatiki_db';
$user = 'root';
$pass = ''; // Το MAMP default δεν έχει κωδικό για root
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // για debugging
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // επιστροφή ως πίνακες
    PDO::ATTR_EMULATE_PREPARES   => false,                   // ασφάλεια
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "❌ Σφάλμα σύνδεσης: " . $e->getMessage();
    exit;
}
?>
