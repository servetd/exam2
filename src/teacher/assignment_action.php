<?php

session_start();

// Güvenlik: Sadece öğretmen bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_exam') {
    $teacher_id = $_SESSION['user_id'];
    $exam_id = $_POST['exam_id'] ?? null;
    $student_ids = $_POST['student_ids'] ?? [];

    // Zamanlamayı al, boşsa null yap
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

    if (!$exam_id) {
        header('Location: ../../index.php?page=manage_exams');
        exit;
    }

    // Güvenlik: Sınavın bu öğretmene ait olduğunu doğrula
    $exam_stmt = $pdo->prepare("SELECT id FROM exams WHERE id = :id AND teacher_id = :teacher_id");
    $exam_stmt->execute(['id' => $exam_id, 'teacher_id' => $teacher_id]);
    if (!$exam_stmt->fetch()) {
        header('Location: ../../index.php?page=manage_exams');
        exit;
    }

    try {
        // Seçilen öğrenciler için yeni atamalar ekle
        if (!empty($student_ids)) {
            $insert_stmt = $pdo->prepare(
                "INSERT INTO exam_assignments (exam_id, student_id, start_time, end_time) VALUES (:exam_id, :student_id, :start_time, :end_time)"
            );
            foreach ($student_ids as $student_id) {
                // Check if assignment already exists
                $check_stmt = $pdo->prepare("SELECT id FROM exam_assignments WHERE exam_id = :exam_id AND student_id = :student_id");
                $check_stmt->execute(['exam_id' => $exam_id, 'student_id' => $student_id]);
                if (!$check_stmt->fetch()) {
                    try {
                        $insert_stmt->execute([
                            'exam_id' => intval($exam_id),
                            'student_id' => intval($student_id),
                            'start_time' => $start_time,
                            'end_time' => $end_time
                        ]);
                    } catch (PDOException $inner_e) {
                        error_log("Error assigning exam to student $student_id: " . $inner_e->getMessage());
                        throw $inner_e;
                    }
                }
            }
        }
        $_SESSION['success'] = 'Öğrenciler sınava başarıyla atanmıştır.';
        header('Location: ../../index.php?page=exams');
        exit;

    } catch (PDOException $e) {
        error_log("Error assigning exam: " . $e->getMessage());
        $_SESSION['error'] = 'Sınav ataması sırasında bir hata oluştu: ' . $e->getMessage();
        header('Location: ../../index.php?page=assign_exam&exam_id=' . $exam_id);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_assignment') {
    $teacher_id = $_SESSION['user_id'];
    $assignment_id = $_POST['assignment_id'] ?? null;
    $exam_id = $_POST['exam_id'] ?? null; // exam_id for redirect

    if ($assignment_id && $exam_id) {
        // Güvenlik: Öğretmenin bu atamayı silme yetkisi olduğunu doğrula
        $stmt = $pdo->prepare(
            'SELECT ea.id FROM exam_assignments ea JOIN exams e ON ea.exam_id = e.id WHERE ea.id = :assignment_id AND e.teacher_id = :teacher_id'
        );
        $stmt->execute(['assignment_id' => $assignment_id, 'teacher_id' => $teacher_id]);
        $assignment = $stmt->fetch();

        if ($assignment) {
            try {
                $delete_stmt = $pdo->prepare("DELETE FROM exam_assignments WHERE id = :id");
                $delete_stmt->execute(['id' => $assignment_id]);
                $_SESSION['success'] = 'Atama başarıyla iptal edilmiştir.';
            } catch (PDOException $e) {
                error_log("Error cancelling assignment: " . $e->getMessage());
                $_SESSION['error'] = 'Atama iptal edilirken bir hata oluştu: ' . $e->getMessage();
            }
        }
    }

    header('Location: ../../index.php?page=assign_exam&exam_id=' . $exam_id);
    exit;
}
