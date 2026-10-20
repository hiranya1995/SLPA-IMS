<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Transaction History';
$division_id = $_SESSION['division_id']; 

// ── Filters ──────────────────────────────────────────────────
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';

// ── Fetch issue logs  ───
$where_issue = "WHERE `action_type` = 'ISSUE' AND `division_id` = $division_id";
if ($date_from) $where_issue .= " AND DATE(`log_date`) >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
if ($date_to)   $where_issue .= " AND DATE(`log_date`) <= '" . mysqli_real_escape_string($conn, $date_to) . "'";

$issue_res = mysqli_query($conn, "SELECT * FROM `logs` $where_issue ORDER BY `log_date` DESC LIMIT 200");
$issue_logs = [];
while ($r = mysqli_fetch_assoc($issue_res)) $issue_logs[] = $r;

// ── Fetch return logs  ──
$where_return = "WHERE `action_type` = 'RETURN' AND `division_id` = $division_id";
if ($date_from) $where_return .= " AND DATE(`log_date`) >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
if ($date_to)   $where_return .= " AND DATE(`log_date`) <= '" . mysqli_real_escape_string($conn, $date_to) . "'";

$return_res = mysqli_query($conn, "SELECT * FROM `logs` $where_return ORDER BY `log_date` DESC LIMIT 200");
$return_logs = [];
while ($r = mysqli_fetch_assoc($return_res)) $return_logs[] = $r;

include 'partials/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — <?= htmlspecialchars($_SESSION['division_name']) ?></div>
      <h1 class="page-title">Transaction History Logs</h1>
      <p class="page-subtitle">Track historical device issuance actions, destination locations, and associated work orders.</p>
    </div>
    <div style="display:flex;gap:.5rem;">
      <a href="view.php" class="btn btn-outline btn-sm">📦 View Current Stock</a>
      <a href="index.php" class="btn btn-outline btn-sm">← Dashboard</a>
    </div>
  </div>

  <form method="GET" action="history.php" style="margin-bottom:2rem; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; background:var(--steel-50); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--steel-200);">
    <label style="font-size:12px; font-weight:600; color:var(--steel-600); text-transform:uppercase; letter-spacing:.05em;">Filter From</label>
    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-control" style="width:160px; height:34px; font-size:13px;">
    
    <label style="font-size:12px; font-weight:600; color:var(--steel-600); text-transform:uppercase; letter-spacing:.05em;">To</label>
    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="form-control" style="width:160px; height:34px; font-size:13px;">
    
    <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
    <?php if ($date_from || $date_to): ?>
        <a href="history.php" class="btn btn-outline btn-sm">Clear Filter</a>
    <?php endif; ?>
  </form>

  <div class="section-divider" style="margin-bottom:1.5rem;">
    <h3>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
      Issued Items History Logs
    </h3>
  </div>

  <div class="card" style="margin-bottom:2.5rem;">
    <div class="table-wrap">
      <?php if (count($issue_logs) > 0): ?>
      <table>
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 15%;">Date &amp; Time</th>
            <th style="width: 35%;">Item Name</th>
            <th style="width: 10%;">Activity</th>
            <th style="width: 8%;">Quantity</th>
            <th style="width: 14%;">Division / Location</th>
            <th style="width: 13%;">Work Order No</th> 
          </tr>
        </thead>
        <tbody>
          <?php 
          $j = 1; 
          foreach ($issue_logs as $log): 
          ?>
          <tr>
            <td style="font-weight:600; color:var(--navy-800);"><?= $j ?></td>
            <td class="table-mono" style="color:var(--steel-600); white-space:nowrap;"><?= date('d M Y', strtotime($log['log_date'])) ?><br><span style="font-size:11px; color:var(--steel-400);"><?= date('H:i:s', strtotime($log['log_date'])) ?></span></td>
            <td style="font-weight:500; color:var(--navy-900);"><?= htmlspecialchars($log['item_name']) ?></td>
            <td><span class="badge badge-yellow">↓ Issued</span></td>
            <td class="table-mono" style="font-weight:700; font-size:15px; color:var(--warning);"><?= $log['quantity'] ?></td>
            <td>
              <?php if ($log['location']): ?>
              <span style="display:flex; align-items:center; gap:.35rem;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($log['location']) ?>
              </span>
              <?php else: ?>
              <span style="color:var(--steel-400);">—</span>
              <?php endif; ?>
            </td>
            <td class="table-mono" style="font-weight:600; color:var(--navy-700); font-size:12.5px;">
              <?= $log['work_order_no'] ? htmlspecialchars($log['work_order_no']) : '<span style="color:var(--steel-300);">—</span>' ?>
            </td>
          </tr>
          <?php 
            $j++; 
          endforeach; 
          ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state" style="padding:2rem;">
        <p>No item issue records match your current criteria sequence traces.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="section-divider" style="margin-bottom:1.5rem;">
    <h3>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
      Returned Items Ledger Logs
    </h3>
  </div>

  <div class="card">
    <div class="table-wrap">
      <?php if (count($return_logs) > 0): ?>
      <table>
        <thead>
          <tr>
            <th style="width: 6%;">#</th>
            <th style="width: 18%;">Date &amp; Time</th>
            <th style="width: 54%;">Item Name</th>
            <th style="width: 12%;">Activity</th>
            <th style="width: 10%;">Quantity Returned</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $k = 1; 
          foreach ($return_logs as $log): 
          ?>
          <tr>
            <td style="font-weight:600; color:var(--navy-800);"><?= $k ?></td>
            <td class="table-mono" style="color:var(--steel-600); white-space:nowrap;"><?= date('d M Y', strtotime($log['log_date'])) ?><br><span style="font-size:11px; color:var(--steel-400);"><?= date('H:i:s', strtotime($log['log_date'])) ?></span></td>
            <td style="font-weight:500; color:var(--navy-900);"><?= htmlspecialchars($log['item_name']) ?></td>
            <td><span class="badge badge-blue">↺ Returned</span></td>
            <td class="table-mono" style="font-weight:700; font-size:15px; color:var(--success);">+<?= $log['quantity'] ?></td>
          </tr>
          <?php 
            $k++; 
          endforeach; 
          ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state" style="padding:2rem;">
        <p>No historical asset items have been re-routed back to stock inventory registers.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include 'partials/footer.php'; ?>