<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'View Stock';
$msg = '';
$msg_type = '';
$division_id = $_SESSION['division_id'];

// ── Handle Late Image Upload POST ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_late_image'])) {
    $item_id = intval($_POST['late_item_id']);
    
    if (isset($_FILES['late_image']) && $_FILES['late_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['late_image']['name'])) {
        $file_tmp = $_FILES['late_image']['tmp_name'];
        $original_name = $_FILES['late_image']['name'];
        $file_size = $_FILES['late_image']['size'];
        
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed_types)) {
            if ($file_size <= 2 * 1024 * 1024) { 
                $image_name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/' . $image_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    
                    $update_img = mysqli_query($conn, "UPDATE `inventory` SET `item_image` = '$image_name' WHERE `id` = $item_id AND `division_id` = $division_id");
                    if ($update_img) {
                        $msg = '✓ Hardware reference image updated successfully!';
                        $msg_type = 'success';
                    }
                } else {
                    $msg = 'Failed to save uploaded file onto server filesystem.';
                    $msg_type = 'danger';
                }
            } else {
                $msg = 'The uploaded file exceeds the 2MB size limit.';
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Invalid file footprint detected. Only JPG, PNG, WEBP allowed.';
            $msg_type = 'danger';
        }
    } else {
        $msg = 'Please select a valid image file before uploading.';
        $msg_type = 'danger';
    }
}

// ── Handle Return POST ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_item_id'])) {
    $item_id    = intval($_POST['return_item_id']);
    $return_qty = intval($_POST['return_qty'] ?? 0);

    if ($return_qty <= 0) {
        $msg = 'Return quantity must be at least 1.';
        $msg_type = 'danger';
    } else {
       
        $res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `id` = $item_id AND `division_id` = $division_id");
        if ($res && mysqli_num_rows($res) > 0) {
            $item = mysqli_fetch_assoc($res);
            $item_name_safe = mysqli_real_escape_string($conn, $item['item_name']);
            $new_qty = $item['quantity'] + $return_qty;
            
            // Increment inventory
            mysqli_query($conn, "UPDATE `inventory` SET `quantity` = $new_qty WHERE `id` = $item_id AND `division_id` = $division_id");
            
            // Log entry with division_id (Foreign Key Enabled)
            mysqli_query($conn,
                "INSERT INTO `logs` (`division_id`, `item_name`, `action_type`, `quantity`, `location`, `log_date`)
                 VALUES ($division_id, '$item_name_safe', 'RETURN', $return_qty, NULL, NOW())"
            );
            $msg = "✓ <strong>$return_qty unit(s)</strong> of <strong>" . htmlspecialchars($item['item_name']) . "</strong> returned to stock.";
            $msg_type = 'success';
        }
    }
}

// ── Fetch all stock for this division ────────────────────────
$stock_res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `division_id` = $division_id ORDER BY `item_name` ASC");
$stock = [];
while ($r = mysqli_fetch_assoc($stock_res)) $stock[] = $r;

// Division Totals Summary
$total_units = array_sum(array_column($stock, 'quantity'));
$in_stock_count  = count(array_filter($stock, fn($i) => $i['quantity'] > 0));
$zero_stock_count = count(array_filter($stock, fn($i) => $i['quantity'] == 0));

// Count only Today's Issue Transactions for this Division
$today_res = mysqli_query($conn, "SELECT COUNT(*) as today_total FROM `logs` WHERE `action_type` = 'ISSUE' AND `division_id` = $division_id AND DATE(`log_date`) = CURDATE()");
$today_data = mysqli_fetch_assoc($today_res);
$today_issue_count = $today_data['today_total'];

include 'partials/header.php';
?>

<style>
  .table-item-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--steel-200, #cbd5e1);
    background: #f1f5f9;
    display: block;
  }
  .item-meta-container strong {
    display: block;
    color: var(--navy-800);
    font-size: 14.5px;
  }
  .thumb-container {
    position: relative;
    display: inline-block;
  }
  .upload-late-btn {
    display: block;
    font-size: 10px;
    color: #2563eb;
    text-decoration: none;
    margin-top: 3px;
    font-weight: 600;
    text-align: center;
  }
  .upload-late-btn:hover {
    text-decoration: underline;
  }
  .late-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.4);
    align-items: center; justify-content: center;
  }
  .late-modal-content {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    width: 100%; max-width: 400px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
</style>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — <?= htmlspecialchars($_SESSION['division_name']) ?></div>
      <h1 class="page-title">Stock Reports</h1>
      <p class="page-subtitle">View current live inventory levels and manage return operations.</p>
    </div>
    <div style="display:flex;gap:.5rem;">
      <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="add.php"   class="btn btn-success btn-sm">+ Add Items</a>
        <a href="issue.php" class="btn btn-outline btn-sm">Issue Items</a>
      <?php endif; ?>
      <a href="history.php" class="btn btn-outline btn-sm">📋 View History Logs</a>
      <a href="index.php" class="btn btn-outline btn-sm">← Dashboard</a>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
  <?php endif; ?>

  <div class="stat-row">
    <div class="stat-pill">
      <div class="stat-pill-icon blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
      </div>
      <div class="stat-pill-info">
        <div class="stat-pill-value"><?= count($stock) ?></div>
        <div class="stat-pill-label">Item Types</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-icon green">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <div class="stat-pill-info">
        <div class="stat-pill-value"><?= number_format($total_units) ?></div>
        <div class="stat-pill-label">Total Units in Stock</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-icon green">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/></svg>
      </div>
      <div class="stat-pill-info">
        <div class="stat-pill-value"><?= $in_stock_count ?></div>
        <div class="stat-pill-label">Types In Stock</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-icon red">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <div class="stat-pill-info">
        <div class="stat-pill-value"><?= $zero_stock_count ?></div>
        <div class="stat-pill-label">Out of Stock</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-icon yellow">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
      </div>
      <div class="stat-pill-info">
        <div class="stat-pill-value"><?= $today_issue_count ?></div>
        <div class="stat-pill-label">Issues Today</div>
      </div>
    </div>
  </div>

  <div class="section-divider">
    <h3>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
      Current Stock Levels Balance Sheet
    </h3>
  </div>

  <div class="card" style="margin-bottom:2.5rem;">
    <div class="card-header">
      <span class="card-title">
        <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        Live Inventory — All Items
      </span>
      <div style="display:flex;align-items:center;gap:.75rem;">
        <a href="export.php" class="btn btn-success btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export to Excel
        </a>
        <input type="text" id="stockFilter" class="form-control" placeholder="Filter…" style="width:160px;height:32px;font-size:12.5px;" oninput="filterStock(this.value)">
        <span style="font-size:12px;color:var(--steel-400);">Last updated: <?= date('d M Y H:i') ?></span>
      </div>
    </div>
    <div class="table-wrap">
      <table id="stockTable">
        <thead>
          <tr>
            <th style="width: 6%;">#</th>
            <th style="width: 12%;">Image</th>
            <th style="width: 46%;">Item Name</th>
            <th style="width: 12%;">Quantity</th>
            <th style="width: 12%;">Stock Status</th>
            <th style="width: 12%;">Return Units</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1; 
          foreach ($stock as $item): 
            $img_file = htmlspecialchars($item['item_image'] ?? 'default.png');
          ?>
          <tr>
            <td style="font-weight: 600; color: var(--navy-800);"><?= $i ?></td>
            
            <td>
              <div class="thumb-container">
                <img src="uploads/<?= $img_file ?>" class="table-item-thumb" alt="Hardware Image">
                <?php if ($img_file === 'default.png' || $img_file === ''): ?>
                  <a href="javascript:void(0)" class="upload-late-btn" onclick="openLateUpload(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>')">Add Photo</a>
                <?php endif; ?>
              </div>
            </td>
            <td class="item-meta-container">
              <strong><?= htmlspecialchars($item['item_name']) ?></strong>
            </td>

            <td>
              <span class="table-mono" style="font-size:16px;font-weight:700;color:<?=
                $item['quantity']>10 ? 'var(--success)' :
                ($item['quantity']>0 ? 'var(--warning)' : 'var(--danger)')
              ?>;"><?= $item['quantity'] ?></span>
            </td>
            <td>
              <?php if ($item['quantity'] > 10): ?>
                <span class="badge badge-green">In Stock</span>
              <?php elseif ($item['quantity'] > 0): ?>
                <span class="badge badge-yellow">Low Stock</span>
              <?php else: ?>
                <span class="badge badge-red">Out of Stock</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($_SESSION['role'] === 'admin'): ?>
              <form method="POST" action="view.php" style="display:flex;gap:.35rem;align-items:center;">
                <input type="hidden" name="return_item_id" value="<?= $item['id'] ?>">
                <input type="number" name="return_qty" class="form-control" min="1" placeholder="Qty"
                  style="width:70px;height:30px;font-size:12px;text-align:center;padding:.2rem .4rem;">
                <button type="submit" class="btn btn-outline btn-sm" title="Return to stock">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                  Return
                </button>
              </form>
              <?php else: ?>
                <span style="color:var(--steel-300); font-size:12px; font-style:italic;">Admin Only</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php 
            $i++; 
          endforeach; 
          ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<div id="lateUploadModal" class="late-modal">
  <div class="late-modal-content">
    <h3 style="margin-top:0;color:var(--navy-800);font-size:16px;" id="modalItemName">Upload Item Photo</h3>
    <p style="font-size:12px;color:#64748b;margin-bottom:1rem;">Upload a hardware verification image for this division asset.</p>
    
    <form method="POST" action="view.php" enctype="multipart/form-data">
      <input type="hidden" name="upload_late_image" value="1">
      <input type="hidden" name="late_item_id" id="modalItemId" value="">
      
      <div style="margin-bottom: 1.25rem;">
        <input type="file" name="late_image" class="form-control" accept="image/*" required style="font-size:13px;">
      </div>
      
      <div style="display:flex;justify-content:flex-end;gap:.5rem;">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeLateUpload()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Image</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterStock(q) {
  var rows = document.querySelectorAll('#stockTable tbody tr');
  var lower = q.toLowerCase();
  rows.forEach(function(row) {
    var nameCell = row.querySelector('td:nth-child(3)');
    if (nameCell) {
      var name = nameCell.textContent.toLowerCase();
      row.style.display = name.includes(lower) ? '' : 'none';
    }
  });
}

function openLateUpload(id, name) {
  document.getElementById('modalItemId').value = id;
  document.getElementById('modalItemName').innerText = "Add Photo: " + name;
  document.getElementById('lateUploadModal').style.display = 'flex';
}

// Close Modal Controls
function closeLateUpload() {
  document.getElementById('lateUploadModal').style.display = 'none';
}

window.onclick = function(event) {
  var modal = document.getElementById('lateUploadModal');
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>

<?php include 'partials/footer.php'; ?>