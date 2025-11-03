<?php

$pdo = require __DIR__ . '/../config/database.php';

$sql = <<<'SQL'
ALTER TABLE exams ADD COLUMN subject_id INTEGER;
UPDATE exams SET subject_id = 0;
SQL;

try {
    $pdo->exec($sql);
    echo "'exams' tablosuna 'subject_id' kolonu başarıyla eklendi.\n";
} catch (PDOException $e) {
    // Bu hata, kolon zaten varsa ortaya çıkabilir, bu yüzden görmezden gelebiliriz.
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "'subject_id' kolonu zaten mevcut.\n";
    } else {
        die("Tablo değiştirme hatası: " . $e->getMessage());
    }
}

