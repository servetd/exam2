<?php

session_start();

// Veritabanı bağlantısını dahil et
$pdo = require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        // Boş kullanıcı adı veya şifre
        header('Location: ../index.php?page=login&error=credentials');
        exit;
    }

    // Kullanıcıyı veritabanında bul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // Kullanıcı bulundu ve şifre doğru
    if ($user && password_verify($password, $user['password_hash'])) {
        // Session bilgilerini ayarla
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Kontrol paneline yönlendir
        header('Location: ../index.php?page=dashboard');
        exit;
    } else {
        // Hatalı kimlik bilgileri
        header('Location: ../index.php?page=login&error=credentials');
        exit;
    }
} else {
    // POST isteği değilse, doğrudan erişimi engelle
    header('Location: ../index.php?page=login');
    exit;
}
