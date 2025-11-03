<?php

session_start();

// Güvenlik: Sadece öğrenci bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_exam') {
    $student_id = $_SESSION['user_id'];
    $assignment_id = $_POST['assignment_id'] ?? null;
    $answers = $_POST['answers'] ?? [];

    if (!$assignment_id) {
        header('Location: ../../index.php?page=dashboard');
        exit;
    }

    // Güvenlik: Atamanın bu öğrenciye ait ve 'in_progress' olduğunu doğrula
    $assignment_stmt = $pdo->prepare("SELECT id FROM exam_assignments WHERE id = :id AND student_id = :student_id AND status = 'in_progress'");
    $assignment_stmt->execute(['id' => $assignment_id, 'student_id' => $student_id]);
    if (!$assignment_stmt->fetch()) {
        header('Location: ../../index.php?page=dashboard');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Cevapları işle
        $insert_stmt = $pdo->prepare(
            "INSERT INTO student_answers (assignment_id, question_id, answer_text, answer_file_url) VALUES (:assignment_id, :question_id, :answer_text, :answer_file_url)"
        );

        foreach ($answers as $question_id => $answer) {
            $answer_text = null;
            $answer_file_url = null;

            if (is_array($answer)) { // Essay sorusu için metin cevabı
                $answer_text = $answer['text'];
            } else { // Çoktan seçmeli veya D/Y
                $answer_text = $answer;
            }

            // Essay için dosya yükleme
            if (isset($_FILES['answers_files']['name'][$question_id]) && $_FILES['answers_files']['error'][$question_id] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/';
                $file_name = 'answer_' . $assignment_id . '_' . $question_id . '_' . time() . '_' . basename($_FILES['answers_files']['name'][$question_id]);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['answers_files']['tmp_name'][$question_id], $target_file)) {
                    $answer_file_url = 'public/uploads/' . $file_name;
                }
            }

            $insert_stmt->execute([
                'assignment_id' => $assignment_id,
                'question_id' => $question_id,
                'answer_text' => $answer_text,
                'answer_file_url' => $answer_file_url
            ]);
        }

        // Sınav durumunu 'submitted' olarak güncelle
        $update_status_stmt = $pdo->prepare("UPDATE exam_assignments SET status = 'submitted' WHERE id = :id");
        $update_status_stmt->execute(['id' => $assignment_id]);

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Sınav gönderilirken bir hata oluştu: " . $e->getMessage());
    }
}

// İşlem sonrası panoya geri dön
header('Location: ../../index.php?page=dashboard');
exit;
