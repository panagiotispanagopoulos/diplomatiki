<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$professor_name = $_SESSION['user_name'];

// Αρχικοποίηση μεταβλητών
$stats_total = ['supervisor' => 0, 'member' => 0];
$stats_grade = ['supervisor' => 0, 'member' => 0];

try {
    // Query για ΣΥΝΟΛΙΚΟ ΠΛΗΘΟΣ (από τον πίνακα thesis_assignments)
    $stmt_total = $pdo->prepare("
        SELECT cm.role, COUNT(ta.id) as total_count
        FROM thesis_assignments ta
        JOIN committee_members cm ON ta.id = cm.diploma_id
        WHERE cm.professor_id = ? AND ta.status = 'Περατωμένη'
        GROUP BY cm.role
    ");
    $stmt_total->execute([$professor_id]);
    $results_total = $stmt_total->fetchAll();
    foreach ($results_total as $row) {
        $stats_total[$row['role']] = (int) $row['total_count'];
    }

    // Query για ΜΕΣΟ ΒΑΘΜΟ (υπολογισμένος από τον πίνακα grades)
    $stmt_grade = $pdo->prepare("
        SELECT cm.role, AVG(g.grade) as avg_grade
        FROM thesis_assignments ta
        JOIN committee_members cm ON ta.id = cm.diploma_id
        JOIN grades g ON ta.id = g.diploma_id
        WHERE cm.professor_id = ? AND ta.status = 'Περατωμένη'
        GROUP BY cm.role
    ");
    $stmt_grade->execute([$professor_id]);
    $results_grade = $stmt_grade->fetchAll();
    foreach ($results_grade as $row) {
        $stats_grade[$row['role']] = round((float) $row['avg_grade'], 2);
    }
    
    // Ο ΥΠΟΛΟΓΙΣΜΟΣ ΤΟΥ ΜΕΣΟΥ ΧΡΟΝΟΥ ΕΙΝΑΙ ΑΔΥΝΑΤΟΣ ΜΕ ΤΗΝ ΤΡΕΧΟΥΣΑ ΔΟΜΗ
    // Ο πινακας `thesis_assignments` δεν έχει πεδίο `completion_date`.
    $stats_time = ['supervisor' => 0, 'member' => 0];

} catch (PDOException $e) {
    die("Σφάλμα κατά την ανάκτηση των στατιστικών: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Στατιστικά Διπλωματικών - <?php echo htmlspecialchars($professor_name); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; }
        h1, h2 { text-align: center; }
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            margin-top: 30px;
        }
        .chart-box {
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<p style="text-align:right;"><a href="professor_dashboard.php">🔙 Επιστροφή στο Dashboard</a></p>

<h1>Στατιστικά για τον/την <?php echo htmlspecialchars($professor_name); ?></h1>

<div class="charts-container">
    
    <div class="chart-box">
        <h2>Συνολικό Πλήθος Διπλωματικών</h2>
        <canvas id="chartTotalCount"></canvas>
    </div>

    <div class="chart-box">
        <h2>Μέσος Βαθμός</h2>
        <canvas id="chartAverageGrade"></canvas>
    </div>

    <!-- Το παρακάτω γράφημα είναι απενεργοποιημένο διότι λείπουν δεδομένα από τη βάση -->
    <!--
    <div class="chart-box">
        <h2>Μέσος Χρόνος Περάτωσης (Ημέρες)</h2>
        <p style="text-align:center; color:red;">Ο υπολογισμός είναι αδύνατος. Λείπει η ημερομηνία ολοκλήρωσης από τη βάση.</p>
        <canvas id="chartAverageTime"></canvas>
    </div>
    -->

</div>


<script>
// --- Γράφημα 1: Συνολικό Πλήθος ---
const ctxTotal = document.getElementById('chartTotalCount').getContext('2d');
new Chart(ctxTotal, {
    type: 'bar',
    data: {
        labels: ['Ως Επιβλέπων/ουσα', 'Ως Μέλος Επιτροπής'],
        datasets: [{
            label: 'Πλήθος Διπλωματικών',
            data: [
                <?php echo $stats_total['supervisor']; ?>,
                <?php echo $stats_total['member']; ?>
            ],
            backgroundColor: ['rgba(54, 162, 235, 0.6)', 'rgba(255, 99, 132, 0.6)'],
            borderColor: ['rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// --- Γράφημα 2: Μέσος Βαθμός ---
const ctxGrade = document.getElementById('chartAverageGrade').getContext('2d');
new Chart(ctxGrade, {
    type: 'bar',
    data: {
        labels: ['Ως Επιβλέπων/ουσα', 'Ως Μέλος Επιτροπής'],
        datasets: [{
            label: 'Μέσος Βαθμός',
            data: [
                <?php echo $stats_grade['supervisor']; ?>,
                <?php echo $stats_grade['member']; ?>
            ],
            backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 159, 64, 0.6)'],
            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 159, 64, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 10 } }
    }
});

// --- Γράφημα 3: Απενεργοποιημένο ---
/*
const ctxTime = document.getElementById('chartAverageTime').getContext('2d');
new Chart(ctxTime, {
    type: 'bar',
    data: {
        // ... Δεδομένα ...
    },
});
*/
</script>

</body>
</html>

// Μου έχει δώσει και άλλη έκδοση του κώδικα γιατί λέει ότι η βάση είναι προβληματική
// Δε θα έπρεπε να υπάρχει ο πίνακας `thesis_assignments` με πεδίο `completion_date` για να υπολογιστεί ο μέσος χρόνος
// Επίσης, ο πίνακας `grades` δεν έχει πεδίο `
