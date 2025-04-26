<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

include '../includes/config.php';

$test_id = $_GET['test_id'];
$student_id = $_SESSION['user_id'];

// Check if the student has already taken this test
$check_sql = "SELECT COUNT(*) AS count FROM answers a JOIN questions q ON a.question_id = q.id WHERE a.student_id = ? AND q.test_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $student_id, $test_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if ($check_result['count'] > 0) {
    // Student has already taken the test
    $test_name_sql = "SELECT test_name FROM tests WHERE id = ?";
    $test_name_stmt = $conn->prepare($test_name_sql);
    $test_name_stmt->bind_param('i', $test_id);
    $test_name_stmt->execute();
    $test_name_result = $test_name_stmt->get_result()->fetch_assoc();
    $test_name = $test_name_result ? htmlspecialchars($test_name_result['test_name']) : 'this test';
    $test_name_stmt->close();

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Test Already Taken</title>
        <link rel='stylesheet' href='../css/styles.css'>
    </head>
    <body>";
    include 'header_stud.php';
    echo "<div class='container'>
            <h1>Test Already Taken</h1>
            <p>You have already completed " . $test_name . ".</p>
            <p><a href='student_dashboard.php'>Go back to Dashboard</a></p>
          </div>";
    include __DIR__ . '/../includes/footer.php';
    echo "</body></html>";
    exit();
}


$sql = "SELECT * FROM questions WHERE test_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $test_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Test</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php include 'header_stud.php'; ?>

    <div class="container">
        <h1>Take Test</h1>
        <form action="submit_test.php" method="POST">
            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
            <?php foreach ($questions as $question): ?>
                <div>
                    <p><strong><?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="1"> <?php echo htmlspecialchars($question['option1']); ?><br>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="2"> <?php echo htmlspecialchars($question['option2']); ?><br>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="3"> <?php echo htmlspecialchars($question['option3']); ?><br>
                    <input type="radio" name="answer[<?php echo $question['id']; ?>]" value="4"> <?php echo htmlspecialchars($question['option4']); ?><br>
                </div>
            <?php endforeach; ?>
            <div>
                <button type="submit" name="submit_test">Submit Test</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
