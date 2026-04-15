<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'Database/connection.php';

// Fetch patients (exclude admin)
$stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin'");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - MedLog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <input type="text" placeholder="Search by name or email...">
                <button id="openModal">Add Patient</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if(count($patients) > 0): ?>
                    <?php foreach($patients as $row): ?>
                        <tr>
                            <td><?= $row['user_id'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= ucfirst($row['role']) ?></td>
                            <td><?= $row['course'] ?: 'N/A' ?></td>
                            <td><?= $row['year_level'] ?: 'N/A' ?></td>
                            <td>
                                <button class="editBtn"
                                    data-id="<?= $row['user_id'] ?>"
                                    data-name="<?= $row['name'] ?>"
                                    data-email="<?= $row['email'] ?>"
                                    data-role="<?= $row['role'] ?>"
                                    data-course="<?= $row['course'] ?>"
                                    data-year="<?= $row['year_level'] ?>"
                                >Edit</button>

                                <form action="Database/delete_patient.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                    <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>

                                <button>View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No patients found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

        </section>

    </main>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Patient</h2>

        <form action="Database/add_patient.php" method="POST">
            <input type="text" name="user_id" placeholder="User ID" required>
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
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Add Patient</button>
        </form>
    </div>
</div>

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

            <button type="submit">Update Patient</button>
        </form>
    </div>
</div>

<script>
// ADD MODAL
const modal = document.getElementById("addModal");
const btn = document.getElementById("openModal");
const closeBtn = document.querySelector(".close");

btn.onclick = () => modal.style.display = "block";
closeBtn.onclick = () => modal.style.display = "none";

// EDIT MODAL
const editModal = document.getElementById("editModal");
const editBtns = document.querySelectorAll(".editBtn");
const closeEdit = document.querySelector(".closeEdit");

editBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        edit_id.value = btn.dataset.id;
        edit_name.value = btn.dataset.name;
        edit_email.value = btn.dataset.email;
        edit_role.value = btn.dataset.role;
        edit_course.value = btn.dataset.course;
        edit_year.value = btn.dataset.year;

        editModal.style.display = "block";
    });
});

closeEdit.onclick = () => editModal.style.display = "none";

window.onclick = (e) => {
    if (e.target == modal) modal.style.display = "none";
    if (e.target == editModal) editModal.style.display = "none";
};
</script>

</body>
</html>