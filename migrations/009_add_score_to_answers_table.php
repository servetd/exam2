<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
ALTER TABLE student_answers ADD COLUMN score INTEGER NULL;
SQL;

try {
    $pdo->exec($sql);
    echo "'student_answers' tablosuna 'score' kolonu başarıyla eklendi.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "'score' kolonu zaten mevcut.\n";
    } else {
        die("Tablo değiştirme hatası: " . $e->getMessage());
    }
}

