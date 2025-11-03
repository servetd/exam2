<?php
session_start();
header('Content-Type: application/json');

// Güvenlik: Sadece öğretmen bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = require __DIR__ . '/../config/database.php';
$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Subject ID is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM topics WHERE subject_id = :subject_id ORDER BY name");
$stmt->execute(['subject_id' => $subject_id]);
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($topics);
