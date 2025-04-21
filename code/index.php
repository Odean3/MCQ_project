<?php
session_start();

include 'includes/config.php';

$logged_in = isset($_SESSION['user_id']);
$username = $logged_in ? $_SESSION['username'] : '';
$role = $logged_in ? $_SESSION['role'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Project - Home</title>
    
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Welcome to the MCQ Project</h1>

        <?php if ($logged_in): ?>
            <p>Hello, <?php echo htmlspecialchars($username); ?>! You are logged in as a <?php echo htmlspecialchars($role); ?>.</p>

            <?php if ($role == 'student'): ?>
                <!-- Student-specific content -->
                <p><a href="student/student_dashboard.php">Go to Student Dashboard</a></p>
            <?php elseif ($role == 'teacher'): ?>
                <!-- Teacher-specific content -->
                <p><a href="teacher/teacher_dashboard.php">Go to Teacher Dashboard</a></p>
            <?php endif; ?>

        <?php else: ?>
            <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to access the MCQ system.</p>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>