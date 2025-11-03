<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['topic_file'])) {
    $subject_id = $_POST['subject_id'] ?? null;

    if (!$subject_id) {
        $_SESSION['error'] = 'Ders seçimi yapılmadı.';
        header('Location: ../../index.php?page=manage_topics');
        exit;
    }

    $file_mimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    if (in_array($_FILES['topic_file']['type'], $file_mimes)) {
        $spreadsheet = IOFactory::load($_FILES['topic_file']['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        array_shift($rows);

        $root_topics = [];
        $child_topics = [];

        foreach ($rows as $row) {
            $topic_name = trim($row[0]);
            $parent_topic_name = trim($row[1]);
            $importance = (int)($row[2] ?? 1);

            if (empty($topic_name)) {
                continue;
            }

            if (empty($parent_topic_name)) {
                $root_topics[] = ['name' => $topic_name, 'importance' => $importance];
            } else {
                $child_topics[] = ['name' => $topic_name, 'parent_name' => $parent_topic_name, 'importance' => $importance];
            }
        }

        $pdo->beginTransaction();
        try {
            $stmt_insert_topic = $pdo->prepare("INSERT INTO topics (subject_id, parent_id, name, importance) VALUES (:subject_id, :parent_id, :name, :importance)");
            $stmt_find_parent = $pdo->prepare("SELECT id FROM topics WHERE name = :name AND subject_id = :subject_id");

            // Insert root topics first
            foreach ($root_topics as $topic) {
                $stmt_insert_topic->execute([
                    'subject_id' => $subject_id,
                    'parent_id' => null,
                    'name' => $topic['name'],
                    'importance' => $topic['importance']
                ]);
            }

            // Insert child topics
            foreach ($child_topics as $topic) {
                $stmt_find_parent->execute(['name' => $topic['parent_name'], 'subject_id' => $subject_id]);
                $parent = $stmt_find_parent->fetch();

                if ($parent) {
                    $parent_id = $parent['id'];
                    $stmt_insert_topic->execute([
                        'subject_id' => $subject_id,
                        'parent_id' => $parent_id,
                        'name' => $topic['name'],
                        'importance' => $topic['importance']
                    ]);
                } else {
                    $_SESSION['warning'] = 'Parent topic "' . htmlspecialchars($topic['parent_name']) . '" not found for topic "' . htmlspecialchars($topic['name']) . '". Skipping.';
                }
            }

            $pdo->commit();
            $_SESSION['success'] = 'Konular başarıyla içeri aktarıldı.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Konular içeri aktarılırken bir hata oluştu: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Geçersiz dosya türü. Lütfen Excel veya CSV dosyası yükleyin.';
    }
} else {
    $_SESSION['error'] = 'Dosya yüklenirken bir hata oluştu.';
}

header('Location: ../../index.php?page=manage_topics&subject_id=' . $subject_id);
exit;
