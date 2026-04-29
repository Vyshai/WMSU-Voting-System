<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'usc'])) {
    header("Location: ../login.php"); exit();
}
require_once "../Election.php";
require_once "../Vote.php";
require_once "admin_sidebar.php";

$electionObj = new Election();
$electionObj->updateStatuses();
$voteObj = new Vote();

$message = ""; $message_type = "";

// DELETE
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    $id = (int)$_GET['delete'];
    if ($electionObj->deleteElection($id)) {
        $message = "Election deleted."; $message_type = "success";
        $voteObj->logAction($_SESSION['user_id'], 'DELETE_ELECTION', "Deleted election #$id", $_SERVER['REMOTE_ADDR'] ?? '');
    } else { $message = "Failed to delete."; $message_type = "error"; }
}

// DELETE POSITION
if (isset($_GET['del_pos'])) {
    $pid = (int)$_GET['del_pos']; $eid = (int)$_GET['eid'];
    $electionObj->deletePosition($pid);
    header("Location: elections.php?edit=$eid"); exit();
}

$editElection = null; $action = $_GET['action'] ?? '';
if (isset($_GET['edit'])) { $editElection = $electionObj->getElectionById((int)$_GET['edit']); $action = 'edit'; }
if (isset($_GET['msg']) && $_GET['msg'] === 'created' && isset($_GET['edit'])) {
    $editElection = $electionObj->getElectionById((int)$_GET['edit']); $action = 'edit';
    $message = "Election created! Add positions below."; $message_type = "success";
}

// CREATE / EDIT SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_position'])) {
    $title = trim(htmlspecialchars($_POST['title']));
    $description = trim(htmlspecialchars($_POST['description']));
    $start_date = $_POST['start_date']; $end_date = $_POST['end_date'];
    $status = $_POST['status']; $election_id_post = (int)($_POST['election_id'] ?? 0);
    $errors = [];
    if (empty($title)) $errors[] = "Title required.";
    if (empty($start_date) || empty($end_date)) $errors[] = "Dates required.";
    if (!empty($start_date) && !empty($end_date) && $start_date >= $end_date) $errors[] = "End date must be after start.";
    if (empty($errors)) {
        $electionObj->title = $title; $electionObj->description = $description;
        $electionObj->start_date = $start_date; $electionObj->end_date = $end_date; $electionObj->status = $status;
        if ($election_id_post > 0) {
            if ($electionObj->updateElection($election_id_post)) {
                $message = "Election updated!"; $message_type = "success";
                $voteObj->logAction($_SESSION['user_id'], 'UPDATE_ELECTION', "Updated election #$election_id_post", $_SERVER['REMOTE_ADDR'] ?? '');
                $editElection = $electionObj->getElectionById($election_id_post); $action = 'edit';
            } else { $message = "Update failed."; $message_type = "error"; }
        } else {
            $new_id = $electionObj->createElection($_SESSION['user_id']);
            if ($new_id) {
                $voteObj->logAction($_SESSION['user_id'], 'CREATE_ELECTION', "Created election #$new_id: $title", $_SERVER['REMOTE_ADDR'] ?? '');
                header("Location: elections.php?edit=$new_id&msg=created"); exit();
            } else { $message = "Creation failed."; $message_type = "error"; }
        }
    } else { $message = implode(' ', $errors); $message_type = "error"; }
}

// ADD POSITION
if (isset($_POST['add_position'])) {
    $eid = (int)$_POST['pos_election_id'];
    $ptitle = trim(htmlspecialchars($_POST['pos_title']));
    $pdesc = trim(htmlspecialchars($_POST['pos_description']));
    $pmaxvotes = max(1, (int)$_POST['max_votes']);
    $porder = (int)$_POST['sort_order'];
    if (!empty($ptitle)) {
        $electionObj->addPosition($eid, $ptitle, $pdesc, $pmaxvotes, $porder);
        $message = "Position added!"; $message_type = "success";
    }
    $editElection = $electionObj->getElectionById($eid); $action = 'edit';
}

$allElections = $electionObj->getAllElections();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style><?php echo getAdminCSS(); ?></style>
</head>
<body>
<div class="layout">
    <?php renderAdminSidebar('elections'); ?>

    <main class="main">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Manage Elections</h1>
            <a href="elections.php?action=create" class="btn btn-primary">+ New Election</a>
        </div>

        <?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div><?php endif; ?>

        <div class="grid">
            <div>
                <?php if ($action === 'create' || $action === 'edit'): ?>
                <div class="card">
                    <div class="card-title"><?php echo $editElection ? 'Edit Election' : 'New Election'; ?></div>
                    <form method="POST">
                        <input type="hidden" name="election_id" value="<?php echo $editElection['id'] ?? 0; ?>">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($editElection['title'] ?? ''); ?>" placeholder="e.g. USC General Elections 2025" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" placeholder="Brief description..."><?php echo htmlspecialchars($editElection['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date &amp; Time *</label>
                                <input type="datetime-local" name="start_date" value="<?php echo $editElection ? date('Y-m-d\TH:i', strtotime($editElection['start_date'])) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>End Date &amp; Time *</label>
                                <input type="datetime-local" name="end_date" value="<?php echo $editElection ? date('Y-m-d\TH:i', strtotime($editElection['end_date'])) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach (['upcoming','active','ended'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($editElection['status'] ?? 'upcoming') === $s ? 'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <button type="submit" class="btn btn-primary"><?php echo $editElection ? 'Update' : 'Create'; ?></button>
                            <a href="elections.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>

                    <?php if ($editElection): ?>
                    <hr class="section-divider">
                    <div class="card-title">Positions</div>
                    <?php $positions = $electionObj->getPositions($editElection['id']); ?>
                    <?php if (count($positions) > 0): ?>
                        <?php foreach ($positions as $pos): ?>
                            <div class="position-item">
                                <div>
                                    <h4><?php echo htmlspecialchars($pos['title']); ?></h4>
                                    <p><?php echo $pos['candidate_count']; ?> candidates · Max <?php echo $pos['max_votes']; ?> vote(s)</p>
                                </div>
                                <a href="elections.php?del_pos=<?php echo $pos['id']; ?>&eid=<?php echo $editElection['id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this position?')">Delete</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#a0aec0;font-size:13px;margin-bottom:15px;">No positions yet.</p>
                    <?php endif; ?>
                    <div class="add-pos-box">
                        <div style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:12px;">+ Add Position</div>
                        <form method="POST">
                            <input type="hidden" name="pos_election_id" value="<?php echo $editElection['id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Position Title *</label>
                                    <input type="text" name="pos_title" placeholder="e.g. President" required>
                                </div>
                                <div class="form-group">
                                    <label>Sort Order</label>
                                    <input type="text" name="sort_order" value="<?php echo count($positions); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="pos_description" placeholder="Optional">
                            </div>
                            <div class="form-group">
                                <label>Max Votes Per Voter</label>
                                <input type="number" name="max_votes" value="1" min="1" max="24" style="width:100%;">
                                <p style="font-size:11px;color:#a0aec0;margin-top:4px;">Set to 1 for single-choice (e.g. President). Set higher for multi-vote (e.g. 12 for Senators).</p>
                            </div>
                            <button type="submit" name="add_position" class="btn btn-gold">Add Position</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <div class="card" style="text-align:center;padding:50px;">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="var(--primary)" style="margin-bottom:15px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        <h3 style="color:var(--primary);margin-bottom:10px;">Create or Edit an Election</h3>
                        <p style="color:#718096;font-size:14px;margin-bottom:20px;">Click an election to edit, or create a new one.</p>
                        <a href="elections.php?action=create" class="btn btn-primary">+ New Election</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">All Elections (<?php echo count($allElections); ?>)</div>
                <?php if (count($allElections) > 0): ?>
                <table>
                    <thead><tr><th>Title</th><th>Status</th><th>Dates</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($allElections as $e): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($e['title']); ?></strong><br>
                                <span style="color:#a0aec0;font-size:11px;"><?php echo $e['position_count']; ?> positions</span>
                            </td>
                            <td><span class="status-pill pill-<?php echo $e['status']; ?>"><?php echo ucfirst($e['status']); ?></span></td>
                            <td style="font-size:12px;color:#718096;">
                                <?php echo date('M d Y', strtotime($e['start_date'])); ?><br>
                                to <?php echo date('M d Y', strtotime($e['end_date'])); ?>
                            </td>
                            <td>
                                <a href="elections.php?edit=<?php echo $e['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="candidates.php?election_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-gold">Cands</a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="elections.php?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this election and ALL its data?')">Del</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="no-data">No elections yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
