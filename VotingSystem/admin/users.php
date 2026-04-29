<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
require_once "../User.php";
require_once "../Vote.php";
require_once "admin_sidebar.php";
$userObj = new User(); $voteObj = new Vote();
$message = ""; $message_type = "";

if (isset($_GET['delete']) && (int)$_GET['delete'] !== $_SESSION['user_id']) {
    $userObj->deleteUser((int)$_GET['delete']);
    $message = "User deleted."; $message_type = "success";
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_active'])) {
    $uid = (int)$_POST['user_id']; $cur = (int)$_POST['current_active'];
    $data = array_merge((array)$userObj->getUserById($uid), ['is_active'=>$cur?0:1]);
    $userObj->updateUser($uid, $data);
    $message = "Status updated."; $message_type = "success";
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_role'])) {
    $uid = (int)$_POST['user_id']; $role = $_POST['new_role'];
    $u = $userObj->getUserById($uid);
    if ($u) { $u['role']=$role; $userObj->updateUser($uid,$u); $message="Role updated."; $message_type="success"; }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])) {
    $nu = new User();
    $nu->student_id      = trim(htmlspecialchars($_POST['student_id']));
    $nu->last_name       = trim(htmlspecialchars($_POST['last_name']));
    $nu->first_name      = trim(htmlspecialchars($_POST['first_name']));
    $nu->middle_initial  = trim(htmlspecialchars($_POST['middle_initial'] ?? ''));
    $nu->email           = trim(htmlspecialchars($_POST['email']));
    $nu->course          = trim(htmlspecialchars($_POST['course']));
    $nu->year_level      = (int)$_POST['year_level'];
    $nu->role            = $_POST['role'];
    $nu->password        = $_POST['password'];
    if ($nu->studentIdExists($nu->student_id)) { $message="Student ID already registered."; $message_type="error"; }
    elseif ($nu->emailExists($nu->email)) { $message="Email already registered."; $message_type="error"; }
    elseif ($nu->register()) { $message="User added!"; $message_type="success"; $voteObj->logAction($_SESSION['user_id'],'ADD_USER',"Added: {$nu->last_name}, {$nu->first_name}",$_SERVER['REMOTE_ADDR']??''); }
    else { $message="Failed to add user."; $message_type="error"; }
}

$role_filter = $_GET['role'] ?? '';
$users = $userObj->getAllUsers($role_filter);
$courses = ["BS Computer Science","BS Information Technology","BS Computer Engineering","BS Electrical Engineering","BS Civil Engineering","BS Nursing","BS Education","BA Communication","BA Political Science","BS Business Administration","Others"];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Users — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style><?php echo getAdminCSS(); ?>
.btn-warning{background:var(--gold);color:var(--primary-dark);}
</style>
</head><body>
<div class="layout">
<?php renderAdminSidebar('users'); ?>
<main class="main">
<div class="page-header"><h1><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg> Manage Users</h1></div>
<?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div><?php endif; ?>
<div class="grid" style="grid-template-columns:1fr 1.8fr;">
  <div class="card">
    <div class="card-title">Add User</div>
    <form method="POST">
      <input type="hidden" name="add_user" value="1">
      <div class="form-group"><label>Student ID *</label><input type="text" name="student_id" placeholder="2021-00001" required></div>
      <div class="form-row">
        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" placeholder="Dela Cruz" required></div>
        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" placeholder="Juan" required></div>
      </div>
      <div class="form-group"><label>M.I.</label><input type="text" name="middle_initial" placeholder="A." maxlength="5"></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" placeholder="student@wmsu.edu.ph" required></div>
      <div class="form-row">
        <div class="form-group"><label>Course</label><select name="course"><option value="">--</option><?php foreach($courses as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Year</label><select name="year_level"><?php for($i=1;$i<=5;$i++): ?><option value="<?php echo $i; ?>">Year <?php echo $i; ?></option><?php endfor; ?></select></div>
      </div>
      <div class="form-group"><label>Role</label><select name="role"><option value="voter">Voter</option><option value="usc">USC</option><option value="admin">Admin</option></select></div>
      <div class="form-group"><label>Password *</label><input type="password" name="password" placeholder="Min 8 chars" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Add User</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">All Users (<?php echo count($users); ?>)</div>
    <div class="filter-tabs">
      <a href="users.php" class="filter-tab <?php echo !$role_filter?'active':''; ?>">All</a>
      <a href="users.php?role=voter" class="filter-tab <?php echo $role_filter==='voter'?'active':''; ?>">Voters</a>
      <a href="users.php?role=usc" class="filter-tab <?php echo $role_filter==='usc'?'active':''; ?>">USC</a>
      <a href="users.php?role=admin" class="filter-tab <?php echo $role_filter==='admin'?'active':''; ?>">Admins</a>
    </div>
    <?php if (count($users)>0): ?>
    <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Name</th><th>Student ID</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong><br><span style="font-size:11px;color:#a0aec0;"><?php echo htmlspecialchars($u['email']); ?></span></td>
          <td style="font-size:12px;"><?php echo htmlspecialchars($u['student_id']); ?></td>
          <td><span class="role-pill pill-<?php echo $u['role']; ?>"><?php echo strtoupper($u['role']); ?></span></td>
          <td><?php echo $u['is_active'] ? '<span class="active-dot"></span>Active' : '<span class="inactive-dot"></span>Inactive'; ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
              <select name="new_role" style="font-size:11px;padding:3px 6px;width:auto;">
                <?php foreach(['voter','usc','admin'] as $r): ?><option value="<?php echo $r; ?>" <?php echo $u['role']===$r?'selected':''; ?>><?php echo ucfirst($r); ?></option><?php endforeach; ?>
              </select>
              <button type="submit" name="change_role" class="btn btn-sm btn-primary">Set</button>
            </form>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><input type="hidden" name="current_active" value="<?php echo $u['is_active']; ?>">
              <button type="submit" name="toggle_active" class="btn btn-sm <?php echo $u['is_active']?'btn-warning':'btn-primary'; ?>"><?php echo $u['is_active']?'Disable':'Enable'; ?></button>
            </form>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Del</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php else: ?><div class="no-data">No users found.</div><?php endif; ?>
  </div>
</div>
</main></div></body></html>
