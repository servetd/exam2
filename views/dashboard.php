<?php
// Session yoksa veya user_id tanımlı değilse, login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Role names
$role_names = [
    'admin' => ['Ad' => 'Administrator', 'Icon' => '⚙️'],
    'teacher' => ['Ad' => 'Teacher', 'Icon' => '👨‍🏫'],
    'student' => ['Ad' => 'Student', 'Icon' => '👨‍🎓'],
    'coach' => ['Ad' => 'Coach', 'Icon' => '🏆']
];

$role_info = $role_names[$_SESSION['role']] ?? ['Ad' => $_SESSION['role'], 'Icon' => '👤'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Hero Section -->
            <div class="hero-section mb-5">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h1>
                        <p class="mb-0">
                            <?php echo $role_info['Icon']; ?>
                            You are logged in as <strong><?php echo htmlspecialchars($role_info['Ad']); ?></strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Admin Panel -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card mb-4">
                            <div class="card-header">
                                ⚙️ Administration Panel
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <a href="index.php?page=manage_subjects" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">📚 Manage Subjects</h5>
                                            <p class="mb-0 text-muted small">Add, edit, or delete subjects</p>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php?page=manage_topics" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">📋 Manage Topics</h5>
                                            <p class="mb-0 text-muted small">Manage topics and subtopics</p>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php?page=manage_users" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">👥 Manage Users</h5>
                                            <p class="mb-0 text-muted small">Add, edit, or delete users</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Teacher Panel -->
            <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card mb-4">
                            <div class="card-header">
                                👨‍🏫 Teacher Panel
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <a href="index.php?page=exams" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">📝 Manage My Exams</h5>
                                            <p class="mb-0 text-muted small">Create and manage exams</p>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php?page=evaluate_exams" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">✅ Grade Answers</h5>
                                            <p class="mb-0 text-muted small">Evaluate student answers</p>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php?page=results" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">📊 View Results</h5>
                                            <p class="mb-0 text-muted small">View and analyze exam results</p>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php?page=analytics" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">📈 Analytics</h5>
                                            <p class="mb-0 text-muted small">Analyze student performance</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Coach Panel -->
            <?php elseif ($_SESSION['role'] === 'coach'): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card mb-4">
                            <div class="card-header">
                                🏆 Coach Panel
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <a href="index.php?page=coach_students" class="list-group-item list-group-item-action p-3" style="text-decoration: none;">
                                            <h5 class="mb-1">👥 My Students</h5>
                                            <p class="mb-0 text-muted small">View assigned students</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Student Panel -->
            <?php else: // Student Role
                $student_id = $_SESSION['user_id'];
                $assignments_stmt = $pdo->prepare(
                    "SELECT a.id as assignment_id, ex.name as exam_name, ex.id as exam_id, s.name as subject_name, u.username as teacher_name, a.status, a.start_time, a.end_time
                     FROM exam_assignments a
                     JOIN exams ex ON a.exam_id = ex.id
                     JOIN subjects s ON ex.subject_id = s.id
                     JOIN users u ON ex.teacher_id = u.id
                     WHERE a.student_id = :student_id
                     ORDER BY s.name, ex.name"
                );
                $assignments_stmt->execute(['student_id' => $student_id]);
                $all_assignments = $assignments_stmt->fetchAll();

                // Derslere göre grupla
                $assignments_by_subject = [];
                foreach ($all_assignments as $assignment) {
                    $subject_name = $assignment['subject_name'];
                    if (!isset($assignments_by_subject[$subject_name])) {
                        $assignments_by_subject[$subject_name] = [];
                    }
                    $assignments_by_subject[$subject_name][] = $assignment;
                }
            ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                📋 My Assigned Exams
                            </div>
                            <div class="card-body">
                                <?php if (empty($assignments_by_subject)): ?>
                                    <div class="alert alert-info">
                                        <strong>ℹ️ Info</strong> - No exams have been assigned to you.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($assignments_by_subject as $subject_name => $assignments): ?>
                                        <div class="mb-4">
                                            <h5 style="color: #2563eb; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.75rem; margin-bottom: 1rem;">
                                                📚 <?php echo htmlspecialchars($subject_name); ?>
                                            </h5>
                                            <div class="row g-3">
                                                <?php foreach ($assignments as $assignment): ?>
                                            <?php if ($assignment['status'] === 'graded'):
                                                // --- SCORE CALCULATION ---
                                                $assignment_id = $assignment['assignment_id'];
                                                $exam_id_stmt = $pdo->prepare("SELECT exam_id FROM exam_assignments WHERE id = ?");
                                                $exam_id_stmt->execute([$assignment_id]);
                                                $exam_id = $exam_id_stmt->fetchColumn();

                                                $questions_stmt = $pdo->prepare("SELECT * FROM exam_questions WHERE exam_id = ?");
                                                $questions_stmt->execute([$exam_id]);
                                                $questions = $questions_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

                                                $answers_stmt = $pdo->prepare("SELECT * FROM student_answers WHERE assignment_id = ?");
                                                $answers_stmt->execute([$assignment_id]);
                                                $student_answers = $answers_stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

                                                $student_total_score = 0;
                                                $max_total_score = 0;

                                                if (!empty($questions)) {
                                                    foreach($questions as $qid => $q) {
                                                        $max_total_score += 10;
                                                        $answer = $student_answers[$qid] ?? null;
                                                        if ($answer) {
                                                            if ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
                                                                $q_meta = json_decode($q['metadata'], true);
                                                                if (isset($q_meta['answer']) && $answer['answer_text'] == $q_meta['answer']) {
                                                                    $student_total_score += 10;
                                                                }
                                                            } elseif ($q['type'] === 'essay') {
                                                                $student_total_score += (int)$answer['score'];
                                                            }
                                                        }
                                                    }
                                                }
                                                $percentage = ($max_total_score > 0) ? ($student_total_score / $max_total_score) * 100 : 0;
                                                $percentage_badge_color = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                            ?>
                                                <div class="col-md-6">
                                                    <div class="card stat-card">
                                                        <h5 class="card-title mb-2">✅ <?php echo htmlspecialchars($assignment['exam_name']); ?></h5>
                                                        <p class="text-muted small mb-2">Teacher: <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
                                                        <div class="number text-<?php echo $percentage_badge_color; ?>">
                                                            <?php echo round($percentage); ?>%
                                                        </div>
                                                        <p class="mb-2 small">
                                                            <span class="badge bg-primary"><?php echo $student_total_score; ?>/<?php echo $max_total_score; ?> Points</span>
                                                        </p>
                                                        <div class="progress mb-3" style="height: 6px;">
                                                            <div class="progress-bar bg-<?php echo $percentage_badge_color; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                                                        </div>
                                                        <p class="text-muted small mb-0">Status: <strong>Graded</strong></p>
                                                    </div>
                                                </div>
                                            <?php else:
                                                $is_active = ($assignment['status'] === 'assigned' || $assignment['status'] === 'in_progress');
                                            ?>
                                                <div class="col-md-6">
                                                    <a href="index.php?page=take_exam&assignment_id=<?php echo $assignment['assignment_id']; ?>"
                                                       class="card stat-card text-decoration-none <?php echo !$is_active ? 'opacity-50' : ''; ?>"
                                                       style="<?php echo !$is_active ? 'cursor: not-allowed;' : 'cursor: pointer;'; ?>">
                                                        <h5 class="card-title mb-2">📝 <?php echo htmlspecialchars($assignment['exam_name']); ?></h5>
                                                        <p class="text-muted small mb-2">Teacher: <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
                                                        <p class="mb-2">
                                                            <span class="badge bg-<?php echo $assignment['status'] === 'in_progress' ? 'warning' : 'info'; ?>">
                                                                <?php
                                                                    if ($assignment['status'] === 'in_progress') echo '⏳ In Progress';
                                                                    elseif ($assignment['status'] === 'assigned') echo '🔓 Start';
                                                                    else echo htmlspecialchars($assignment['status']);
                                                                ?>
                                                            </span>
                                                        </p>
                                                        <p class="text-muted small mb-0">
                                                            <?php echo $is_active ? '👆 Click to start exam' : '🔒 Exam ended'; ?>
                                                        </p>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
