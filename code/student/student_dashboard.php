<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

include '../includes/config.php';

$sql = "SELECT * FROM tests";
$result = $conn->query($sql);
$tests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Welcome, Student <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

        <h2>Available Tests</h2>
        <?php if (empty($tests)): ?>
            <p>No tests available at the moment.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($tests as $test): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                        <a href="take_test.php?test_id=<?php echo $test['id']; ?>">Take Test</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php include '../footer.php'; ?>
</body>
</html>