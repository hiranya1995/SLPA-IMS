<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Add Items';
$msg = '';
$msg_type = '';
$division_id = $_SESSION['division_id']; 

// ── FETCH DIVISION CATALOG DYNAMICALLY FOR THE DROPDOWN ──────

$catalog_res = mysqli_query($conn, "SELECT category_group, item_name FROM `item_catalog` WHERE `division_id` = $division_id ORDER BY category_group ASC, item_name ASC");

$categories = [];
while ($row = mysqli_fetch_assoc($catalog_res)) {
    $categories[$row['category_group']][] = $row['item_name'];
}

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name_raw = trim($_POST['item_name'] ?? '');
    $custom_name   = trim($_POST['custom_name'] ?? '');
    $quantity      = intval($_POST['quantity'] ?? 0);
    $description   = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    $item_name = ($item_name_raw === '__custom__') ? $custom_name : $item_name_raw;
    $item_name = mysqli_real_escape_string($conn, $item_name);

    if ($item_name === '') {
        $msg = 'Please select or enter an item name.';
        $msg_type = 'danger';
    } elseif ($quantity <= 0) {
        $msg = 'Quantity must be a positive integer greater than zero.';
        $msg_type = 'danger';
    } else {
      
        $check = mysqli_query($conn, "SELECT id, quantity, item_image, description FROM `inventory` WHERE `item_name` = '$item_name' AND `division_id` = $division_id");
        $is_existing = (mysqli_num_rows($check) > 0);
        
        $image_name = 'default.png';
        $upload_error = false;
        $has_new_image = (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['item_image']['name']));

        if ($has_new_image) {
            $file_tmp = $_FILES['item_image']['tmp_name'];
            $original_name = $_FILES['item_image']['name'];
            $file_size = $_FILES['item_image']['size'];
            
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed_types)) {
                if ($file_size <= 2 * 1024 * 1024) { 
                    $image_name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $upload_path = 'uploads/' . $image_name;
                    
                    if (!move_uploaded_file($file_tmp, $upload_path)) {
                        $msg = 'Failed to save uploaded file onto server filesystem.';
                        $msg_type = 'danger';
                        $upload_error = true;
                    }
                } else {
                    $msg = 'The uploaded file exceeds the maximum security size threshold of 2MB.';
                    $msg_type = 'danger';
                    $upload_error = true;
                }
            } else {
                $msg = 'Invalid file footprint detected. Execution blocked for security reasons.';
                $msg_type = 'danger';
                $upload_error = true;
            }
        }

        if (!$upload_error) {
            if ($is_existing) {
                $existing = mysqli_fetch_assoc($check);
                $new_qty  = $existing['quantity'] + $quantity;
                
                $final_image = ($has_new_image) ? $image_name : $existing['item_image'];
                $final_desc = (!empty($description)) ? $description : $existing['description'];
                
                mysqli_query($conn, "UPDATE `inventory` SET `quantity` = $new_qty, `item_image` = '$final_image', `description` = '$final_desc' WHERE `id` = {$existing['id']}");
            } else {
                
                mysqli_query($conn, "INSERT INTO `inventory` (`division_id`, `item_name`, `quantity`, `item_image`, `description`) VALUES ($division_id, '$item_name', $quantity, '$image_name', '$description')");
            }

            
            mysqli_query($conn, "INSERT INTO `logs` (`division_id`, `item_name`, `action_type`, `quantity`, `location`, `log_date`) 
            VALUES ($division_id, '$item_name', 'ADD', $quantity, NULL, NOW())");

            $msg = "✓ Successfully added <strong>$quantity unit(s)</strong> of <strong>" . htmlspecialchars($item_name) . "</strong> to inventory.";
            $msg_type = 'success';
        }
    }
}

include 'partials/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — <?= htmlspecialchars($_SESSION['division_name']) ?></div>
      <h1 class="page-title">Add Items to Stock</h1>
      <p class="page-subtitle">Receive new hardware and update inventory quantities.</p>
    </div>
    <a href="index.php" class="btn btn-outline">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Back to Dashboard
    </a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start;">

    <div class="card">
      <div class="card-header">
        <span class="card-title">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Item Receive Form
        </span>
        <span class="badge badge-blue"><?= htmlspecialchars($_SESSION['division_name']) ?></span>
      </div>
      <div class="card-body">
        
        <form method="POST" action="add.php" id="addForm" enctype="multipart/form-data">

          <div class="form-group">
            <label class="form-label">Hardware Item <span style="color:var(--danger)">*</span></label>
            <select name="item_name" class="form-select" id="itemSelect" onchange="toggleCustom(this.value)" required>
              <option value="">— Select Category &amp; Item —</option>
              <?php foreach ($categories as $cat => $items): ?>
              <optgroup label="── <?= htmlspecialchars($cat) ?> ──">
                <?php foreach ($items as $item): ?>
                <option value="<?= htmlspecialchars($item) ?>"
                  <?= (isset($_POST['item_name']) && $_POST['item_name'] === $item) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($item) ?>
                </option>
                <?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
              <optgroup label="── Other ──">
                <option value="__custom__" <?= (isset($_POST['item_name']) && $_POST['item_name']==='__custom__') ? 'selected' : '' ?>>Custom Item (type below)</option>
              </optgroup>
            </select>
            <p class="form-hint">Select an item pre-defined in this division's catalog.</p>
          </div>

          <div class="form-group" id="customNameGroup" style="display:none;">
            <label class="form-label">Custom Item Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="custom_name" class="form-control" placeholder="e.g. Cisco ASA 5506-X Firewall"
              value="<?= htmlspecialchars($_POST['custom_name'] ?? '') ?>">
            <p class="form-hint">Enter the full descriptive item name.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Quantity Received <span style="color:var(--danger)">*</span></label>
            <input type="number" name="quantity" class="form-control" min="1" max="9999"
              placeholder="Enter number of units" required
              value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
            <p class="form-hint">Number of units being added to stock.</p>
          </div>

          <div class="form-group">
            <label class="form-label">Hardware Reference Image</label>
            <input type="file" name="item_image" class="form-control" accept="image/*">
            <p class="form-hint">Upload a visual blueprint for inventory verification (Max 2MB. JPG, PNG, WEBP).</p>
          </div>

          <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
            <button type="submit" class="btn btn-success btn-lg" style="flex:1;justify-content:center;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add to Inventory
            </button>
            <a href="add.php" class="btn btn-outline btn-lg">Reset</a>
          </div>

        </form>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:1rem;">

      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Low / Zero Stock Alert
          </span>
        </div>
        <div class="card-body" style="padding:0;">
          <?php
            
            $low_res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE quantity <= 5 AND `division_id` = $division_id ORDER BY quantity ASC LIMIT 10");
            $low_items = [];
            while($r = mysqli_fetch_assoc($low_res)) $low_items[] = $r;
          ?>
          <?php if (count($low_items) > 0): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Item</th><th>Qty</th></tr>
              </thead>
              <tbody>
                <?php foreach ($low_items as $li): ?>
                <tr>
                  <td style="font-size:12px;"><?= htmlspecialchars($li['item_name']) ?></td>
                  <td>
                    <span class="badge <?= $li['quantity']==0 ? 'badge-red' : 'badge-yellow' ?>">
                      <?= $li['quantity'] ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="empty-state" style="padding:1.5rem;">
            <p style="color:var(--success);">✓ All items have adequate stock.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Instructions
          </span>
        </div>
        <div class="card-body" style="font-size:13px;color:var(--steel-600);line-height:1.7;">
          <ol style="padding-left:1.25rem;">
            <li>Select the hardware item from the dropdown.</li>
            <li>Use <em>Custom Item</em> for unlisted equipment.</li>
            <li>Enter the quantity received.</li>
            <li>Provide standard image snapshot if available.</li>
            <li>Click <strong>Add to Inventory</strong>.</li>
          </ol>
          <p style="margin-top:.75rem;color:var(--steel-400);font-size:12px;">All additions are logged dynamically for audit purposes.</p>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
function toggleCustom(val) {
  document.getElementById('customNameGroup').style.display = (val === '__custom__') ? 'block' : 'none';
  if (val === '__custom__') {
    document.querySelector('[name="custom_name"]').focus();
  }
}
window.addEventListener('DOMContentLoaded', function(){
  var sel = document.getElementById('itemSelect');
  if (sel) toggleCustom(sel.value);
});
</script>

<?php include 'partials/footer.php'; ?>