<?php
/**
 * Modern Navbar Component
 * Standard top bar used on all pages
 */

// Translate role names to English
$role_names = [
    'admin' => ['name' => 'Administrator', 'icon' => '⚙️'],
    'teacher' => ['name' => 'Teacher', 'icon' => '👨‍🏫'],
    'student' => ['name' => 'Student', 'icon' => '👨‍🎓'],
    'coach' => ['name' => 'Coach', 'icon' => '🏆']
];

$current_role = $_SESSION['role'] ?? 'student';
$role_info = $role_names[$current_role] ?? ['name' => $current_role, 'icon' => '👤'];
?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1f2937 0%, #374151 100%); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-bottom: 3px solid #2563eb;">
    <div class="container-fluid" style="padding: 0.75rem 1.5rem;">
        <a class="navbar-brand" href="index.php?page=dashboard" style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">
            <span style="font-size: 1.5rem; margin-right: 0.5rem;">📝</span>
            <span>Exam System</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" style="align-items: center; gap: 1rem;">
                <li class="nav-item">
                    <span class="nav-link" style="color: white; cursor: default; font-weight: 500;">
                        <span style="font-size: 1.2rem; margin-right: 0.5rem; display: inline-block;">
                            <?php echo $role_info['icon']; ?>
                        </span>
                        <strong><?php echo htmlspecialchars($role_info['name']); ?></strong>
                    </span>
                </li>
                <li class="nav-item">
                    <span class="nav-link" style="color: #9ca3af; cursor: default; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=logout" style="color: #fca5a5; transition: color 0.2s ease;">
                        🚪 Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
