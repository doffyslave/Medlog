<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'Database/connection.php';

$search = $_GET['search'] ?? '';
$showInactive = $_GET['show_inactive'] ?? 0;

// 🔥 FIX SEARCH (IGNORE DASHES)
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

<?php if (isset($_GET['success'])): ?>
    <div style="color:green; margin-bottom:10px;">
        Patient updated successfully.
    </div>
<?php endif; ?>

<div class="top-bar">

    <form method="GET" autocomplete="off">
        
        <div class="search-wrapper">
            <input type="text" id="searchInput" name="search" placeholder="Search patient..." value="<?= htmlspecialchars($search) ?>">
        </div>

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
<tr class="<?= $row['status'] == 'inactive' ? 'inactive-row' : '' ?>">

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

<script>
// 🔥 LIVE SEARCH
const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("input", function () {
    let value = this.value.toLowerCase();

    let rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();

        if (text.includes(value)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});

// PROFILE
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