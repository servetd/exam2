<?php
$pdo = require __DIR__ . '/../config/database.php';
$sql = "ALTER TABLE exam_questions ADD COLUMN points INTEGER NOT NULL DEFAULT 10";
try {
    $pdo->exec($sql);
    echo "'exam_questions' tablosuna 'points' kolonu eklendi.\n";
} catch (PDOException $e) {
    // Ignore if column already exists
    if (strpos($e->getMessage(), 'duplicate column name') === false) {
        die("Sütun ekleme hatası: " . $e->getMessage());
    }
}

