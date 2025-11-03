$teacher_id = $_SESSION['user_id'];

// Öğretmenin derslerini getir
$subjects_stmt = $pdo->prepare(
    "SELECT DISTINCT s.id, s.name FROM subjects s
     JOIN exams e ON s.id = e.subject_id
     WHERE e.teacher_id = :teacher_id
     ORDER BY s.name"
);
$subjects_stmt->execute(['teacher_id' => $teacher_id]);
$subjects = $subjects_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Sınav Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Analytics</h1>
            <a href="index.php?page=dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">My Subjects</h5>
            </div>
            <div class="card-body">
                <?php if (empty($subjects)): ?>
                    <p class="text-muted">You haven't created any exams for any subject yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($subjects as $subject): ?>
                            <a href="index.php?page=analytics_students&subject_id=<?php echo $subject['id']; ?>"
                               class="list-group-item list-group-item-action">
                                <h5 class="mb-0"><?php echo htmlspecialchars($subject['name']); ?></h5>
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
