<?php

session_start();

// Veritabanı bağlantısını dahil et
$pdo = require __DIR__ . '/config/database.php';

// Basit yönlendirme (routing)
$page = $_GET['page'] ?? 'login';

// Eğer kullanıcı giriş yapmışsa ve anasayfaya gitmek istiyorsa
if (isset($_SESSION['user_id']) && $page === 'login') {
    $page = 'dashboard'; // veya anasayfa
}

// Sayfaları dahil etme
switch ($page) {
    case 'login':
        require __DIR__ . '/views/login.php';
        break;
    case 'dashboard':
        // Yetkilendirme kontrolü
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/dashboard.php';
        break;

    case 'take_exam':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_GET['assignment_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        require __DIR__ . '/views/student/take_exam.php';
        break;

    case 'exams':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/teacher/exams.php';
        break;

    case 'create_exam':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/teacher/exam_form.php';
        break;

    case 'manage_questions':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['exam_id'])) {
            header('Location: index.php?page=exams');
            exit;
        }
        require __DIR__ . '/views/teacher/manage_questions.php';
        break;

    case 'assign_exam':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['exam_id'])) {
            header('Location: index.php?page=exams');
            exit;
        }
        require __DIR__ . '/views/teacher/assign_exam.php';
        break;

    case 'evaluate_exams':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/teacher/evaluate_exams_list.php';
        break;

    case 'results':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/teacher/results.php';
        break;

    case 'results_students':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['subject_id'])) {
            header('Location: index.php?page=results');
            exit;
        }
        require __DIR__ . '/views/teacher/results_students.php';
        break;

    case 'results_exams':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['student_id']) || !isset($_GET['subject_id'])) {
            header('Location: index.php?page=results');
            exit;
        }
        require __DIR__ . '/views/teacher/results_exams.php';
        break;

    case 'results_exam_detail':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['assignment_id'])) {
            header('Location: index.php?page=results');
            exit;
        }
        require __DIR__ . '/views/teacher/results_exam_detail.php';
        break;

    case 'analytics':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/teacher/analytics.php';
        break;

    case 'analytics_students':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['subject_id'])) {
            header('Location: index.php?page=analytics');
            exit;
        }
        require __DIR__ . '/views/teacher/analytics_students.php';
        break;

    case 'analytics_topics':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['student_id']) || !isset($_GET['subject_id'])) {
            header('Location: index.php?page=analytics');
            exit;
        }
        require __DIR__ . '/views/teacher/analytics_topics.php';
        break;

    case 'evaluate_submissions':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['exam_id'])) {
            header('Location: index.php?page=evaluate_exams');
            exit;
        }
        require __DIR__ . '/views/teacher/evaluate_submissions_list.php';
        break;

    case 'grade_submission':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher' || !isset($_GET['assignment_id'])) {
            header('Location: index.php?page=evaluate_exams');
            exit;
        }
        require __DIR__ . '/views/teacher/grade_submission.php';
        break;
    case 'manage_enrollments':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_GET['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        require __DIR__ . '/views/admin/enrollments.php';
        break;

    case 'manage_users':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/admin/users.php';
        break;

    case 'manage_topics':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/admin/topics.php';
        break;

    case 'manage_subjects':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/admin/subjects.php';
        break;

    case 'manage_coach_students':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/admin/manage_coach_students.php';
        break;

    case 'coach_students':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/coach/students.php';
        break;

    case 'coach_student_results':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/coach/student_results.php';
        break;

    case 'coach_exam_detail':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/coach/exam_detail.php';
        break;

    case 'coach_student_analysis':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
            header('Location: index.php?page=login');
            exit;
        }
        require __DIR__ . '/views/coach/student_analysis.php';
        break;

    case 'logout':
        session_destroy();
        header('Location: index.php?page=login');
        exit;

    default:
        http_response_code(404);
        echo "Sayfa bulunamadı!";
        break;
}
