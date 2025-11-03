<?php
$coach_id = $_SESSION['user_id'];

// Koça atanan öğrencileri getir
$students_stmt = $pdo->prepare(
    "SELECT u.id, u.username FROM users u
     INNER JOIN coach_students cs ON u.id = cs.student_id
     WHERE cs.coach_id = :coach_id AND u.role = 'student'
     ORDER BY u.username"
);
$students_stmt->execute(['coach_id' => $coach_id]);
$students = $students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Coach Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1>My Students</h1>
                <p class="text-muted">Students monitored by coach</p>
            </div>
            <a href="index.php?page=dashboard" class="btn btn-secondary">Return to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Student List</h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted">You have no assigned students.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($students as $student): ?>
                            <a href="index.php?page=coach_student_results&student_id=<?php echo $student['id']; ?>"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($student['username']); ?></h5>
                                    <small class="text-muted">View Results →</small>
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
