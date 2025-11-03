<?php
$subject_id = $_GET['subject_id'] ?? null;
$teacher_id = $_SESSION['user_id'];

if (!$subject_id) {
    header('Location: index.php?page=results');
    exit;
}

// Ders kontrolü (öğretmene ait mi?)
$subject_check = $pdo->prepare(
    "SELECT s.* FROM subjects s
     WHERE s.id = :subject_id AND EXISTS(
        SELECT 1 FROM exams WHERE subject_id = s.id AND teacher_id = :teacher_id
     )"
);
$subject_check->execute(['subject_id' => $subject_id, 'teacher_id' => $teacher_id]);
$subject = $subject_check->fetch();

if (!$subject) {
    header('Location: index.php?page=results');
    exit;
}

// Bu derste kayıtlı öğrencileri ve onların sınav sonuçlarını getir
$students_stmt = $pdo->prepare(
    "SELECT DISTINCT u.id, u.username FROM users u
     JOIN enrollments e ON u.id = e.user_id
     WHERE e.subject_id = :subject_id AND e.teacher_id = :teacher_id AND u.role = 'student'
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
    <title>Students - Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($subject['name']); ?></h1>
                <p class="text-muted">Student Results</p>
            </div>
            <a href="index.php?page=results" class="btn btn-secondary">Derslere Dön</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Kayıtlı Öğrenciler</h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted">Bu derste kayıtlı öğrenci bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($students as $student): ?>
                            <a href="index.php?page=results_exams&student_id=<?php echo $student['id']; ?>&subject_id=<?php echo $subject_id; ?>"
                               class="list-group-item list-group-item-action">
                                <h5 class="mb-0"><?php echo htmlspecialchars($student['username']); ?></h5>
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
