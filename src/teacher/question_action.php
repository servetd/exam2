<?php

session_start();

// Güvenlik: Sadece öğretmen bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $exam_id = $_POST['exam_id'] ?? null;
    $question_number = $_POST['question_number'] ?? null;
    $type = $_POST['type'] ?? '';

    // Redirect URL
    $redirect_url = '../../index.php?page=manage_questions&exam_id=' . $exam_id;

    // Security check: ensure the exam belongs to the teacher
    $exam_stmt = $pdo->prepare("SELECT id FROM exams WHERE id = :id AND teacher_id = :teacher_id");
    $exam_stmt->execute(['id' => $exam_id, 'teacher_id' => $_SESSION['user_id']]);
    if (!$exam_stmt->fetch()) {
        header('Location: ../../index.php?page=manage_exams');
        exit;
    }

    if ($exam_id && $question_number && $type) {
        $metadata = null;
        $data = [];

        switch ($type) {
            case 'multiple_choice':
                $data['options'] = $_POST['options'] ?? [];
                $data['answer'] = $_POST['answer'] ?? '';
                break;
            case 'true_false':
                $data['answer'] = $_POST['answer'] ?? '';
                break;
            case 'essay':
                // No specific metadata needed
                break;
        }

        $metadata = json_encode($data);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO exam_questions (exam_id, question_number, type, metadata) VALUES (:exam_id, :question_number, :type, :metadata)"
            );
            $stmt->execute([
                'exam_id' => $exam_id,
                'question_number' => $question_number,
                'type' => $type,
                'metadata' => $metadata
            ]);
        } catch (PDOException $e) {
            // TODO: Handle errors, like duplicate question number
            die("Soru eklenirken bir hata oluştu: " . $e->getMessage());
        }
    }

    header('Location: ' . $redirect_url);
    exit;
}

// Fallback redirect
header('Location: ../../index.php?page=manage_exams');
exit;
