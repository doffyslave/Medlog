<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'Database/connection.php';

$user_id = $_GET['user_id'] ?? null;
$selectedUser = null;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT 
            visits.*, 
            users.name,
            users.role AS patient_role,
            GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
        FROM visits
        JOIN users ON visits.user_id = users.user_id
        LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
        LEFT JOIN medicines ON treatments.med_id = medicines.med_id
        WHERE visits.user_id = ?
        GROUP BY visits.visit_id
        ORDER BY visit_date DESC
    ");
    $stmt->execute([$user_id]);

    $userStmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $selectedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

} else {
    $stmt = $conn->prepare("
        SELECT 
            visits.*, 
            users.name,
            users.role AS patient_role,
            GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
        FROM visits
        JOIN users ON visits.user_id = users.user_id
        LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
        LEFT JOIN medicines ON treatments.med_id = medicines.med_id
        GROUP BY visits.visit_id
        ORDER BY visit_date DESC
    ");
    $stmt->execute();
}

$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user = $_SESSION['user'];

ob_start();
?>
<button type="button" id="openVisitModal" class="add-btn">+ Add Visit</button>
<?php
$__visitHeaderActions = ob_get_clean();

$__visitBelow = '';
if ($user_id && !empty($selectedUser)) {
    $__visitBelow = '<div class="medlog-page-header__banner">'
        . '<span>Showing visits for <strong>' . htmlspecialchars($selectedUser['name'], ENT_QUOTES, 'UTF-8') . '</strong></span>'
        . '<a href="visits.php" class="filter-btn">← All visits</a>'
        . '</div>';
}

$medlogPageHeader = [
    'title' => 'Visits',
    'subtitle' => 'Visit activity timeline — review and record clinic encounters.',
    'icon' => 'visits',
    'class' => 'medlog-page-header--visits',
    'actions' => $__visitHeaderActions,
    'below' => $__visitBelow,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visits - MedLog</title>

<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/visits.css">

</head>

<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content visits-page">

<?php include 'includes/medlog-page-header.php'; ?>

<div class="visits-shell">
    <div class="visits-toolbar">
        <div class="visits-toolbar-label">
            <span class="visits-timeline-icon" aria-hidden="true"></span>
            <div>
                <span class="visits-section-kicker">Timeline</span>
                <span class="visits-section-title">Recent activity</span>
            </div>
        </div>
        <div class="visits-filter-chips" role="group" aria-label="Filter by patient role">
            <button type="button" class="visit-filter-chip active" data-filter="all">All</button>
            <button type="button" class="visit-filter-chip" data-filter="student">Students</button>
            <button type="button" class="visit-filter-chip" data-filter="teacher">Teachers</button>
        </div>
    </div>

    <div class="visit-feed" id="visitFeed">
<?php if (!empty($visits)): ?>
<?php foreach ($visits as $index => $visit): ?>
<?php
    $layoutClass = $index % 2 === 0 ? 'layout-left' : 'layout-right';
    $roleSlug = strtolower((string) ($visit['patient_role'] ?? ''));
    $roleLabel = $roleSlug !== '' ? ucfirst($roleSlug) : '';
    $visitJson = htmlspecialchars(json_encode($visit), ENT_QUOTES, 'UTF-8');
    $rowNum = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
?>
        <article
            class="visit-card <?= $layoutClass ?>"
            style="--stagger-delay: <?= (($index % 12) * 70) ?>ms;"
            data-patient-role="<?= htmlspecialchars($roleSlug) ?>"
            role="button"
            tabindex="0"
            data-visit="<?= $visitJson ?>"
        >
            <div class="visit-accent" aria-hidden="true"></div>
            <div class="visit-ribbon">
                <span class="visit-index" aria-hidden="true"><?= $rowNum ?></span>
                <div class="visit-ribbon-primary">
                    <span class="visit-name"><?= htmlspecialchars($visit['name']) ?></span>
                    <?php if ($roleLabel !== ''): ?>
                        <span class="visit-role-pill"><?= htmlspecialchars($roleLabel) ?></span>
                    <?php endif; ?>
                </div>
                <div class="visit-ribbon-col visit-ribbon-complaint">
                    <span class="visit-kicker">Complaint</span>
                    <span class="visit-line"><?= htmlspecialchars($visit['complaint']) ?></span>
                </div>
                <div class="visit-ribbon-col visit-ribbon-treatment">
                    <span class="visit-kicker">Treatment</span>
                    <span class="visit-line"><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></span>
                </div>
                <div class="visit-ribbon-meta">
                    <time class="visit-datetime" datetime="<?= htmlspecialchars(date('c', strtotime($visit['visit_date']))) ?>">
                        <?= date('M j, Y', strtotime($visit['visit_date'])) ?>
                        <span class="visit-time"><?= date('g:i A', strtotime($visit['visit_date'])) ?></span>
                    </time>
                    <span class="visit-recorded">
                        <span class="visit-recorded-kicker">Recorded</span>
                        <?= htmlspecialchars($visit['recorded_by']) ?>
                    </span>
                </div>
                <button type="button" class="visit-details-btn">
                    View details
                </button>
            </div>
        </article>
<?php endforeach; ?>
<?php else: ?>
        <div class="visit-empty">
            <p>No visit records yet.</p>
            <span class="visit-empty-hint">Add a visit to populate this timeline.</span>
        </div>
<?php endif; ?>
    </div>
</div>

</section>
</main>
</div>

<!-- 🔥 VIEW MODAL -->
<div id="viewModal" class="modal">
<div class="modal-content">
<span class="closeView">&times;</span>
<h2>Visit Details</h2>

<div id="viewContent"></div>

</div>
</div>

<!-- ADD VISIT MODAL -->
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

<textarea name="notes" placeholder="Notes (optional)..."></textarea>

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

<button type="submit">Save Visit</button>

</form>
</div>
</div>

<script>
const visitModal = document.getElementById("visitModal");
const viewModal = document.getElementById("viewModal");

document.getElementById("openVisitModal").onclick = () => {
    visitModal.classList.add("show");
};

document.querySelector(".closeVisit").onclick = () => {
    visitModal.classList.remove("show");
};

document.querySelector(".closeView").onclick = () => {
    viewModal.classList.remove("show");
};

window.onclick = (e) => {
    if (e.target === visitModal) visitModal.classList.remove("show");
    if (e.target === viewModal) viewModal.classList.remove("show");
};

function openViewModal(data) {
    document.getElementById("viewContent").innerHTML = `
        <p><strong>Patient:</strong> ${data.name}</p>
        <p><strong>Date:</strong> ${data.visit_date}</p>
        <p><strong>Recorded By:</strong> ${data.recorded_by}</p>
        <hr>
        <p><strong>Complaint:</strong> ${data.complaint}</p>
        <p><strong>Treatment:</strong> ${data.medicines_used || 'None'}</p>
        <p><strong>Notes:</strong> ${data.notes || 'None'}</p>

        <br>

        <a href="print_visit.php?id=${data.visit_id}" target="_blank" class="visit-print-link">
           🖨 Print Visit
        </a>
    `;

    viewModal.classList.add("show");
}

function parseVisitDataset(card) {
    try {
        return JSON.parse(card.dataset.visit);
    } catch (e) {
        return null;
    }
}

document.querySelectorAll(".visit-card[data-visit]").forEach(card => {
    card.addEventListener("click", () => {
        const data = parseVisitDataset(card);
        if (data) openViewModal(data);
    });
    card.addEventListener("keydown", (e) => {
        if (e.key !== "Enter" && e.key !== " ") return;
        e.preventDefault();
        const data = parseVisitDataset(card);
        if (data) openViewModal(data);
    });
});

document.querySelectorAll(".visit-details-btn").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const card = btn.closest(".visit-card");
        if (!card) return;
        const data = parseVisitDataset(card);
        if (data) openViewModal(data);
    });
});

document.querySelectorAll(".visit-filter-chip").forEach(chip => {
    chip.addEventListener("click", () => {
        const filter = chip.dataset.filter || "all";
        document.querySelectorAll(".visit-filter-chip").forEach(c => c.classList.remove("active"));
        chip.classList.add("active");
        document.querySelectorAll(".visit-card[data-visit]").forEach(card => {
            const role = (card.dataset.patientRole || "").toLowerCase();
            const show = filter === "all" || role === filter;
            card.hidden = !show;
        });
    });
});

const visitCards = document.querySelectorAll(".visit-card");

if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.12,
        rootMargin: "0px 0px -24px 0px"
    });

    visitCards.forEach(card => revealObserver.observe(card));
} else {
    visitCards.forEach(card => card.classList.add("is-visible"));
}
</script>

</body>
</html>
