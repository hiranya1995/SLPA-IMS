<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Issue Items';
$messages = [];
$division_id = $_SESSION['division_id']; 

// ── Handle Issue POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue'])) {
    $issued_count = 0;
    $errors = [];

    foreach ($_POST['issue'] as $item_id => $data) {
        $issue_qty = intval($data['qty'] ?? 0);
        $location  = trim($data['location'] ?? '');
        $work_order = trim($data['work_order'] ?? ''); // Capture Work Order input data

        if ($issue_qty <= 0) continue;

        $item_id = intval($item_id);
        $location_safe = mysqli_real_escape_string($conn, $location);
        $work_order_safe = mysqli_real_escape_string($conn, $work_order);

        
        $res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `id` = $item_id AND `division_id` = $division_id");
        if (!$res || mysqli_num_rows($res) == 0) continue;

        $item = mysqli_fetch_assoc($res);
        $item_name_safe = mysqli_real_escape_string($conn, $item['item_name']);

        if ($issue_qty > $item['quantity']) {
            $errors[] = "Insufficient stock for <strong>" . htmlspecialchars($item['item_name']) .
                        "</strong> — Requested: $issue_qty, Available: {$item['quantity']}";
            continue;
        }

        if (empty($location)) {
            $errors[] = "Please enter a Destination Division/Location for <strong>" . htmlspecialchars($item['item_name']) . "</strong>.";
            continue;
        }

        
        $new_qty = $item['quantity'] - $issue_qty;
        mysqli_query($conn, "UPDATE `inventory` SET `quantity` = $new_qty WHERE `id` = $item_id AND `division_id` = $division_id");

        
        mysqli_query($conn,
            "INSERT INTO `logs` (`division_id`, `item_name`, `action_type`, `quantity`, `location`, `work_order_no`, `log_date`)
             VALUES ($division_id, '$item_name_safe', 'ISSUE', $issue_qty, '$location_safe', " . (!empty($work_order_safe) ? "'$work_order_safe'" : "NULL") . ", NOW())"
        );

        $issued_count++;
    }

    if ($issued_count > 0) {
        $messages[] = ['type' => 'success',
            'text' => "✓ Successfully issued <strong>$issued_count item type(s)</strong>. Stock updated and transactions logged."];
    }
    foreach ($errors as $err) {
        $messages[] = ['type' => 'danger', 'text' => $err];
    }
}

// ── Fetch available stock ────────────────────────────────────
$stock_res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `division_id` = $division_id ORDER BY `item_name` ASC");
$stock = [];
while ($r = mysqli_fetch_assoc($stock_res)) $stock[] = $r;

include 'partials/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — <?= htmlspecialchars($_SESSION['division_name']) ?></div>
      <h1 class="page-title">Issue Items</h1>
      <p class="page-subtitle">Dispatch hardware to port divisions, bind operational work order numbers, and log transactions.</p>
    </div>
    <a href="index.php" class="btn btn-outline">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
  </div>

  <?php foreach ($messages as $m): ?>
  <div class="alert alert-<?= $m['type'] ?>"><?= $m['text'] ?></div>
  <?php endforeach; ?>

  <div class="alert alert-info" style="margin-bottom:1.5rem;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>Type the <strong>Specific Destination Division/Workshop</strong> and corresponding <strong>Work Order Number</strong> for each issue selection block.</span>
  </div>

  <form method="POST" action="issue.php" id="issueForm">
    <div class="card">
      <div class="card-header">
        <span class="card-title">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          Available Stock — Issue Table
        </span>
        <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Issue Selected Items
          </button>
      </div>

      <div style="padding:.75rem 1.5rem;border-bottom:1px solid var(--steel-200);background:var(--steel-50);display:flex;align-items:center;gap:.75rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--steel-400);flex-shrink:0;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="tableFilter" class="form-control" placeholder="Filter items…" style="max-width:320px;height:34px;font-size:13px;" oninput="filterTable(this.value)">
      </div>

      <div class="table-wrap">
        <table id="issueTable">
          <thead>
            <tr>
              <th style="width:50px;">#</th>
              <th>Item Name</th>
              <th style="width:90px;">Available</th>
              <th style="width:230px;">Destination / Specific Location</th>
              <th style="width:180px;">Work Order No</th> 
              <th style="width:110px;">Issue Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 1; 
            foreach ($stock as $item): 
            ?>
            <tr data-qty="<?= $item['quantity'] ?>">
              <td style="font-weight:600; color:var(--navy-800);"><?= $i ?></td>
              <td style="font-weight:500; color:var(--navy-900);"><?= htmlspecialchars($item['item_name']) ?></td>
              <td class="table-mono" style="font-weight:700; color:<?= $item['quantity']>0 ? 'var(--success)' : 'var(--danger)' ?>;">
                <?= $item['quantity'] ?>
              </td>
              
              <td>
                <input type="text" 
                       name="issue[<?= $item['id'] ?>][location]" 
                       class="form-control" 
                       placeholder="e.g. JCT Workshop, CD Unit..." 
                       style="height:34px; font-size:12.5px;"
                       <?= $item['quantity'] == 0 ? 'disabled' : '' ?>>
              </td>

              <td>
                <input type="text" 
                       name="issue[<?= $item['id'] ?>][work_order]" 
                       class="form-control" 
                       placeholder="e.g. WO-2026-004" 
                       style="height:34px; font-size:12.5px; font-family:monospace;"
                       <?= $item['quantity'] == 0 ? 'disabled' : '' ?>>
              </td>
              
              <td>
                <input type="number"
                  name="issue[<?= $item['id'] ?>][qty]"
                  class="form-control issue-qty"
                  min="0"
                  max="<?= $item['quantity'] ?>"
                  value="0"
                  style="height:34px;font-size:13px;text-align:center;font-weight:700;"
                  <?= $item['quantity'] == 0 ? 'disabled' : '' ?>
                  oninput="highlightRow(this)"
                >
              </td>
            </tr>
            <?php 
              $i++; 
            endforeach; 
            ?>
          </tbody>
        </table>
      </div>

      <div style="padding:1rem 1.5rem;border-top:1px solid var(--steel-200);display:flex;align-items:center;justify-content:flex-end;gap:.75rem;background:var(--steel-50);">
        <span id="issueCount" style="font-size:13px;color:var(--steel-500);"></span>
        <button type="submit" class="btn btn-primary btn-lg">Issue Selected Items</button>
      </div>

    </div>
  </form>
</div>

<script>
function highlightRow(input) {
  var row = input.closest('tr');
  var qty = parseInt(input.value) || 0;
  row.style.background = qty > 0 ? 'var(--blue-50)' : ''; 
  updateCount();
}

function updateCount() {
  var inputs = document.querySelectorAll('.issue-qty');
  var count = 0;
  inputs.forEach(function(inp) { if ((parseInt(inp.value)||0) > 0) count++; });
  var el = document.getElementById('issueCount');
  el.textContent = count > 0 ? count + ' item type(s) selected' : '';
}

function filterTable(q) {
  var rows = document.querySelectorAll('#issueTable tbody tr');
  var lower = q.toLowerCase();
  rows.forEach(function(row) {
    var name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
    row.style.display = name.includes(lower) ? '' : 'none';
  });
}
</script>

<?php include 'partials/footer.php'; ?>