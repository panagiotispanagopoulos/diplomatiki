<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$selected_student_id = $_GET['student_id'] ?? null;

// Ανάκτηση φοιτητών που έχουν ανατεθεί στον συγκεκριμένο καθηγητή
$stmt = $pdo->prepare("
    SELECT s.id AS student_id, s.name AS student_name, t.title
    FROM thesis_assignments t
    JOIN users s ON t.student_id = s.id
    WHERE t.professor_id = :professor_id AND t.status != 'Περατωμένη'
");
$stmt->execute([':professor_id' => $professor_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Μηνύματα Καθηγητή</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .chat-box {
            border: 1px solid #aaa;
            padding: 15px;
            margin-top: 20px;
            width: 500px;
            background-color: #f8f8f8;
        }
        .message {
            margin: 5px 0;
        }
        .from-me {
            text-align: right;
            color: blue;
        }
        .from-them {
            text-align: left;
            color: green;
        }
        .close-btn {
            float: right;
            color: red;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h1>📩 Μηνύματα Καθηγητή</h1>

<p><a href="professor_dashboard.php">⬅ Επιστροφή στον πίνακα</a></p>

<h2>Φοιτητές με Διπλωματικές Υπό Επιμέλεια</h2>
<?php if (count($students) > 0): ?>
    <ul>
        <?php foreach ($students as $stu): ?>
            <li>
                <strong><?php echo htmlspecialchars($stu['student_name']); ?></strong> - 
                <?php echo htmlspecialchars($stu['title']); ?> |
                <a href="messages.php?student_id=<?php echo $stu['student_id']; ?>">✉️ Συνομιλία</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Δεν υπάρχουν φοιτητές με ενεργή διπλωματική υπό την επίβλεψή σας.</p>
<?php endif; ?>

<?php if ($selected_student_id): ?>
    <?php
    // Επαλήθευση ότι ο φοιτητής έχει ανατεθεί στον συγκεκριμένο καθηγητή
    $stmt = $pdo->prepare("
        SELECT s.name
        FROM thesis_assignments t
        JOIN users s ON t.student_id = s.id
        WHERE t.professor_id = :prof_id AND s.id = :stu_id
    ");
    $stmt->execute([
        ':prof_id' => $professor_id,
        ':stu_id' => $selected_student_id
    ]);
    $student = $stmt->fetch();

    if ($student):
    ?>
    <div class="chat-box">
        <span class="close-btn" onclick="window.location.href='messages.php'">✖</span>
        <h3>Συνομιλία με: <?php echo htmlspecialchars($student['name']); ?></h3>

        <div>
            <?php
            $stmt = $pdo->prepare("
                SELECT m.*, u.name AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = :prof_id AND m.receiver_id = :stu_id)
                   OR (m.sender_id = :stu_id_2 AND m.receiver_id = :prof_id_2)
                ORDER BY m.sent_at ASC
            ");
            $stmt->execute([
                ':prof_id' => $professor_id,
                ':stu_id' => $selected_student_id,
                ':stu_id_2' => $selected_student_id,
                ':prof_id_2' => $professor_id
            ]);
            $messages = $stmt->fetchAll();

            if ($messages):
                foreach ($messages as $msg):
                    $isMe = $msg['sender_id'] == $professor_id;
                    echo '<div class="message ' . ($isMe ? 'from-me' : 'from-them') . '">';
                    echo '<strong>' . htmlspecialchars($msg['sender_name']) . ':</strong> ';
                    echo nl2br(htmlspecialchars($msg['message']));
                    echo '</div>';
                endforeach;
            else:
                echo "<p>Δεν υπάρχουν μηνύματα ακόμα.</p>";
            endif;
            ?>
        </div>

        <form method="post" action="send_message.php" style="margin-top: 15px;">
            <input type="hidden" name="receiver_id" value="<?php echo $selected_student_id; ?>">
            <textarea name="message" rows="4" cols="50" placeholder="Γράψε το μήνυμά σου..." required></textarea><br>
            <button type="submit">📨 Αποστολή</button>
        </form>
    </div>
    <?php else: ?>
        <p style="color:red;">⚠️ Δεν έχεις εξουσιοδότηση για συνομιλία με αυτόν τον φοιτητή.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
