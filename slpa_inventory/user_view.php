<?php
session_start();
require_once 'config.php';

$page_title = 'User View — Inventory Check';

// ── 1. FETCH ALL ACTIVE DIVISIONS FOR THE DROPDOWN ─────────
$div_res = mysqli_query($conn, "SELECT * FROM `divisions` ORDER BY `id` ASC");
$divisions = [];
while ($row = mysqli_fetch_assoc($div_res)) {
    $divisions[] = $row;
}

$selected_division_id = isset($_GET['division_id']) ? intval($_GET['division_id']) : ($divisions[0]['id'] ?? 1);

$current_div_name = 'Technical Division';
foreach ($divisions as $d) {
    if ($d['id'] == $selected_division_id) {
        $current_div_name = $d['division_name'];
        break;
    }
}

// ── 2. FETCH ITEM CATALOG FOR SEARCH AUTOCOMPLETE ───────────
$catalog_res = mysqli_query($conn, "SELECT category_group, item_name FROM `item_catalog` WHERE `division_id` = $selected_division_id ORDER BY item_name ASC");
$catalog_items = [];
while ($row = mysqli_fetch_assoc($catalog_res)) {
    $catalog_items[$row['category_group']][] = $row['item_name'];
}

// ── 3. FETCH LIVE INVENTORY STOCK LIST FOR THIS DIVISION ────
$stock_res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `division_id` = $selected_division_id ORDER BY `item_name` ASC");
$stock_list = [];
while ($row = mysqli_fetch_assoc($stock_res)) {
    $stock_list[] = $row;
}

// ── 4. HANDLE SINGLE ITEM FAST CHECK ────────────────────────
$search_result = null;
if (isset($_GET['item']) && !empty($_GET['item'])) {
    $item_name = mysqli_real_escape_string($conn, trim($_GET['item']));
    $res = mysqli_query($conn, "SELECT * FROM `inventory` WHERE `item_name` = '$item_name' AND `division_id` = $selected_division_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $search_result = mysqli_fetch_assoc($res);
    } else {
        $search_result = ['item_name' => $_GET['item'], 'quantity' => 0, 'item_image' => 'default.png'];
    }
}

include 'partials/header.php';
?>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-eyebrow">Sri Lanka Ports Authority — Public Access Portal</div>
      <h1 class="page-title">Hardware Availability Check</h1>
      <p class="page-subtitle">Select a port division below to real-time track and verify hardware stock footprints.</p>
    </div>
    <a href="login.php" class="btn btn-outline">Back to Login</a>
  </div>

  <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--blue-400);">
    <div style="padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: .75rem;">
            <div style="width: 40px; height: 40px; background: var(--blue-100); color: var(--blue-400); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h3 style="margin:0; font-size:15px; color:var(--navy-800);">Currently Viewing Division</h3>
                <p style="margin:2px 0 0; font-size:13px; color:var(--steel-500); font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($current_div_name) ?></p>
            </div>
        </div>
        
        <div>
            <form method="GET" action="user_view.php" id="divForm">
                <select name="division_id" class="form-select" style="min-width: 280px; height: 40px; font-weight: 600;" onchange="document.getElementById('divForm').submit();">
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?= $div['id'] ?>" <?= $div['id'] == $selected_division_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($div['division_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:2.5rem;">
    <div style="padding:2rem;">
      <div style="max-width:700px; margin:0 auto; text-align:center;">
        <p style="font-size:13px; text-transform:uppercase; color:var(--steel-500); font-weight:600; margin-bottom:.5rem;">Fast Search By Letter</p>
        
        <form method="GET" action="user_view.php">
          <input type="hidden" name="division_id" value="<?= $selected_division_id ?>">
          <div style="display:flex; gap:.5rem;">
            <input
              type="text"
              id="searchBox"
              name="item"
              list="hardwareList"
              class="form-control"
              placeholder="Type starting letter to check this division's stock..."
              autocomplete="off"
              oninput="updateUserDatalist(this)"
              value="<?= htmlspecialchars($_GET['item'] ?? '') ?>"
              required
              style="height:50px;"
            >
            <datalist id="hardwareList"></datalist>
            <button type="submit" class="btn btn-primary">Check Stock</button>
            <?php if(isset($_GET['item'])): ?>
                <a href="user_view.php?division_id=<?= $selected_division_id ?>" class="btn btn-outline" style="height:50px; display:flex; align-items:center;">Clear</a>
            <?php endif; ?>
          </div>
        </form>

        <?php if ($search_result && isset($_GET['item'])): ?>
        <div style="margin-top:1.5rem; padding:1.25rem; background:var(--steel-50); border-radius:8px; border:1px solid var(--steel-200); text-align:left; display:grid; grid-template-columns: 50px 1fr auto; gap:1rem; align-items:center;">
            <img src="uploads/<?= htmlspecialchars($search_result['item_image'] ?? 'default.png') ?>" style="width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid var(--steel-200);" alt="Thumbnail">
            <div>
                <h4 style="margin:0; font-size:15px; color:var(--navy-800);"><?= htmlspecialchars($search_result['item_name']) ?></h4>
                <p style="margin:2px 0 0; font-size:12px; color:var(--steel-400);"><?= htmlspecialchars($current_div_name) ?> Registered Asset</p>
            </div>
            <div style="text-align:right;">
                <span class="badge" style="background:<?= $search_result['quantity'] > 0 ? 'var(--success-bg)' : 'var(--danger-bg)' ?>; color:<?= $search_result['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= $search_result['quantity'] > 0 ? 'Available' : 'Out of Stock' ?>
                </span>
                <strong style="display:block; margin-top:4px; font-size:16px; color:var(--navy-900);"><?= $search_result['quantity'] ?> Units</strong>
            </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Full Hardware Stock List (<?= htmlspecialchars($current_div_name) ?>)</span>
      <span style="font-size:12px; color:var(--steel-400);"><?= count($stock_list) ?> item types found</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Item Name</th>
            <th style="width:120px; text-align:center;">Available</th>
            <th style="width:120px; text-align:center;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($stock_list) > 0): ?>
              <?php foreach ($stock_list as $item): ?>
              <tr>
                <td style="font-weight:500; color:var(--navy-900);"><?= htmlspecialchars($item['item_name']) ?></td>
                <td class="table-mono" style="font-weight:700; text-align:center; color:var(--navy-800);"><?= $item['quantity'] ?></td>
                <td style="text-align:center;">
                  <?php if ($item['quantity'] > 10): ?>
                    <span class="badge badge-green">In Stock</span>
                  <?php elseif ($item['quantity'] > 0): ?>
                    <span class="badge badge-yellow">Low Stock</span>
                  <?php else: ?>
                    <span class="badge badge-red">Out of Stock</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr>
                <td colspan="3" style="text-align:center; color:var(--steel-400); font-style:italic; padding:2rem;">
                    No hardware stock data balances currently available inside this partition.
                </td>
              </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>

const currentCatalog = <?php echo json_encode($catalog_items); ?>;

function updateUserDatalist(input) {
    const query = input.value.toUpperCase();
    const dataList = document.getElementById('hardwareList');
    dataList.innerHTML = '';
    
    if (query.length > 0) {
        for (const letter in currentCatalog) {
            currentCatalog[letter].forEach(item => {
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