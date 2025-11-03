<?php
$student_id = $_GET['student_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;
$teacher_id = $_SESSION['user_id'];

if (!$student_id || !$subject_id) {
    header('Location: index.php?page=results');
    exit;
}

// Öğrenci kontrolü
$student_stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id AND role = 'student'");
$student_stmt->execute(['id' => $student_id]);
$student = $student_stmt->fetch();

if (!$student) {
    header('Location: index.php?page=results');
    exit;
}

// Ders kontrolü
$subject_stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE id = :id");
$subject_stmt->execute(['id' => $subject_id]);
$subject = $subject_stmt->fetch();

if (!$subject) {
    header('Location: index.php?page=results');
    exit;
}

// Öğrencinin bu derste aldığı ve değerlendirilen sınavları getir
$exams_stmt = $pdo->prepare(
    "SELECT a.id as assignment_id, ex.id as exam_id, ex.name as exam_name, a.status,
            COUNT(DISTINCT sa.id) as answered_questions
     FROM exam_assignments a
     JOIN exams ex ON a.exam_id = ex.id
     JOIN student_answers sa ON a.id = sa.assignment_id
     WHERE a.student_id = :student_id
     AND ex.subject_id = :subject_id
     AND ex.teacher_id = :teacher_id
     AND a.status = 'graded'
     GROUP BY a.id, ex.id, ex.name, a.status
     ORDER BY ex.name"
);
$exams_stmt->execute(['student_id' => $student_id, 'subject_id' => $subject_id, 'teacher_id' => $teacher_id]);
$exams = $exams_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınav Sonuçları - Sonuçlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($student['username']); ?></h1>
                <p class="text-muted"><?php echo htmlspecialchars($subject['name']); ?> - Sınav Sonuçları</p>
            </div>
            <a href="index.php?page=results_students&subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">Öğrencilere Dön</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Graded Exams</h5>
            </div>
            <div class="card-body">
                <?php if (empty($exams)): ?>
                    <p class="text-muted">Bu öğrencinin değerlendirilen sınavı bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Sınav Adı</th>
                                    <th>Status</th>
                                    <th>Answered Questions</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Değerlendirilen</span>
                                        </td>
                                        <td>
                                            <?php echo $exam['answered_questions']; ?> soru
                                        </td>
                                        <td>
                                            <a href="index.php?page=results_exam_detail&assignment_id=<?php echo $exam['assignment_id']; ?>"
                                               class="btn btn-sm btn-primary">Detayları Gör</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
