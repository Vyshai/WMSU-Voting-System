<?php
/**
 * Renders the admin sidebar with the WMSU logo and maroon/gold theme.
 * @param string $active The current active page key (dashboard, elections, candidates, results, users, audit)
 */
function renderAdminSidebar($active = '') {
    $role = $_SESSION['role'];
    $logoPath = dirname(__DIR__) === dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']) ? 'uploads/wmsu_logo.png' : '../uploads/wmsu_logo.png';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><img src="<?php echo $logoPath; ?>" alt="WMSU" style="width:100%;height:100%;object-fit:cover;border-radius:8px;"></div>
        <h2>WMSU Voting Admin</h2>
        <p><?php echo strtoupper($role); ?> Panel</p>
    </div>
    <div class="nav-section">
        <div class="nav-section-title">Overview</div>
        <div class="nav-item<?php echo $active==='dashboard'?' active':''; ?>"><a href="dashboard.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-5h2v5zm4 0h-2v-7h2v7zm4 0h-2v-3h2v3z"/></svg> Dashboard</a></div>
    </div>
    <div class="nav-section">
        <div class="nav-section-title">Elections</div>
        <div class="nav-item<?php echo $active==='elections'?' active':''; ?>"><a href="elections.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Manage Elections</a></div>
        <div class="nav-item<?php echo $active==='candidates'?' active':''; ?>"><a href="candidates.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Candidates</a></div>
        <div class="nav-item<?php echo $active==='results'?' active':''; ?>"><a href="results.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg> Results &amp; Reports</a></div>
    </div>
    <?php if ($role === 'admin'): ?>
    <div class="nav-section">
        <div class="nav-section-title">Administration</div>
        <div class="nav-item<?php echo $active==='users'?' active':''; ?>"><a href="users.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg> Manage Users</a></div>
        <div class="nav-item<?php echo $active==='audit'?' active':''; ?>"><a href="audit.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Audit Log</a></div>
    </div>
    <?php endif; ?>
    <div class="nav-section">
        <div class="nav-item"><a href="../index.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg> View Site</a></div>
    </div>
    <div class="sidebar-footer">
        <div style="font-size:13px;font-weight:600;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        <a href="../logout.php"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:middle;margin-right:4px;"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> Logout</a>
    </div>
</aside>
<?php } ?>

<?php
/**
 * Returns the shared admin CSS for the maroon/gold theme.
 */
function getAdminCSS() {
    return '
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #7B1113; --primary-dark: #5a0c0e; --primary-light: #9e2a2b; --gold: #D4A843; --gold-dark: #b8912a; --sidebar: 250px; --light: #f7f3ef; --border: #e8ddd3; }
        body { font-family: "Inter", "Segoe UI", Arial, sans-serif; background: var(--light); }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar); background: var(--primary-dark); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 50; }
        .sidebar-brand { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-logo { width: 42px; height: 42px; border-radius: 8px; overflow: hidden; margin-bottom: 10px; border: 2px solid var(--gold); }
        .sidebar-brand h2 { font-size: 15px; font-weight: 700; }
        .sidebar-brand p { font-size: 11px; opacity: 0.5; margin-top: 2px; }
        .nav-section { padding: 8px 0; }
        .nav-section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.4); padding: 12px 20px 6px; }
        .nav-item a { display: flex; align-items: center; gap: 10px; padding: 11px 20px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; border-left: 3px solid transparent; transition: all 0.2s; }
        .nav-item a svg { flex-shrink: 0; }
        .nav-item a:hover, .nav-item.active a { background: rgba(255,255,255,0.08); color: white; border-left-color: var(--gold); }
        .sidebar-footer { margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.08); }
        .sidebar-footer a { color: #fc8181; font-size: 12px; text-decoration: none; display: block; margin-top: 8px; }
        .main { margin-left: var(--sidebar); flex: 1; padding: 35px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h1 { font-size: 24px; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .page-header h1 svg { width: 24px; height: 24px; fill: var(--primary); }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar h1 { font-size: 24px; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .topbar h1 svg { width: 24px; height: 24px; fill: var(--primary); }
        .topbar p { font-size: 13px; color: #718096; margin-top: 3px; }
        .btn { display: inline-block; padding: 9px 18px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; font-family: "Inter", sans-serif; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-gold { background: var(--gold); color: var(--primary-dark); }
        .btn-gold:hover { background: var(--gold-dark); }
        .btn-danger { background: #fc8181; color: white; }
        .btn-danger:hover { background: #e53e3e; }
        .btn-success { background: #48bb78; color: white; }
        .btn-outline { border: 2px solid var(--primary); color: var(--primary); background: none; }
        .btn-outline:hover { background: var(--primary); color: white; }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        .btn-create { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; background: var(--primary); color: white; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; }
        .btn-create:hover { background: var(--primary-dark); }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        .card { background: white; border-radius: 14px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .card-title { font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 12px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 4px solid var(--primary); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card.gold { border-left-color: var(--gold); }
        .stat-card.green { border-left-color: #48bb78; }
        .stat-card.red { border-left-color: #fc8181; }
        .stat-label { font-size: 12px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .stat-number { font-size: 36px; font-weight: 800; color: var(--primary); }
        .stat-card.gold .stat-number { color: var(--gold-dark); }
        .stat-card.green .stat-number { color: #276749; }
        .stat-card.red .stat-number { color: #c53030; }
        .stat-icon { font-size: 28px; float: right; opacity: 0.3; }
        .stat-icon svg { width: 28px; height: 28px; fill: currentColor; }
        .panels { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; }
        .panel { background: white; border-radius: 14px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .panel-title { font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
        .panel-title a { font-size: 13px; font-weight: 500; color: var(--gold-dark); text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 10px 12px; text-align: left; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        table th { background: #f8fafc; color: #718096; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        tr:hover td { background: #fafbff; }
        .status-pill { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .pill-active { background: #c6f6d5; color: #276749; }
        .pill-upcoming { background: #fef3c7; color: #92400e; }
        .pill-ended { background: #e2e8f0; color: #4a5568; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }
        input[type="text"], input[type="email"], input[type="password"], input[type="datetime-local"], textarea, select { width: 100%; padding: 11px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; transition: all 0.2s; font-family: "Inter", sans-serif; }
        input:focus, textarea:focus, select:focus { border-color: var(--primary); outline: none; background: white; }
        textarea { resize: vertical; min-height: 80px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 25px; }
        .audit-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .audit-item:last-child { border-bottom: none; }
        .audit-icon { width: 30px; height: 30px; border-radius: 50%; background: #fff8ee; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .audit-icon svg { width: 14px; height: 14px; fill: var(--primary); }
        .audit-info { flex: 1; }
        .audit-action { font-size: 13px; font-weight: 600; color: #2d3748; }
        .audit-meta { font-size: 11px; color: #a0aec0; margin-top: 2px; }
        .no-data { text-align: center; padding: 30px; color: #a0aec0; font-size: 14px; }
        .section-divider { border: none; border-top: 2px dashed #e2e8f0; margin: 25px 0; }
        .position-item { background: #f8fafc; border-radius: 8px; padding: 12px 15px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .position-item h4 { font-size: 14px; font-weight: 600; color: #2d3748; }
        .position-item p { font-size: 12px; color: #a0aec0; margin-top: 2px; }
        .add-pos-box { background: #f8fafc; border-radius: 10px; padding: 18px; margin-top: 15px; }
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
        .filter-tab { padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; background: #f0f0f0; color: #4a5568; transition: all 0.2s; }
        .filter-tab.active, .filter-tab:hover { background: var(--primary); color: white; }
        .role-pill { padding: 3px 9px; border-radius: 15px; font-size: 11px; font-weight: 700; }
        .pill-admin { background: #e9d8fd; color: #553c9a; }
        .pill-usc { background: #bee3f8; color: #2c5282; }
        .pill-voter { background: #c6f6d5; color: #276749; }
        .active-dot { width: 8px; height: 8px; border-radius: 50%; background: #48bb78; display: inline-block; margin-right: 5px; }
        .inactive-dot { width: 8px; height: 8px; border-radius: 50%; background: #fc8181; display: inline-block; margin-right: 5px; }
        .cand-pill { padding: 3px 9px; border-radius: 15px; font-size: 11px; font-weight: 700; }
        .cand-approved { background: #c6f6d5; color: #276749; }
        .cand-pending { background: #fef3c7; color: #92400e; }
        .cand-rejected { background: #fed7d7; color: #c53030; }
        .cand-photo { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; overflow: hidden; flex-shrink: 0; }
        .cand-photo img { width: 100%; height: 100%; object-fit: cover; }
        .action-tag { padding: 3px 9px; border-radius: 10px; font-size: 11px; font-weight: 700; background: #e2e8f0; color: #4a5568; }
        .tag-LOGIN { background: #ebf8ff; color: #2b6cb0; }
        .tag-LOGOUT { background: #fff5f5; color: #c53030; }
        .tag-VOTE_CAST { background: #c6f6d5; color: #276749; }
        .tag-CREATE_ELECTION { background: #fef3c7; color: #92400e; }
        .tag-ADD_CANDIDATE { background: #e9d8fd; color: #553c9a; }
        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .panels { grid-template-columns: 1fr; } }
        @media (max-width: 1000px) { .grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 20px; } }
    ';
}
?>
