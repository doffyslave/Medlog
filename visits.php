<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'Database/connection.php';

$stmt = $conn->prepare("
    SELECT 
        visits.*, 
        users.name,
        GROUP_CONCAT(medicines.medicine_name SEPARATOR ', ') AS medicines_used
    FROM visits
    JOIN users ON visits.user_id = users.user_id
    LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
    LEFT JOIN medicines ON treatments.med_id = medicines.med_id
    GROUP BY visits.visit_id
    ORDER BY visit_date DESC
");

$stmt->execute();
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visits - MedLog</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="Css/layout.css">
    <link rel="stylesheet" href="Css/visits.css">
</head>
<body>

<div class="dashboard">

    <!-- 🔥 SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- 🔥 HEADER -->
        <?php include 'includes/header.php'; ?>

        <section class="content">
            <div class="header-row">
    <div>
        <h1>Visits</h1>
        <p>Manage clinic visit records</p>
    </div>

    <button id="openVisitModal" class="add-btn">
        <i class="fas fa-plus"></i> Add Visit
    </button>
</div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Visit Date</th>
                            <th>Complaint</th>
                            <th>Treatment</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($visits)): ?>
                            <?php foreach ($visits as $visit): ?>
                                <tr>
                                    <td><?= htmlspecialchars($visit['name']) ?></td>
                                    <td><?= htmlspecialchars($visit['visit_date']) ?></td>
                                    <td><?= htmlspecialchars($visit['complaint']) ?></td>
                                    <td><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></td>
                                    <td><?= htmlspecialchars($visit['recorded_by']) ?></td>
                                    <td>
                                        <button class="edit-btn"
                                            data-id="<?= $visit['visit_id'] ?>"
                                            data-user="<?= $visit['user_id'] ?>"
                                            data-date="<?= $visit['visit_date'] ?>"
                                            data-complaint='<?= json_encode($visit['complaint']) ?>'
                                            data-treatment='<?= json_encode($visit['treatment']) ?>'
                                            data-recorded='<?= json_encode($visit['recorded_by']) ?>'>
                                            Edit
                                        </button>
                                        <button class="delete-btn" data-id="<?= $visit['visit_id'] ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No visit records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </section>

    </main>

</div>

<div id="visitModal" class="modal">
    <div class="modal-content">
        <span class="closeVisit">&times;</span>
        <h2>Add Visit</h2>

        <form action="Database/add_visit.php" method="POST">

            <select name="user_id" required>
                <option value="">Select Patient</option>
                <?php
                $users = $conn->query("SELECT user_id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($users as $u):
                ?>
                    <option value="<?= $u['user_id'] ?>">
                        <?= htmlspecialchars($u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="complaint" placeholder="Complaint" required>
            <select name="med_id" required>
    <option value="">Select Medicine</option>
    <?php
    $meds = $conn->query("SELECT med_id, medicine_name FROM medicines")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($meds as $m):
    ?>
        <option value="<?= $m['med_id'] ?>">
            <?= htmlspecialchars($m['medicine_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<input type="number" name="quantity" placeholder="Quantity" required>

            <input type="text" name="recorded_by" placeholder="Recorded By" required>

            <button type="submit">Save Visit</button>
        </form>
    </div>
</div>

<div id="editVisitModal" class="modal">
    <div class="modal-content">
        <span class="closeEdit">&times;</span>
        <h2>Edit Visit</h2>

        <form action="Database/edit_visit.php" method="POST">

            <input type="hidden" name="visit_id" id="edit_visit_id">

            <select name="user_id" id="edit_user_id" required>
                <option value="">Select Patient</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['user_id'] ?>">
                        <?= htmlspecialchars($u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="datetime-local" name="visit_date" id="edit_visit_date" required>

            <input type="text" name="complaint" id="edit_complaint" required>
            <input type="text" name="treatment" id="edit_treatment" required>
            <input type="text" name="recorded_by" id="edit_recorded_by" required>

            <button type="submit">Update Visit</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const visitModal = document.getElementById("visitModal");
    const editModal = document.getElementById("editVisitModal");

    const openVisitBtn = document.getElementById("openVisitModal");
    const closeVisit = document.querySelector(".closeVisit");
    const closeEdit = document.querySelector(".closeEdit");

    // OPEN ADD MODAL
    openVisitBtn.addEventListener("click", () => {
        visitModal.classList.add("show");
    });

    // CLOSE ADD MODAL
    closeVisit.addEventListener("click", () => {
        visitModal.classList.remove("show");
    });

    // CLOSE WHEN CLICK OUTSIDE
    window.addEventListener("click", (e) => {
        if (e.target === visitModal) visitModal.classList.remove("show");
        if (e.target === editModal) editModal.classList.remove("show");
    });

    // EDIT BUTTON LOGIC
    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", () => {

            editModal.classList.add("show");

            document.getElementById("edit_visit_id").value = btn.dataset.id;
            document.getElementById("edit_user_id").value = btn.dataset.user;

            let rawDate = btn.dataset.date;
            let formatted = rawDate.replace(" ", "T").slice(0,16);
            document.getElementById("edit_visit_date").value = formatted;

            document.getElementById("edit_complaint").value = JSON.parse(btn.dataset.complaint);
            document.getElementById("edit_treatment").value = JSON.parse(btn.dataset.treatment);
            document.getElementById("edit_recorded_by").value = JSON.parse(btn.dataset.recorded);
        });
    });

    // CLOSE EDIT MODAL
    closeEdit.addEventListener("click", () => {
        editModal.classList.remove("show");
    });

    // DELETE FUNCTION
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", () => {

            const visitId = btn.dataset.id;

            if (confirm("Are you sure you want to delete this visit?")) {
                window.location.href = "Database/delete_visit.php?id=" + visitId;
            }
        });
    });

});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        visitModal.classList.remove("show");
        editModal.classList.remove("show");
    }
}); 
</script>


</body>
</html>

