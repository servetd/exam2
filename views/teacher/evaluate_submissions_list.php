<?php
$exam_id = $_GET['exam_id'];
$teacher_id = $_SESSION['user_id'];

// Fetch exam details and verify ownership
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = :id AND teacher_id = :teacher_id");
$exam_stmt->execute(['id' => $exam_id, 'teacher_id' => $teacher_id]);
$exam = $exam_stmt->fetch();

if (!$exam) {
    header('Location: index.php?page=evaluate_exams');
    exit;
}

// Fetch submissions for this exam
$submissions_stmt = $pdo->prepare(
    "SELECT u.username, ea.id as assignment_id, ea.status 
     FROM exam_assignments ea 
     JOIN users u ON ea.student_id = u.id 
     WHERE ea.exam_id = :exam_id AND ea.status IN ('submitted', 'graded')
     ORDER BY u.username"
);
$submissions_stmt->execute(['exam_id' => $exam_id]);
$submissions = $submissions_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cevapları Değerlendir: <?php echo htmlspecialchars($exam['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Cevapları Değerlendir: <small class="text-muted"><?php echo htmlspecialchars($exam['name']); ?></small></h1>
            <a href="index.php?page=evaluate_exams" class="btn btn-secondary">Sınav Listesine Dön</a>
        </div>

        <div class="list-group">
            <?php if (empty($submissions)):
            ?>
                <div class="list-group-item">Bu sınava gönderilmiş bir cevap bulunmamaktadır.</div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($submission['username']); ?></h5>
                            <small>Status: 
                                <?php if ($submission['status'] === 'submitted'): ?>
                                    <span class="badge bg-warning">Puanlanmadı</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Puanlandı</span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <a href="index.php?page=grade_submission&assignment_id=<?php echo $submission['assignment_id']; ?>" class="btn btn-primary">
                            <?php echo ($submission['status'] === 'submitted') ? 'Puanla' : 'Görüntüle'; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
