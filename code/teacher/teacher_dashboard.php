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

// Fetch student names and their grades from the 'grades' table
$sql_results = "SELECT g.grade, u.username AS student_name, t.test_name
                FROM grades g
                JOIN users u ON g.student_id = u.id
                JOIN tests t ON g.test_id = t.id
                WHERE t.created_by = ?";
$stmt_results = $conn->prepare($sql_results);
$stmt_results->bind_param('i', $teacher_id);
$stmt_results->execute();
$result_results = $stmt_results->get_result();
$student_grades = $result_results->fetch_all(MYSQLI_ASSOC);
$stmt_results->close();

// Get selected test ID from the form submission
$selected_test_id = isset($_POST['test_id']) ? (int)$_POST['test_id'] : null;

// Fetch student names and their grades based on selected test
$sql_filtered_grades = "SELECT g.grade, u.username AS student_name
                        FROM grades g
                        JOIN users u ON g.student_id = u.id
                        WHERE g.test_id = ?";
$stmt_filtered_grades = $conn->prepare($sql_filtered_grades);
$filtered_grades = [];
if ($selected_test_id) {
    $stmt_filtered_grades->bind_param('i', $selected_test_id);
    $stmt_filtered_grades->execute();
    $result_filtered_grades = $stmt_filtered_grades->get_result();
    $filtered_grades = $result_filtered_grades->fetch_all(MYSQLI_ASSOC);
}
$stmt_filtered_grades->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header_teach.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-3">Welcome, Teacher <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

        <h2>Your Tests</h2>
        <?php if (empty($tests)): ?>
            <p>No tests found</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($tests as $test): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                        <a href="view_submissions.php?test_id=<?php echo $test['id']; ?>" class="btn btn-info btn-sm">View & Grade Submissions</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 class="mt-4">Student Grades by Test</h2>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="testSelect" class="form-label">Select a Test:</label>
                <select class="form-select" id="testSelect" name="test_id" onchange="this.form.submit()">
                    <option value="">-- Select Test --</option>
                    <?php foreach ($tests as $test): ?>
                        <option value="<?php echo $test['id']; ?>" <?php echo ($selected_test_id == $test['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($test['test_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($selected_test_id && empty($filtered_grades)): ?>
            <p>No grades found for the selected test yet.</p>
        <?php elseif ($selected_test_id): ?>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_grades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
             <p>Select a test from the dropdown to view grades.</p>
        <?php endif; ?>

        <p class="mt-4"><a href="create_test.php" class="btn btn-primary">Create a New Test</a></p>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
