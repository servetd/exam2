<?php
// This page is only for teachers, checked in index.php

$teacher_id = $_SESSION['user_id'];
$selected_subject_id = $_GET['subject_id'] ?? null;

// Fetch subjects assigned to this teacher
$subjects_stmt = $pdo->prepare(
    "SELECT s.id, s.name FROM subjects s JOIN enrollments e ON s.id = e.subject_id WHERE e.user_id = :teacher_id GROUP BY s.id"
);
$subjects_stmt->execute(['teacher_id' => $teacher_id]);
$teacher_subjects = $subjects_stmt->fetchAll();

// Fetch exams created by this teacher, optionally filtered by subject
$sql = "SELECT * FROM exams WHERE teacher_id = :teacher_id";
$params = ['teacher_id' => $teacher_id];

if ($selected_subject_id) {
    $sql .= " AND subject_id = :subject_id";
    $params['subject_id'] = $selected_subject_id;
}
$sql .= " ORDER BY created_at DESC";

$exams_stmt = $pdo->prepare($sql);
$exams_stmt->execute($params);
$exams = $exams_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage My Exams - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Manage My Exams</h1>
            <a href="index.php?page=create_exam<?php echo $selected_subject_id ? '&subject_id=' . $selected_subject_id : ''; ?>" class="btn btn-primary">Create New Exam</a>
        </div>

        <div class="mb-3">
            <label for="subjectFilter" class="form-label">Filter by Subject:</label>
            <select class="form-select" id="subjectFilter" onchange="if(this.value) window.location.href='index.php?page=exams&subject_id='+this.value; else window.location.href='index.php?page=exams';">
                <option value="">All Subjects</option>
                <?php foreach ($teacher_subjects as $subject): ?>
                    <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject_id == $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card">
            <div class="card-header">My Exams</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Sınav Adı</th>
                            <th>Status</th>
                            <th>Question Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Henüz sınav oluşturmadınız.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                    <td>
                                        <?php if ($exam['status'] === 'draft'): ?>
                                            <span class="badge bg-warning">Draft</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>-</td>  <!-- Soru sayısı daha sonra eklenecek -->
                                    <td>
                                        <a href="#" class="btn btn-sm btn-secondary disabled">Düzenle</a>
                                        <a href="index.php?page=manage_questions&exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info">Soruları Yönet</a>
                                        <?php if ($exam['status'] === 'draft'): ?>
                                            <form action="src/teacher/exam_action.php" method="post" style="display: inline-block;">
                                                <input type="hidden" name="action" value="publish_exam">
                                                <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Yayınla</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($exam['status'] === 'published'): ?>
                                            <a href="index.php?page=assign_exam&exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success">Ata</a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-success disabled">Ata</a>
                                        <?php endif; ?>
                                        <form action="src/teacher/exam_action.php" method="post" style="display: inline-block;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu sınavı silmek istediğinizden emin misiniz?');">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
