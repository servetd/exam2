<?php
$exam_id = $_GET['exam_id'];
$teacher_id = $_SESSION['user_id'];

// Fetch exam details and verify ownership
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = :id AND teacher_id = :teacher_id AND status = 'published'");
$exam_stmt->execute(['id' => $exam_id, 'teacher_id' => $teacher_id]);
$exam = $exam_stmt->fetch();

if (!$exam) {
    header('Location: index.php?page=exams');
    exit;
}

// Fetch students enrolled in this teacher's class for this subject
$students_stmt = $pdo->prepare(
    "SELECT u.id, u.username FROM users u JOIN enrollments e ON u.id = e.user_id 
     WHERE u.role = 'student' AND e.subject_id = :subject_id AND e.teacher_id = :teacher_id"
);
$students_stmt->execute(['subject_id' => $exam['subject_id'], 'teacher_id' => $teacher_id]);
$students = $students_stmt->fetchAll();

// Fetch students already assigned to this exam to pre-check them
$assigned_stmt = $pdo->prepare("SELECT student_id FROM exam_assignments WHERE exam_id = :exam_id");
$assigned_stmt->execute(['exam_id' => $exam_id]);
$assigned_student_ids = $assigned_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Exam: <?php echo htmlspecialchars($exam['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Assign Exam: <small class="text-muted"><?php echo htmlspecialchars($exam['name']); ?></small></h1>
            <a href="index.php?page=exams" class="btn btn-secondary">Back to Exams</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Assigned Students</div>
                    <div class="card-body">
                        <?php if (empty($assigned_student_ids)): ?>
                            <p>No students have been assigned to this exam yet.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php 
                                $assigned_students_stmt = $pdo->prepare("SELECT u.username, ea.id as assignment_id FROM users u JOIN exam_assignments ea ON u.id = ea.student_id WHERE ea.exam_id = :exam_id");
                                $assigned_students_stmt->execute(['exam_id' => $exam_id]);
                                $assigned_students = $assigned_students_stmt->fetchAll();
                                foreach ($assigned_students as $student): 
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($student['username']); ?>
                                        <form action="src/teacher/assignment_action.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="cancel_assignment">
                                            <input type="hidden" name="assignment_id" value="<?php echo $student['assignment_id']; ?>">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this assignment?');">Cancel</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <form action="src/teacher/assignment_action.php" method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <div class="card">
                        <div class="card-header">Assign New Student</div>
                        <div class="card-body">
                            <?php
                            $unassigned_students = array_filter($students, function($student) use ($assigned_student_ids) {
                                return !in_array($student['id'], $assigned_student_ids);
                            });
                            ?>
                            <?php if (empty($unassigned_students)): ?>
                                <p class="text-info">All students enrolled in this subject have already been assigned to this exam.</p>
                            <?php else: ?>
                                <p>Select the students you want to assign this exam to.</p>
                                <?php foreach ($unassigned_students as $student): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" id="student-<?php echo $student['id']; ?>">
                                        <label class="form-check-label" for="student-<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['username']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">Scheduling (Optional)</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="datetime-local" id="start_time" name="start_time" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="datetime-local" id="end_time" name="end_time" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="action" value="assign_exam" class="btn btn-primary mt-3" <?php echo empty($unassigned_students) ? 'disabled' : ''; ?>>Assign Selected Students</button>
                </form>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
