<?php

session_start();

// Güvenlik: Sadece admin bu işlemi yapabilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$pdo = require __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Yeni kullanıcı ekle
    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        // Basit doğrulama
        if (!empty($username) && !empty($password) && in_array($role, ['student', 'teacher', 'coach'])) {
            try {
                // Şifreyi hash'le
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)"
                );
                $stmt->execute([
                    'username' => $username,
                    'password_hash' => $password_hash,
                    'role' => $role
                ]);
                $_SESSION['success'] = 'Kullanıcı başarıyla eklendi.';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Bu kullanıcı adı zaten kullanılıyor.';
            }
        } else {
            $_SESSION['error'] = 'Lütfen tüm alanları doldurun.';
        }
    }

    // Şifre sıfırla
    elseif ($action === 'reset_password') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($user_id > 0 && !empty($password) && $password === $password_confirm) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "UPDATE users SET password_hash = :password_hash WHERE id = :id"
                );
                $stmt->execute([
                    'password_hash' => $password_hash,
                    'id' => $user_id
                ]);
                $_SESSION['success'] = 'Şifre başarıyla sıfırlandı.';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Şifre sıfırlanırken hata oluştu.';
                error_log("Error resetting password: " . $e->getMessage());
            }
        } else {
            $_SESSION['error'] = 'Şifreler eşleşmiyor veya boş alanlar var.';
        }
    }

    // Kullanıcı adı ve rol düzenle
    elseif ($action === 'edit_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($user_id > 0 && !empty($username) && in_array($role, ['student', 'teacher', 'coach'])) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE users SET username = :username, role = :role WHERE id = :id"
                );
                $stmt->execute([
                    'username' => $username,
                    'role' => $role,
                    'id' => $user_id
                ]);
                $_SESSION['success'] = 'Kullanıcı başarıyla güncellendi.';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Bu kullanıcı adı zaten kullanılıyor veya hata oluştu.';
                error_log("Error updating user: " . $e->getMessage());
            }
        } else {
            $_SESSION['error'] = 'Lütfen tüm alanları doldurun.';
        }
    }

    // Kullanıcı sil
    elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);

        if ($user_id > 0) {
            try {
                // Kullanıcı ile ilişkili verileri kontrol et
                // 1. Sınav atamalarını sil
                $pdo->prepare("DELETE FROM exam_assignments WHERE student_id = :id OR student_id IN (SELECT id FROM users WHERE id = :id)")->execute(['id' => $user_id]);

                // 2. Sınav sonuçlarını sil
                $pdo->prepare("DELETE FROM exam_results WHERE student_id = :id")->execute(['id' => $user_id]);

                // 3. Kayıtları sil
                $pdo->prepare("DELETE FROM enrollments WHERE user_id = :id OR teacher_id = :id")->execute(['id' => $user_id]);

                // 4. Sınavları sil (öğretmen sınavlarını)
                $pdo->prepare("DELETE FROM exams WHERE teacher_id = :id")->execute(['id' => $user_id]);

                // 5. Son olarak kullanıcıyı sil
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
                $stmt->execute(['id' => $user_id]);

                $_SESSION['success'] = 'Kullanıcı ve ilişkili veriler başarıyla silindi.';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Kullanıcı silinirken hata oluştu.';
                error_log("Error deleting user: " . $e->getMessage());
            }
        } else {
            $_SESSION['error'] = 'Geçersiz kullanıcı ID\'si.';
        }
    }
}

// İşlem sonrası kullanıcı yönetimi sayfasına geri dön
header('Location: ../../index.php?page=manage_users');
exit;
