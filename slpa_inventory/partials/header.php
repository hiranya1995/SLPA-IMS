<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Call: include 'partials/header.php';  (set $page_title before)
$page_title = $page_title ?? 'SLPA Inventory System';


$current_role = $_SESSION['role'] ?? 'guest';
$current_user = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — SLPA IMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>

:root {
  --navy-900: #0a1628;
  --navy-800: #0e2044;
  --navy-700: #12295a;
  --navy-600: #1a3a72;
  --navy-500: #1e4898;
  --blue-400: #2563eb;
  --blue-300: #3b82f6;
  --blue-200: #93c5fd;
  --blue-100: #dbeafe;
  --steel-700: #374151;
  --steel-600: #4b5563;
  --steel-500: #6b7280;
  --steel-400: #9ca3af;
  --steel-300: #d1d5db;
  --steel-200: #e5e7eb;
  --steel-100: #f3f4f6;
  --steel-50:  #f9fafb;
  --white:      #ffffff;
  --danger:      #dc2626;
  --danger-bg: #fef2f2;
  --success:    #059669;
  --success-bg:#ecfdf5;
  --warning:    #d97706;
  --warning-bg:#fffbeb;
  --font-main: 'IBM Plex Sans', sans-serif;
  --font-mono: 'IBM Plex Mono', monospace;
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --shadow-sm: 0 1px 3px rgba(10,22,40,.08), 0 1px 2px rgba(10,22,40,.06);
  --shadow-md: 0 4px 12px rgba(10,22,40,.10), 0 2px 4px rgba(10,22,40,.06);
  --shadow-lg: 0 10px 30px rgba(10,22,40,.12), 0 4px 8px rgba(10,22,40,.08);
  --transition: 150ms cubic-bezier(.4,0,.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  font-family: var(--font-main);
  font-size: 14px;
  line-height: 1.6;
  color: var(--steel-700);
  background: var(--steel-50);
  min-height: 100vh;
}

/* ── Top Navigation Bar ── */
.topbar {
  background: var(--navy-900);
  border-bottom: 3px solid var(--blue-400);
  padding: 0 2rem;
  display: flex;
  align-items: center;
  height: 64px;
  gap: 2rem;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,.3);
}

.topbar-brand {
  display: flex;
  align-items: center;
  gap: .75rem;
  text-decoration: none;
  flex-shrink: 0;
}

.topbar-logo {
  width: 36px;
  height: 36px;
  background: var(--blue-400);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
}

.topbar-logo svg { width: 22px; height: 22px; color: white; }

.topbar-name {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.topbar-name strong {
  font-size: 15px;
  font-weight: 700;
  color: var(--white);
  letter-spacing: .01em;
}

.topbar-name span {
  font-size: 10px;
  color: var(--blue-200);
  letter-spacing: .08em;
  text-transform: uppercase;
  font-weight: 500;
}

.topbar-divider { width: 1px; height: 32px; background: rgba(255,255,255,.12); }

.topbar-nav {
  display: flex;
  align-items: center;
  gap: .25rem;
  flex: 1;
}

.topbar-nav a {
  color: var(--steel-300);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  padding: .45rem .85rem;
  border-radius: var(--radius-sm);
  transition: background var(--transition), color var(--transition);
  letter-spacing: .01em;
}

.topbar-nav a:hover { background: rgba(255,255,255,.08); color: var(--white); }
.topbar-nav a.active { background: var(--blue-400); color: var(--white); }

.topbar-badge {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: .5rem;
  color: var(--steel-400);
  font-size: 12px;
  font-family: var(--font-mono);
}

/* User Identity Tag */
.user-tag { 
  color: white; 
  font-weight: 600; 
  display: flex; 
  align-items: center; 
  gap: 8px; 
  margin-right: 15px;
}

/* ── Page Wrapper ── */
.page-wrap {
  max-width: 1300px;
  margin: 0 auto;
  padding: 2rem 2rem 4rem;
}

/* ── Page Header ── */
.page-header {
  margin-bottom: 2rem;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-eyebrow {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--blue-400);
  margin-bottom: .25rem;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: var(--navy-800);
  letter-spacing: -.02em;
  line-height: 1.2;
}

.page-subtitle {
  font-size: 14px;
  color: var(--steel-500);
  margin-top: .25rem;
}

/* ── Cards ── */
.card {
  background: var(--white);
  border: 1px solid var(--steel-200);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--steel-200);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: var(--steel-50);
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--navy-700);
  display: flex;
  align-items: center;
  gap: .5rem;
}

.card-title .icon { color: var(--blue-400); }

.card-body { padding: 1.5rem; }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .55rem 1.2rem;
  border-radius: var(--radius-md);
  font-size: 13px;
  font-weight: 600;
  font-family: var(--font-main);
  cursor: pointer;
  border: none;
  text-decoration: none;
  transition: all var(--transition);
  letter-spacing: .01em;
  white-space: nowrap;
}

.btn-primary { background: var(--blue-400); color: var(--white); }
.btn-primary:hover { background: var(--navy-600); transform: translateY(-1px); box-shadow: var(--shadow-md); }

.btn-success { background: var(--success); color: var(--white); }
.btn-success:hover { background: #047857; transform: translateY(-1px); box-shadow: var(--shadow-md); }

.btn-danger { background: var(--danger); color: var(--white); }
.btn-danger:hover { background: #b91c1c; }

.btn-outline {
  background: transparent;
  color: var(--steel-600);
  border: 1.5px solid var(--steel-300);
}
.btn-outline:hover { border-color: var(--blue-400); color: var(--blue-400); background: var(--blue-100); }

.btn-sm { padding: .35rem .8rem; font-size: 12px; }
.btn-lg { padding: .75rem 1.75rem; font-size: 15px; border-radius: var(--radius-lg); }

/* ── Form Elements ── */
.form-group { margin-bottom: 1.25rem; }

.form-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--steel-600);
  margin-bottom: .4rem;
}

.form-control,
.form-select {
  width: 100%;
  padding: .65rem .9rem;
  border: 1.5px solid var(--steel-300);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-family: var(--font-main);
  color: var(--steel-700);
  background: var(--white);
  transition: border-color var(--transition), box-shadow var(--transition);
  appearance: none;
}

.form-control:focus,
.form-select:focus {
  outline: none;
  border-color: var(--blue-400);
  box-shadow: 0 0 0 3px rgba(37,99,235,.15);
}

.form-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right .75rem center;
  padding-right: 2.5rem;
}

.form-hint { font-size: 11px; color: var(--steel-400); margin-top: .3rem; }

/* ── Tables ── */
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; font-size: 13.5px; }

thead tr { background: var(--navy-900); }

thead th {
  padding: .75rem 1rem;
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--blue-200);
  white-space: nowrap;
}

tbody tr { border-bottom: 1px solid var(--steel-200); transition: background var(--transition); }

tbody tr:hover { background: var(--blue-100); }
tbody tr:last-child { border-bottom: none; }

tbody td { padding: .75rem 1rem; color: var(--steel-700); vertical-align: middle; }

.table-mono { font-family: var(--font-mono); font-size: 12.5px; }

/* ── Badges ── */
.badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 100px; font-size: 11px; font-weight: 600; letter-spacing: .03em; }

.badge-blue   { background: var(--blue-100); color: var(--navy-600); }
.badge-green  { background: var(--success-bg); color: var(--success); }
.badge-red    { background: var(--danger-bg); color: var(--danger); }
.badge-yellow { background: var(--warning-bg); color: var(--warning); }
.badge-grey   { background: var(--steel-200); color: var(--steel-600); }

/* ── Alerts ── */
.alert { padding: .85rem 1.1rem; border-radius: var(--radius-md); font-size: 13.5px; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: .6rem; border-left: 4px solid; }

.alert-success { background: var(--success-bg); color: #065f46; border-color: var(--success); }
.alert-danger  { background: var(--danger-bg);  color: #991b1b; border-color: var(--danger); }
.alert-warning { background: var(--warning-bg); color: #92400e; border-color: var(--warning); }
.alert-info    { background: var(--blue-100);   color: var(--navy-700); border-color: var(--blue-300); }

/* ── Section Divider ── */
.section-divider { margin: 2.5rem 0 1.5rem; display: flex; align-items: center; gap: .75rem; }

.section-divider h3 { font-size: 15px; font-weight: 700; color: var(--navy-700); white-space: nowrap; display: flex; align-items: center; gap: .5rem; }

.section-divider::after { content: ''; flex: 1; height: 1px; background: var(--steel-200); }

/* ── Empty State ── */
.empty-state { text-align: center; padding: 3rem 1rem; color: var(--steel-400); }

.empty-state svg { width: 40px; height: 40px; margin: 0 auto .75rem; display: block; opacity: .4; }
.empty-state p { font-size: 13.5px; }

/* ── Stat Pills ── */
.stat-row { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }

.stat-pill { background: var(--white); border: 1px solid var(--steel-200); border-radius: var(--radius-md); padding: .75rem 1.25rem; display: flex; align-items: center; gap: .75rem; flex: 1; min-width: 160px; box-shadow: var(--shadow-sm); }

.stat-pill-icon { width: 38px; height: 38px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

.stat-pill-icon.blue   { background: var(--blue-100); color: var(--blue-400); }
.stat-pill-icon.green  { background: var(--success-bg); color: var(--success); }
.stat-pill-icon.red    { background: var(--danger-bg);  color: var(--danger); }
.stat-pill-icon.yellow { background: var(--warning-bg); color: var(--warning); }

.stat-pill-value { font-size: 22px; font-weight: 700; color: var(--navy-800); line-height: 1; }
.stat-pill-label { font-size: 11px; color: var(--steel-500); text-transform: uppercase; letter-spacing: .05em; margin-top: .15rem; }

/* ── Footer ── */
footer { margin-top: 4rem; border-top: 1px solid var(--steel-200); padding: 1.5rem 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }

footer p { font-size: 12px; color: var(--steel-400); }
footer strong { color: var(--navy-700); }

/* ── Responsive ── */
@media (max-width: 768px) {
  .topbar { padding: 0 1rem; gap: 1rem; }
  .topbar-nav a { padding: .4rem .6rem; font-size: 12px; }
  .page-wrap { padding: 1.25rem 1rem 3rem; }
  .page-title { font-size: 22px; }
  .stat-row { flex-direction: column; }
}
</style>
</head>
<body>

<nav class="topbar">
 
  <a href="<?= ($current_role === 'admin') ? 'index.php' : 'user_view.php' ?>" class="topbar-brand">
    <div class="topbar-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
    </div>
    <div class="topbar-name">
      <strong>SLPA — IMS</strong>
      <span>Inventory Management</span>
    </div>
  </a>

  <div class="topbar-divider"></div>

 <div class="topbar-nav">
    <?php if ($current_role === 'admin'): ?>
        <a href="index.php" <?= (basename($_SERVER['PHP_SELF'])=='index.php') ? 'class="active"':'' ?>>Dashboard</a>
        <a href="add.php"   <?= (basename($_SERVER['PHP_SELF'])=='add.php')   ? 'class="active"':'' ?>>Add Items</a>
        <a href="issue.php" <?= (basename($_SERVER['PHP_SELF'])=='issue.php') ? 'class="active"':'' ?>>Issue Items</a>
        <a href="view.php"  <?= (basename($_SERVER['PHP_SELF'])=='view.php')  ? 'class="active"':'' ?>>View Stock</a>
        
        <a href="history.php" <?= (basename($_SERVER['PHP_SELF'])=='history.php') ? 'class="active"':'' ?>>Transaction History</a>
    <?php else: ?>
        <?php if (basename($_SERVER['PHP_SELF']) !== 'user_view.php'): ?>
            <a href="user_view.php">Search Inventory</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

  <div class="topbar-badge">
    <div class="user-tag">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?= htmlspecialchars($current_user) ?>
    </div>
    <div style="font-family: var(--font-mono);">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></svg>
        <?= date('d M Y') ?>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" style="color: var(--danger); text-decoration: none; font-weight: 700; margin-left: 15px;">Logout</a>
    <?php endif; ?>
  </div>
</nav>