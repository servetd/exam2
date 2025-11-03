<?php
$subject_id = $_GET['subject_id'] ?? null;
$teacher_id = $_SESSION['user_id'];

if (!$subject_id) {
    header('Location: index.php?page=analytics');
    exit;
}

// Ders kontrolü
$subject_check = $pdo->prepare(
    "SELECT s.* FROM subjects s
     WHERE s.id = :subject_id AND EXISTS(
        SELECT 1 FROM exams WHERE subject_id = s.id AND teacher_id = :teacher_id
     )"
);
$subject_check->execute(['subject_id' => $subject_id, 'teacher_id' => $teacher_id]);
$subject = $subject_check->fetch();

if (!$subject) {
    header('Location: index.php?page=analytics');
    exit;
}

// Bu derste kayıtlı ve sınava girmiş öğrencileri getir
$students_stmt = $pdo->prepare(
    "SELECT DISTINCT u.id, u.username,
            COUNT(DISTINCT a.id) as exam_count,
            SUM(CASE WHEN a.status = 'graded' THEN 1 ELSE 0 END) as graded_count
     FROM users u
     JOIN enrollments e ON u.id = e.user_id
     JOIN exam_assignments a ON u.id = a.student_id
     JOIN exams ex ON a.exam_id = ex.id
     WHERE e.subject_id = :subject_id
     AND e.teacher_id = :teacher_id
     AND u.role = 'student'
     AND ex.teacher_id = :teacher_id
     GROUP BY u.id, u.username
     ORDER BY u.username"
);
$students_stmt->execute(['subject_id' => $subject_id, 'teacher_id' => $teacher_id]);
$students = $students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenciler Analiz - Sınav Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($subject['name']); ?></h1>
                <p class="text-muted">Öğrenci Analizi</p>
            </div>
            <a href="index.php?page=analytics" class="btn btn-secondary">Derslere Dön</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Kayıtlı Öğrenciler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted">Bu derste sınava giren öğrenci bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($students as $student): ?>
                            <a href="index.php?page=analytics_topics&student_id=<?php echo $student['id']; ?>&subject_id=<?php echo $subject_id; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($student['username']); ?></h5>
                                    <small class="text-muted">
                                        <?php echo $student['graded_count']; ?>/<?php echo $student['exam_count']; ?> sınav değerlendirildi
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
