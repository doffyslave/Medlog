<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'Database/connection.php';

// FETCH VISITS WITH PATIENT NAME
$stmt = $conn->prepare("
    SELECT visits.*, users.name 
    FROM visits
    JOIN users ON visits.user_id = users.user_id
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

    <!-- ICONS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- GLOBAL -->
    <link rel="stylesheet" href="Css/layout.css">

    <!-- PAGE -->
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
            <h1>Visits</h1>
            <p>Manage clinic visit records</p>

            <div class="top-bar">
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
                                    <td><?= htmlspecialchars($visit['treatment']) ?></td>
                                    <td><?= htmlspecialchars($visit['recorded_by']) ?></td>
                                    <td>
                                        <button class="edit-btn">Edit</button>
                                        <button class="delete-btn">Delete</button>
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
            <input type="text" name="treatment" placeholder="Treatment" required>

            <input type="text" name="recorded_by" placeholder="Recorded By" required>

            <button type="submit">Save Visit</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const visitModal = document.getElementById("visitModal");
    const openVisitBtn = document.getElementById("openVisitModal");
    const closeVisit = document.querySelector(".closeVisit");

    if (openVisitBtn && visitModal) {
        openVisitBtn.addEventListener("click", () => {
            visitModal.style.display = "block";
        });
    }

    if (closeVisit) {
        closeVisit.addEventListener("click", () => {
            visitModal.style.display = "none";
        });
    }

    window.addEventListener("click", (e) => {
        if (e.target == visitModal) {
            visitModal.style.display = "none";
        }
    });

});
</script>


</body>
</html>

