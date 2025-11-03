<?php
$coach_id = $_GET['coach_id'] ?? null;

if (!$coach_id) {
    header('Location: index.php?page=manage_users');
    exit;
}

// Koç bilgisini getir
$coach_stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id AND role = 'coach'");
$coach_stmt->execute(['id' => $coach_id]);
$coach = $coach_stmt->fetch();

if (!$coach) {
    header('Location: index.php?page=manage_users');
    exit;
}

// Koça atanan öğrencileri getir
$assigned_students_stmt = $pdo->prepare(
    "SELECT u.id, u.username FROM users u
     INNER JOIN coach_students cs ON u.id = cs.student_id
     WHERE cs.coach_id = :coach_id AND u.role = 'student'
     ORDER BY u.username"
);
$assigned_students_stmt->execute(['coach_id' => $coach_id]);
$assigned_students = $assigned_students_stmt->fetchAll();

// Atanmamış öğrencileri getir
$available_students_stmt = $pdo->prepare(
    "SELECT id, username FROM users
     WHERE role = 'student' AND id NOT IN (
        SELECT student_id FROM coach_students WHERE coach_id = :coach_id
     )
     ORDER BY username"
);
$available_students_stmt->execute(['coach_id' => $coach_id]);
$available_students = $available_students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Student Management - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($coach['username']); ?></h1>
                <p class="text-muted">Coach Student Management</p>
            </div>
            <a href="index.php?page=manage_users" class="btn btn-secondary">Return to Users</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Assigned Students -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Assigned Students (<?php echo count($assigned_students); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assigned_students)): ?>
                            <p class="text-muted">No students assigned to this coach.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($assigned_students as $student): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($student['username']); ?></span>
                                        <form method="POST" action="src/admin/coach_action.php" style="margin: 0;">
                                            <input type="hidden" name="action" value="remove_student">
                                            <input type="hidden" name="coach_id" value="<?php echo $coach_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this student?')">Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Available Students -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Available Students (<?php echo count($available_students); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_students)): ?>
                            <p class="text-muted">No students available to assign.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($available_students as $student): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($student['username']); ?></span>
                                        <form method="POST" action="src/admin/coach_action.php" style="margin: 0;">
                                            <input type="hidden" name="action" value="assign_student">
                                            <input type="hidden" name="coach_id" value="<?php echo $coach_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Assign</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
