<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title  = 'Dashboard';
$user_role   = $_SESSION['role']; 
$division_id = $_SESSION['division_id']; // 

// ── FETCH DIVISION CATALOG DYNAMICALLY FROM DATABASE ─────────
$catalog_res = mysqli_query($conn, "SELECT category_group, item_name FROM `item_catalog` WHERE `division_id` = $division_id ORDER BY category_group ASC, item_name ASC");

$inventory_items = [];
while ($row = mysqli_fetch_assoc($catalog_res)) {
    $inventory_items[$row['category_group']][] = $row['item_name'];
}

// ── Handle Search Request ────────────────────────────────────
$search_result = null;
if (isset($_GET['item']) && !empty($_GET['item'])) {
    $item_name = mysqli_real_escape_string($conn, $_GET['item']);
    $res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `item_name` = '$item_name' AND `division_id` = $division_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $search_result = mysqli_fetch_assoc($res);
    } else {
        $search_result = ['item_name' => $_GET['item'], 'quantity' => 0, 'id' => 0];
    }
}

// ── Summary Stats Narrowed by Division ───────────────────────
$total_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM `inventory` WHERE `division_id` = $division_id"))['c'];
$in_stock    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM `inventory` WHERE quantity > 0 AND `division_id` = $division_id"))['c'];
$zero_stock  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM `inventory` WHERE quantity = 0 AND `division_id` = $division_id"))['c'];

$weekly_issued_query = "SELECT COUNT(*) AS c FROM `logs` 
                        WHERE action_type='ISSUE' AND `division_id` = $division_id
                        AND log_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$total_issued = mysqli_fetch_assoc(mysqli_query($conn, $weekly_issued_query))['c'];

// Recent Activity for this division only
$recent_res = mysqli_query($conn, "SELECT * FROM `logs` WHERE `division_id` = $division_id ORDER BY `log_date` DESC LIMIT 8");

include 'partials/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — <?= htmlspecialchars($_SESSION['division_name']) ?></div>
      <h1 class="page-title">Inventory Management System</h1>
      <p class="page-subtitle">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= ucfirst($user_role) ?> Mode)</p>
    </div>
    <a href="logout.php" class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);">Logout</a>
  </div>

  <div class="stat-row">
    <div class="stat-pill">
        <div class="stat-pill-icon blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
        </div>
        <div class="stat-pill-info">
            <div class="stat-pill-value"><?= $total_items ?></div>
            <div class="stat-pill-label">Item Types</div>
        </div>
    </div>

    <div class="stat-pill">
        <div class="stat-pill-icon green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        </div>
        <div class="stat-pill-info">
            <div class="stat-pill-value"><?= $in_stock ?></div>
            <div class="stat-pill-label">In Stock</div>
        </div>
    </div>

    <div class="stat-pill">
        <div class="stat-pill-icon red">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="stat-pill-info">
            <div class="stat-pill-value"><?= $zero_stock ?></div>
            <div class="stat-pill-label">Out of Stock</div>
        </div>
    </div>

    <div class="stat-pill">
        <div class="stat-pill-icon yellow">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        </div>
        <div class="stat-pill-info">
            <div class="stat-pill-value"><?= $total_issued ?></div>
            <div class="stat-pill-label">Issues This Week</div>
        </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:2.5rem;">
    <div style="padding:2.5rem 2rem;">
      <div style="max-width:700px;margin:0 auto;text-align:center;">
        <p style="font-size:13px;text-transform:uppercase;letter-spacing:.08em;color:var(--steel-50);font-weight:600;margin-bottom:.75rem;">Fast Search By Letter</p>
        
        <form method="GET" action="index.php">
          <div style="display:flex;gap:.5rem;">
            <div style="position:relative;flex:1;">
              <svg style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--steel-400);pointer-events:none;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input
                type="text"
                id="searchBox"
                name="item"
                list="hardwareList"
                class="form-control"
                placeholder="Type a letter to filter this division's catalog..."
                style="padding-left:2.5rem;font-size:15px;height:52px;border-radius:var(--radius-lg);"
                autocomplete="off"
                oninput="updateDatalist(this)"
                value="<?= htmlspecialchars($_GET['item'] ?? '') ?>"
                required
              >
              <datalist id="hardwareList"></datalist>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="height:52px;padding:0 2rem;">Check Stock</button>
            <?php if (isset($_GET['item']) && !empty($_GET['item'])): ?>
                <a href="index.php" class="btn btn-outline btn-lg" style="height:52px; display:flex; align-items:center;">Clear</a>
            <?php endif; ?>
          </div>
          <p style="font-size:11px;color:var(--steel-400);margin-top:.65rem;">List only appears after you type the starting letter.</p>
        </form>

        <?php if ($search_result && isset($_GET['item'])) { ?>
        <div style="margin-top:2rem; padding:1.5rem; background:var(--steel-50); border-radius:var(--radius-lg); border:1px solid var(--steel-200); text-align:left;">
            <div style="display:grid; grid-template-columns: 65px 1fr auto; gap:1.25rem; align-items:center;">
                <div>
                    <img src="uploads/<?= htmlspecialchars($search_result['item_image'] ?? 'default.png') ?>" 
                         style="width: 65px; height: 65px; object-fit: cover; border-radius: var(--radius-md, 8px); border: 1px solid var(--steel-200); background: #fff;" 
                         alt="Hardware Thumbnail">
                </div>
                <div>
                    <h4 style="margin:0; color:var(--navy-800); font-size:18px;"><?= htmlspecialchars($search_result['item_name']) ?></h4>
                    <p style="margin:4px 0 0; color:var(--steel-500); font-size:13px;"><?= htmlspecialchars($_SESSION['division_name']) ?> Asset</p>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:24px; font-weight:700; color:<?= $search_result['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= $search_result['quantity'] ?>
                    </div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--steel-400);">Units in Hand</div>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--steel-200); margin:1.25rem 0;">

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span class="badge" style="background:<?= $search_result['quantity'] > 10 ? 'var(--success-bg)' : ($search_result['quantity'] > 0 ? 'var(--warning-bg)' : 'var(--danger-bg)') ?>; color:<?= $search_result['quantity'] > 10 ? 'var(--success)' : ($search_result['quantity'] > 0 ? 'var(--warning)' : 'var(--danger)') ?>;">
                    <?= $search_result['quantity'] > 10 ? 'In Stock' : ($search_result['quantity'] > 0 ? 'Low Stock' : 'Out of Stock') ?>
                </span>
                
                <?php if ($search_result['id'] > 0): ?>
                    <a href="issue.php?id=<?= $search_result['id'] ?>" class="btn btn-navy btn-sm" style="text-decoration:none;">Issue This Item</a>
                <?php else: ?>
                    <span style="font-size:12px; color:var(--steel-400); font-style:italic;">Cannot issue unregistered catalog entries</span>
                <?php endif; ?>
            </div>
        </div>
        <?php } ?>

      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:2.5rem;">
    <?php if ($user_role === 'admin'): ?>
    <a href="add.php" style="text-decoration:none;">
      <div class="card" style="border-top:4px solid var(--success);cursor:pointer;transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div style="padding:2rem 1.75rem;text-align:center;">
          <div style="width:56px;height:56px;border-radius:var(--radius-lg);background:var(--success-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--success);">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/><line x1="12" y1="8" x2="12" y2="14"/><line x1="9" y1="11" x2="15" y2="11"/></svg>
          </div>
          <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin-bottom:.35rem;">Add Items</h3>
          <p style="font-size:12.5px;color:var(--steel-500);">Receive hardware stock and update inventory.</p>
        </div>
      </div>
    </a>

    <a href="issue.php" style="text-decoration:none;">
      <div class="card" style="border-top:4px solid var(--warning);cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div style="padding:2rem 1.75rem;text-align:center;">
          <div style="width:56px;height:56px;border-radius:var(--radius-lg);background:var(--warning-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--warning);">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin-bottom:.35rem;">Issue Items</h3>
          <p style="font-size:12.5px;color:var(--steel-500);">Dispatch hardware to port divisions.</p>
        </div>
      </div>
    </a>
    <?php endif; ?>

    <a href="view.php" style="text-decoration:none; grid-column: <?= ($user_role === 'admin' ? 'auto' : '1 / span 3') ?>;">
      <div class="card" style="border-top:4px solid var(--blue-400);cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div style="padding:2rem 1.75rem;text-align:center;">
          <div style="width:56px;height:56px;border-radius:var(--radius-lg);background:var(--blue-100);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--blue-400);">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </div>
          <h3 style="font-size:17px;font-weight:700;color:var(--navy-800);margin-bottom:.35rem;">View Stock</h3>
          <p style="font-size:12.5px;color:var(--steel-500);">Full stock levels and issue history.</p>
        </div>
      </div>
    </a>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">
        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Recent Activity
      </span>
      <a href="view.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <?php
        $recent_rows = [];
        while($r = mysqli_fetch_assoc($recent_res)) $recent_rows[] = $r;
      ?>
      <?php if (count($recent_rows) > 0): ?>
      <table>
        <thead>
          <tr>
            <th style="width: 18%;">Date &amp; Time</th>
            <th style="width: 32%;">Item</th>
            <th style="width: 12%;">Action</th>
            <th style="width: 8%;">Qty</th>
            <th style="width: 15%;">Location / Division</th>
            <th style="width: 15%;">Work Order No</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_rows as $log): ?>
          <tr>
            <td class="table-mono" style="color:var(--steel-500); white-space:nowrap;">
                <?= date('d M Y H:i', strtotime($log['log_date'])) ?>
            </td>
            <td style="font-weight:500; color:var(--navy-900);">
                <?= htmlspecialchars($log['item_name']) ?>
            </td>
            <td>
              <?php if ($log['action_type'] == 'ADD'): ?>
                <span class="badge badge-green">↑ Added</span>
              <?php elseif ($log['action_type'] == 'ISSUE'): ?>
                <span class="badge badge-yellow">↓ Issued</span>
              <?php else: ?>
                <span class="badge badge-blue">↺ Returned</span>
              <?php endif; ?>
            </td>
            <td class="table-mono" style="font-weight:700; color:var(--navy-800);">
                <?= $log['quantity'] ?>
            </td>
            <td style="color:var(--steel-600);">
                <?= $log['location'] ? htmlspecialchars($log['location']) : '<span style="color:var(--steel-300);">—</span>' ?>
            </td>
            <td class="table-mono" style="font-weight:600; font-size:12.5px; color:var(--navy-700);">
                <?= $log['work_order_no'] ? htmlspecialchars($log['work_order_no']) : '<span style="color:var(--steel-300);">—</span>' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <p>No activity logged yet.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
const fullCatalog = <?php echo json_encode($inventory_items); ?>;
function updateDatalist(input) {
    const query = input.value.toUpperCase();
    const dataList = document.getElementById('hardwareList');
    dataList.innerHTML = '';
    if (query.length > 0) {
        for (const letter in fullCatalog) {
            fullCatalog[letter].forEach(item => {
                if (item.toUpperCase().startsWith(query)) {
                    const option = document.createElement('option');
                    option.value = item;
                    dataList.appendChild(option);
                }
            });
        }
    }
}
</script>

<?php include 'partials/footer.php'; ?>