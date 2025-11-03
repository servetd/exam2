<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    -- For a student, this is the teacher of the class they are enrolled in.
    -- For a teacher, this can be NULL.
    teacher_id INTEGER DEFAULT NULL, 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, subject_id, teacher_id) -- Prevents duplicate enrollments
);
SQL;

try {
    $pdo->exec($sql);
    echo "'enrollments' tablosu başarıyla oluşturuldu veya zaten mevcut.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

