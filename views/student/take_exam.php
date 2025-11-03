<?php
$assignment_id = $_GET['assignment_id'];
$student_id = $_SESSION['user_id'];

// Güvenlik: Atamanın bu öğrenciye ait olduğunu ve hala aktif olduğunu doğrula
$assignment_stmt = $pdo->prepare(
    "SELECT a.*, e.name as exam_name, e.question_pdf_url FROM exam_assignments a 
     JOIN exams e ON a.exam_id = e.id
     WHERE a.id = :id AND a.student_id = :student_id"
);
$assignment_stmt->execute(['id' => $assignment_id, 'student_id' => $student_id]);
$assignment = $assignment_stmt->fetch();

if (!$assignment) {
    // Atama bulunamazsa, panoya yönlendir
    header('Location: index.php?page=dashboard');
    exit;
}

// Sınav zaten gönderilmişse (submitted/graded), panoya yönlendir
if ($assignment['status'] === 'submitted' || $assignment['status'] === 'graded') {
    header('Location: index.php?page=dashboard');
    exit;
}

// Sınav devam ediyor (in_progress) veya başlanmamış (assigned) ise devam et

// Sınav durumunu 'in_progress' olarak güncelle
$update_status_stmt = $pdo->prepare("UPDATE exam_assignments SET status = 'in_progress' WHERE id = :id");
$update_status_stmt->execute(['id' => $assignment_id]);

// Sınavın tüm sorularını çek
$questions_stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_number");
$questions_stmt->execute(['exam_id' => $assignment['exam_id']]);
$questions = $questions_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam: <?php echo htmlspecialchars($assignment['exam_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .exam-container {
            height: 100vh;
            overflow: hidden;
        }
        .pdf-column {
            height: calc(100vh - 100px);
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }
        .answer-column {
            height: calc(100vh - 100px);
            overflow-y: auto;
            padding-left: 15px;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-3 exam-container">
        <h1 class="mb-3"><?php echo htmlspecialchars($assignment['exam_name']); ?></h1>
        <div class="row g-3">
            <!-- Left Column: PDF and Multiple Choice Answers -->
            <div class="col-lg-7">
                <!-- PDF Section -->
                <div class="card mb-3" style="height: 650px; display: flex; flex-direction: column;">
                    <div class="card-header">Questions (PDF)</div>
                    <div class="card-body p-0" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column;">
                        <?php if ($assignment['question_pdf_url']): ?>
                            <div class="p-3" style="flex-shrink: 0;">
                                <a href="<?php echo htmlspecialchars($assignment['question_pdf_url']); ?>" target="_blank" class="btn btn-sm btn-primary">Open in New Tab</a>
                            </div>
                            <iframe src="<?php echo htmlspecialchars($assignment['question_pdf_url']); ?>" width="100%" style="border: none; display: block; flex: 1; min-height: 500px;"></iframe>
                        <?php else: ?>
                            <div class="p-3">
                                <p class="text-muted">No question PDF has been uploaded for this exam.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Full Answer Sheet -->
            <div class="col-lg-5 answer-column">
                <div class="card">
                    <div class="card-header">All Answers</div>
                    <div class="card-body">
                        <form action="src/student/submit_exam_action.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to submit the exam? This action cannot be undone.');">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">

                            <?php foreach ($questions as $q):
                                $meta = json_decode($q['metadata'], true);
                                if (empty($meta)) $meta = [];
                            ?>
                                <div class="mb-4 p-2 border rounded bg-light">
                                    <h6 class="text-primary">Question <?php echo htmlspecialchars($q['question_number']); ?></h6>
                                    <?php switch ($q['type']):
                                        case 'multiple_choice': ?>
                                            <?php if (!empty($meta) && isset($meta['options'])): ?>
                                                <?php foreach ($meta['options'] as $key => $text): if(empty($text)) continue; ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input right-choice-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="right-q-<?php echo $q['id']; ?>-<?php echo $key; ?>" value="<?php echo $key; ?>" data-question-id="<?php echo $q['id']; ?>">
                                                        <label class="form-check-label" for="right-q-<?php echo $q['id']; ?>-<?php echo $key; ?>">
                                                            <?php echo chr(65 + $key); ?>) <?php echo htmlspecialchars($text); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input right-choice-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="right-q-<?php echo $q['id']; ?>-<?php echo $i; ?>" value="<?php echo $i; ?>" data-question-id="<?php echo $q['id']; ?>">
                                                            <label class="form-check-label" for="right-q-<?php echo $q['id']; ?>-<?php echo $i; ?>">
                                                                <strong><?php echo chr(65 + $i); ?></strong>
                                                            </label>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php break;

                                        case 'true_false': ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="right-q-<?php echo $q['id']; ?>-true" value="true">
                                                <label class="form-check-label" for="right-q-<?php echo $q['id']; ?>-true">True</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="right-q-<?php echo $q['id']; ?>-false" value="false">
                                                <label class="form-check-label" for="right-q-<?php echo $q['id']; ?>-false">False</label>
                                            </div>
                                            <?php break;

                                        case 'essay': ?>
                                            <div class="mb-2">
                                                <label for="q-<?php echo $q['id']; ?>-text" class="form-label"><small>Your Answer:</small></label>
                                                <textarea class="form-control form-control-sm" id="q-<?php echo $q['id']; ?>-text" name="answers[<?php echo $q['id']; ?>][text]" rows="2"></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label for="q-<?php echo $q['id']; ?>-file" class="form-label"><small>File:</small></label>
                                                <input class="form-control form-control-sm" type="file" id="q-<?php echo $q['id']; ?>-file" name="answers_files[<?php echo $q['id']; ?>]">
                                            </div>
                                            <?php break;

                                    endswitch; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="d-grid">
                                <button type="submit" name="action" value="submit_exam" class="btn btn-success btn-lg">Submit Exam</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
