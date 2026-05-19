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

$statsQuery = "SELECT 
    COUNT(*) AS total_patients,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count,
    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS student_count,
    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) AS teacher_count
    FROM users
    WHERE role != 'admin'";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$medlogPageHeader = [
    'title' => 'Patients',
    'subtitle' => 'Manage clinic patients - search, filter, and open profiles.',
    'icon' => 'patients',
    'class' => 'medlog-page-header--patients',
];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients</title>
    <link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
    <link rel="stylesheet" href="Css/patients.css?v=20260517-mobile-cards">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content patients-page">

    <?php include 'includes/medlog-page-header.php'; ?>

    <div class="patients-shell">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Total Patients</span>
                <strong class="stat-value"><?= (int) ($stats['total_patients'] ?? 0) ?></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Active</span>
                <strong class="stat-value"><?= (int) ($stats['active_count'] ?? 0) ?></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Inactive</span>
                <strong class="stat-value"><?= (int) ($stats['inactive_count'] ?? 0) ?></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Students</span>
                <strong class="stat-value"><?= (int) ($stats['student_count'] ?? 0) ?></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Teachers</span>
                <strong class="stat-value"><?= (int) ($stats['teacher_count'] ?? 0) ?></strong>
            </article>
        </div>

        <div class="patients-toolbar">
            <div class="toolbar-left">
                <form method="GET" autocomplete="off" class="search-form">
                    <input
                        type="text"
                        id="searchInput"
                        name="search"
                        placeholder="Search patient..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                    <button type="submit">Search</button>
                    <input type="hidden" name="show_inactive" value="<?= (int) $showInactive ?>">
                </form>

                <div class="toolbar-selects">
                    <label class="chip-select">
                        <span>Sort by</span>
                        <select id="sortSelect">
                            <option value="name-asc">Name A-Z</option>
                            <option value="name-desc">Name Z-A</option>
                            <option value="email-asc">Email A-Z</option>
                            <option value="email-desc">Email Z-A</option>
                            <option value="status-asc">Status</option>
                            <option value="role-asc">Role</option>
                        </select>
                    </label>

                    <label class="chip-select">
                        <span>Role</span>
                        <select id="roleFilter">
                            <option value="all">All Roles</option>
                            <option value="student">Students</option>
                            <option value="teacher">Teachers</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="toolbar-right">
                <div class="filter-chips">
                    <a href="?search=<?= urlencode($search) ?>" class="filter-btn <?= !$showInactive ? 'active-chip' : '' ?>">
                        Active Only
                    </a>
                    <a href="?show_inactive=1&search=<?= urlencode($search) ?>" class="filter-btn <?= $showInactive ? 'active-chip' : '' ?>">
                        Show Inactive
                    </a>
                </div>
                <button id="openModal" class="add-btn">
                    + Add Patient
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="patients-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($patients) > 0): ?>
                    <?php foreach($patients as $row): ?>

                    <tr
                        id="patient-row-<?= $row['user_id'] ?>"
                        class="<?= $row['status'] == 'inactive' ? 'inactive-row' : '' ?> patient-row"
                        data-user-id="<?= $row['user_id'] ?>"
                        data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>"
                        data-email="<?= strtolower(htmlspecialchars($row['email'])) ?>"
                        data-status="<?= htmlspecialchars($row['status']) ?>"
                        data-role="<?= htmlspecialchars($row['role']) ?>"
                        onclick="openProfile('<?= $row['user_id'] ?>')"
                    >
                        <td class="patient-name">
                            <div class="name-wrapper">
                                <div class="avatar">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>

                                <div class="patient-meta">
                                    <div class="name">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </div>

                                    <div class="sub">
                                        <?= ucfirst($row['role']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="clickable">
                            <?= htmlspecialchars($row['email']) ?>
                        </td>

                        <td>
                            <span class="status <?= $row['status'] ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <td onclick="event.stopPropagation();">
                            <button
                                class="editBtn"
                                data-id="<?= $row['user_id'] ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                data-role="<?= $row['role'] ?>"
                                data-course="<?= htmlspecialchars($row['course']) ?>"
                                data-year="<?= htmlspecialchars($row['year_level']) ?>"
                                data-status="<?= $row['status'] ?>"
                            >
                                Edit
                            </button>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty-state">
                            No patients found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>
</main>
</div>

<!-- PROFILE PANEL -->
<div id="profileContainer" class="profile-hidden">
    <div class="profile-box" onclick="event.stopPropagation();">
        <div id="profileContent"></div>
    </div>
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
const searchInput = document.getElementById("searchInput");
const tableBody = document.querySelector(".patients-table tbody");
const sortSelect = document.getElementById("sortSelect");
const roleFilter = document.getElementById("roleFilter");
const profilePanel = document.getElementById("profileContainer");
const profileContent = document.getElementById("profileContent");

function getPatientRows() {
    return Array.from(document.querySelectorAll(".patient-row"));
}

function applyClientFilters() {
    const query = searchInput.value.toLowerCase();
    const roleValue = roleFilter ? roleFilter.value : "all";

    getPatientRows().forEach(row => {
        const rowText = row.innerText.toLowerCase();
        const rowRole = row.dataset.role;
        const searchMatch = rowText.includes(query);
        const roleMatch = roleValue === "all" || rowRole === roleValue;
        row.style.display = searchMatch && roleMatch ? "" : "none";
    });
}

function applySort() {
    if (!sortSelect || !tableBody) return;

    const value = sortSelect.value;
    const [field, direction] = value.split("-");
    const directionFactor = direction === "desc" ? -1 : 1;
    const rows = getPatientRows();

    rows.sort((a, b) => {
        let left = "";
        let right = "";

        if (field === "name") {
            left = a.dataset.name || "";
            right = b.dataset.name || "";
        } else if (field === "email") {
            left = a.dataset.email || "";
            right = b.dataset.email || "";
        } else if (field === "status") {
            left = a.dataset.status || "";
            right = b.dataset.status || "";
        } else if (field === "role") {
            left = a.dataset.role || "";
            right = b.dataset.role || "";
        }

        return left.localeCompare(right) * directionFactor;
    });

    rows.forEach(row => tableBody.appendChild(row));
}

searchInput.addEventListener("input", function () {
    applyClientFilters();
});

if (roleFilter) {
    roleFilter.addEventListener("change", applyClientFilters);
}

if (sortSelect) {
    sortSelect.addEventListener("change", applySort);
}

function openProfile(user_id) {
    profileContent.innerHTML = `
        <div class="drawer-loading">
            <div class="drawer-loading-title"></div>
            <div class="drawer-loading-line"></div>
            <div class="drawer-loading-line short"></div>
        </div>
    `;

    profilePanel.classList.remove("profile-hidden");
    profilePanel.classList.add("active");

    fetch("Database/get_patient.php?id=" + user_id)
    .then(res => res.text())
    .then(data => {
        profileContent.innerHTML = data;

        document.querySelectorAll(".patient-row").forEach(row => row.classList.remove("row-selected"));
        const selectedRow = document.getElementById("patient-row-" + user_id);
        if (selectedRow) {
            selectedRow.classList.add("row-selected");
        }
    })
    .catch(() => {
        profileContent.innerHTML = `
            <div class="drawer-section">
                <h3>Unable to load profile</h3>
                <p class="empty-activity">Please try again.</p>
            </div>
        `;
    });
}

function closeProfile() {
    profilePanel.classList.remove("active");
    profilePanel.classList.add("profile-hidden");
    document.querySelectorAll(".patient-row").forEach(row => row.classList.remove("row-selected"));
}

const addModal = document.getElementById("addModal");
const openBtn = document.getElementById("openModal");
const closeBtn = document.querySelector(".close");

openBtn.onclick = () => addModal.classList.add("show");
closeBtn.onclick = () => addModal.classList.remove("show");

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
    if (e.target === addModal) addModal.classList.remove("show");
    if (e.target === editModal) editModal.classList.remove("show");
});

profilePanel.addEventListener("click", function (e) {
    if (e.target === profilePanel) {
        closeProfile();
    }
});

document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        if (profilePanel.classList.contains("active")) closeProfile();
        if (addModal.classList.contains("show")) addModal.classList.remove("show");
        if (editModal.classList.contains("show")) editModal.classList.remove("show");
    }
});

applyClientFilters();
applySort();
</script>

</body>
</html>









