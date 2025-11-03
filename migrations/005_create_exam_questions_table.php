<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS exam_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    question_number INTEGER NOT NULL,
    type TEXT CHECK(type IN ('multiple_choice', 'true_false', 'fill_in_the_blank', 'essay')) NOT NULL,
    -- metadata stores JSON like {"options": ["A", "B", "C"], "answer": "C"} or {"answer": "Some text"}
    metadata TEXT,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($sql);
    echo "'exam_questions' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

