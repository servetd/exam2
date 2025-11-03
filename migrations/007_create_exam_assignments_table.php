<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS exam_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status TEXT CHECK(status IN ('assigned', 'in_progress', 'submitted', 'graded')) NOT NULL DEFAULT 'assigned',
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($sql);
    echo "'exam_assignments' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

