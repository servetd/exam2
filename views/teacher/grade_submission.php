<?php
$assignment_id = $_GET['assignment_id'];
$teacher_id = $_SESSION['user_id'];

// Fetch assignment, exam, and student details with security check
$stmt = $pdo->prepare(
    "SELECT a.id as assignment_id, a.status, e.id as exam_id, e.name as exam_name, u.id as student_id, u.username as student_name 
     FROM exam_assignments a 
     JOIN exams e ON a.exam_id = e.id
     JOIN users u ON a.student_id = u.id
     WHERE a.id = :assignment_id AND e.teacher_id = :teacher_id"
);
$stmt->execute(['assignment_id' => $assignment_id, 'teacher_id' => $teacher_id]);
$submission_details = $stmt->fetch();

if (!$submission_details) {
    header('Location: index.php?page=evaluate_exams');
    exit;
}

// Fetch all questions for the exam
$questions_stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_number");
$questions_stmt->execute(['exam_id' => $submission_details['exam_id']]);
$questions = $questions_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

// Fetch all student answers for this assignment
$answers_stmt = $pdo->prepare("SELECT * FROM student_answers WHERE assignment_id = :assignment_id");
$answers_stmt->execute(['assignment_id' => $assignment_id]);
$student_answers = $answers_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puanlama: <?php echo htmlspecialchars($submission_details['student_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>Cevap Kağıdı</h1>
                <p class="lead"><strong>Öğrenci:</strong> <?php echo htmlspecialchars($submission_details['student_name']); ?><br>
                <strong>Sınav:</strong> <?php echo htmlspecialchars($submission_details['exam_name']); ?></p>
            </div>
            <a href="index.php?page=evaluate_submissions&exam_id=<?php echo $submission_details['exam_id']; ?>" class="btn btn-secondary">Geri Dön</a>
        </div>

        <form action="src/teacher/grading_action.php" method="POST">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
            <input type="hidden" name="exam_id" value="<?php echo $submission_details['exam_id']; ?>">

            <?php foreach ($questions as $qid => $q): 
                $answer = $student_answers[$qid] ?? null;
                $q_meta = json_decode($q['metadata'], true);
            ?>
            <div class="card mb-3">
                <div class="card-header"><strong>Soru <?php echo $q['question_number']; ?></strong> (<?php echo $q['type']; ?>)</div>
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Öğrenci Cevabı:</h6>
                    <?php if (!$answer): ?>
                        <p class="text-danger">Bu soru cevaplanmamış.</p>
                    <?php else: ?>
                        <?php if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false'): 
                            $is_correct = ($answer['answer_text'] == $q_meta['answer']);
                        ?>
                            <p style="font-size: 1.1rem;">
                                <?php echo htmlspecialchars($answer['answer_text']); ?>
                                <?php if ($is_correct): ?>
                                    <span class="badge bg-success">Doğru</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Yanlış</span> (Doğru Cevap: <?php echo htmlspecialchars($q_meta['answer']); ?>)
                                <?php endif; ?>
                            </p>
                        <?php elseif ($q['type'] === 'essay'): ?>
                            <?php if ($answer['answer_text']): ?>
                                <p class="border p-2 bg-light"><strong>Metin Cevabı:</strong><br><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                            <?php endif; ?>
                            <?php if ($answer['answer_file_url']): ?>
                                <p><a href="/Users/academic/Desktop/Labidary/Sınav/<?php echo $answer['answer_file_url']; ?>" target="_blank" class="btn btn-sm btn-info">Yüklenen Dosyayı Görüntüle</a></p>
                            <?php endif; ?>
                            <hr>
                            <div class="mb-3">
                                <label for="score-<?php echo $answer['id']; ?>" class="form-label"><strong>Puan:</strong></label>
                                <input type="number" name="scores[<?php echo $answer['id']; ?>]" id="score-<?php echo $answer['id']; ?>" class="form-control" style="width: 100px;" value="<?php echo htmlspecialchars($answer['score'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="d-grid">
                <button type="submit" name="action" value="save_grades" class="btn btn-primary btn-lg">Puanları Kaydet</button>
            </div>
        </form>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
