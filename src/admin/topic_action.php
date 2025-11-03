<?php

session_start();

// Güvenlik: Sadece admin bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

$redirect_url = '../../index.php?page=manage_topics';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $subject_id = $_POST['subject_id'] ?? null;
    $parent_id = empty($_POST['parent_id']) ? null : $_POST['parent_id'];
    $name = trim($_POST['name'] ?? '');
    $importance = $_POST['importance'] ?? 1;

    // Add subject_id to redirect URL to stay on the same page
    if ($subject_id) {
        $redirect_url .= '&subject_id=' . $subject_id;
    }

    if (!empty($name) && !empty($subject_id)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO topics (subject_id, parent_id, name, importance) VALUES (:subject_id, :parent_id, :name, :importance)"
            );
            $stmt->execute([
                'subject_id' => $subject_id,
                'parent_id' => $parent_id,
                'name' => $name,
                'importance' => $importance
            ]);
        } catch (PDOException $e) {
            // TODO: Handle potential errors and show feedback to the user
            // For now, we just redirect
        }
    }
}

// İşlem sonrası konu yönetimi sayfasına geri dön
header('Location: ' . $redirect_url);
exit;
