<?php
require_once "session_config.php";
require_once "Election.php";
require_once "Candidate.php";
require_once "Vote.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$candidateObj = new Candidate();
$voteObj = new Vote();

$election_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$election = $electionObj->getElectionById($election_id);

if (!$election) {
    header("Location: index.php"); exit();
}

$positions = $electionObj->getPositions($election_id);
$results = $candidateObj->getResultsByElection($election_id);
$turnout = $electionObj->getVoterTurnout($election_id);
$totalVoters = (new \User())->getTotalVoters();

// Group results by position
$byPosition = [];
foreach ($results as $r) {
    $byPosition[$r['position_id']][] = $r;
}
require_once "User.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($election['title']); ?> — Results</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --gold: #D4A843; --gold-dark: #b8912a; --light: #f7f3ef; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; background: var(--light); color: #2d3748; }
        nav {
            background: var(--primary); padding: 0 40px; height: 64px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .nav-brand { color: white; font-size: 15px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-logo { width: 36px; height: 36px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold); }
        .nav-logo img { width: 100%; height: 100%; object-fit: cover; }
        nav a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; margin-left: 20px; font-weight: 500; }
        nav a:hover { color: white; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-header { text-align: center; margin-bottom: 40px; }
        .page-header h1 { font-size: 32px; font-weight: 800; color: var(--primary); }
        .page-header p { color: #718096; margin-top: 8px; }
        .status-badge {
            display: inline-block; padding: 6px 18px; border-radius: 30px;
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            margin-top: 10px;
        }
        .status-active { background: #c6f6d5; color: #276749; }
        .status-ended { background: #e2e8f0; color: #4a5568; }
        .status-upcoming { background: #fef3c7; color: #92400e; }
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 40px;
        }
        .stat-card {
            background: white; border-radius: 12px; padding: 25px;
            text-align: center; box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            border-top: 4px solid var(--primary);
        }
        .stat-card.gold { border-top-color: var(--gold); }
        .stat-number { font-size: 38px; font-weight: 800; color: var(--primary); }
        .stat-card.gold .stat-number { color: var(--gold-dark); }
        .stat-label { font-size: 13px; color: #718096; margin-top: 4px; text-transform: uppercase; }
        .position-block {
            background: white; border-radius: 14px; padding: 30px;
            margin-bottom: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.06);
        }
        .position-title {
            font-size: 20px; font-weight: 800; color: var(--primary);
            border-bottom: 3px solid var(--gold); padding-bottom: 12px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .position-title svg { width: 22px; height: 22px; fill: var(--primary); }
        .candidate-row {
            display: flex; align-items: center; gap: 15px;
            padding: 14px 0; border-bottom: 1px solid #f0f0f0; position: relative;
        }
        .candidate-row:last-child { border-bottom: none; }
        .candidate-rank { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0; }
        .rank-1 { background: #f6e05e; color: #744210; }
        .rank-2 { background: #e2e8f0; color: #4a5568; }
        .rank-3 { background: #fbd38d; color: #7b341e; }
        .candidate-photo {
            width: 50px; height: 50px; border-radius: 50%; background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 700; flex-shrink: 0;
            overflow: hidden;
        }
        .candidate-photo img { width: 100%; height: 100%; object-fit: cover; }
        .candidate-info { flex: 1; }
        .candidate-name { font-weight: 700; font-size: 16px; color: #2d3748; }
        .candidate-course { font-size: 13px; color: #718096; margin-top: 2px; }
        .vote-count { font-size: 22px; font-weight: 800; color: var(--primary); min-width: 50px; text-align: right; }
        .progress-wrap { flex: 1; }
        .progress-bar {
            height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden; margin-top: 5px;
        }
        .progress-fill { height: 100%; border-radius: 5px; background: var(--primary); transition: width 1s ease; }
        .winner-fill { background: var(--gold); }
        .percent-label { font-size: 12px; color: #718096; margin-top: 4px; }
        .winner-badge { background: var(--gold); color: var(--primary-dark); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; margin-left: 8px; }
        .winner-badge svg { width: 12px; height: 12px; fill: var(--primary-dark); vertical-align: middle; margin-right: 2px; }
        .no-votes { color: #718096; text-align: center; padding: 30px; font-size: 15px; }
        .pending-note {
            background: #fff8ee; border: 1px solid #f0e0c0; color: #7b6930;
            border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 30px; font-size: 15px;
        }
        .pending-note svg { width: 20px; height: 20px; fill: var(--gold-dark); vertical-align: middle; margin-right: 6px; }
        .back-btn { display: inline-block; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 7px; font-weight: 600; margin-bottom: 25px; font-size: 13px; }
        .back-btn:hover { background: var(--primary-dark); }
    </style>
</head>
<body>
<nav>
    <a href="index.php" class="nav-brand">
        <div class="nav-logo"><img src="uploads/wmsu_logo.png" alt="WMSU"></div>
        WMSU Online Voting
    </a>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $_SESSION['role'] === 'voter' ? 'voter/dashboard.php' : 'admin/dashboard.php'; ?>">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <a href="index.php" class="back-btn">Back to Home</a>

    <div class="page-header">
        <h1><?php echo htmlspecialchars($election['title']); ?></h1>
        <p><?php echo htmlspecialchars($election['description'] ?? ''); ?></p>
        <span class="status-badge status-<?php echo $election['status']; ?>"><?php echo ucfirst($election['status']); ?></span>
    </div>

    <?php if ($election['status'] === 'upcoming'): ?>
        <div class="pending-note"><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg> Voting has not started yet. Results will be available after the election ends.</div>
    <?php elseif ($election['status'] === 'active'): ?>
        <div class="pending-note"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Voting is currently in progress. Live results will be shown here.</div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number"><?php echo $turnout['voted_count'] ?? 0; ?></div>
            <div class="stat-label">Total Votes Cast</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-number"><?php echo $totalVoters; ?></div>
            <div class="stat-label">Registered Voters</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php echo $totalVoters > 0 ? round(($turnout['voted_count'] / $totalVoters) * 100, 1) : 0; ?>%
            </div>
            <div class="stat-label">Voter Turnout</div>
        </div>
    </div>

    <?php foreach ($positions as $pos): ?>
        <div class="position-block">
            <div class="position-title"><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg> <?php echo htmlspecialchars($pos['title']); ?></div>
            <?php
            $posCandidates = $byPosition[$pos['id']] ?? [];
            $totalPositionVotes = array_sum(array_column($posCandidates, 'vote_count'));
            ?>
            <?php if (count($posCandidates) > 0): ?>
                <?php foreach ($posCandidates as $idx => $c): ?>
                    <?php
                    $pct = $totalPositionVotes > 0 ? round(($c['vote_count'] / $totalPositionVotes) * 100, 1) : 0;
                    $rank = $idx + 1;
                    $isWinner = $idx === 0 && $election['status'] === 'ended' && $c['vote_count'] > 0;
                    ?>
                    <div class="candidate-row">
                        <div class="candidate-rank rank-<?php echo $rank; ?>"><?php echo $rank; ?></div>
                        <div class="candidate-photo">
                            <?php if ($c['photo']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($c['photo']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(substr($c['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="candidate-info">
                            <div class="candidate-name">
                                <?php echo htmlspecialchars($c['full_name']); ?>
                                <?php if ($isWinner): ?><span class="winner-badge"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> WINNER</span><?php endif; ?>
                            </div>
                            <div class="candidate-course"><?php echo htmlspecialchars($c['course']); ?></div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $isWinner ? 'winner-fill' : ''; ?>"
                                     style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <div class="percent-label"><?php echo $pct; ?>% of position votes</div>
                        </div>
                        <div class="vote-count"><?php echo $c['vote_count']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-votes">No approved candidates for this position.</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>