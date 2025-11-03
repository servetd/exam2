<?php
$pdo = require __DIR__ . '/../config/database.php';
$sql = "CREATE TABLE IF NOT EXISTS exam_question_topics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_question_id INTEGER NOT NULL,
    topic_id INTEGER NOT NULL,
    FOREIGN KEY (exam_question_id) REFERENCES exam_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);
";
try {
    $pdo->exec($sql);
    echo "'exam_question_topics' tablosu başarıyla oluşturuldu.\n";
} catch (PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}

