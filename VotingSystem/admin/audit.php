<?php
require_once "../session_config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit();
}
require_once "../Vote.php";
require_once "admin_sidebar.php";
$voteObj = new Vote();
$logs = $voteObj->getAuditLog(200);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Audit Log — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style><?php echo getAdminCSS(); ?></style>
</head><body>
<div class="layout">
<?php renderAdminSidebar('audit'); ?>
<main class="main">
<div class="page-header" style="display:block;">
  <h1><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Audit Log</h1>
  <p style="color:#718096;font-size:14px;margin-top:5px;">Complete record of all system actions for transparency and accountability.</p>
</div>
<div class="card">
  <div class="card-title">System Activity Log (Last 200 entries)</div>
  <?php if (count($logs)>0): ?>
  <div style="overflow-x:auto;">
  <table>
    <thead><tr><th>#</th><th>Timestamp</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $i => $log): ?>
      <tr>
        <td style="color:#a0aec0;"><?php echo $i+1; ?></td>
        <td style="font-size:12px;white-space:nowrap;"><?php echo date('M d, Y g:i:s A', strtotime($log['created_at'])); ?></td>
        <td>
          <?php if ($log['full_name']): ?>
          <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
          <span style="font-size:11px;color:#a0aec0;"><?php echo htmlspecialchars($log['student_id'] ?? ''); ?></span>
          <?php else: ?><span style="color:#a0aec0;">System</span><?php endif; ?>
        </td>
        <td><span class="action-tag tag-<?php echo $log['action']; ?>"><?php echo htmlspecialchars($log['action']); ?></span></td>
        <td style="font-size:12px;color:#718096;max-width:250px;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
        <td style="font-size:12px;color:#a0aec0;font-family:monospace;"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?><div class="no-data">No activity recorded yet.</div><?php endif; ?>
</div>
</main></div></body></html>