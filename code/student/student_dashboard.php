<?php
session_start();

// Enable error reporting for debugging


// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

require '../includes/config.php';

$student_id = $_SESSION['user_id'];

// Fetch all tests
$tests = [];
$tests_stmt = $conn->prepare("SELECT id, test_name FROM tests");
$tests_stmt->execute();
$tests_result = $tests_stmt->get_result();
$tests = $tests_result->fetch_all(MYSQLI_ASSOC);
$tests_stmt->close();

// Fetch test attempts (using question->test relationship)
$attempts = [];
$attempts_stmt = $conn->prepare("
    SELECT q.test_id, COUNT(a.id) as questions_answered 
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.student_id = ? 
    GROUP BY q.test_id
");
$attempts_stmt->bind_param('i', $student_id);
$attempts_stmt->execute();
$attempts_result = $attempts_stmt->get_result();
while ($row = $attempts_result->fetch_assoc()) {
    $attempts[$row['test_id']] = $row;
}
$attempts_stmt->close();

// Fetch grades
$grades = [];
$grades_stmt = $conn->prepare("
    SELECT test_id, grade, feedback 
    FROM grades 
    WHERE student_id = ?
");
$grades_stmt->bind_param('i', $student_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();
while ($row = $grades_result->fetch_assoc()) {
    $grades[$row['test_id']] = $row;
}
$grades_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-card { margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .test-card .card-body { padding: 1.25rem; }
        .badge { font-size: 0.9rem; padding: 0.35em 0.65em; }
    </style>
</head>
<body>
    <?php include 'header_stud.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-3">Your Tests</h2>
                
                <?php if (empty($tests)): ?>
                    <div class="alert alert-info">No tests available</div>
                <?php else: ?>
                    <?php foreach ($tests as $test): ?>
                        <div class="card test-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                                        <?php if (isset($attempts[$test['id']])): ?>
                                            <small class="text-muted">
                                                <?php echo $attempts[$test['id']]['questions_answered']; ?> questions answered
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <?php if (isset($grades[$test['id']])): ?>
                                            <span class="badge bg-primary me-2">
                                                Grade: <?php echo $grades[$test['id']]['grade']; ?>%
                                            </span>
                                            <a href="view_results.php?test_id=<?php echo $test['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Details
                                            </a>
                                        <?php elseif (isset($attempts[$test['id']])): ?>
                                            <span class="badge bg-warning text-dark">
                                                Submitted (pending grading)
                                            </span>
                                        <?php else: ?>
                                            <a href="take_test.php?test_id=<?php echo $test['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                Take Test
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (isset($grades[$test['id']]['feedback']) && !empty($grades[$test['id']]['feedback'])): ?>
                                    <div class="mt-2 pt-2 border-top">
                                        <small><strong>Feedback:</strong> <?php echo htmlspecialchars($grades[$test['id']]['feedback']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Progress</h5>
                        <?php
                            $completed = count($grades);
                            $total = count($tests);
                            $percentage = ($total > 0) ? round(($completed/$total)*100) : 0;
                        ?>
                        <p class="card-text">Completed <?php echo $completed; ?> of <?php echo $total; ?> tests</p>
                        <div class="progress">
                            <div class="progress-bar" 
                                 role="progressbar" 
                                 style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>