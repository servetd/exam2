<?php
$assignment_id = $_GET['assignment_id'] ?? null;
$teacher_id = $_SESSION['user_id'];

if (!$assignment_id) {
    header('Location: index.php?page=results');
    exit;
}

// Atama bilgisini getir
$assignment_stmt = $pdo->prepare(
    "SELECT a.id, a.student_id, a.exam_id, u.username as student_name, ex.name as exam_name, ex.subject_id
     FROM exam_assignments a
     JOIN users u ON a.student_id = u.id
     JOIN exams ex ON a.exam_id = ex.id
     WHERE a.id = :assignment_id
     AND ex.teacher_id = :teacher_id
     AND a.status = 'graded'"
);
$assignment_stmt->execute(['assignment_id' => $assignment_id, 'teacher_id' => $teacher_id]);
$assignment = $assignment_stmt->fetch();

if (!$assignment) {
    header('Location: index.php?page=results');
    exit;
}

// Soruları, cevapları ve konu bilgisini getir
$questions_stmt = $pdo->prepare(
    "SELECT eq.id, eq.question_number, eq.type, eq.points, eq.metadata,
            sa.answer_text, sa.answer_file_url, sa.score,
            GROUP_CONCAT(t.name, ', ') as topics
     FROM exam_questions eq
     LEFT JOIN student_answers sa ON eq.id = sa.question_id AND sa.assignment_id = :assignment_id
     LEFT JOIN exam_question_topics eqt ON eq.id = eqt.exam_question_id
     LEFT JOIN topics t ON eqt.topic_id = t.id
     WHERE eq.exam_id = :exam_id
     GROUP BY eq.id
     ORDER BY eq.question_number"
);
$questions_stmt->execute(['assignment_id' => $assignment_id, 'exam_id' => $assignment['exam_id']]);
$questions = $questions_stmt->fetchAll();

// Toplam puan hesapla
$total_questions = count($questions);
$total_points = 0;
$student_points = 0;

foreach ($questions as $q) {
    $total_points += $q['points'] ?? 10;
    $student_points += $q['score'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınav Detayları - Sonuçlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .topic-badge {
            display: inline-block;
            margin: 2px;
        }
        .correct-answer {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .incorrect-answer {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .partial-score {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($assignment['exam_name']); ?></h1>
                <p class="text-muted">Öğrenci: <strong><?php echo htmlspecialchars($assignment['student_name']); ?></strong></p>
            </div>
            <a href="javascript:history.back();" class="btn btn-secondary">Geri Dön</a>
        </div>

        <!-- Puan Özeti -->
        <div class="alert alert-info" role="alert">
            <div class="row">
                <div class="col-md-6">
                    <h5>Genel Başarı</h5>
                    <p class="mb-0">
                        <strong><?php echo $student_points; ?> / <?php echo $total_points; ?> Puan</strong>
                        <br>
                        <span class="badge bg-primary">%<?php echo round(($student_points / max($total_points, 1)) * 100); ?></span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Sınav İstatistikleri</h5>
                    <p class="mb-0">
                        Toplam Soru: <strong><?php echo $total_questions; ?></strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Sorular ve Cevaplar -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Soru Detayları</h5>
            </div>
            <div class="card-body">
                <?php foreach ($questions as $q):
                    $meta = json_decode($q['metadata'], true) ?? [];
                    $is_correct = false;
                    $status_class = '';

                    if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
                        $correct_answer = $meta['answer'] ?? null;
                        $is_correct = $q['answer_text'] == $correct_answer;
                        $status_class = $is_correct ? 'correct-answer' : 'incorrect-answer';
                    } elseif ($q['type'] === 'essay') {
                        if ($q['score'] > 0) {
                            $status_class = 'partial-score';
                        } else {
                            $status_class = 'incorrect-answer';
                        }
                    }
                ?>
                    <div class="mb-4 p-3 border rounded <?php echo $status_class; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">Soru <?php echo $q['question_number']; ?></h6>
                                <div class="mb-2">
                                    <?php if (!empty($q['topics'])): ?>
                                        <?php foreach (explode(', ', $q['topics']) as $topic): ?>
                                            <span class="badge bg-secondary topic-badge"><?php echo htmlspecialchars($topic); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong><?php echo $q['score'] ?? 0; ?> / <?php echo $q['points'] ?? 10; ?> Puan</strong>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><strong>Soru Türü:</strong></label>
                            <p class="mb-0">
                                <?php
                                    $type_names = [
                                        'multiple_choice' => 'Çoktan Seçmeli',
                                        'true_false' => 'Doğru/Yanlış',
                                        'fill_in_the_blank' => 'Boş Doldur',
                                        'essay' => 'Açık Uçlu'
                                    ];
                                    echo $type_names[$q['type']] ?? $q['type'];
                                ?>
                            </p>
                        </div>

                        <?php if ($q['type'] === 'multiple_choice'): ?>
                            <div class="mb-2">
                                <label class="form-label"><strong>Seçenekler:</strong></label>
                                <?php if (isset($meta['options'])): ?>
                                    <ul class="mb-0">
                                        <?php foreach ($meta['options'] as $key => $option): if(empty($option)) continue; ?>
                                            <li>
                                                <strong><?php echo chr(65 + $key); ?></strong>)
                                                <?php echo htmlspecialchars($option); ?>
                                                <?php if ($key == ($meta['answer'] ?? null)): ?>
                                                    <span class="badge bg-success ms-2">Doğru Cevap</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label"><strong>Öğrenci Cevabı:</strong></label>
                            <?php if ($q['answer_text']): ?>
                                <div class="alert alert-light border mb-0">
                                    <?php if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false'): ?>
                                        <?php
                                            if ($q['type'] === 'multiple_choice' && isset($meta['options'])) {
                                                $answer_index = intval($q['answer_text']);
                                                echo chr(65 + $answer_index) . ') ' . htmlspecialchars($meta['options'][$answer_index] ?? 'Bilinmeyen Seçenek');
                                            } else {
                                                echo $q['answer_text'] === 'true' ? 'Doğru' : 'Yanlış';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($q['answer_text']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Cevap verilmedi</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($q['type'] === 'essay' && $q['answer_file_url']): ?>
                            <div class="mb-2">
                                <label class="form-label"><strong>Ek Dosya:</strong></label>
                                <a href="<?php echo htmlspecialchars($q['answer_file_url']); ?>" target="_blank" class="btn btn-sm btn-info">
                                    Dosyayı Görüntüle
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
