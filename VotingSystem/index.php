<?php
require_once "session_config.php";
require_once "Election.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$active_elections = $electionObj->getActiveElections();
$all_elections = $electionObj->getAllElections();
$upcoming = array_filter($all_elections, fn($e) => $e['status'] === 'upcoming');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMSU Online Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #7B1113;
            --primary-dark: #5a0c0e;
            --primary-light: #9e2a2b;
            --gold: #D4A843;
            --gold-dark: #b8912a;
            --gold-light: #e6c76a;
            --cream: #FFF8EE;
            --light: #f7f3ef;
            --white: #ffffff;
            --dark: #2d1a1a;
            --text: #3d2c2c;
            --muted: #8a7575;
            --border: #e8ddd3;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: var(--light);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* ==================== NAVBAR ==================== */
        nav {
            background: var(--primary);
            padding: 0 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            height: 64px;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .nav-logo {
            width: 38px;
            height: 38px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .nav-title-group { line-height: 1.2; }
        .nav-title { color: white; font-size: 15px; font-weight: 700; }
        .nav-subtitle { color: rgba(255,255,255,0.6); font-size: 10px; font-weight: 500; }
        .nav-links { display: flex; gap: 6px; align-items: center; }
        .nav-links a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-links a:hover { background: rgba(255,255,255,0.12); color: white; }
        .nav-links .btn-login {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.6);
            color: white;
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 20px;
        }
        .nav-links .btn-login:hover { background: rgba(255,255,255,0.15); border-color: white; }
        .nav-links .btn-register {
            background: var(--gold);
            color: var(--primary);
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 20px;
        }
        .nav-links .btn-register:hover { background: var(--gold-light); }

        /* ==================== ALERT BANNER ==================== */
        .alert-active {
            background: var(--gold);
            color: var(--primary-dark);
            text-align: center;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        .alert-active a { color: var(--primary-dark); text-decoration: underline; font-weight: 700; }
        .alert-active a:hover { color: var(--primary); }

        /* ==================== HERO ==================== */
        .hero {
            background: var(--primary);
            color: white;
            padding: 80px 40px 70px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        /* Decorative pattern overlay */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M30 10 L30 50 M20 20 L40 20 M20 40 L40 40 M15 25 L15 45 L45 45 L45 25 Z' stroke='rgba(255,255,255,0.04)' fill='none' stroke-width='1.5'/%3E%3C/svg%3E");
            background-size: 80px 80px;
            opacity: 1;
        }
        /* Subtle gradient glow */
        .hero::after {
            content: '';
            position: absolute;
            top: -40%;
            left: 50%;
            transform: translateX(-50%);
            width: 120%;
            height: 100%;
            background: radial-gradient(ellipse, rgba(212,168,67,0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 1; max-width: 700px; margin: 0 auto; }
        .hero h1 {
            font-size: 50px;
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.15;
            font-style: italic;
        }
        .hero h1 span { color: var(--gold); }
        .hero p {
            font-size: 15px;
            opacity: 0.8;
            margin-bottom: 36px;
            line-height: 1.7;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }
        .hero-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .hero-btn {
            padding: 13px 32px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s;
            display: inline-block;
        }
        .hero-btn-primary {
            background: var(--gold);
            color: var(--primary-dark);
        }
        .hero-btn-primary:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212,168,67,0.35);
        }
        .hero-btn-outline {
            border: 2px solid rgba(255,255,255,0.5);
            color: white;
            background: transparent;
        }
        .hero-btn-outline:hover {
            background: rgba(255,255,255,0.12);
            border-color: white;
            transform: translateY(-2px);
        }

        /* ==================== STATS BAR ==================== */
        .stats-bar {
            background: var(--white);
            max-width: 780px;
            margin: -30px auto 0;
            position: relative;
            z-index: 2;
            border-radius: 16px;
            padding: 28px 40px;
            display: flex;
            justify-content: center;
            gap: 0;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .stat-item {
            text-align: center;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .stat-item + .stat-item { border-left: 1px solid var(--border); }
        .stat-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon svg { width: 28px; height: 28px; }
        .stat-info { text-align: left; }
        .stat-number { font-size: 28px; font-weight: 800; color: var(--primary); line-height: 1; }
        .stat-label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 2px;
            font-weight: 600;
        }

        /* ==================== SECTIONS ==================== */
        .section { padding: 70px 40px; }
        .section-alt { background: var(--white); }
        .section-title { text-align: center; margin-bottom: 45px; }
        .section-title h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .section-title p { color: var(--muted); font-size: 14px; }

        /* ==================== ELECTION CARDS ==================== */
        .election-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .election-card {
            background: var(--white);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.3s;
            border: 1px solid var(--border);
        }
        .election-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
        }
        .election-card-header {
            padding: 22px 24px 16px;
            border-bottom: 1px solid var(--border);
        }
        .election-card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .election-status {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active { background: #22c55e; color: white; }
        .status-upcoming { background: var(--gold); color: var(--primary-dark); }
        .status-ended { background: #94a3b8; color: white; }
        .election-card-body { padding: 18px 24px; }
        .election-card-body p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 14px;
        }
        .election-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .election-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .election-meta .meta-icon { color: var(--primary); font-weight: 600; }
        .election-card-footer {
            padding: 14px 24px 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-gold { background: var(--primary); color: white; }
        .btn-gold:hover { background: var(--primary-dark); }
        .btn-outline {
            border: 1.5px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        .btn-outline:hover { background: var(--primary); color: white; }

        /* ==================== FEATURES ==================== */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .features-grid-bottom {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            max-width: 490px;
            margin: 24px auto 0;
        }
        .feature-card {
            text-align: center;
            padding: 32px 18px 28px;
            background: var(--white);
            border-radius: 14px;
            border: 1.5px solid var(--border);
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }
        .feature-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 2.5px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            background: var(--white);
        }
        .feature-icon-circle svg {
            width: 28px;
            height: 28px;
            fill: var(--primary);
        }
        .feature-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0;
        }

        /* ==================== HOW IT WORKS ==================== */
        .steps {
            display: flex;
            gap: 0;
            max-width: 900px;
            margin: 0 auto;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .step {
            flex: 0 0 auto;
            text-align: center;
            padding: 10px 16px;
            position: relative;
        }
        .step-circle-wrap {
            position: relative;
            width: 70px;
            height: 70px;
            margin: 0 auto 12px;
        }
        .step-num-badge {
            position: absolute;
            top: -4px;
            left: -4px;
            width: 22px;
            height: 22px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        .step-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2.5px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
        }
        .step-icon-circle svg {
            width: 28px;
            height: 28px;
            fill: var(--primary);
        }
        .step h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
        }
        .step-arrow {
            display: flex;
            align-items: center;
            padding-top: 24px;
            color: var(--muted);
            font-size: 20px;
        }
        .step-arrow svg { width: 24px; height: 24px; fill: var(--muted); }

        /* ==================== FOOTER ==================== */
        footer {
            background: var(--dark);
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 40px 40px 30px;
        }
        footer h3 {
            color: white;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        footer p { font-size: 13px; line-height: 1.6; }
        footer .footer-dev { margin-top: 8px; font-size: 12px; opacity: 0.7; }
        footer .footer-copy { margin-top: 14px; font-size: 11px; opacity: 0.4; }

        /* ==================== MISC ==================== */
        .no-elections { text-align: center; padding: 60px 20px; color: var(--muted); }
        .no-elections p { font-size: 16px; }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 900px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            nav { padding: 0 20px; }
            .nav-links a:not(.btn-login):not(.btn-register) { display: none; }
            .hero { padding: 60px 20px 50px; }
            .hero h1 { font-size: 34px; }
            .stats-bar { margin: -20px 16px 0; flex-direction: column; gap: 16px; padding: 24px; border-radius: 12px; }
            .stat-item + .stat-item { border-left: none; border-top: 1px solid var(--border); padding-top: 16px; }
            .section { padding: 50px 20px; }
            .features-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .features-grid-bottom { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .steps { flex-direction: column; align-items: center; }
            .step-arrow { transform: rotate(90deg); padding-top: 0; }
            .election-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php if (count($active_elections) > 0): ?>
<div class="alert-active">
    <strong>VOTING IS NOW OPEN!</strong> — <?php echo htmlspecialchars($active_elections[0]['title']); ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="vote.php?id=<?php echo $active_elections[0]['id']; ?>">Cast Your Vote Now →</a>
    <?php else: ?>
        <a href="login.php">Login to Vote →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<nav>
    <a href="index.php" class="nav-brand">
        <div class="nav-logo"><img src="uploads/wmsu_logo.png" alt="WMSU" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>
        <div class="nav-title-group">
            <div class="nav-title">WMSU</div>
            <div class="nav-subtitle">Online Voting</div>
        </div>
    </a>
    <div class="nav-links">
        <a href="#elections">Elections</a>
        <a href="#features">Features</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'usc'): ?>
                <a href="admin/dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="voter/dashboard.php">My Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <h1><em>Your Vote,<br><span>Your Voice.</span></em></h1>
        <p>A transparent, secure, and accessible online voting platform for WMSU student elections and organizational polls. Participate in campus democracy from anywhere.</p>
        <div class="hero-btns">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'voter'): ?>
                    <a href="voter/dashboard.php" class="hero-btn hero-btn-primary">Go to My Dashboard</a>
                <?php else: ?>
                    <a href="admin/dashboard.php" class="hero-btn hero-btn-primary">Admin Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="register.php" class="hero-btn hero-btn-primary">Register to Vote</a>
                <a href="login.php" class="hero-btn hero-btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- STATS -->
<?php
$electionObj2 = new Election();
$allE = $electionObj2->getAllElections();
$totalElections = count($allE);
$activeCount = count(array_filter($allE, fn($e) => $e['status'] === 'active'));
require_once "User.php";
$userObj = new User();
$totalVoters = $userObj->getTotalVoters();
?>
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="var(--primary)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalElections; ?></div>
            <div class="stat-label">Total Elections</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="var(--gold-dark)"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $activeCount; ?></div>
            <div class="stat-label">Active Now</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="var(--primary)"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalVoters; ?></div>
            <div class="stat-label">Registered Voters</div>
        </div>
    </div>
</div>

<!-- ACTIVE & UPCOMING ELECTIONS -->
<section class="section" id="elections">
    <div class="section-title">
        <h2>Current & Upcoming Elections</h2>
    </div>

    <?php
    $displayElections = array_filter($allE, fn($e) => $e['status'] !== 'ended');
    ?>

    <?php if (count($displayElections) > 0): ?>
        <div class="election-grid">
            <?php foreach ($displayElections as $election): ?>
                <div class="election-card">
                    <div class="election-card-header">
                        <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                        <span class="election-status status-<?php echo $election['status']; ?>">
                            <?php echo ucfirst($election['status']); ?>
                        </span>
                    </div>
                    <div class="election-card-body">
                        <p><?php echo htmlspecialchars(substr($election['description'] ?? 'No description available.', 0, 120)); ?>...</p>
                        <div class="election-meta">
                            <span><span class="meta-icon"></span> <?php echo date('M d, Y', strtotime($election['start_date'])); ?></span>
                            <span><span class="meta-icon"></span> <?php echo $election['position_count']; ?> positions</span>
                            <span><span class="meta-icon"></span> Ends <?php echo date('M d, Y g:i A', strtotime($election['end_date'])); ?></span>
                        </div>
                    </div>
                    <div class="election-card-footer">
                        <?php if ($election['status'] === 'active'): ?>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'voter'): ?>
                                <a href="voter/ballot.php?election_id=<?php echo $election['id']; ?>" class="btn btn-gold">Login to Vote</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Vote</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--muted); font-size: 13px;">Voting opens <?php echo date('M d, Y g:i A', strtotime($election['start_date'])); ?></span>
                        <?php endif; ?>
                        <a href="results.php?id=<?php echo $election['id']; ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-elections">
            <p style="margin-bottom: 12px;"><svg viewBox="0 0 24 24" width="40" height="40" fill="var(--primary)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></p>
            <p>No active or upcoming elections at the moment.</p>
            <p style="font-size: 13px; margin-top: 6px;">Check back soon or contact the USC.</p>
        </div>
    <?php endif; ?>
</section>

<!-- FEATURES -->
<section class="section section-alt" id="features">
    <div class="section-title">
        <h2>System Features</h2>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <h3>Secure Authentication</h3>
        </div>
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
            <h3>One-Vote Enforcement</h3>
        </div>
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg>
            </div>
            <h3>Real-Time Results</h3>
        </div>
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            </div>
            <h3>Audit Trail</h3>
        </div>
    </div>
    <div class="features-grid-bottom">
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7l-2 3v1h8v-1l-2-3h7c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z"/></svg>
            </div>
            <h3>Accessible Anywhere</h3>
        </div>
        <div class="feature-card">
            <div class="feature-icon-circle">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
            </div>
            <h3>Ballot Management</h3>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <div class="section-title">
        <h2>How It Works</h2>
    </div>
    <div class="steps">
        <!-- Step 1 -->
        <div class="step">
            <div class="step-circle-wrap">
                <div class="step-num-badge">1</div>
                <div class="step-icon-circle">
                    <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
            </div>
            <h4>Register</h4>
        </div>
        <div class="step-arrow">
            <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </div>
        <!-- Step 2 -->
        <div class="step">
            <div class="step-circle-wrap">
                <div class="step-num-badge">2</div>
                <div class="step-icon-circle">
                    <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                </div>
            </div>
            <h4>Login</h4>
        </div>
        <div class="step-arrow">
            <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </div>
        <!-- Step 3 -->
        <div class="step">
            <div class="step-circle-wrap">
                <div class="step-num-badge">3</div>
                <div class="step-icon-circle">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </div>
            </div>
            <h4>View Ballot</h4>
        </div>
        <div class="step-arrow">
            <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </div>
        <!-- Step 4 -->
        <div class="step">
            <div class="step-circle-wrap">
                <div class="step-num-badge">4</div>
                <div class="step-icon-circle">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                </div>
            </div>
            <h4>Cast Your Vote</h4>
        </div>
    </div>
    <!-- Step 5 (centered below) -->
    <div class="steps" style="margin-top: 10px;">
        <div class="step">
            <div class="step-circle-wrap">
                <div class="step-num-badge">5</div>
                <div class="step-icon-circle">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg>
                </div>
            </div>
            <h4>See Results</h4>
        </div>
    </div>
</section>

<footer>
    <h3>WMSU Online Voting System</h3>
    <p>Western Mindanao State University — University Student Council</p>
    <p class="footer-dev">Developed by Palmer Vincent B. Ong · Qaiser T. Hamid · Kurt Aldrich Canilang</p>
    <p class="footer-copy">&copy; <?php echo date('Y'); ?> All rights reserved.</p>
</footer>

</body>
</html>
