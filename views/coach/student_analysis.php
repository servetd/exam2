<?php
$coach_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Öğrenci kontrolü - koça atanmış mı?
$student_stmt = $pdo->prepare(
    "SELECT u.id, u.username FROM users u
     INNER JOIN coach_students cs ON u.id = cs.student_id
     WHERE cs.coach_id = :coach_id AND u.id = :student_id AND u.role = 'student'"
);
$student_stmt->execute(['coach_id' => $coach_id, 'student_id' => $student_id]);
$student = $student_stmt->fetch();

if (!$student) {
    header('Location: index.php?page=coach_students');
    exit;
}

// Tüm konuları getir (hiyerarşik yapı)
$topics_stmt = $pdo->prepare(
    "SELECT DISTINCT t.id, t.name, t.parent_id, t.importance
     FROM topics t
     ORDER BY t.parent_id, t.name"
);
$topics_stmt->execute();
$all_topics = $topics_stmt->fetchAll();

// Öğrencinin tüm sınavlarındaki konu performansını hesapla
$performance_stmt = $pdo->prepare(
    "SELECT t.id, t.name, t.parent_id,
            COUNT(DISTINCT eq.id) as total_questions,
            SUM(CASE WHEN sa.score IS NOT NULL AND sa.score > 0 THEN 1 ELSE 0 END) as correct_answers,
            ROUND(AVG(CASE WHEN sa.score IS NOT NULL THEN CAST(sa.score AS REAL) / COALESCE(eq.points, 10) * 100 ELSE 0 END), 2) as success_rate
     FROM topics t
     LEFT JOIN exam_question_topics eqt ON t.id = eqt.topic_id
     LEFT JOIN exam_questions eq ON eqt.exam_question_id = eq.id
     LEFT JOIN exam_assignments a ON eq.exam_id = a.exam_id
     LEFT JOIN student_answers sa ON a.id = sa.assignment_id AND eq.id = sa.question_id
     WHERE a.student_id = :student_id
     AND a.status = 'graded'
     GROUP BY t.id, t.name, t.parent_id
     HAVING COUNT(DISTINCT eq.id) > 0
     ORDER BY t.parent_id, t.name"
);
$performance_stmt->execute(['student_id' => $student_id]);
$performance = $performance_stmt->fetchAll();

// Performance dizisini ID'ye göre indexle
$performance_by_id = [];
foreach ($performance as $p) {
    $performance_by_id[$p['id']] = $p;
}

// Konuları kategorilere ayır (Ana Konular ve Alt Konular)
$main_topics = [];
$sub_topics = [];

foreach ($all_topics as $topic) {
    if (is_null($topic['parent_id'])) {
        $main_topics[] = $topic;
    } else {
        if (!isset($sub_topics[$topic['parent_id']])) {
            $sub_topics[$topic['parent_id']] = [];
        }
        $sub_topics[$topic['parent_id']][] = $topic;
    }
}

// Başarı yüzdesine göre renk belirle
function getSuccessColor($rate) {
    if ($rate === null) return 'secondary';
    if ($rate >= 80) return 'success';
    if ($rate >= 60) return 'warning';
    return 'danger';
}

function getSuccessLabel($rate) {
    if ($rate === null) return 'No Questions';
    if ($rate >= 80) return 'Excellent';
    if ($rate >= 60) return 'Good';
    return 'Poor';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Analysis - Coach Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .topic-card {
            margin-bottom: 15px;
            border-left: 5px solid #dee2e6;
        }
        .topic-card.success {
            border-left-color: #28a745;
        }
        .topic-card.warning {
            border-left-color: #ffc107;
        }
        .topic-card.danger {
            border-left-color: #dc3545;
        }
        .sub-topic {
            margin-left: 20px;
            margin-top: 10px;
        }
        .performance-badge {
            font-size: 14px;
            padding: 8px 12px;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1><?php echo htmlspecialchars($student['username']); ?></h1>
                <p class="text-muted">Topic Analysis</p>
            </div>
            <a href="index.php?page=coach_student_results&student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Back to Results</a>
        </div>

        <!-- General Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Topic Status</h6>
                        <?php
                            $excellent = 0;
                            $good = 0;
                            $weak = 0;
                            foreach ($performance as $p) {
                                if ($p['success_rate'] === null) continue;
                                if ($p['success_rate'] >= 80) $excellent++;
                                elseif ($p['success_rate'] >= 60) $good++;
                                else $weak++;
                            }
                        ?>
                        <p class="mb-1">
                            <span class="badge bg-success"><?php echo $excellent; ?> Excellent</span>
                            <span class="badge bg-warning"><?php echo $good; ?> Good</span>
                            <span class="badge bg-danger"><?php echo $weak; ?> Poor</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Total Questions</h6>
                        <h4><?php echo array_sum(array_column($performance, 'total_questions')); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title">Overall Success</h6>
                        <?php
                            $total_score = 0;
                            $total_questions_count = 0;
                            foreach ($performance as $p) {
                                $total_score += $p['correct_answers'] ?? 0;
                                $total_questions_count += $p['total_questions'] ?? 0;
                            }
                            $overall_rate = $total_questions_count > 0 ? round(($total_score / $total_questions_count) * 100) : 0;
                        ?>
                        <h4><span class="badge bg-primary"><?php echo $overall_rate; ?>%</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topics -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Topic Success Details</h5>
            </div>
            <div class="card-body">
                <?php if (empty($performance)): ?>
                    <p class="text-muted">No exam data found for this student.</p>
                <?php else: ?>
                    <?php foreach ($main_topics as $main_topic):
                        $main_perf = $performance_by_id[$main_topic['id']] ?? null;
                        $has_sub_topics = isset($sub_topics[$main_topic['id']]);
                    ?>
                        <div class="card topic-card <?php echo $main_perf ? getSuccessColor($main_perf['success_rate']) : 'secondary'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">📌 <?php echo htmlspecialchars($main_topic['name']); ?></h6>
                                        <?php if ($main_perf): ?>
                                            <small class="text-muted">
                                                <?php echo $main_perf['total_questions']; ?> question |
                                                <?php echo $main_perf['correct_answers'] ?? 0; ?> correct
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($main_perf): ?>
                                        <span class="badge bg-<?php echo getSuccessColor($main_perf['success_rate']); ?> performance-badge">
                                            <?php echo round($main_perf['success_rate']); ?>% - <?php echo getSuccessLabel($main_perf['success_rate']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary performance-badge">No Questions</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Subtopics -->
                                <?php if ($has_sub_topics): ?>
                                    <div class="sub-topic">
                                        <?php foreach ($sub_topics[$main_topic['id']] as $sub_topic):
                                            $sub_perf = $performance_by_id[$sub_topic['id']] ?? null;
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-start border-secondary">
                                                <div>
                                                    <small><strong>└─ <?php echo htmlspecialchars($sub_topic['name']); ?></strong></small>
                                                    <?php if ($sub_perf): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo $sub_perf['total_questions']; ?> question |
                                                            <?php echo $sub_perf['correct_answers'] ?? 0; ?> correct
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($sub_perf): ?>
                                                    <span class="badge bg-<?php echo getSuccessColor($sub_perf['success_rate']); ?>">
                                                        <?php echo round($sub_perf['success_rate']); ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Questions</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="alert alert-info mt-4">
            <h6>Success Levels:</h6>
            <ul class="mb-0">
                <li><span class="badge bg-success">80% and above</span> - Excellent (Student has learned this topic very well)</li>
                <li><span class="badge bg-warning">60-79%</span> - Good (Student has learned this topic)</li>
                <li><span class="badge bg-danger">59% and below</span> - Poor (Student needs support in this topic)</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
