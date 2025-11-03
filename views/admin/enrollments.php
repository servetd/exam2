<?php
$managed_user_id = $_GET['user_id'];

// Get the user being managed
$user_stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id");
$user_stmt->execute(['id' => $managed_user_id]);
$managed_user = $user_stmt->fetch();

if (!$managed_user) {
    die("User not found!");
}

// Get all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Get current enrollments for this user
$current_enrollments_stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = :user_id");
$current_enrollments_stmt->execute(['user_id' => $managed_user_id]);
$current_enrollments = $current_enrollments_stmt->fetchAll();

// For teachers, create a simple list of assigned subject IDs
$teacher_subject_ids = [];
if ($managed_user['role'] === 'teacher') {
    foreach ($current_enrollments as $enrollment) {
        $teacher_subject_ids[] = $enrollment['subject_id'];
    }
}

// For students, get all teachers and their assigned subjects to build the class list
$teachers_by_subject = [];
if ($managed_user['role'] === 'student') {
    $teacher_enrollments = $pdo->query(
        "SELECT u.id as teacher_id, u.username, e.subject_id FROM enrollments e JOIN users u ON e.user_id = u.id WHERE u.role = 'teacher'"
    )->fetchAll();
    foreach ($teacher_enrollments as $te) {
        $teachers_by_subject[$te['subject_id']][] = ['id' => $te['teacher_id'], 'username' => $te['username']];
    }
    
    // Create a map of student's current class enrollments
    $student_class_map = [];
    foreach ($current_enrollments as $enrollment) {
        $student_class_map[$enrollment['subject_id']] = $enrollment['teacher_id'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - <?php echo htmlspecialchars($managed_user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Manage Assignments: <?php echo htmlspecialchars($managed_user['username']); ?> <span class="badge bg-secondary"><?php echo $managed_user['role']; ?></span></h1>
            <a href="index.php?page=manage_users" class="btn btn-secondary">Return to User List</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="src/admin/enrollment_action.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $managed_user['id']; ?>">
                    <input type="hidden" name="role" value="<?php echo $managed_user['role']; ?>">

                    <?php if ($managed_user['role'] === 'teacher'): ?>
                        <h5 class="card-title">Select Subjects Teacher Will Teach</h5>
                        <?php foreach ($subjects as $subject): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subjects[]" value="<?php echo $subject['id']; ?>" id="subject-<?php echo $subject['id']; ?>" <?php echo in_array($subject['id'], $teacher_subject_ids) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="subject-<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" name="action" value="update_teacher_enrollments" class="btn btn-primary mt-3">Save</button>

                    <?php elseif ($managed_user['role'] === 'student'): ?>
                        <h5 class="card-title">Select Classes Student Is Enrolled In</h5>
                        <table class="table">
                            <thead><tr><th>Subject</th><th>Teacher</th></tr></thead>
                            <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                    <td>
                                        <?php if (isset($teachers_by_subject[$subject['id']])): ?>
                                            <select name="enrollments[<?php echo $subject['id']; ?>]" class="form-select">
                                                <option value="">-- Not Enrolled --</option>
                                                <?php foreach ($teachers_by_subject[$subject['id']] as $teacher): ?>
                                                    <option value="<?php echo $teacher['id']; ?>" <?php echo (isset($student_class_map[$subject['id']]) && $student_class_map[$subject['id']] == $teacher['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($teacher['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="text-muted">No teacher teaches this subject.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="action" value="update_student_enrollments" class="btn btn-primary mt-3">Save</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
