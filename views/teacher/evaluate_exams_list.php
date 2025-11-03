<?php
$teacher_id = $_SESSION['user_id'];

// Fetch exams by this teacher that have submissions to evaluate
$exams_stmt = $pdo->prepare(
    "SELECT ex.id, ex.name, COUNT(ea.id) as submission_count 
     FROM exams ex 
     JOIN exam_assignments ea ON ex.id = ea.exam_id 
     WHERE ex.teacher_id = :teacher_id AND ea.status = 'submitted' 
     GROUP BY ex.id, ex.name
     ORDER BY ex.name"
);
$exams_stmt->execute(['teacher_id' => $teacher_id]);
$evaluable_exams = $exams_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Değerlendirilecek Sınavlar - Sınav Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Değerlendirilecek Sınavlar</h1>
            <a href="index.php?page=dashboard" class="btn btn-secondary">Kontrol Paneline Dön</a>
        </div>

        <div class="list-group">
            <?php if (empty($evaluable_exams)): ?>
                <div class="list-group-item">Değerlendirilecek sınav bulunmamaktadır.</div>
            <?php else: ?>
                <?php foreach ($evaluable_exams as $exam): ?>
                    <a href="index.php?page=evaluate_submissions&exam_id=<?php echo $exam['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($exam['name']); ?></h5>
                            <span class="badge bg-primary rounded-pill"><?php echo $exam['submission_count']; ?> öğrenci</span>
                        </div>
                        <p class="mb-1">Bu sınava gönderilen cevapları görmek ve puanlamak için tıklayın.</p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
