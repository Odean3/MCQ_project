<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/config.php';

// Fetch tests created by the teacher
$teacher_id = $_SESSION['user_id'];
$sql = "SELECT * FROM tests WHERE created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Welcome, Teacher <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

        <h2>Your Tests</h2>
        <?php if (empty($tests)): ?>
            <p>No tests found</p>
        <?php else: ?>
            <ul>
                <?php foreach ($tests as $test): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                        <a href="view_submissions.php"; ?>View & grade Submissions</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p><a href="create_test.php">Create a New Test</a></p>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>