<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS topics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject_id INTEGER NOT NULL,
    parent_id INTEGER DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    importance INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES topics(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($sql);
    echo "'topics' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

