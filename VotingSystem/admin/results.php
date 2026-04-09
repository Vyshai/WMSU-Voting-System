<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','usc'])) {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../Candidate.php";
require_once "../Vote.php";
require_once "../User.php";
require_once "admin_sidebar.php";

$electionObj = new Election(); $electionObj->updateStatuses();
$candidateObj = new Candidate(); $voteObj = new Vote(); $userObj = new User();

$allElections = $electionObj->getAllElections();
$selected_election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
if (!$selected_election_id && count($allElections) > 0) $selected_election_id = $allElections[0]['id'];
$selectedElection = $selected_election_id ? $electionObj->getElectionById($selected_election_id) : null;
$positions = $selected_election_id ? $electionObj->getPositions($selected_election_id) : [];
$results = $selected_election_id ? $candidateObj->getResultsByElection($selected_election_id) : [];
$turnout = $selected_election_id ? $electionObj->getVoterTurnout($selected_election_id) : ['voted_count'=>0];
$totalVoters = $userObj->getTotalVoters();

$byPosition = [];
foreach ($results as $r) $byPosition[$r['position_id']][] = $r;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Results — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style><?php echo getAdminCSS(); ?>
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:30px;}
.stat-card-r{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-top:4px solid var(--primary);text-align:center;}
.stat-card-r.gold{border-top-color:var(--gold);}
.stat-num-r{font-size:34px;font-weight:800;color:var(--primary);}
.stat-card-r.gold .stat-num-r{color:var(--gold-dark);}
.stat-lbl-r{font-size:11px;color:#718096;text-transform:uppercase;letter-spacing:1px;margin-top:4px;}
.candidate-row-r{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f5f5f5;}
.candidate-row-r:last-child{border-bottom:none;}
.rank-badge{width:30px;height:30px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;}
.rank-1{background:#f6e05e;color:#744210;}.rank-2{background:#e2e8f0;color:#4a5568;}.rank-3{background:#fbd38d;color:#7b341e;}
.cand-avatar{width:45px;height:45px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;flex-shrink:0;overflow:hidden;}
.cand-avatar img{width:100%;height:100%;object-fit:cover;}
.cand-info-r{flex:1;}
.cand-name-r{font-size:15px;font-weight:700;color:#2d3748;}
.cand-sub-r{font-size:12px;color:#718096;margin-top:2px;}
.bar-wrap{margin-top:5px;}.bar{height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;}
.bar-fill{height:100%;border-radius:4px;background:var(--primary);transition:width 1s ease;}
.bar-fill.winner{background:var(--gold);}
.bar-pct{font-size:11px;color:#a0aec0;margin-top:3px;}
.vote-num-r{font-size:22px;font-weight:800;color:var(--primary);min-width:45px;text-align:right;}
.winner-tag{background:var(--gold);color:var(--primary-dark);font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;margin-left:6px;}
@media print{.sidebar,.page-header form,select{display:none;}.main{margin-left:0;}}
</style>
</head><body>
<div class="layout">
<?php renderAdminSidebar('results'); ?>
<main class="main">
<div class="page-header" style="display:block;">
  <h1><svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg> Results &amp; Reports</h1>
  <form method="GET" style="display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap;">
    <select name="election_id" onchange="this.form.submit()" style="padding:10px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;max-width:350px;">
      <option value="">-- Select Election --</option>
      <?php foreach ($allElections as $e): ?><option value="<?php echo $e['id']; ?>" <?php echo $e['id']==$selected_election_id?'selected':''; ?>><?php echo htmlspecialchars($e['title']); ?> (<?php echo ucfirst($e['status']); ?>)</option><?php endforeach; ?>
    </select>
    <?php if ($selectedElection): ?><button type="button" onclick="window.print()" class="btn btn-primary">Print</button><a href="../results.php?id=<?php echo $selected_election_id; ?>" class="btn btn-gold" target="_blank">Public View</a><?php endif; ?>
  </form>
</div>

<?php if (!$selectedElection): ?><div style="background:white;border-radius:14px;padding:60px;text-align:center;color:#a0aec0;box-shadow:0 2px 12px rgba(0,0,0,.06);">Select an election above to view results.</div>
<?php else: ?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:25px;flex-wrap:wrap;">
  <h2 style="font-size:20px;font-weight:800;color:var(--primary);"><?php echo htmlspecialchars($selectedElection['title']); ?></h2>
  <span class="status-pill pill-<?php echo $selectedElection['status']; ?>"><?php echo ucfirst($selectedElection['status']); ?></span>
</div>
<div class="stats">
  <div class="stat-card-r"><div class="stat-num-r"><?php echo $turnout['voted_count']; ?></div><div class="stat-lbl-r">Total Votes Cast</div></div>
  <div class="stat-card-r gold"><div class="stat-num-r"><?php echo $totalVoters; ?></div><div class="stat-lbl-r">Registered Voters</div></div>
  <div class="stat-card-r"><div class="stat-num-r"><?php echo $totalVoters > 0 ? round(($turnout['voted_count']/$totalVoters)*100,1) : 0; ?>%</div><div class="stat-lbl-r">Voter Turnout</div></div>
  <div class="stat-card-r gold"><div class="stat-num-r"><?php echo count($positions); ?></div><div class="stat-lbl-r">Positions</div></div>
</div>

<?php foreach ($positions as $pos): ?>
  <?php $cands = $byPosition[$pos['id']] ?? []; $totalPosVotes = array_sum(array_column($cands,'vote_count')); ?>
  <div class="card" style="margin-bottom:25px;">
    <div class="card-title" style="border-bottom:3px solid var(--gold);"><svg viewBox="0 0 24 24" width="18" height="18" fill="var(--primary)" style="vertical-align:middle;margin-right:6px;"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg> <?php echo htmlspecialchars($pos['title']); ?> <span style="font-size:13px;color:#718096;font-weight:400;">(<?php echo $totalPosVotes; ?> votes)</span></div>
    <?php if (count($cands) > 0): ?>
      <?php foreach ($cands as $idx => $c): ?>
        <?php $pct = $totalPosVotes > 0 ? round(($c['vote_count']/$totalPosVotes)*100,1) : 0; $rank=$idx+1; $isWinner = $rank===1 && $selectedElection['status']==='ended' && $c['vote_count']>0; ?>
        <div class="candidate-row-r">
          <div class="rank-badge rank-<?php echo min($rank,4); ?>"><?php echo $rank; ?></div>
          <div class="cand-avatar"><?php if ($c['photo']): ?><img src="../uploads/<?php echo htmlspecialchars($c['photo']); ?>" alt=""><?php else: echo strtoupper(substr($c['full_name'],0,1)); endif; ?></div>
          <div class="cand-info-r">
            <div class="cand-name-r"><?php echo htmlspecialchars($c['full_name']); ?><?php if ($isWinner): ?><span class="winner-tag">WINNER</span><?php endif; ?></div>
            <div class="cand-sub-r"><?php echo htmlspecialchars($c['course']); ?></div>
            <div class="bar-wrap"><div class="bar"><div class="bar-fill <?php echo $isWinner?'winner':''; ?>" style="width:<?php echo $pct; ?>%"></div></div><div class="bar-pct"><?php echo $pct; ?>%</div></div>
          </div>
          <div class="vote-num-r"><?php echo $c['vote_count']; ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?><div class="no-data">No approved candidates.</div><?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if ($selectedElection['status']==='active'): ?><div style="background:#fff8ee;border:1px solid #f0e0c0;padding:16px 20px;border-radius:10px;color:#7b6930;font-size:14px;">Results update in real-time. Refresh the page for the latest counts.</div><?php endif; ?>
<?php endif; ?>
</main></div></body></html>
