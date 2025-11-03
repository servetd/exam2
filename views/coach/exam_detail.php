<?php
$coach_id = $_SESSION['user_id'];
$assignment_id = $_GET['assignment_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

if (!$assignment_id || !$student_id) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Atamayı ve öğrenci kontrolünü yap
$assignment_stmt = $pdo->prepare(
    "SELECT a.*, ex.name as exam_name, ex.id as exam_id, s.name as subject_name
     FROM exam_assignments a
     JOIN exams ex ON a.exam_id = ex.id
     JOIN subjects s ON ex.subject_id = s.id
     WHERE a.id = :assignment_id AND a.student_id = :student_id"
);
$assignment_stmt->execute(['assignment_id' => $assignment_id, 'student_id' => $student_id]);
$assignment = $assignment_stmt->fetch();

if (!$assignment) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Öğrenci kontrolü - koça atanmış mı?
$student_check = $pdo->prepare(
    "SELECT u.id FROM users u
     INNER JOIN coach_students cs ON u.id = cs.student_id
     WHERE cs.coach_id = :coach_id AND u.id = :student_id"
);
$student_check->execute(['coach_id' => $coach_id, 'student_id' => $student_id]);
if (!$student_check->fetch()) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Sınav sorularını ve cevapları getir
$questions_stmt = $pdo->prepare(
    "SELECT eq.*, GROUP_CONCAT(eqt.topic_id) as topic_ids
     FROM exam_questions eq
     LEFT JOIN exam_question_topics eqt ON eq.id = eqt.exam_question_id
     WHERE eq.exam_id = :exam_id
     GROUP BY eq.id
     ORDER BY eq.id"
);
$questions_stmt->execute(['exam_id' => $assignment['exam_id']]);
$questions = $questions_stmt->fetchAll();

// Öğrencinin cevaplarını getir
$answers_stmt = $pdo->prepare(
    "SELECT * FROM student_answers WHERE assignment_id = :assignment_id"
);
$answers_stmt->execute(['assignment_id' => $assignment_id]);
$answers_list = $answers_stmt->fetchAll();
$student_answers = [];
foreach ($answers_list as $answer) {
    $student_answers[$answer['question_id']] = $answer;
}

// Konuları getir
$topics_stmt = $pdo->prepare("SELECT id, name FROM topics");
$topics_stmt->execute();
$topics_list = $topics_stmt->fetchAll();
$topics = [];
foreach ($topics_list as $topic) {
    $topics[$topic['id']] = $topic['name'];
}

// Toplam puanı hesapla
$total_score = 0;
$max_score = 0;
foreach ($questions as $q) {
    $max_score += 10; // Default 10 puan
    $answer = $student_answers[$q['id']] ?? null;
    if ($answer) {
        if ($q['type'] === 'essay') {
            $total_score += intval($answer['score']);
        } else {
            $meta = json_decode($q['metadata'], true);
            if ($meta && isset($meta['answer']) && $answer['answer_text'] == $meta['answer']) {
                $total_score += 10;
            }
        }
    }
}
$percentage = ($max_score > 0) ? ($total_score / $max_score) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assignment['exam_name']); ?> - Exam Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .question-correct {
            border-left: 5px solid #28a745;
        }
        .question-incorrect {
            border-left: 5px solid #dc3545;
        }
        .question-partial {
            border-left: 5px solid #ffc107;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($assignment['exam_name']); ?></h1>
                <p class="text-muted"><?php echo htmlspecialchars($assignment['subject_name']); ?></p>
            </div>
            <a href="index.php?page=coach_student_results&student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Back</a>
        </div>

        <!-- Score Summary -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Score Summary</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h6>Total Score</h6>
                        <h3><?php echo intval($total_score); ?> / <?php echo intval($max_score); ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h6>Success Rate</h6>
                        <h3><span class="badge bg-primary"><?php echo round($percentage); ?>%</span></h3>
                    </div>
                    <div class="col-md-4">
                        <h6>Success Status</h6>
                        <?php if ($percentage >= 80): ?>
                            <h3><span class="badge bg-success">Excellent</span></h3>
                        <?php elseif ($percentage >= 60): ?>
                            <h3><span class="badge bg-warning">Good</span></h3>
                        <?php else: ?>
                            <h3><span class="badge bg-danger">Poor</span></h3>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Details -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Question Details</h5>
            </div>
            <div class="card-body">
                <?php if (empty($questions)): ?>
                    <p class="text-muted">No questions found.</p>
                <?php else: ?>
                    <?php foreach ($questions as $index => $question): ?>
                        <?php
                        $answer = $student_answers[$question['id']] ?? null;
                        $meta = json_decode($question['metadata'], true);

                        // Cevap doğru mu?
                        $is_correct = false;
                        $question_class = '';
                        if ($question['type'] === 'essay') {
                            $is_correct = $answer && intval($answer['score']) > 0;
                            $question_class = $is_correct ? 'question-correct' : 'question-incorrect';
                        } else {
                            if ($answer && $meta && isset($meta['answer']) && $answer['answer_text'] == $meta['answer']) {
                                $is_correct = true;
                                $question_class = 'question-correct';
                            } else {
                                $question_class = 'question-incorrect';
                            }
                        }
                        ?>
                        <div class="card mb-3 <?php echo $question_class; ?>">
                            <div class="card-header">
                                <h6 class="mb-0">Question <?php echo $index + 1; ?>: <?php echo htmlspecialchars($question['question_text']); ?></h6>
                            </div>
                            <div class="card-body">
                                <!-- Topics -->
                                <?php if ($question['topic_ids']): ?>
                                    <div class="mb-2">
                                        <small><strong>Topics:</strong></small>
                                        <?php foreach (explode(',', $question['topic_ids']) as $tid): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($topics[$tid] ?? 'Unknown'); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Question Type -->
                                <p><strong>Question Type:</strong>
                                    <?php echo ucfirst(str_replace('_', ' ', $question['type'])); ?>
                                </p>

                                <!-- Options (Multiple Choice / True-False) -->
                                <?php if ($question['type'] === 'multiple_choice' || $question['type'] === 'true_false'): ?>
                                    <div class="mb-2">
                                        <strong>Options:</strong>
                                        <?php if ($meta && isset($meta['options']) && is_array($meta['options'])): ?>
                                            <ul>
                                                <?php foreach ($meta['options'] as $idx => $option):
                                                    $letter = chr(65 + $idx); // A, B, C, D, E
                                                    $is_correct_option = ($meta['answer'] === $letter);
                                                    $is_selected = $answer && $answer['answer_text'] === $letter;
                                                    ?>
                                                    <li class="<?php echo $is_correct_option ? 'text-success fw-bold' : ''; ?><?php echo $is_selected && !$is_correct_option ? ' text-danger' : ''; ?>">
                                                        <?php echo $letter; ?>) <?php echo htmlspecialchars($option); ?>
                                                        <?php if ($is_correct_option): ?> <span class="badge bg-success">✓ Correct</span><?php endif; ?>
                                                        <?php if ($is_selected && !$is_correct_option): ?> <span class="badge bg-danger">✗ Selected</span><?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No options found.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Student Answer -->
                                <?php if ($answer): ?>
                                    <div class="alert alert-info">
                                        <strong>Student's Answer:</strong>
                                        <?php if ($question['type'] === 'essay'): ?>
                                            <p><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                                            <?php if ($answer['score'] > 0): ?>
                                                <p class="text-success"><strong>Score: <?php echo intval($answer['score']); ?>/10</strong></p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p><?php echo htmlspecialchars($answer['answer_text']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <strong>No answer provided</strong>
                                    </div>
                                <?php endif; ?>

                                <!-- Correct/Wrong Indicator -->
                                <div class="mt-2">
                                    <?php if ($is_correct): ?>
                                        <span class="badge bg-success">✓ Correct Answer</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✗ Wrong Answer</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
