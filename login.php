<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Αν είναι ήδη συνδεδεμένος, τον ανακατευθύνουμε
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Σύνδεση Χρήστη</title>
    <style>
        body {
            font-family: Arial;
            max-width: 400px;
            margin: 40px auto;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<h2>Σύνδεση Χρήστη</h2>

<form method="post" action="check_login.php">
    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="password">Κωδικός:</label>
    <input type="password" name="password" required>

    <button type="submit">Σύνδεση</button>
</form>

<?php if (isset($_GET['error'])): ?>
    <p class="error">Λάθος email ή κωδικός!</p>
<?php endif; ?>

</body>
</html>
