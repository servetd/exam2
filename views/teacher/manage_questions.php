<?php
$exam_id = $_GET['exam_id'];
$teacher_id = $_SESSION['user_id'];

// Fetch exam details and verify it belongs to the logged-in teacher
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = :id AND teacher_id = :teacher_id");
$exam_stmt->execute(['id' => $exam_id, 'teacher_id' => $teacher_id]);
$exam = $exam_stmt->fetch();

if (!$exam) {
    // Exam not found or doesn't belong to the teacher
    header('Location: index.php?page=exams');
    exit;
}

// Fetch existing questions for this exam
$questions_stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_number");
$questions_stmt->execute(['exam_id' => $exam_id]);
$questions = $questions_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management: <?php echo htmlspecialchars($exam['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Question Management: <small class="text-muted"><?php echo htmlspecialchars($exam['name']); ?></small></h1>
            <a href="index.php?page=exams" class="btn btn-secondary">Back to Exams</a>
        </div>

        <!-- Add Question Form -->
        <div class="card mb-4">
            <div class="card-header">Add New Question</div>
            <div class="card-body">
                <form action="src/teacher/question_action.php" method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="question_number" class="form-label">Question Number</label>
                            <input type="number" class="form-control" name="question_number" id="question_number" required>
                        </div>
                        <div class="col-md-10">
                            <label for="type" class="form-label">Question Type</label>
                            <select class="form-select" name="type" id="question_type_selector" required>
                                <option value="">Select...</option>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic fields based on question type -->
                    <div id="metadata_fields" class="mt-3"></div>

                    <button type="submit" name="action" value="add_question" class="btn btn-primary mt-3">Add Question</button>
                </form>
            </div>
        </div>

        <!-- Existing Questions -->
        <div class="card">
            <div class="card-header">Added Questions</div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>No</th><th>Type</th><th>Details</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($questions as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['question_number']); ?></td>
                            <td><?php echo htmlspecialchars($q['type']); ?></td>
                            <td><?php echo htmlspecialchars(substr($q['metadata'], 0, 50)) . '...'; ?></td>
                            <td><button class="btn btn-sm btn-danger" disabled>Delete</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($questions)): ?>
                        <tr><td colspan="4" class="text-center">No questions have been added to this exam yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>



    </div>

<script>
document.getElementById('question_type_selector').addEventListener('change', function() {
    const type = this.value;
    const container = document.getElementById('metadata_fields');
    let html = '';

    switch (type) {
        case 'multiple_choice':
            html = `
                <p class="fw-bold">Enter Options and Correct Answer:</p>
                <div class="row g-2 mb-2">
                    <div class="col-auto">A)</div><div class="col"><input type="text" name="options[A]" class="form-control" required></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-auto">B)</div><div class="col"><input type="text" name="options[B]" class="form-control" required></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-auto">C)</div><div class="col"><input type="text" name="options[C]" class="form-control"></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-auto">D)</div><div class="col"><input type="text" name="options[D]" class="form-control"></div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Correct Answer:</label>
                    <select name="answer" class="form-select" required>
                        <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                    </select>
                </div>
            `;
            break;
        case 'true_false':
            html = `
                <p class="fw-bold">Mark the Correct Answer:</p>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answer" id="true_answer" value="true" checked>
                    <label class="form-check-label" for="true_answer">True</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="answer" id="false_answer" value="false">
                    <label class="form-check-label" for="false_answer">False</label>
                </div>
            `;
            break;
        case 'essay':
            html = '<p class="text-muted">Essay questions are graded manually. No additional fields are needed.</p>';
            break;
    }
    container.innerHTML = html;
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
