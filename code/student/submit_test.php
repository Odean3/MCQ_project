<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

include '../includes/config.php';

$test_id = $_POST['test_id'];
$student_id = $_SESSION['user_id'];
$answers = $_POST['answer']; // Array of [question_id => selected_option]

if (empty($answers)) {
    die("No answers submitted.");
}

foreach ($answers as $question_id => $selected_option) {
    if (empty($selected_option)) continue;

    $sql = "INSERT INTO answers (student_id, question_id, selected_option) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $student_id, $question_id, $selected_option);
    
    if (!$stmt->execute()) {
        die("Error saving answer for question ID $question_id: " . $conn->error);
    }
    $stmt->close();
}

$_SESSION['message'] = "Test submitted successfully!";
header('Location: student_dashboard.php');
exit();
?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
