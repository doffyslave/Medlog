<?php
require 'database/connection.php';
session_start();

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';

// FETCH MEDICINES
$stmt = $conn->query("SELECT * FROM medicines ORDER BY medicine_name ASC");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medicines</title>

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
    <h1>Medicines</h1>

    <?php if ($role === 'admin'): ?>
        <button class="add-btn" onclick="openModal()">+ Add Medicine</button>
    <?php endif; ?>
</div>

<table class="inventory-table">
    <thead>
        <tr>
            <th>Medicine</th>

            <?php if ($role === 'admin'): ?>
                <th>Quantity</th>
            <?php endif; ?>

            <th>Status</th>

            <?php if ($role === 'admin'): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>

    <tbody>
    <?php foreach($medicines as $med): 

        $qty = $med['total_quantity'];

        if ($qty <= 0) {
            $status = 'Out of Stock';
            $class = 'badge-low';
        } elseif ($qty <= 10) {
            $status = 'Low Stock';
            $class = 'badge-low';
        } else {
            $status = 'Available';
            $class = 'badge-ok';
        }
    ?>
        <tr>
            <td><?= htmlspecialchars($med['medicine_name']) ?></td>

            <?php if ($role === 'admin'): ?>
                <td><?= $qty ?></td>
            <?php endif; ?>

            <td>
                <span class="<?= $class ?>">
                    <?= $status ?>
                </span>
            </td>

            <?php if ($role === 'admin'): ?>
            <td>
                <button class="edit-btn" onclick='editMed(<?= json_encode($med) ?>)'>Edit</button>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>
</div>
</div>

<!-- ADD MEDICINE MODAL -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>Add Medicine</h3>

        <form method="POST" action="Database/add_medicine.php">
            <input type="text" name="medicine_name" placeholder="Medicine Name" required>

            <button type="submit" class="save-btn">Save</button>
            <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit Medicine</h3>

        <form method="POST" action="Database/edit_medicine.php">
            <input type="hidden" name="id" id="edit_id">

            <input type="text" name="medicine_name" id="edit_name" required>

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

function editMed(data) {
    document.getElementById("editModal").style.display = "flex";

    document.getElementById("edit_id").value = data.med_id;
    document.getElementById("edit_name").value = data.medicine_name;
}

function closeEdit() {
    document.getElementById("editModal").style.display = "none";
}
</script>

</body>
</html>