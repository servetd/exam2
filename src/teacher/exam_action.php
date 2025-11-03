<?php

session_start();

// Güvenlik: Sadece öğretmen bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_exam_with_questions') {
    $teacher_id = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $subject_id = $_POST['subject_id'] ?? null;
    $questions = $_POST['questions'] ?? [];
    $source_type = $_POST['source_type'] ?? 'pdf';
    $file_path = null;

    // Validate basic exam info
    if (empty($name) || empty($subject_id) || empty($questions)) {
        $_SESSION['error'] = 'Sınav adı, ders ve en az bir soru gereklidir.';
        header('Location: ../../index.php?page=create_exam');
        exit;
    }

    if ($source_type === 'pdf') {
        if (!isset($_FILES['question_pdf']) || $_FILES['question_pdf']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Lütfen bir PDF dosyası yükleyin.';
            header('Location: ../../index.php?page=create_exam');
            exit;
        }
        // PDF dosya yükleme işlemi
        $upload_dir = '/Applications/XAMPP/xamppfiles/htdocs/public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['question_pdf']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['question_pdf']['tmp_name'], $target_file)) {
            $file_path = 'public/uploads/' . $file_name;
        } else {
            $_SESSION['error'] = 'PDF dosyası yüklenirken bir hata oluştu.';
            header('Location: ../../index.php?page=create_exam');
            exit;
        }
    } else { // source_type === 'url'
        $url = filter_var($_POST['question_url'], FILTER_VALIDATE_URL);
        if ($url === false) {
            $_SESSION['error'] = 'Geçersiz URL formatı.';
            header('Location: ../../index.php?page=create_exam');
            exit;
        }
        $file_path = $url;
    }

    $pdo->beginTransaction();

    try {
        // 1. Create the exam
        $stmt = $pdo->prepare(
            "INSERT INTO exams (teacher_id, name, subject_id, question_pdf_url, status) VALUES (:teacher_id, :name, :subject_id, :pdf_path, 'draft')"
        );
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'name' => $name,
            'subject_id' => $subject_id,
            'pdf_path' => $file_path
        ]);
        $exam_id = $pdo->lastInsertId();

        // 2. Create the questions and link topics
        $stmt_question = $pdo->prepare(
            "INSERT INTO exam_questions (exam_id, question_number, type, metadata, points) VALUES (:exam_id, :question_number, :type, :metadata, :points)"
        );
        $stmt_topic = $pdo->prepare(
            "INSERT INTO exam_question_topics (exam_question_id, topic_id) VALUES (:exam_question_id, :topic_id)"
        );

        foreach ($questions as $q) {
            $type = $q['type'] ?? '';
            $metadata_array = [];

            switch ($type) {
                case 'multiple_choice':
                    $metadata_array['answer'] = $q['answer'] ?? '';
                    // Çoktan seçmeli soruların şıklarını kaydet
                    if (isset($q['options'])) {
                        $metadata_array['options'] = $q['options'];
                    }
                    break;
                case 'true_false':
                case 'fill_in_the_blank':
                    $metadata_array['answer'] = $q['answer'] ?? '';
                    break;
                case 'essay':
                    break;
            }

            $metadata = json_encode($metadata_array);

            $stmt_question->execute([
                'exam_id' => $exam_id,
                'question_number' => $q['question_number'],
                'type' => $type,
                'metadata' => $metadata,
                'points' => $q['points']
            ]);
            $exam_question_id = $pdo->lastInsertId();

            $topic_ids = (array)($q['topic_id'] ?? []);
            foreach ($topic_ids as $topic_id) {
                if (!empty($topic_id)) {
                    $stmt_topic->execute([
                        'exam_question_id' => $exam_question_id,
                        'topic_id' => $topic_id
                    ]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Sınav ve sorular başarıyla oluşturuldu.';
        header('Location: ../../index.php?page=exams');
        exit;

    } catch (PDOException $e) {

        $pdo->rollBack();

        $_SESSION['error'] = 'Sınav oluşturulurken bir veritabanı hatası oluştu: ' . $e->getMessage();

        header('Location: ../../index.php?page=create_exam');

        exit;

    }} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'publish_exam') {
    // This part remains the same
    $teacher_id = $_SESSION['user_id'];
    $exam_id = $_POST['exam_id'] ?? null;

    if ($exam_id) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE exams SET status = 'published' WHERE id = :exam_id AND teacher_id = :teacher_id"
            );
            $stmt->execute([
                'exam_id' => $exam_id,
                'teacher_id' => $teacher_id
            ]);
        } catch (PDOException $e) {
            die("Sınav yayınlanırken bir hata oluştu: " . $e->getMessage());
        }
    }
    header('Location: ../../index.php?page=manage_questions&exam_id=' . $exam_id);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $teacher_id = $_SESSION['user_id'];
    $exam_id = $_POST['exam_id'] ?? null;

    if ($exam_id) {
        // Check for assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_assignments WHERE exam_id = :exam_id");
        $stmt->execute(['exam_id' => $exam_id]);
        $assignment_count = $stmt->fetchColumn();

        if ($assignment_count > 0) {
            $_SESSION['error'] = 'Bu sınav öğrencilere atandığı için silinemez.';
            header('Location: ../../index.php?page=exams');
            exit;
        }

        // If no assignments, proceed with deletion
        try {
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = :exam_id AND teacher_id = :teacher_id");
            $stmt->execute(['exam_id' => $exam_id, 'teacher_id' => $teacher_id]);
            $_SESSION['success'] = 'Sınav başarıyla silindi.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Sınav silinirken bir hata oluştu: ' . $e->getMessage();
        }
    }
    header('Location: ../../index.php?page=exams');
    exit;
}

// Fallback for old create_exam action or direct access
header('Location: ../../index.php?page=exams');
exit;
