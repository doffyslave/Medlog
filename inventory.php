<?php
require 'database/connection.php';

// FETCH DATA WITH JOIN
$stmt = $conn->query("
    SELECT inventory.*, medicines.medicine_name 
    FROM inventory 
    LEFT JOIN medicines ON inventory.med_id = medicines.med_id
    ORDER BY inventory.inventory_id DESC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory</title>

<link rel="stylesheet" href="css/layout.css">
<link rel="stylesheet" href="css/inventory.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<div class="dashboard">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php include 'includes/header.php'; ?>

        <div class="content">

            <div class="page-header">
                <h1>Inventory</h1>
                <button class="add-btn" onclick="openModal()">+ Add Item</button>
            </div>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Expiration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach($items as $row): 
                    $status = ($row['quantity'] <= $row['min_stock']) ? 'Low Stock' : 'OK';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['medicine_name'] ?? 'N/A') ?></td>
                        <td><?= $row['category'] ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= $row['unit'] ?></td>
                        <td><?= $row['expiration_date'] ?: '-' ?></td>

                        <td>
                            <span class="<?= $status == 'Low Stock' ? 'badge-low' : 'badge-ok' ?>">
                                <?= $status ?>
                            </span>
                        </td>

                        <td>
                            <button class="edit-btn" onclick='editItem(<?= json_encode($row) ?>)'>Edit</button>

                            <form method="POST" action="Database/delete_inventory.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['inventory_id'] ?>">
                                <button class="delete-btn" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Item</h3>

        <form method="POST" action="Database/add_inventory.php">

            <!-- 🔥 DROPDOWN NA (IMPORTANT) -->
            <select name="med_id" required>
                <option value="">Select Medicine</option>

                <?php
                $meds = $conn->query("SELECT * FROM medicines");
                foreach ($meds as $med):
                ?>
                    <option value="<?= $med['med_id'] ?>">
                        <?= $med['medicine_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="category" required>
                <option value="Medicine">Medicine</option>
                <option value="Supplies">Supplies</option>
                <option value="Equipment">Equipment</option>
            </select>

            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="text" name="unit" placeholder="Unit (e.g. tablets)" required>
            <input type="date" name="expiration_date">
            <input type="number" name="min_stock" placeholder="Min Stock" required>

            <button type="submit" class="save-btn">Save</button>
            <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL (basic lang muna) -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Item</h3>

        <form method="POST" action="Database/edit_inventory.php">
            <input type="hidden" name="id" id="edit_id">

            <input type="number" name="quantity" id="edit_quantity" required>
            <input type="text" name="unit" id="edit_unit" required>
            <input type="date" name="expiration_date" id="edit_expiration">
            <input type="number" name="min_stock" id="edit_min">

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

    edit_id.value = data.inventory_id;
    edit_quantity.value = data.quantity;
    edit_unit.value = data.unit;
    edit_expiration.value = data.expiration_date;
    edit_min.value = data.min_stock;
}

function closeEdit() {
    document.getElementById("editModal").style.display = "none";
}
</script>

</body>
</html>