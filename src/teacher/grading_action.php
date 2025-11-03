<?php

session_start();

// Güvenlik: Sadece öğretmen bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_grades') {
    $teacher_id = $_SESSION['user_id'];
    $assignment_id = $_POST['assignment_id'] ?? null;
    $exam_id = $_POST['exam_id'] ?? null; // For redirect
    $scores = $_POST['scores'] ?? [];

    if (!$assignment_id || !$exam_id) {
        header('Location: ../../index.php?page=evaluate_exams');
        exit;
    }

    // Güvenlik: Atamanın bu öğretmenin bir sınavına ait olduğunu doğrula
    $stmt = $pdo->prepare(
        "SELECT a.id FROM exam_assignments a JOIN exams e ON a.exam_id = e.id WHERE a.id = :assignment_id AND e.teacher_id = :teacher_id"
    );
    $stmt->execute(['assignment_id' => $assignment_id, 'teacher_id' => $teacher_id]);
    if (!$stmt->fetch()) {
        header('Location: ../../index.php?page=evaluate_exams');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Puanları güncelle
        $update_stmt = $pdo->prepare("UPDATE student_answers SET score = :score WHERE id = :answer_id");
        foreach ($scores as $answer_id => $score) {
            if (is_numeric($score)) {
                $update_stmt->execute(['score' => $score, 'answer_id' => $answer_id]);
            }
        }

        // Sınav atamasının durumunu 'graded' olarak güncelle
        $update_status_stmt = $pdo->prepare("UPDATE exam_assignments SET status = 'graded' WHERE id = :id");
        $update_status_stmt->execute(['id' => $assignment_id]);

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Puanlar kaydedilirken bir hata oluştu: " . $e->getMessage());
    }

    // İşlem sonrası öğrenci listesine geri dön
    header('Location: ../../index.php?page=evaluate_submissions&exam_id=' . $exam_id);
    exit;
}

// Fallback redirect
header('Location: ../../index.php?page=evaluate_exams');
exit;
