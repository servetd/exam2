<?php

session_start();

// Güvenlik: Sadece admin bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!$user_id) {
        header('Location: ../../index.php?page=manage_users');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Önce kullanıcının mevcut tüm kayıtlarını sil
        $delete_stmt = $pdo->prepare("DELETE FROM enrollments WHERE user_id = :user_id");
        $delete_stmt->execute(['user_id' => $user_id]);

        if ($action === 'update_teacher_enrollments') {
            $subjects = $_POST['subjects'] ?? [];
            $insert_stmt = $pdo->prepare("INSERT INTO enrollments (user_id, subject_id) VALUES (:user_id, :subject_id)");
            foreach ($subjects as $subject_id) {
                $insert_stmt->execute(['user_id' => $user_id, 'subject_id' => $subject_id]);
            }
        } elseif ($action === 'update_student_enrollments') {
            $enrollments = $_POST['enrollments'] ?? [];
            $insert_stmt = $pdo->prepare("INSERT INTO enrollments (user_id, subject_id, teacher_id) VALUES (:user_id, :subject_id, :teacher_id)");
            foreach ($enrollments as $subject_id => $teacher_id) {
                if (!empty($teacher_id)) { // Sadece bir öğretmen seçilmişse kaydet
                    $insert_stmt->execute([
                        'user_id' => $user_id,
                        'subject_id' => $subject_id,
                        'teacher_id' => $teacher_id
                    ]);
                }
            }
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        // TODO: Hata yönetimi
        die("Bir hata oluştu: " . $e->getMessage());
    }
}

// İşlem sonrası kullanıcı yönetimi sayfasına geri dön
header('Location: ../../index.php?page=manage_users');
exit;
