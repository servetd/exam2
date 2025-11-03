<?php

// Veritabanı bağlantısını dahil et
$pdo = require __DIR__ . '/config/database.php';

if ($pdo) {
    echo "Veritabanı bağlantısı başarılı.\n";
} else {
    echo "Veritabanı bağlantısı başarısız.\n";
    exit;
}

// Users tablosunu oluşturma
$sql_users = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role TEXT CHECK(role IN ('admin', 'teacher', 'student')) NOT NULL
);
SQL;

try {
    $pdo->exec($sql_users);
    echo "'users' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";

    // Varsayılan admin kullanıcısını ekle (eğer yoksa)
    $admin_username = 'admin';
    $admin_password = 'admin123'; // Gerçek bir uygulamada bu şifre güvenli bir şekilde saklanmalıdır.
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $role = 'admin';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $admin_username]);

    if ($stmt->fetch()) {
        echo "Varsayılan admin kullanıcısı zaten mevcut.\n";
    } else {
        $insert_admin_sql = "INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)";
        $stmt = $pdo->prepare($insert_admin_sql);
        $stmt->execute([
            'username' => $admin_username,
            'password_hash' => $password_hash,
            'role' => $role
        ]);
        echo "Varsayılan admin kullanıcısı başarıyla eklendi (Kullanıcı Adı: admin, Şifre: admin123).\n";
    }

} catch (PDOException $e) {
    die("Tablo oluşturma veya veri ekleme hatası: " . $e->getMessage());
}

