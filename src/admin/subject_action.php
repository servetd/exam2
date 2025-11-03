<?php

session_start();

// Güvenlik: Sadece admin bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Yetkisiz erişimde login sayfasına yönlendir
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);
        } catch (PDOException $e) {
            // UNIQUE kısıtlaması ihlali gibi hataları yakalayabiliriz
            // Şimdilik basit bir yönlendirme yapalım
            // TODO: Kullanıcıya hata mesajı göster
        }
    }
}

// İşlem sonrası ders yönetimi sayfasına geri dön
header('Location: ../../index.php?page=manage_subjects');
exit;
