<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'Database/connection.php';

$search = $_GET['search'] ?? '';
$showInactive = $_GET['show_inactive'] ?? 0;

$query = "SELECT * FROM users 
          WHERE role != 'admin'
          AND (name LIKE :search OR email LIKE :search)";

if (!$showInactive) {
    $query .= " AND status = 'active'";
}

$query .= " ORDER BY status ASC, name ASC";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':search' => "%$search%"
]);
    
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Patients</title>
<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/patients.css">
</head>

<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content">

<div class="page-header">
    <h1>Patients</h1>
    <p>Manage clinic patients</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="color:green; margin-bottom:10px;">
        Patient updated successfully.
    </div>
<?php endif; ?>

<div class="top-bar">
    <form method="GET">
        <input type="text" name="search" placeholder="Search patient..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <div>
        <a href="?show_inactive=1" class="filter-btn">Show Inactive</a>
        <a href="patients.php" class="filter-btn">Active Only</a>
        <button id="openModal">+ Add Patient</button>
    </div>
</div>

<table>
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach($patients as $row): ?>    
<tr 
    class="<?= $row['status'] == 'inactive' ? 'inactive-row' : '' ?>">

<td onclick="openProfile('<?= $row['user_id'] ?>')" style="cursor:pointer;">
    <?= htmlspecialchars($row['name']) ?>
</td>

<td onclick="openProfile('<?= $row['user_id'] ?>')" style="cursor:pointer;">
    <?= htmlspecialchars($row['email']) ?>
</td>

<td><?= ucfirst($row['role']) ?></td>
<td><?= ucfirst($row['status']) ?></td>

<td onclick="event.stopPropagation();">
<button class="editBtn"
    data-id="<?= $row['user_id'] ?>"
    data-name="<?= htmlspecialchars($row['name']) ?>"
    data-email="<?= htmlspecialchars($row['email']) ?>"
    data-role="<?= $row['role'] ?>"
    data-course="<?= htmlspecialchars($row['course']) ?>"
    data-year="<?= htmlspecialchars($row['year_level']) ?>"
    data-status="<?= $row['status'] ?>">
Edit
</button>
</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>

</section>
</main>
</div>

<!-- PROFILE PANEL -->
<div id="profileContainer" class="profile-hidden">
    <div class="profile-box">
        <button onclick="closeProfile()" class="close-profile">✕</button>
        <div id="profileContent"></div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal">
<div class="modal-content">
<span class="close">&times;</span>
<h2>Add Patient</h2>

<form action="Database/add_patient.php" method="POST">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>

<select name="role" required>
<option value="">Select Role</option>
<option value="student">Student</option>
<option value="teacher">Teacher</option>
<option value="visitor">Visitor</option>
</select>

<input type="text" name="course" placeholder="Course">
<input type="text" name="year_level" placeholder="Year Level">

<button type="submit">Add Patient</button>
</form>
</div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
<div class="modal-content">
<span class="closeEdit">&times;</span>
<h2>Edit Patient</h2>

<form action="Database/edit_patient.php" method="POST">
<input type="text" name="user_id" id="edit_id" readonly>
<input type="text" name="name" id="edit_name" required>
<input type="email" name="email" id="edit_email" required>

<select name="role" id="edit_role" required>
<option value="student">Student</option>
<option value="teacher">Teacher</option>
<option value="visitor">Visitor</option>
</select>

<input type="text" name="course" id="edit_course">
<input type="text" name="year_level" id="edit_year">

<select name="status" id="edit_status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>

<button type="submit">Update Patient</button>
</form>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ===== ADD MODAL =====
    const modal = document.getElementById("addModal");
    const openBtn = document.getElementById("openModal");
    const closeBtn = document.querySelector("#addModal .close");

    if (openBtn && modal) {
        openBtn.addEventListener("click", function () {
            console.log("Add clicked");
            modal.classList.add("show");
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener("click", function () {
            modal.classList.remove("show");
        });
    }

    // ===== EDIT MODAL =====
    const editBtns = document.querySelectorAll(".editBtn");
    const editModal = document.getElementById("editModal");
    const closeEdit = document.querySelector(".closeEdit");

    editBtns.forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();

            console.log("Edit clicked");

            const id = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const role = this.dataset.role;
            const course = this.dataset.course;
            const year = this.dataset.year;
            const status = this.dataset.status;

            document.getElementById("edit_id").value = id;
            document.getElementById("edit_name").value = name;
            document.getElementById("edit_email").value = email;
            document.getElementById("edit_role").value = role;
            document.getElementById("edit_course").value = course;
            document.getElementById("edit_year").value = year;
            document.getElementById("edit_status").value = status;

            if (editModal) {
                editModal.classList.add("show");
            }
        });
    });

    if (closeEdit && editModal) {
        closeEdit.addEventListener("click", function () {
            editModal.classList.remove("show");
        });
    }

});

// ===== PROFILE =====
function openProfile(user_id) {
    fetch("Database/get_patient.php?id=" + user_id)
    .then(res => res.text())
    .then(data => {
        document.getElementById("profileContent").innerHTML = data;

        document.getElementById("profileContent").innerHTML += `
            <div style="margin-top:15px;">
                <a href="visits.php?user_id=${user_id}" class="filter-btn">
                    View Full Visits →
                </a>
            </div>
        `;

        const panel = document.getElementById("profileContainer");
        panel.classList.remove("profile-hidden");
        panel.classList.add("active");
    });
}

function closeProfile() {
    const panel = document.getElementById("profileContainer");
    panel.classList.remove("active");
    panel.classList.add("profile-hidden");
}
</script>

</body>
</html>