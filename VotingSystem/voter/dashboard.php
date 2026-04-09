<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../Vote.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$voteObj = new Vote();

$active = $electionObj->getActiveElections();
$all = $electionObj->getAllElections();
$ended = array_filter($all, fn($e) => $e['status'] === 'ended');
$upcoming = array_filter($all, fn($e) => $e['status'] === 'upcoming');

// Check voting status for each active election
$votingStatus = [];
foreach ($active as $e) {
    $votingStatus[$e['id']] = $electionObj->hasVoterCompletedElection($_SESSION['user_id'], $e['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard — WMSU Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --gold: #D4A843; --gold-dark: #b8912a; --light: #f7f3ef; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; background: var(--light); }
        /* SIDEBAR */
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px; background: var(--primary-dark); color: white;
            padding: 30px 0; display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 50;
        }
        .sidebar-brand { padding: 0 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h2 { font-size: 16px; font-weight: 700; }
        .sidebar-brand p { font-size: 11px; opacity: 0.6; margin-top: 3px; }
        .sidebar-logo { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; margin-bottom: 12px; border: 2px solid var(--gold); }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-nav { padding: 20px 0; flex: 1; }
        .nav-item a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 25px; color: rgba(255,255,255,0.75);
            text-decoration: none; font-size: 14px; transition: all 0.3s;
        }
        .nav-item a:hover, .nav-item.active a { background: rgba(255,255,255,0.1); color: white; border-left: 3px solid var(--gold); }
        .nav-item a svg { width: 18px; height: 18px; fill: currentColor; flex-shrink: 0; }
        .sidebar-footer { padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer .user-name { font-size: 13px; font-weight: 700; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-footer .user-id { font-size: 11px; opacity: 0.6; }
        .sidebar-footer a { display: block; margin-top: 12px; color: #fc8181; font-size: 13px; text-decoration: none; }
        .sidebar-footer a svg { width: 14px; height: 14px; fill: currentColor; vertical-align: middle; margin-right: 4px; }
        /* MAIN */
        .main { margin-left: 240px; padding: 35px; flex: 1; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar h1 { font-size: 26px; font-weight: 800; color: var(--primary); }
        .topbar p { color: #718096; font-size: 14px; margin-top: 4px; }
        .badge { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-voter { background: #fff8ee; color: var(--gold-dark); }
        /* CARDS */
        .section-title { font-size: 18px; font-weight: 700; color: var(--primary); margin: 30px 0 15px; display: flex; align-items: center; gap: 8px; }
        .section-title svg { width: 20px; height: 20px; fill: var(--primary); }
        .election-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .election-card {
            background: white; border-radius: 14px; overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06); border: 1px solid #e8ddd3;
            transition: all 0.3s;
        }
        .election-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light, #9e2a2b));
            padding: 20px; color: white;
        }
        .card-header h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
        .status-pill {
            display: inline-block; padding: 3px 10px; border-radius: 15px;
            font-size: 11px; font-weight: 700;
        }
        .pill-active { background: #48bb78; color: white; }
        .pill-upcoming { background: var(--gold); color: var(--primary-dark); }
        .pill-ended { background: #718096; color: white; }
        .card-body { padding: 18px 20px; }
        .card-body p { font-size: 13px; color: #718096; margin-bottom: 12px; line-height: 1.5; }
        .card-meta { font-size: 12px; color: #a0aec0; display: flex; flex-direction: column; gap: 4px; }
        .card-meta svg { width: 14px; height: 14px; fill: var(--primary); vertical-align: middle; margin-right: 4px; }
        .card-footer { padding: 14px 20px; border-top: 1px solid #f0f0f0; display: flex; gap: 10px; }
        .btn { padding: 9px 18px; border-radius: 7px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-block; transition: all 0.3s; border: none; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-vote { background: var(--gold); color: var(--primary-dark); }
        .btn-vote:hover { background: var(--gold-dark); }
        .btn-results { border: 2px solid var(--primary); color: var(--primary); background: transparent; }
        .btn-results:hover { background: var(--primary); color: white; }
        .voted-badge { display: flex; align-items: center; gap: 6px; color: #276749; font-size: 13px; font-weight: 700; background: #c6f6d5; padding: 7px 12px; border-radius: 7px; }
        .voted-badge svg { width: 16px; height: 16px; fill: #276749; }
        .empty-state { text-align: center; padding: 50px; color: #a0aec0; }
        .empty-state svg { width: 40px; height: 40px; fill: #a0aec0; margin-bottom: 12px; }
        .info-bar { background: #fff8ee; border-radius: 10px; padding: 16px 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; color: #7b6930; }
        .info-bar svg { width: 20px; height: 20px; fill: var(--gold-dark); flex-shrink: 0; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo"><img src="../uploads/wmsu_logo.png" alt="WMSU"></div>
            <h2>WMSU Voting</h2>
            <p>Student Election Portal</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item active">
                <a href="dashboard.php"><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> Dashboard</a>
            </div>
            <div class="nav-item">
                <a href="../index.php"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> All Elections</a>
            </div>
            <div class="nav-item">
                <a href="../results.php?id=0"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg> Results</a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-id"><?php echo htmlspecialchars($_SESSION['student_id']); ?></div>
            <a href="../logout.php"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars(explode(',', $_SESSION['full_name'])[0]); ?>!</h1>
                <p><?php echo htmlspecialchars($_SESSION['student_id']); ?> · <?php echo htmlspecialchars($_SESSION['course'] ?? ''); ?></p>
            </div>
            <span class="badge badge-voter">Voter</span>
        </div>

        <div class="info-bar">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            You can vote once per position. Once submitted, your vote cannot be changed. Make sure to review your choices before submitting.
        </div>

        <!-- ACTIVE ELECTIONS -->
        <div class="section-title"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Active Elections</div>
        <?php if (count($active) > 0): ?>
            <div class="election-grid">
                <?php foreach ($active as $e): ?>
                    <div class="election-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                            <span class="status-pill pill-active">Active</span>
                        </div>
                        <div class="card-body">
                            <p><?php echo htmlspecialchars(substr($e['description'] ?? '', 0, 100)); ?>...</p>
                            <div class="card-meta">
                                <span><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Starts: <?php echo date('M d, Y g:i A', strtotime($e['start_date'])); ?></span>
                                <span><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg> Ends: <?php echo date('M d, Y g:i A', strtotime($e['end_date'])); ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($votingStatus[$e['id']]): ?>
                                <span class="voted-badge"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Voted</span>
                            <?php else: ?>
                                <a href="ballot.php?election_id=<?php echo $e['id']; ?>" class="btn btn-vote">Vote Now</a>
                            <?php endif; ?>
                            <a href="../results.php?id=<?php echo $e['id']; ?>" class="btn btn-results">Results</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg>
                <strong>No Active Elections</strong>
                <p style="font-size:14px; margin-top:8px;">There are currently no open elections. Check back later!</p>
            </div>
        <?php endif; ?>

        <!-- UPCOMING -->
        <?php if (count($upcoming) > 0): ?>
            <div class="section-title"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg> Upcoming Elections</div>
            <div class="election-grid">
                <?php foreach ($upcoming as $e): ?>
                    <div class="election-card">
                        <div class="card-header" style="background: linear-gradient(135deg, #4a5568, #718096);">
                            <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                            <span class="status-pill pill-upcoming">Upcoming</span>
                        </div>
                        <div class="card-body">
                            <div class="card-meta">
                                <span><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Opens: <?php echo date('M d, Y g:i A', strtotime($e['start_date'])); ?></span>
                                <span><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg> Closes: <?php echo date('M d, Y g:i A', strtotime($e['end_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- PAST ELECTIONS -->
        <?php if (count($ended) > 0): ?>
            <div class="section-title"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Past Elections</div>
            <div class="election-grid">
                <?php foreach ($ended as $e): ?>
                    <div class="election-card">
                        <div class="card-header" style="background: linear-gradient(135deg, #4a5568, #2d3748);">
                            <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                            <span class="status-pill pill-ended">Ended</span>
                        </div>
                        <div class="card-body">
                            <div class="card-meta">
                                <span><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Was: <?php echo date('M d, Y', strtotime($e['start_date'])); ?> — <?php echo date('M d, Y', strtotime($e['end_date'])); ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="../results.php?id=<?php echo $e['id']; ?>" class="btn btn-results">Final Results</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>