<?php
$pdo = require __DIR__ . '/../config/database.php';
$sql = "ALTER TABLE exam_questions DROP COLUMN topic_id";
try {
    $pdo->exec($sql);
    echo "'exam_questions' tablosundan 'topic_id' kolonu kaldırıldı.\n";
} catch (PDOException $e) {
    // Ignore if column doesn't exist
    if (strpos($e->getMessage(), 'Cannot drop column') === false) {
        die("Sütun silme hatası: " . $e->getMessage());
    }
}

