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
    <?php include 'header_stud.php'; ?>

    <div class="container">
        <h1 class="mb-3">Welcome, Student <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

        <h2>Available Tests</h2>
        <?php if (empty($tests)): ?>
            <p>No tests found</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($tests as $test): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                        <a href="take_test.php?test_id=<?php echo $test['id']; ?>" class="btn btn-primary btn-sm">Take Test</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
