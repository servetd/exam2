<?php
// Fetch all subjects for the dropdown
$subjects_stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $subjects_stmt->fetchAll();

$selected_subject_id = $_GET['subject_id'] ?? null;
$topics_tree = [];

if ($selected_subject_id) {
    $topics_stmt = $pdo->prepare("SELECT * FROM topics WHERE subject_id = :subject_id ORDER BY name");
    $topics_stmt->execute(['subject_id' => $selected_subject_id]);
    $all_topics = $topics_stmt->fetchAll();

    // Build a tree structure from the flat topic list
    $topics_by_id = [];
    foreach ($all_topics as $topic) {
        $topics_by_id[$topic['id']] = $topic;
        $topics_by_id[$topic['id']]['children'] = [];
    }

    foreach ($topics_by_id as $id => &$topic) {
        if ($topic['parent_id']) {
            if (isset($topics_by_id[$topic['parent_id']])) {
                 $topics_by_id[$topic['parent_id']]['children'][] =& $topic;
            }
        } else {
            $topics_tree[] =& $topic;
        }
    }
}

// Recursive function to render the topics tree
function render_topics($topics, $pdo, $subject_id, $level = 0) {
    ?>
    <ul class="list-group<?= $level > 0 ? ' mt-2' : '' ?>">
        <?php foreach ($topics as $topic) { ?>
            <li class="list-group-item">
                <div>
                    <?= str_repeat('&nbsp;', $level * 4) . htmlspecialchars($topic['name']) ?>
                    <span class="badge bg-warning text-dark"><?= str_repeat('★', $topic['importance']) . str_repeat('☆', 5 - $topic['importance']) ?></span>
                </div>
                
                <div class="mt-2 ms-4">
                    <form action="src/admin/topic_action.php" method="POST" class="row g-2">
                        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                        <input type="hidden" name="parent_id" value="<?= $topic['id'] ?>">
                        <div class="col-auto">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Subtopic name" required>
                        </div>
                        <div class="col-auto">
                            <select name="importance" class="form-select form-select-sm">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="action" value="add" class="btn btn-sm btn-secondary">Add</button>
                        </div>
                    </form>
                </div>

                <?php if (!empty($topic['children'])) {
                    render_topics($topic['children'], $pdo, $subject_id, $level + 1);
                } ?>
            </li>
        <?php } ?>
    </ul>
    <?php
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Manage Topics and Subtopics</h1>
            <a href="index.php?page=dashboard" class="btn btn-secondary">Return to Dashboard</a>
        </div>

        <!-- Subject Selection Form -->
        <div class="card mb-4">
            <div class="card-header">Subject Selection</div>
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="page" value="manage_topics">
                    <div class="input-group">
                        <select name="subject_id" class="form-select" required>
                            <option value="">Please select a subject...</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" type="submit">Show Topics</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_subject_id): ?>
            <div class="row">
                <!-- Add Main Topic Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Add New Main Topic</div>
                        <div class="card-body">
                            <form action="src/admin/topic_action.php" method="POST">
                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject_id; ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Main Topic Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="importance" class="form-label">Importance Level (1-5)</label>
                                    <select class="form-select" id="importance" name="importance" required>
                                        <option value="1">1 - Less Important</option>
                                        <option value="2">2</option>
                                        <option value="3">3 - Medium</option>
                                        <option value="4">4</option>
                                        <option value="5">5 - Critical</option>
                                    </select>
                                </div>
                                <button type="submit" name="action" value="add" class="btn btn-primary">Add</button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Topic Import Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Upload Topics via Excel/CSV</div>
                        <div class="card-body">
                            <form action="src/admin/topic_import_action.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject_id; ?>">
                                <div class="mb-3">
                                    <label for="topicFile" class="form-label">Select Topic File (Excel or CSV)</label>
                                    <input class="form-control" type="file" id="topicFile" name="topic_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                                </div>
                                <button type="submit" class="btn btn-success">Upload and Import</button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Topics List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Topic Hierarchy</div>
                        <div class="card-body">
                            <?php if (empty($topics_tree)): ?>
                                <p class="text-center">No topics found for this subject.</p>
                            <?php else:
                                render_topics($topics_tree, $pdo, $selected_subject_id);
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
