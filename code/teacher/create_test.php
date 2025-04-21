<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/config.php';

$test_name = '';
$questions = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_test'])) {
    $test_name = trim($_POST['test_name']);
    $teacher_id = $_SESSION['user_id'];

    if (empty($test_name)) {
        $errors[] = "Test name is required.";
    } else {
        $sql = "INSERT INTO tests (test_name, created_by) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $test_name, $teacher_id);

        if ($stmt->execute()) {
            $test_id = $stmt->insert_id; 
            header("Location: create_test.php?test_id=$test_id");
            exit();
        } else {
            $errors[] = "Error creating test.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $test_id = $_POST['test_id'];
    $question_text = trim($_POST['question_text']);
    $option1 = trim($_POST['option1']);
    $option2 = trim($_POST['option2']);
    $option3 = trim($_POST['option3']);
    $option4 = trim($_POST['option4']);
    $correct_option = $_POST['correct_option'];

    if (empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4)) {
        $errors[] = "All fields are required.";
    } elseif (!in_array($correct_option, [1, 2, 3, 4])) {
        $errors[] = "Invalid correct option.";
    } else {
        $sql = "INSERT INTO questions (test_id, question_text, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssi', $test_id, $question_text, $option1, $option2, $option3, $option4, $correct_option);

        if ($stmt->execute()) {
            // Refresh the page to show the newly added question
            header("Location: create_test.php?test_id=$test_id");
            exit();
        } else {
            $errors[] = "Error adding question.";
        }
        $stmt->close();
    }
}

if (isset($_GET['test_id'])) {
    $test_id = $_GET['test_id'];
    $sql = "SELECT * FROM questions WHERE test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Create Test</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($_GET['test_id'])): ?>
            <form action="create_test.php" method="POST">
                <div>
                    <label for="test_name">Test Name:</label>
                    <input type="text" name="test_name" id="test_name" value="<?php echo htmlspecialchars($test_name); ?>" required>
                </div>
                <div>
                    <button type="submit" name="create_test">Create Test</button>
                </div>
            </form>
        <?php else: ?>
            <h2>Add Questions to Test</h2>
            <form action="create_test.php" method="POST">
                <input type="hidden" name="test_id" value="<?php echo $_GET['test_id']; ?>">
                <div>
                    <label for="question_text">Question:</label>
                    <textarea name="question_text" id="question_text" required></textarea>
                </div>
                <div>
                    <label for="option1">Option 1:</label>
                    <input type="text" name="option1" id="option1" required>
                </div>
                <div>
                    <label for="option2">Option 2:</label>
                    <input type="text" name="option2" id="option2" required>
                </div>
                <div>
                    <label for="option3">Option 3:</label>
                    <input type="text" name="option3" id="option3" required>
                </div>
                <div>
                    <label for="option4">Option 4:</label>
                    <input type="text" name="option4" id="option4" required>
                </div>
                <div>
                    <label for="correct_option">Correct Option:</label>
                    <select name="correct_option" id="correct_option" required>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>
                <div>
                    <button type="submit" name="add_question">Add Question</button>
                </div>
            </form>

            <h2>Existing Questions</h2>
            <?php if (empty($questions)): ?>
                <p>No questions added yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($questions as $question): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($question['question_text']); ?></strong>
                            <ul>
                                <li><?php echo htmlspecialchars($question['option1']); ?></li>
                                <li><?php echo htmlspecialchars($question['option2']); ?></li>
                                <li><?php echo htmlspecialchars($question['option3']); ?></li>
                                <li><?php echo htmlspecialchars($question['option4']); ?></li>
                            </ul>
                            <p>Correct Option: <?php echo $question['correct_option']; ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>