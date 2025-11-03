<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    question_pdf_url VARCHAR(255) NULL,
    status TEXT CHECK(status IN ('draft', 'published')) NOT NULL DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($sql);
    echo "'exams' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

