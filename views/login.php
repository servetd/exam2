<?php http_response_code(200); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: none;
        }

        .login-header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            border-bottom: 4px solid #1e40af;
        }

        .login-header h1 {
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }

        .login-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        .form-control:focus {
            border-color: #2563eb;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .login-submit {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .login-submit:hover {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: none;
            border-left: 4px solid;
        }

        .alert-danger {
            background-color: #fee2e2;
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .alert-success {
            background-color: #d1fae5;
            border-left-color: #10b981;
            color: #047857;
        }

        .login-divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: #e5e7eb;
        }

        .login-divider span {
            margin: 0 1rem;
        }

        .role-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .role-admin {
            background-color: #ede9fe;
            color: #6d28d9;
        }

        .role-teacher {
            background-color: #cffafe;
            color: #0e7490;
        }

        .role-student {
            background-color: #d1fae5;
            color: #047857;
        }

        .role-coach {
            background-color: #fef3c7;
            color: #92400e;
        }

        .demo-credentials {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .demo-credentials-title {
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-credential-item {
            font-size: 0.8125rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .demo-credential-item code {
            background-color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            color: #2563eb;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>📝 Exam System</h1>
                <p>Student Tracking and Assessment Platform</p>
            </div>

            <div class="login-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <form action="/src/login_action.php" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">👤 Username</label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">🔐 Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-submit">Login</button>
                </form>

                <div class="login-divider">
                    <span>Demo Accounts</span>
                </div>

                <div class="demo-credentials">
                    <div class="demo-credentials-title">Roles:</div>

                    <div class="demo-credential-item">
                        <span><span class="role-badge role-admin">Admin</span> Administration</span>
                        <code>admin</code>
                    </div>

                    <div class="demo-credential-item">
                        <span><span class="role-badge role-teacher">Teacher</span> Exam Management</span>
                        <code>teacher</code>
                    </div>

                    <div class="demo-credential-item">
                        <span><span class="role-badge role-student">Student</span> Take Exams</span>
                        <code>student</code>
                    </div>

                    <div class="demo-credential-item">
                        <span><span class="role-badge role-coach">Coach</span> Student Tracking</span>
                        <code>coach</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
