<?php
session_start();
require_once 'config.php';

// ── AUTHENTICATION CHECK ──────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$division_id   = $_SESSION['division_id'];
$division_name = $_SESSION['division_name'];

$safe_div_name = str_replace([' ', '&', '/'], '_', $division_name);

// Set headers to force download as Excel .xls
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=SLPA_" . $safe_div_name . "_Stock_Report_" . date('Y-m-d') . ".xls");


$result = mysqli_query($conn, "SELECT id, item_name, quantity FROM inventory WHERE `division_id` = $division_id ORDER BY item_name ASC");

echo "<table border='1'>";
echo "<tr>
        <th style='background-color:#0a1628; color:#ffffff;'>ID</th>
        <th style='background-color:#0a1628; color:#ffffff;'>Item Name</th>
        <th style='background-color:#0a1628; color:#ffffff;'>Current Quantity</th>
      </tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['item_name']) . "</td>
            <td>{$row['quantity']}</td>
          </tr>";
}
echo "</table>";
?>