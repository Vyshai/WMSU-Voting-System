<?php
require_once "session_config.php";
if (isset($_SESSION['user_id'])) {
    header("Location: voter/dashboard.php"); exit();
}
require_once "User.php";
$userObj = new User();

$data = ["student_id"=>"","last_name"=>"","first_name"=>"","middle_initial"=>"","email"=>"","course"=>"","year_level"=>"1","password"=>"","confirm_password"=>""];
$errors = [];
$success = "";

$courses = ["BS Computer Science","BS Information Technology","BS Computer Engineering",
            "BS Electrical Engineering","BS Civil Engineering","BS Nursing","BS Education",
            "BA Communication","BA Political Science","BS Business Administration","Others"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($data as $k => $_) {
        $data[$k] = trim(htmlspecialchars($_POST[$k] ?? ''));
    }
    $data['password'] = $_POST['password'] ?? '';
    $data['confirm_password'] = $_POST['confirm_password'] ?? '';

    if (empty($data['student_id'])) $errors['student_id'] = "Student ID is required";
    if (empty($data['last_name'])) $errors['last_name'] = "Last name is required";
    if (empty($data['first_name'])) $errors['first_name'] = "First name is required";
    if (empty($data['email'])) $errors['email'] = "Email is required";
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";
    elseif (!str_ends_with($data['email'], '@wmsu.edu.ph')) $errors['email'] = "Must use your WMSU email (@wmsu.edu.ph)";
    if (empty($data['course'])) $errors['course'] = "Course is required";
    if (empty($data['password'])) $errors['password'] = "Password is required";
    elseif (strlen($data['password']) < 8) $errors['password'] = "Password must be at least 8 characters";
    if ($data['password'] !== $data['confirm_password']) $errors['confirm_password'] = "Passwords do not match";

    if (empty($errors)) {
        if ($userObj->studentIdExists($data['student_id'])) $errors['student_id'] = "Student ID already registered";
        elseif ($userObj->emailExists($data['email'])) $errors['email'] = "Email already registered";
        else {
            $userObj->student_id = $data['student_id'];
            $userObj->last_name = $data['last_name'];
            $userObj->first_name = $data['first_name'];
            $userObj->middle_initial = $data['middle_initial'];
            $userObj->email = $data['email'];
            $userObj->course = $data['course'];
            $userObj->year_level = (int)$data['year_level'];
            $userObj->password = $data['password'];
            $userObj->role = "voter";
            if ($userObj->register()) {
                $success = "Registration successful! You can now login with your Student ID.";
                $data = ["student_id"=>"","last_name"=>"","first_name"=>"","middle_initial"=>"","email"=>"","course"=>"","year_level"=>"1","password"=>"","confirm_password"=>""];
            } else {
                $errors['general'] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — WMSU Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --gold: #D4A843; --gold-dark: #b8912a; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 20px;
        }
        .container {
            background: white; border-radius: 20px; padding: 50px;
            width: 100%; max-width: 600px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .header { text-align: center; margin-bottom: 35px; }
        .logo { width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin: 0 auto 15px; border: 3px solid var(--gold); }
        .logo img { width: 100%; height: 100%; object-fit: cover; }
        h1 { font-size: 26px; font-weight: 800; color: var(--primary); }
        .subtitle { color: #718096; font-size: 14px; margin-top: 5px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; color: #2d3748; font-size: 13px; margin-bottom: 6px; }
        label span { color: #e53e3e; }
        input, select {
            width: 100%; padding: 12px 14px;
            border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: all 0.3s; background: #f8fafc; font-family: 'Inter', sans-serif;
        }
        input:focus, select:focus { border-color: var(--primary); outline: none; background: white; }
        .field-error { color: #e53e3e; font-size: 12px; margin-top: 4px; }
        .input-error { border-color: #e53e3e !important; }
        .success-box {
            background: #f0fff4; color: #276749; border: 1px solid #9ae6b4;
            border-radius: 10px; padding: 15px 20px; text-align: center;
            margin-bottom: 20px; font-weight: 600;
        }
        .success-box svg { width: 18px; height: 18px; fill: #276749; vertical-align: middle; margin-right: 4px; }
        .error-box {
            background: #fff5f5; color: #c53030; border: 1px solid #feb2b2;
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px;
        }
        .error-box svg { width: 16px; height: 16px; fill: #c53030; vertical-align: middle; margin-right: 4px; }
        .btn { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 9px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 5px; font-family: 'Inter', sans-serif; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 25px 0; }
        .footer-links { text-align: center; font-size: 14px; color: #718096; }
        .footer-links a { color: var(--gold-dark); font-weight: 600; text-decoration: none; }
        .info-box { background: #fff8ee; border-left: 4px solid var(--gold); padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; color: #7b6930; display: flex; align-items: center; gap: 8px; }
        .info-box svg { width: 18px; height: 18px; fill: var(--gold-dark); flex-shrink: 0; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .container { padding: 35px 25px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo"><img src="uploads/wmsu_logo.png" alt="WMSU Logo"></div>
        <h1>Create Voter Account</h1>
        <p class="subtitle">Register to participate in WMSU elections</p>
    </div>

    <?php if ($success): ?>
        <div class="success-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> <?php echo $success; ?> <br><a href="login.php" style="color:var(--primary);">Click here to login</a></div>
    <?php endif; ?>

    <?php if (isset($errors['general'])): ?>
        <div class="error-box"><svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg> <?php echo $errors['general']; ?></div>
    <?php endif; ?>

    <div class="info-box">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        You must use your official WMSU email address (<strong>@wmsu.edu.ph</strong>) to register.
    </div>

    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Student ID <span>*</span></label>
                <input type="text" name="student_id" value="<?php echo $data['student_id']; ?>"
                       placeholder="e.g. 2021-00001"
                       class="<?php echo isset($errors['student_id']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['student_id'])): ?><p class="field-error"><?php echo $errors['student_id']; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Year Level <span>*</span></label>
                <select name="year_level">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $data['year_level'] == $i ? 'selected' : ''; ?>>Year <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-row" style="grid-template-columns: 1fr 1fr 80px;">
            <div class="form-group">
                <label>Last Name <span>*</span></label>
                <input type="text" name="last_name" value="<?php echo $data['last_name']; ?>"
                       placeholder="e.g. Dela Cruz"
                       class="<?php echo isset($errors['last_name']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['last_name'])): ?><p class="field-error"><?php echo $errors['last_name']; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label>First Name <span>*</span></label>
                <input type="text" name="first_name" value="<?php echo $data['first_name']; ?>"
                       placeholder="e.g. Juan"
                       class="<?php echo isset($errors['first_name']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['first_name'])): ?><p class="field-error"><?php echo $errors['first_name']; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label>M.I.</label>
                <input type="text" name="middle_initial" value="<?php echo $data['middle_initial']; ?>"
                       placeholder="e.g. A." maxlength="5">
            </div>
        </div>

        <div class="form-group">
            <label>WMSU Email <span>*</span></label>
            <input type="email" name="email" value="<?php echo $data['email']; ?>"
                   placeholder="yourname@wmsu.edu.ph"
                   class="<?php echo isset($errors['email']) ? 'input-error' : ''; ?>">
            <?php if (isset($errors['email'])): ?><p class="field-error"><?php echo $errors['email']; ?></p><?php endif; ?>
        </div>

        <div class="form-group">
            <label>Course / Program <span>*</span></label>
            <select name="course" class="<?php echo isset($errors['course']) ? 'input-error' : ''; ?>">
                <option value="">-- Select your course --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo $data['course'] === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?><p class="field-error"><?php echo $errors['course']; ?></p><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <input type="password" name="password" placeholder="Min. 8 characters"
                       class="<?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['password'])): ?><p class="field-error"><?php echo $errors['password']; ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <input type="password" name="confirm_password" placeholder="Re-enter password"
                       class="<?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['confirm_password'])): ?><p class="field-error"><?php echo $errors['confirm_password']; ?></p><?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <hr class="divider">
    <div class="footer-links">
        Already have an account? <a href="login.php">Login here</a>
        &nbsp;&middot;&nbsp;
        <a href="index.php" style="color:#718096;">Back to Home</a>
    </div>
</div>
</body>
</html>
