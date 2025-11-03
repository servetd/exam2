<?php

session_start();

// Güvenlik: Sadece admin bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $coach_id = intval($_POST['coach_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);

    if ($coach_id <= 0 || $student_id <= 0) {
        $_SESSION['error'] = 'Geçersiz koç veya öğrenci ID\'si.';
        header('Location: ../../index.php?page=manage_coach_students&coach_id=' . $coach_id);
        exit;
    }

    // Koç kontrolü
    $coach_check = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'coach'");
    $coach_check->execute(['id' => $coach_id]);
    if (!$coach_check->fetch()) {
        $_SESSION['error'] = 'Geçersiz koç.';
        header('Location: ../../index.php?page=manage_users');
        exit;
    }

    // Öğrenci kontrolü
    $student_check = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
    $student_check->execute(['id' => $student_id]);
    if (!$student_check->fetch()) {
        $_SESSION['error'] = 'Geçersiz öğrenci.';
        header('Location: ../../index.php?page=manage_coach_students&coach_id=' . $coach_id);
        exit;
    }

    if ($action === 'assign_student') {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO coach_students (coach_id, student_id) VALUES (:coach_id, :student_id)"
            );
            $stmt->execute([
                'coach_id' => $coach_id,
                'student_id' => $student_id
            ]);
            $_SESSION['success'] = 'Öğrenci başarıyla koça atandı.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Bu öğrenci zaten bu koça atanmış.';
            error_log("Error assigning student: " . $e->getMessage());
        }
    } elseif ($action === 'remove_student') {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM coach_students WHERE coach_id = :coach_id AND student_id = :student_id"
            );
            $stmt->execute([
                'coach_id' => $coach_id,
                'student_id' => $student_id
            ]);
            $_SESSION['success'] = 'Öğrenci başarıyla koçtan çıkarıldı.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Öğrenci çıkarılırken hata oluştu.';
            error_log("Error removing student: " . $e->getMessage());
        }
    }
}

header('Location: ../../index.php?page=manage_coach_students&coach_id=' . $coach_id);
exit;
