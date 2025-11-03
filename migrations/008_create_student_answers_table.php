<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS student_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    assignment_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    answer_text TEXT NULL,
    answer_file_url VARCHAR(255) NULL,
    FOREIGN KEY (assignment_id) REFERENCES exam_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
);
SQL;

try {
    $pdo->exec($sql);
    echo "'student_answers' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

