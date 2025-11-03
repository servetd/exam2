<?php
$coach_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Öğrenci kontrolü - koça atanmış mı?
$student_check = $pdo->prepare(
    "SELECT u.id, u.username FROM users u
     INNER JOIN coach_students cs ON u.id = cs.student_id
     WHERE cs.coach_id = :coach_id AND u.id = :student_id AND u.role = 'student'"
);
$student_check->execute(['coach_id' => $coach_id, 'student_id' => $student_id]);
$student = $student_check->fetch();

if (!$student) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Öğrencinin graded sınavlarını getir (sonuç görmek için)
$exams_stmt = $pdo->prepare(
    "SELECT DISTINCT ex.id, ex.name, s.name as subject_name, a.status, a.id as assignment_id,
            COUNT(eq.id) as total_questions,
            SUM(CASE WHEN sa.score IS NOT NULL AND sa.score > 0 THEN 1 ELSE 0 END) as correct_answers
     FROM exam_assignments a
     JOIN exams ex ON a.exam_id = ex.id
     JOIN subjects s ON ex.subject_id = s.id
     LEFT JOIN exam_questions eq ON ex.id = eq.exam_id
     LEFT JOIN student_answers sa ON a.id = sa.assignment_id AND eq.id = sa.question_id
     WHERE a.student_id = :student_id AND a.status = 'graded'
     GROUP BY a.id, ex.id, ex.name, s.name, a.status
     ORDER BY ex.name"
);
$exams_stmt->execute(['student_id' => $student_id]);
$exams = $exams_stmt->fetchAll();

// Genel istatistikler hesapla
$total_exams = count($exams);
$total_questions = 0;
$total_correct = 0;

foreach ($exams as $exam) {
    $total_questions += ($exam['total_questions'] ?? 0);
    $total_correct += ($exam['correct_answers'] ?? 0);
}

$overall_percentage = $total_questions > 0 ? round(($total_correct / $total_questions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['username']); ?> - Coach Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($student['username']); ?></h1>
                <p class="text-muted">Exam Results</p>
            </div>
            <a href="index.php?page=coach_students" class="btn btn-secondary">Back to Students</a>
        </div>

        <!-- General Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Total Exams</h6>
                        <h3><?php echo $total_exams; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Total Questions</h6>
                        <h3><?php echo $total_questions; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Correct Answers</h6>
                        <h3><?php echo $total_correct; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Overall Success</h6>
                        <h3><span class="badge bg-primary"><?php echo $overall_percentage; ?>%</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam List -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Graded Exams</h5>
            </div>
            <div class="card-body">
                <?php if (empty($exams)): ?>
                    <p class="text-muted">No graded exams found for this student.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Subject</th>
                                    <th>Total Questions</th>
                                    <th>Correct Answers</th>
                                    <th>Success Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                    <?php
                                    $exam_percentage = ($exam['total_questions'] > 0) ?
                                        round(($exam['correct_answers'] / $exam['total_questions']) * 100) : 0;
                                    $percentage_class = 'bg-danger';
                                    if ($exam_percentage >= 80) $percentage_class = 'bg-success';
                                    elseif ($exam_percentage >= 60) $percentage_class = 'bg-warning';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                        <td><?php echo $exam['total_questions'] ?? 0; ?></td>
                                        <td><?php echo $exam['correct_answers'] ?? 0; ?></td>
                                        <td>
                                            <span class="badge <?php echo $percentage_class; ?>"><?php echo $exam_percentage; ?>%</span>
                                        </td>
                                        <td>
                                            <a href="index.php?page=coach_exam_detail&assignment_id=<?php echo $exam['assignment_id']; ?>&student_id=<?php echo $student_id; ?>"
                                               class="btn btn-sm btn-info">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analysis Button -->
        <div class="mt-3">
            <a href="index.php?page=coach_student_analysis&student_id=<?php echo $student_id; ?>"
               class="btn btn-lg btn-success">
                📊 View Topic Analysis
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
