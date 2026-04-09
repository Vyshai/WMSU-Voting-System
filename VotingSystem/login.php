<?php
require_once "session_config.php";

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'voter' ? "voter/dashboard.php" : "admin/dashboard.php"));
    exit();
}

require_once "User.php";
require_once "Vote.php";
$userObj = new User();
$voteObj = new Vote();

$student_id = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim(htmlspecialchars($_POST["student_id"]));
    $password = $_POST["password"];

    if (empty($student_id) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $user = $userObj->login($student_id, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['course'] = $user['course'];

            $voteObj->logAction($user['id'], 'LOGIN', 'User logged in', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            session_write_close();
            if ($user['role'] === 'voter') {
                header("Location: voter/dashboard.php");
            } else {
                header("Location: admin/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid Student ID or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WMSU Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --gold: #D4A843; --gold-dark: #b8912a; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-wrapper {
            display: flex;
            max-width: 900px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .login-left {
            background: linear-gradient(160deg, var(--primary-dark), var(--primary));
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }
        .login-logo {
            width: 80px; height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 3px solid var(--gold);
        }
        .login-logo img { width: 100%; height: 100%; object-fit: cover; }
        .login-left h2 { font-size: 26px; font-weight: 800; margin-bottom: 10px; }
        .login-left p { font-size: 14px; opacity: 0.75; line-height: 1.6; }
        .login-left .features { margin-top: 30px; text-align: left; }
        .login-left .feature-item { display: flex; align-items: center; gap: 10px; margin: 12px 0; font-size: 14px; opacity: 0.85; }
        .login-left .feature-item svg { width: 20px; height: 20px; fill: var(--gold); flex-shrink: 0; }

        .login-right { background: white; padding: 60px 50px; flex: 1; }
        .login-right h1 { font-size: 28px; font-weight: 800; color: var(--primary); margin-bottom: 8px; }
        .login-right p { color: #718096; font-size: 14px; margin-bottom: 35px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: #2d3748; font-size: 14px; margin-bottom: 7px; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 13px 16px;
            border: 2px solid #e2e8f0; border-radius: 9px;
            font-size: 15px; transition: all 0.3s;
            background: #f8fafc; font-family: 'Inter', sans-serif;
        }
        input:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 3px rgba(123,17,19,0.1); }
        .error-box {
            background: #fff5f5; color: #c53030;
            border: 1px solid #feb2b2; border-radius: 8px;
            padding: 12px 16px; font-size: 14px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
        }
        .error-box svg { width: 18px; height: 18px; fill: #c53030; flex-shrink: 0; }
        .btn-login {
            width: 100%; padding: 14px;
            background: var(--primary); color: white;
            border: none; border-radius: 9px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            transition: all 0.3s; margin-top: 5px; font-family: 'Inter', sans-serif;
        }
        .btn-login:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(123,17,19,0.3); }
        .login-footer { margin-top: 25px; text-align: center; font-size: 14px; color: #718096; }
        .login-footer a { color: var(--gold-dark); font-weight: 600; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 25px 0; }
        .home-link { text-align: center; margin-top: 15px; }
        .home-link a { color: #718096; font-size: 13px; text-decoration: none; }
        .home-link a:hover { color: var(--primary); }

        @media (max-width: 700px) {
            .login-left { display: none; }
            .login-right { padding: 40px 30px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-left">
        <div class="login-logo"><img src="uploads/wmsu_logo.png" alt="WMSU Logo"></div>
        <h2>WMSU Online Voting</h2>
        <p>Western Mindanao State University<br>University Student Council Elections</p>
        <div class="features">
            <div class="feature-item"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg> Secure Authentication</div>
            <div class="feature-item"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> One-Vote Per Position</div>
            <div class="feature-item"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg> Real-Time Results</div>
            <div class="feature-item"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Fully Auditable</div>
        </div>
    </div>
    <div class="login-right">
        <h1>Welcome Back!</h1>
        <p>Login with your WMSU Student ID to access the voting system</p>

        <?php if ($error): ?>
            <div class="error-box"><svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" name="student_id" id="student_id"
                       placeholder="e.g., 2021-00001"
                       value="<?php echo $student_id; ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <hr class="divider">
        <div class="login-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        <div class="home-link">
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>