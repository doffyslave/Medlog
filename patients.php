<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'Database/connection.php';

$search = $_GET['search'] ?? '';
$showInactive = $_GET['show_inactive'] ?? 0;

$cleanSearch = str_replace('-', '', $search);

$query = "SELECT * FROM users 
          WHERE role != 'admin'
          AND (
              name LIKE :search 
              OR email LIKE :search 
              OR REPLACE(student_id, '-', '') LIKE :cleanSearch
          )";

if (!$showInactive) {
    $query .= " AND status = 'active'";
}

$query .= " ORDER BY status ASC, name ASC";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':search' => "%$search%",
    ':cleanSearch' => "%$cleanSearch%"
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

<div class="top-bar">

<form method="GET">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
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
<tr>

<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= ucfirst($row['role']) ?></td>
<td><?= ucfirst($row['status']) ?></td>

<td>
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

<!-- ADD MODAL -->
<div id="addModal" class="modal">
<div class="modal-content">
<span class="close">&times;</span>
<h2>Add Patient</h2>

<form method="POST" action="Database/add_patient.php">
<input type="text" name="name" placeholder="Name" required>
<input type="email" name="email" placeholder="Email" required>

<select name="role" required>
<option value="student">Student</option>
<option value="teacher">Teacher</option>
</select>

<input type="text" name="course" placeholder="Course">
<input type="text" name="year_level" placeholder="Year Level">

<button type="submit">Save</button>
</form>
</div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
<div class="modal-content">
<span class="closeEdit">&times;</span>
<h2>Edit Patient</h2>

<form method="POST" action="Database/edit_patient.php">
<input type="hidden" name="user_id" id="edit_id">

<input type="text" name="name" id="edit_name" required>
<input type="email" name="email" id="edit_email" required>

<select name="role" id="edit_role">
<option value="student">Student</option>
<option value="teacher">Teacher</option>
</select>

<input type="text" name="course" id="edit_course">
<input type="text" name="year_level" id="edit_year">

<select name="status" id="edit_status">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>

<button type="submit">Update</button>
</form>
</div>
</div>

<script>
// ADD MODAL
const addModal = document.getElementById("addModal");
const openBtn = document.getElementById("openModal");
const closeBtn = document.querySelector(".close");

openBtn.onclick = () => addModal.classList.add("show");
closeBtn.onclick = () => addModal.classList.remove("show");

window.onclick = (e) => {
    if (e.target === addModal) addModal.classList.remove("show");
};

// EDIT MODAL
const editModal = document.getElementById("editModal");
const closeEdit = document.querySelector(".closeEdit");

document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {

        document.getElementById("edit_id").value = btn.dataset.id;
        document.getElementById("edit_name").value = btn.dataset.name;
        document.getElementById("edit_email").value = btn.dataset.email;
        document.getElementById("edit_role").value = btn.dataset.role;
        document.getElementById("edit_course").value = btn.dataset.course;
        document.getElementById("edit_year").value = btn.dataset.year;
        document.getElementById("edit_status").value = btn.dataset.status;

        editModal.classList.add("show");
    });
});

closeEdit.onclick = () => editModal.classList.remove("show");

window.addEventListener("click", (e) => {
    if (e.target === editModal) editModal.classList.remove("show");
});
</script>

</body>
</html>