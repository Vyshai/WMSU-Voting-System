<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','usc'])) {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../Candidate.php";
require_once "../Vote.php";
require_once "admin_sidebar.php";

$electionObj = new Election(); $electionObj->updateStatuses();
$candidateObj = new Candidate(); $voteObj = new Vote();
$message = ""; $message_type = "";
$selected_election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
$allElections = $electionObj->getAllElections();
if (!$selected_election_id && count($allElections) > 0) $selected_election_id = $allElections[0]['id'];
$selectedElection = $selected_election_id ? $electionObj->getElectionById($selected_election_id) : null;
$positions = $selected_election_id ? $electionObj->getPositions($selected_election_id) : [];

if (isset($_GET['approve'])) { $candidateObj->updateStatus((int)$_GET['approve'], 'approved'); header("Location: candidates.php?election_id=$selected_election_id"); exit(); }
if (isset($_GET['reject']))  { $candidateObj->updateStatus((int)$_GET['reject'],  'rejected'); header("Location: candidates.php?election_id=$selected_election_id"); exit(); }
if (isset($_GET['delete_cand'])) { $candidateObj->deleteCandidate((int)$_GET['delete_cand']); header("Location: candidates.php?election_id=$selected_election_id"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    $c = new Candidate();
    $c->election_id     = $selected_election_id;
    $c->position_id     = (int)$_POST['position_id'];
    $c->last_name       = trim(htmlspecialchars($_POST['last_name']));
    $c->first_name      = trim(htmlspecialchars($_POST['first_name']));
    $c->middle_initial  = trim(htmlspecialchars($_POST['middle_initial'] ?? ''));
    $c->student_id      = trim(htmlspecialchars($_POST['student_id']));
    $c->course          = trim(htmlspecialchars($_POST['course']));
    $c->year_level      = (int)$_POST['year_level'];
    $c->platform        = trim(htmlspecialchars($_POST['platform']));
    $c->status          = $_POST['cand_status'] ?? 'approved';
    $c->photo           = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $dir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fn = uniqid('cand_') . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $fn);
            $c->photo = $fn;
        }
    }
    if (empty($c->last_name) || empty($c->first_name) || !$c->position_id) {
        $message = "Name and position required."; $message_type = "error";
    } elseif ($c->addCandidate()) {
        $message = "Candidate added!"; $message_type = "success";
        $voteObj->logAction($_SESSION['user_id'], 'ADD_CANDIDATE', "Added: {$c->last_name}, {$c->first_name}", $_SERVER['REMOTE_ADDR'] ?? '');
    } else { $message = "Failed to add."; $message_type = "error"; }
}

$candidates = $selected_election_id ? $candidateObj->getCandidatesByElection($selected_election_id) : [];
$courses = ["BS Computer Science","BS Information Technology","BS Computer Engineering","BS Electrical Engineering","BS Civil Engineering","BS Nursing","BS Education","BA Communication","BA Political Science","BS Business Administration","Others"];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Candidates — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style><?php echo getAdminCSS(); ?></style>
</head><body>
<div class="layout">
<?php renderAdminSidebar('candidates'); ?>
<main class="main">
<div class="page-header"><h1><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Candidates</h1></div>
<?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div><?php endif; ?>
<form method="GET" style="display:flex;align-items:center;gap:10px;margin-bottom:25px;">
    <label style="display:inline;font-size:14px;font-weight:600;color:var(--primary);">Election:</label>
    <select name="election_id" onchange="this.form.submit()" style="max-width:320px;font-size:14px;">
        <option value="">-- Select Election --</option>
        <?php foreach ($allElections as $e): ?>
            <option value="<?php echo $e['id']; ?>" <?php echo $e['id']==$selected_election_id?'selected':''; ?>><?php echo htmlspecialchars($e['title']); ?> (<?php echo ucfirst($e['status']); ?>)</option>
        <?php endforeach; ?>
    </select>
</form>
<?php if (!$selectedElection): ?><div class="card"><div class="no-data">Select an election above.</div></div>
<?php else: ?>
<div class="grid">
  <div class="card">
    <div class="card-title">Add Candidate</div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="add_candidate" value="1">
      <div class="form-group"><label>Position *</label><select name="position_id" required><option value="">-- Select Position --</option><?php foreach ($positions as $pos): ?><option value="<?php echo $pos['id']; ?>"><?php echo htmlspecialchars($pos['title']); ?></option><?php endforeach; ?></select></div>
      <div class="form-row">
        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" placeholder="Dela Cruz" required></div>
        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" placeholder="Juan" required></div>
      </div>
      <div class="form-group"><label>M.I.</label><input type="text" name="middle_initial" placeholder="A." maxlength="5"></div>
      <div class="form-row">
        <div class="form-group"><label>Student ID</label><input type="text" name="student_id" placeholder="2021-00001"></div>
        <div class="form-group"><label>Year Level</label><select name="year_level"><?php for($i=1;$i<=5;$i++): ?><option value="<?php echo $i; ?>">Year <?php echo $i; ?></option><?php endfor; ?></select></div>
      </div>
      <div class="form-group"><label>Course</label><select name="course"><option value="">-- Select --</option><?php foreach ($courses as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Platform / Advocacy</label><textarea name="platform" placeholder="Campaign platform..."></textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Photo</label><input type="file" name="photo" accept="image/*" style="padding:6px;"></div>
        <div class="form-group"><label>Status</label><select name="cand_status"><option value="approved">Approved</option><option value="pending">Pending</option></select></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Add Candidate</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">Candidates — <?php echo htmlspecialchars($selectedElection['title']); ?> (<?php echo count($candidates); ?>)</div>
    <?php if (count($candidates) > 0):
      $byPos = [];
      foreach ($candidates as $c) $byPos[$c['position_title']][] = $c;
      foreach ($byPos as $posTitle => $cands): ?>
        <div style="margin-bottom:18px;">
          <div style="font-size:13px;font-weight:700;color:var(--primary);background:#fff8ee;padding:8px 12px;border-radius:7px;margin-bottom:8px;display:flex;align-items:center;gap:6px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="var(--primary)"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg> <?php echo htmlspecialchars($posTitle); ?></div>
          <?php foreach ($cands as $c): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #f0f0f0;border-radius:9px;margin-bottom:7px;background:#fafbff;">
            <div class="cand-photo"><?php if ($c['photo']): ?><img src="../uploads/<?php echo htmlspecialchars($c['photo']); ?>" alt=""><?php else: echo strtoupper(substr($c['full_name'],0,1)); endif; ?></div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:700;color:#2d3748;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($c['full_name']); ?></div>
              <div style="font-size:11px;color:#a0aec0;"><?php echo htmlspecialchars($c['course']); ?></div>
              <span class="cand-pill cand-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span>
            </div>
            <div style="display:flex;gap:4px;">
              <?php if ($c['status']==='pending'): ?><a href="candidates.php?election_id=<?php echo $selected_election_id; ?>&approve=<?php echo $c['id']; ?>" class="btn btn-sm btn-success">&#10003;</a><a href="candidates.php?election_id=<?php echo $selected_election_id; ?>&reject=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger">&#10007;</a><?php endif; ?>
              <a href="candidates.php?election_id=<?php echo $selected_election_id; ?>&delete_cand=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove?')">Del</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?><div class="no-data">No candidates yet.</div><?php endif; ?>
  </div>
</div>
<?php endif; ?>
</main></div></body></html>
