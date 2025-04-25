<?php
session_start();
require '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $test_id = (int)$_POST['test_id'];
    $student_id = (int)$_POST['student_id'];
    $grade = (int)$_POST['grade'];
    
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, test_id, grade) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE grade = VALUES(grade)
    ");
    $stmt->bind_param('iii', $student_id, $test_id, $grade);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['message'] = "Grade saved successfully!";
    header("Location: view_submissions.php?test_id=$test_id");
    exit();
}

$current_test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;

$teacher_id = $_SESSION['user_id'];
$tests = [];
$stmt = $conn->prepare("SELECT id, test_name FROM tests WHERE created_by = ?");
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}
$stmt->close();

$submissions = [];
if ($current_test_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.id as student_id, u.username, 
               COUNT(a.id) as answered, 
               SUM(a.selected_option = q.correct_option) as correct,
               g.grade
        FROM users u
        JOIN answers a ON u.id = a.student_id
        JOIN questions q ON a.question_id = q.id AND q.test_id = ?
        LEFT JOIN grades g ON g.student_id = u.id AND g.test_id = ?
        WHERE u.role = 'student'
        GROUP BY u.id
    ");
    $stmt->bind_param('ii', $current_test_id, $current_test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['percentage'] = $row['answered'] > 0 ? round(($row['correct'] / $row['answered']) * 100) : 0;
        $submissions[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submissions</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .submission { margin: 15px 0; padding: 15px; border: 1px solid #ddd; }
        .grade-form { display: inline-block; margin-left: 20px; }
        .grade-input { width: 50px; }
        .correct { color: green; }
        .incorrect { color: red; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Student Submissions</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <form method="get">
            <label for="test_id">Select Test:</label>
            <select name="test_id" id="test_id" onchange="this.form.submit()">
                <option value="">-- Select Test --</option>
                <?php foreach ($tests as $test): ?>
                    <option value="<?php echo $test['id']; ?>" 
                        <?php if ($test['id'] == $current_test_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($test['test_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($current_test_id > 0): ?>
            <h2>Submissions</h2>
            <?php if (empty($submissions)): ?>
                <p>No submissions yet for this test.</p>
            <?php else: ?>
                <?php foreach ($submissions as $sub): ?>
                    <div class="submission">
                        <h3><?php echo htmlspecialchars($sub['username']); ?></h3>
                        <p>
                            Score: <span class="<?php echo ($sub['percentage'] >= 50) ? 'correct' : 'incorrect'; ?>">
                                <?php echo $sub['percentage']; ?>%
                            </span>
                            (<?php echo $sub['correct']; ?>/<?php echo $sub['answered']; ?> correct)
                        </p>
                        
                        <form class="grade-form" method="post">
                            <input type="hidden" name="test_id" value="<?php echo $current_test_id; ?>">
                            <input type="hidden" name="student_id" value="<?php echo $sub['student_id']; ?>">
                            <label>
                                Grade:
                                <input type="number" name="grade" class="grade-input" 
                                       min="0" max="100" value="<?php echo $sub['grade'] ?? ''; ?>" required>
                            </label>
                            <button type="submit" name="submit_grade">Save</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
