<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'voter') {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../Candidate.php";
require_once "../Vote.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$candidateObj = new Candidate();
$voteObj = new Vote();

$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
$election = $electionObj->getElectionById($election_id);

if (!$election || $election['status'] !== 'active') {
    $_SESSION['ballot_error'] = "This election is not currently active.";
    header("Location: dashboard.php"); exit();
}

$positions = $electionObj->getPositions($election_id);
$voter_id = $_SESSION['user_id'];

// Get voter's already cast votes per position
$votedPositions = [];
$votedCounts = [];
foreach ($positions as $pos) {
    $count = $voteObj->getVoterVoteCountForPosition($voter_id, $election_id, $pos['id']);
    $votedCounts[$pos['id']] = $count;
    $maxVotes = (int)$pos['max_votes'];
    if ($count >= $maxVotes) {
        $votedPositions[$pos['id']] = true;
    }
}

$errors = [];
$success = false;

// Handle vote submission — supports multi-vote
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedVotes = $_POST['votes'] ?? [];
    $newVotes = 0;

    foreach ($positions as $pos) {
        if (isset($votedPositions[$pos['id']])) continue;
        $maxVotes = (int)$pos['max_votes'];

        if (isset($submittedVotes[$pos['id']])) {
            $selectedIds = $submittedVotes[$pos['id']];
            // Normalize: if single vote (radio), wrap in array
            if (!is_array($selectedIds)) $selectedIds = [$selectedIds];

            // Validate count
            $remaining = $maxVotes - $votedCounts[$pos['id']];
            if (count($selectedIds) > $remaining) {
                $errors[] = "Too many candidates selected for " . htmlspecialchars($pos['title']) . " (max $maxVotes)";
                continue;
            }

            // Validate each candidate and cast vote
            $candidates = $candidateObj->getCandidatesByPosition($pos['id']);
            $validIds = array_column($candidates, 'id');
            foreach ($selectedIds as $cid) {
                $cid = (int)$cid;
                if (!in_array($cid, $validIds)) {
                    $errors[] = "Invalid candidate for " . htmlspecialchars($pos['title']);
                    continue;
                }
                if ($voteObj->castVote($election_id, $pos['id'], $voter_id, $cid)) {
                    $newVotes++;
                }
            }
        }
    }

    if (empty($errors) && $newVotes > 0) {
        $voteObj->logAction($voter_id, 'VOTE_CAST', "Voted in election #$election_id, $newVotes vote(s)", $_SERVER['REMOTE_ADDR'] ?? '');
        // Refresh voted positions
        foreach ($positions as $pos) {
            $count = $voteObj->getVoterVoteCountForPosition($voter_id, $election_id, $pos['id']);
            $votedCounts[$pos['id']] = $count;
            if ($count >= (int)$pos['max_votes']) {
                $votedPositions[$pos['id']] = true;
            }
        }
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ballot — <?php echo htmlspecialchars($election['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --gold: #D4A843; --gold-dark: #b8912a; --light: #f7f3ef; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; background: var(--light); color: #2d3748; }
        header { background: var(--primary); color: white; padding: 0 40px; height: 64px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .header-brand { display: flex; align-items: center; gap: 10px; }
        .header-logo { width: 36px; height: 36px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold); }
        .header-logo img { width: 100%; height: 100%; object-fit: cover; }
        header h1 { font-size: 16px; font-weight: 700; }
        header p { font-size: 12px; opacity: 0.7; }
        header a { color: rgba(255,255,255,0.8); font-size: 13px; text-decoration: none; font-weight: 500; }
        header a:hover { color: white; }
        .container { max-width: 820px; margin: 40px auto; padding: 0 20px 60px; }
        .ballot-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; text-align: center; }
        .ballot-header h2 { font-size: 26px; font-weight: 800; margin-bottom: 8px; }
        .ballot-header p { opacity: 0.8; font-size: 14px; }
        .ballot-header .deadline { margin-top: 12px; font-size: 13px; background: rgba(255,255,255,0.15); display: inline-flex; align-items: center; gap: 6px; padding: 5px 15px; border-radius: 20px; }
        .ballot-header .deadline svg { width: 14px; height: 14px; fill: white; }
        .success-banner { background: #c6f6d5; border: 1px solid #9ae6b4; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 25px; color: #276749; }
        .success-banner h3 { font-size: 20px; margin-bottom: 6px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .success-banner h3 svg { width: 22px; height: 22px; fill: #276749; }
        .error-box { background: #fff5f5; border: 1px solid #feb2b2; color: #c53030; border-radius: 10px; padding: 14px; margin-bottom: 20px; }
        .error-box svg { width: 16px; height: 16px; fill: #c53030; vertical-align: middle; margin-right: 4px; }
        .position-card { background: white; border-radius: 14px; margin-bottom: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.06); overflow: hidden; border: 2px solid transparent; transition: border 0.3s; }
        .position-card.has-voted { border-color: #9ae6b4; }
        .position-header { background: var(--primary); color: white; padding: 16px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        .position-header h3 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .position-header h3 svg { width: 18px; height: 18px; fill: white; }
        .max-votes-badge { background: var(--gold); color: var(--primary-dark); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
        .selection-counter { font-size: 12px; color: rgba(255,255,255,0.8); }
        .voted-indicator { background: #48bb78; color: white; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 15px; display: flex; align-items: center; gap: 4px; }
        .voted-indicator svg { width: 14px; height: 14px; fill: white; }
        .candidates-list { padding: 15px; display: grid; gap: 10px; }
        .candidate-option { display: flex; align-items: center; gap: 15px; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s; position: relative; }
        .candidate-option:hover { border-color: var(--primary); background: #fdf5f5; }
        .candidate-option input[type="radio"], .candidate-option input[type="checkbox"] { position: absolute; opacity: 0; }
        .candidate-option.selected { border-color: var(--gold); background: #fffbeb; }
        .candidate-option.disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
        .candidate-photo { width: 52px; height: 52px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; color: white; flex-shrink: 0; overflow: hidden; }
        .candidate-photo img { width: 100%; height: 100%; object-fit: cover; }
        .candidate-info { flex: 1; }
        .candidate-info h4 { font-size: 16px; font-weight: 700; color: #2d3748; }
        .candidate-info p { font-size: 13px; color: #718096; margin-top: 2px; }
        .candidate-platform { font-size: 12px; color: #a0aec0; margin-top: 4px; font-style: italic; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .check-circle { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #cbd5e0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; }
        .check-circle.checked { border-color: var(--gold); background: var(--gold); }
        .check-circle.checked::after { content: '\2713'; color: white; font-size: 13px; font-weight: 700; }
        .check-square { width: 22px; height: 22px; border-radius: 5px; border: 2px solid #cbd5e0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s; }
        .check-square.checked { border-color: var(--gold); background: var(--gold); }
        .check-square.checked::after { content: '\2713'; color: white; font-size: 13px; font-weight: 700; }
        .no-candidates { text-align: center; padding: 30px; color: #a0aec0; }
        .abstain-note { font-size: 12px; color: #a0aec0; padding: 10px 15px; border-top: 1px solid #f0f0f0; display: flex; align-items: center; gap: 6px; }
        .abstain-note svg { width: 14px; height: 14px; fill: var(--gold-dark); flex-shrink: 0; }
        .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 15px 40px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); }
        .progress-info { font-size: 14px; color: #718096; }
        .progress-info strong { color: var(--primary); }
        .btn-submit { padding: 14px 35px; background: var(--gold); color: var(--primary-dark); border: none; border-radius: 9px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .btn-submit:hover { background: var(--gold-dark); transform: translateY(-1px); }
        .btn-back { padding: 12px 25px; border: 2px solid var(--primary); color: var(--primary); background: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; font-family: 'Inter', sans-serif; }
        .alert-info { background: #fff8ee; border-left: 4px solid var(--gold); padding: 14px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; color: #7b6930; display: flex; align-items: center; gap: 8px; }
        .alert-info svg { width: 18px; height: 18px; fill: var(--gold-dark); flex-shrink: 0; }
        .voted-card { padding: 14px 20px; background: #f0fff4; border-radius: 10px; color: #276749; font-weight: 600; font-size: 14px; }
        .voted-card svg { width: 16px; height: 16px; fill: #276749; flex-shrink: 0; vertical-align: middle; margin-right: 4px; }
        .voted-card ul { list-style: none; margin-top: 8px; }
        .voted-card ul li { padding: 3px 0; font-weight: 500; }
        .voted-card ul li::before { content: '\2713'; color: #276749; font-weight: 700; margin-right: 6px; }
    </style>
</head>
<body>
<header>
    <div class="header-brand">
        <div class="header-logo"><img src="../uploads/wmsu_logo.png" alt="WMSU"></div>
        <div><h1>Official Ballot</h1><p><?php echo htmlspecialchars($election['title']); ?></p></div>
    </div>
    <a href="dashboard.php">Back to Dashboard</a>
</header>

<div class="container">
    <div class="ballot-header">
        <h2><?php echo htmlspecialchars($election['title']); ?></h2>
        <p>Voter: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> · <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
        <div class="deadline"><svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.2 3.2.8-1.3-4.5-2.7V7z"/></svg> Voting closes: <?php echo date('M d, Y g:i A', strtotime($election['end_date'])); ?></div>
    </div>

    <?php if ($success): ?>
        <div class="success-banner">
            <h3><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Votes Submitted Successfully!</h3>
            <p>Your votes have been recorded securely. Thank you for participating!</p>
            <a href="dashboard.php" style="display:inline-block;margin-top:12px;padding:10px 22px;background:var(--primary);color:white;border-radius:8px;text-decoration:none;font-weight:700;">Back to Dashboard</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $e): ?><p><svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg> <?php echo $e; ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="alert-info">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        Select your candidates for each position. Some positions allow multiple votes. Your vote is final once submitted.
    </div>

    <form method="POST" id="ballotForm">
        <?php foreach ($positions as $pos): ?>
            <?php
            $candidates = $candidateObj->getCandidatesByPosition($pos['id']);
            $maxVotes = (int)$pos['max_votes'];
            $isMulti = $maxVotes > 1;
            $alreadyVoted = isset($votedPositions[$pos['id']]);
            $votedHistory = $voteObj->getVoterHistory($voter_id, $election_id);
            $votedNames = [];
            foreach ($votedHistory as $vh) {
                if ($vh['position_id'] == $pos['id']) $votedNames[] = $vh['candidate_name'];
            }
            ?>
            <div class="position-card <?php echo $alreadyVoted ? 'has-voted' : ''; ?>">
                <div class="position-header">
                    <h3><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg> <?php echo htmlspecialchars($pos['title']); ?>
                        <?php if ($isMulti): ?><span class="max-votes-badge">Vote up to <?php echo $maxVotes; ?></span><?php endif; ?>
                    </h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php if (!$alreadyVoted && $isMulti): ?>
                            <span class="selection-counter" id="counter-<?php echo $pos['id']; ?>">0 / <?php echo $maxVotes; ?> selected</span>
                        <?php endif; ?>
                        <?php if ($alreadyVoted): ?>
                            <span class="voted-indicator"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Voted</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($alreadyVoted): ?>
                    <div class="voted-card">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> You voted for:
                        <ul><?php foreach ($votedNames as $vn): ?><li><?php echo htmlspecialchars($vn); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php elseif (count($candidates) > 0): ?>
                    <div class="candidates-list">
                        <?php foreach ($candidates as $c): ?>
                            <?php
                            $inputType = $isMulti ? 'checkbox' : 'radio';
                            $inputName = $isMulti ? "votes[{$pos['id']}][]" : "votes[{$pos['id']}]";
                            $circleClass = $isMulti ? 'check-square' : 'check-circle';
                            ?>
                            <label class="candidate-option" data-pos="<?php echo $pos['id']; ?>" data-max="<?php echo $maxVotes; ?>">
                                <input type="<?php echo $inputType; ?>" name="<?php echo $inputName; ?>" value="<?php echo $c['id']; ?>">
                                <div class="candidate-photo">
                                    <?php if ($c['photo']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($c['photo']); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($c['last_name'] ?? $c['full_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="candidate-info">
                                    <h4><?php echo htmlspecialchars($c['full_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($c['course']); ?> · Year <?php echo $c['year_level']; ?></p>
                                    <?php if ($c['platform']): ?>
                                        <div class="candidate-platform">"<?php echo htmlspecialchars($c['platform']); ?>"</div>
                                    <?php endif; ?>
                                </div>
                                <div class="<?php echo $circleClass; ?>" id="chk-<?php echo $pos['id']; ?>-<?php echo $c['id']; ?>"></div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="abstain-note"><svg viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg> Leave unselected to abstain.<?php if ($isMulti): ?> You can select up to <?php echo $maxVotes; ?> candidates.<?php endif; ?></div>
                <?php else: ?>
                    <div class="no-candidates">No approved candidates for this position.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="height: 80px;"></div>

        <?php
        $totalPositions = count($positions);
        $alreadyVotedCount = count($votedPositions);
        $remaining = $totalPositions - $alreadyVotedCount;
        ?>
        <?php if ($remaining > 0): ?>
            <div class="sticky-footer">
                <div class="progress-info">
                    <strong><?php echo $alreadyVotedCount; ?>/<?php echo $totalPositions; ?></strong> positions voted
                    <?php if ($alreadyVotedCount > 0): ?> · <?php echo $remaining; ?> remaining<?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="dashboard.php" class="btn-back">Cancel</a>
                    <button type="submit" class="btn-submit">Submit Ballot</button>
                </div>
            </div>
        <?php else: ?>
            <div class="sticky-footer" style="justify-content:center;">
                <div style="text-align:center;">
                    <strong style="color:#276749;font-size:16px;display:flex;align-items:center;justify-content:center;gap:6px;"><svg viewBox="0 0 24 24" width="20" height="20" fill="#276749"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> You have voted in all positions!</strong><br>
                    <a href="dashboard.php" style="color:var(--primary);font-size:14px;margin-top:5px;display:inline-block;">Back to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.querySelectorAll('.candidate-option').forEach(el => {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        const input = this.querySelector('input');
        const posId = this.dataset.pos;
        const maxVotes = parseInt(this.dataset.max);
        const candId = input.value;
        const isCheckbox = input.type === 'checkbox';

        if (isCheckbox) {
            // Multi-vote: toggle checkbox
            const checked = input.checked;
            if (!checked) {
                // Check how many are already selected
                const currentCount = document.querySelectorAll(`label[data-pos="${posId}"] input:checked`).length;
                if (currentCount >= maxVotes) return; // At limit
            }
            input.checked = !input.checked;
            this.classList.toggle('selected', input.checked);
            const circle = document.getElementById(`chk-${posId}-${candId}`);
            if (circle) circle.classList.toggle('checked', input.checked);

            // Update counter
            const newCount = document.querySelectorAll(`label[data-pos="${posId}"] input:checked`).length;
            const counter = document.getElementById(`counter-${posId}`);
            if (counter) counter.textContent = `${newCount} / ${maxVotes} selected`;

            // Disable/enable unchecked options at limit
            document.querySelectorAll(`label[data-pos="${posId}"]`).forEach(lbl => {
                const inp = lbl.querySelector('input');
                if (!inp.checked && newCount >= maxVotes) {
                    lbl.classList.add('disabled');
                } else {
                    lbl.classList.remove('disabled');
                }
            });
        } else {
            // Single vote: radio behavior
            document.querySelectorAll(`label[data-pos="${posId}"]`).forEach(lbl => {
                lbl.classList.remove('selected');
                const c = lbl.querySelector('.check-circle');
                if (c) c.classList.remove('checked');
                lbl.querySelector('input').checked = false;
            });
            input.checked = true;
            this.classList.add('selected');
            const circle = document.getElementById(`chk-${posId}-${candId}`);
            if (circle) circle.classList.add('checked');
        }
    });
});

document.getElementById('ballotForm').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to submit your ballot? This action cannot be undone.')) {
        e.preventDefault();
    }
});
</script>
</body>
</html>
