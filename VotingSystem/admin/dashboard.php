<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'usc'])) {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../User.php";
require_once "../Candidate.php";
require_once "../Vote.php";
require_once "admin_sidebar.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$userObj = new User();
$candidateObj = new Candidate();
$voteObj = new Vote();

$allElections = $electionObj->getAllElections();
$active = array_filter($allElections, fn($e) => $e['status'] === 'active');
$upcoming = array_filter($allElections, fn($e) => $e['status'] === 'upcoming');
$ended = array_filter($allElections, fn($e) => $e['status'] === 'ended');

$totalVoters = $userObj->getTotalVoters();
$allUsers = $userObj->getAllUsers();
$totalUsers = count($allUsers);

// Recent audit log
$auditLog = $voteObj->getAuditLog(15);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — WMSU Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style><?php echo getAdminCSS(); ?></style>
</head>
<body>
<div class="layout">
    <?php renderAdminSidebar('dashboard'); ?>

    <main class="main">
        <div class="topbar">
            <div>
                <h1><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg> Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?> · <?php echo date('l, F j, Y'); ?></p>
            </div>
            <a href="elections.php?action=create" class="btn-create">+ New Election</a>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><svg viewBox="0 0 24 24" fill="var(--primary)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
                <div class="stat-label">Total Elections</div>
                <div class="stat-number"><?php echo count($allElections); ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><svg viewBox="0 0 24 24" fill="#276749"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
                <div class="stat-label">Active Now</div>
                <div class="stat-number"><?php echo count($active); ?></div>
            </div>
            <div class="stat-card gold">
                <div class="stat-icon"><svg viewBox="0 0 24 24" fill="var(--gold-dark)"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
                <div class="stat-label">Registered Voters</div>
                <div class="stat-number"><?php echo $totalVoters; ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><svg viewBox="0 0 24 24" fill="#c53030"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg></div>
                <div class="stat-label">Upcoming</div>
                <div class="stat-number"><?php echo count($upcoming); ?></div>
            </div>
        </div>

        <?php foreach ($active as $e): ?>
            <?php
            $turnout = $electionObj->getVoterTurnout($e['id']);
            $pct = $totalVoters > 0 ? round(($turnout['voted_count'] / $totalVoters) * 100, 1) : 0;
            ?>
            <div style="background: linear-gradient(135deg, #276749, #38a169); color:white; border-radius:12px; padding:20px 25px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:12px;opacity:0.8;text-transform:uppercase;letter-spacing:1px;">LIVE ELECTION</div>
                    <div style="font-size:20px;font-weight:800;margin-top:4px;"><?php echo htmlspecialchars($e['title']); ?></div>
                    <div style="font-size:14px;opacity:0.85;margin-top:6px;"><?php echo $turnout['voted_count']; ?> votes cast · <?php echo $pct; ?>% turnout · Ends <?php echo date('M d g:i A', strtotime($e['end_date'])); ?></div>
                </div>
                <a href="results.php?election_id=<?php echo $e['id']; ?>" style="background:white;color:#276749;padding:10px 20px;border-radius:8px;font-weight:700;text-decoration:none;font-size:14px;">View Live Results</a>
            </div>
        <?php endforeach; ?>

        <div class="panels">
            <!-- ELECTIONS TABLE -->
            <div class="panel">
                <div class="panel-title">Recent Elections <a href="elections.php">View All</a></div>
                <?php if (count($allElections) > 0): ?>
                    <table>
                        <thead><tr><th>Election</th><th>Status</th><th>Votes</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($allElections, 0, 8) as $e): ?>
                                <?php $t = $electionObj->getVoterTurnout($e['id']); ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($e['title']); ?></strong><br><span style="color:#a0aec0;font-size:11px;"><?php echo date('M d, Y', strtotime($e['start_date'])); ?></span></td>
                                    <td><span class="status-pill pill-<?php echo $e['status']; ?>"><?php echo ucfirst($e['status']); ?></span></td>
                                    <td><?php echo $t['voted_count'] ?? 0; ?></td>
                                    <td>
                                        <a href="elections.php?edit=<?php echo $e['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="results.php?election_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-gold">Results</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No elections yet. <a href="elections.php?action=create">Create one</a></div>
                <?php endif; ?>
            </div>

            <!-- AUDIT LOG -->
            <div class="panel">
                <div class="panel-title">Recent Activity <a href="audit.php">View All</a></div>
                <?php if (count($auditLog) > 0): ?>
                    <?php
                    $actionIcons = [
                        'LOGIN' => '<svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>',
                        'LOGOUT' => '<svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>',
                        'VOTE_CAST' => '<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
                        'CREATE_ELECTION' => '<svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
                        'UPDATE_ELECTION' => '<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
                    ];
                    ?>
                    <?php foreach (array_slice($auditLog, 0, 10) as $log): ?>
                        <div class="audit-item">
                            <div class="audit-icon">
                                <?php echo $actionIcons[$log['action']] ?? '<svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>'; ?>
                            </div>
                            <div class="audit-info">
                                <div class="audit-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div class="audit-meta">
                                    <?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?>
                                    · <?php echo date('M d, g:i A', strtotime($log['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">No activity yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>