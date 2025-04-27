<?php
session_start();



// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

require '../includes/config.php';

$student_id = $_SESSION['user_id'];
$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;

if ($test_id <= 0) {
    header('Location: student_dashboard.php');
    exit();
}

// Fetch test details
$test = [];
$stmt = $conn->prepare("SELECT test_name FROM tests WHERE id = ?");
$stmt->bind_param('i', $test_id);
$stmt->execute();
$stmt->bind_result($test['name']);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Test not found";
    header('Location: student_dashboard.php');
    exit();
}
$stmt->close();

// Fetch overall grade
$grade = [];
$stmt = $conn->prepare("SELECT grade, feedback FROM grades WHERE test_id = ? AND student_id = ?");
$stmt->bind_param('ii', $test_id, $student_id);
$stmt->execute();
$stmt->bind_result($grade['score'], $grade['feedback']);
$stmt->fetch();
$stmt->close();

// Fetch all questions and student answers
$questions = [];
$stmt = $conn->prepare("
    SELECT 
        q.id,
        q.question_text,
        q.option1,
        q.option2,
        q.option3,
        q.option4,
        q.correct_option,
        a.selected_option
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.student_id = ?
    WHERE q.test_id = ?
");
$stmt->bind_param('ii', $student_id, $test_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
$stmt->close();

// Calculate score breakdown
$total_questions = count($questions);
$correct_answers = 0;
foreach ($questions as $q) {
    if (isset($q['selected_option']) && $q['selected_option'] == $q['correct_option']) {
        $correct_answers++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css"/>
    <title>Test Results</title>
    
</head>
<body>
    <?php include 'header_stud.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Test Results: <?php echo htmlspecialchars($test['name']); ?></h1>
            <a href="student_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
        <section class="mb-4">
            <!-- Overall Score Card -->
            <div class="card score-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h3 class="mb-0"><?php echo isset($grade['score']) ? $grade['score'] : 0; ?>%</h3>
                            <p class="text-muted">Overall Score</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="mb-0"><?php echo $correct_answers; ?>/<?php echo $total_questions; ?></h3>
                            <p class="text-muted">Correct Answers</p>
                        </div>
                        <div class="col-md-4">
                            <?php if (isset($grade['feedback']) && !empty($grade['feedback'])): ?>
                                <p><strong>Teacher Feedback:</strong> <?php echo htmlspecialchars($grade['feedback']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-3">Question Details</h2>
            <?php foreach ($questions as $index => $q): ?>
                <div class="card question-card <?php echo (isset($q['selected_option']) && $q['selected_option'] == $q['correct_option']) ? 'correct' : 'incorrect'; ?>">
                    <div class="card-body">
                        <h5 class="card-title">Question <?php echo $index + 1; ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($q['question_text']); ?></p>
                        <ul class="options">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <?php 
                                $option = 'option' . $i;
                                $is_correct = ($i == $q['correct_option']);
                                $is_your_answer = (isset($q['selected_option']) && $q['selected_option'] == $i);
                                $classes = '';
                                if ($is_correct) $classes .= ' correct-answer';
                                if ($is_your_answer && !$is_correct) $classes .= ' your-answer';
                                ?>
                                <li class="<?php echo $classes; ?>">
                                    <span class="option-number"><?php echo $i ?>.</span>
                                    <span class="option-text"><?php echo htmlspecialchars($q[$option]); ?></span>
                                    <?php if ($is_correct): ?>
                                        <span class="badge bg-success float-end">Correct Answer</span>
                                    <?php elseif ($is_your_answer): ?>
                                        <span class="badge bg-danger float-end">Your Answer</span>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <?php if (isset($q['selected_option']) && $q['selected_option'] != $q['correct_option']): ?>
                            <div class="alert alert-warning mt-3">
                                <strong>Explanation:</strong> The correct answer was option <?php echo $q['correct_option']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
