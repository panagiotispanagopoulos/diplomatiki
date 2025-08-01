// dashboard
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['user_role'];

switch ($role) {
    case 'student':
        header("Location: student_dashboard.php");
        break;
    case 'professor':
        header("Location: professor_dashboard.php");
        break;
    case 'secretariat':
        header("Location: secretariat_dashboard.php");
        break;
    default:
        echo "Άγνωστος ρόλος.";
        exit;
}
?>
