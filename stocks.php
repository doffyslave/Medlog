<?php
require 'database/connection.php';

// FETCH DATA WITH JOIN
$stmt = $conn->query("
    SELECT stocks.*, medicines.medicine_name 
    FROM stocks 
    LEFT JOIN medicines ON stocks.med_id = medicines.med_id
    ORDER BY stocks.stock_id DESC
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stocks Inventory</title>

<link rel="stylesheet" href="css/layout.css">
<link rel="stylesheet" href="css/stocks.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="dashboard">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content">

            <div class="page-header">
                <h1>Stocks Inventory</h1>
                <button class="add-btn" onclick="openModal()">+ Add / Adjust Stock</button>
            </div>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Quantity</th>
                        <th>Expiration</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach($items as $row): 
                    $type = ($row['quantity'] < 0) ? 'Adjustment' : 'Stock In';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['medicine_name'] ?? 'N/A') ?></td>

                        <td style="color: <?= $row['quantity'] < 0 ? 'red' : 'green' ?>">
                            <?= $row['quantity'] > 0 ? '+' : '' ?><?= $row['quantity'] ?>
                        </td>

                        <td><?= $row['expiration_date'] ?: '-' ?></td>

                        <td>
                            <span class="<?= $row['quantity'] < 0 ? 'badge-low' : 'badge-ok' ?>">
                                <?= $type ?>
                            </span>
                        </td>

                        <td>
                            <button class="edit-btn" onclick='editItem(<?= json_encode($row) ?>)'>Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<!-- ADD / ADJUST MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add / Adjust Stock</h3>

        <form method="POST" action="Database/add_stocks.php">

            <select name="med_id" required>
                <option value="">Select Medicine</option>
                <?php
                $meds = $conn->query("SELECT * FROM medicines");
                foreach ($meds as $med):
                ?>
                    <option value="<?= $med['med_id'] ?>">
                        <?= htmlspecialchars($med['medicine_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="quantity" placeholder="(+ add, - adjust)" required>
            <small style="color:gray;">Use negative values to correct mistakes</small>

            <input type="date" name="expiration_date">

            <button type="submit" class="save-btn">Save</button>
            <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Stock Record</h3>
        <p id="edit_name" style="font-size: 14px; color: gray;"></p>

        <form method="POST" action="Database/edit_stocks.php">
            <input type="hidden" name="id" id="edit_id">

            <input type="date" name="expiration_date" id="edit_expiration">

            <button type="submit" class="save-btn">Update</button>
            <button type="button" onclick="closeEdit()" class="cancel-btn">Cancel</button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById("addModal").style.display = "flex";
}
function closeModal() {
    document.getElementById("addModal").style.display = "none";
}

function editItem(data) {
    document.getElementById("editModal").style.display = "flex";

    document.getElementById("edit_id").value = data.stock_id;
    document.getElementById("edit_expiration").value = data.expiration_date;

    document.getElementById("edit_name").innerText = 
        "Medicine: " + (data.medicine_name ?? "Unknown");
}

function closeEdit() {
    document.getElementById("editModal").style.display = "none";
}
</script>

</body>
</html>