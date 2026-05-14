<?php
session_start();
require 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];
$role = $user['role'] ?? 'guest';

$stmt = $conn->prepare("
    SELECT 
        visits.*, 
        GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
    FROM visits
    LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
    LEFT JOIN medicines ON treatments.med_id = medicines.med_id
    WHERE visits.user_id = ?
    GROUP BY visits.visit_id
    ORDER BY visits.visit_date DESC
");
$stmt->execute([$user_id]);

$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$medlogPageHeader = [
    'title' => 'My visits',
    'subtitle' => 'Your personal clinic visit history.',
    'icon' => 'my-visits',
    'class' => 'medlog-page-header--my-visits',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Visits | MedLog</title>

<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/visits.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
.no-data {
    text-align: center;
    margin-top: 20px;
    color: gray;
}

.visit-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.visit-card {
    opacity: 1 !important;
    transform: none !important;
    width: 100% !important;
    cursor: pointer;
    padding: 14px;
}

.visit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.visit-meta {
    font-size: 12px;
    color: #4A7FA7;
    font-weight: 700;
}

.visit-body p {
    margin: 0 0 8px;
}

.badge {
    display: inline-block;
    margin-top: 4px;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(179, 207, 229, 0.45);
    color: #1A3D63;
    font-size: 12px;
    font-weight: 700;
}

</style>
</head>

<body<?= $role === 'student' ? ' class="medlog-student-shell"' : '' ?>>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<section class="content">

<?php include 'includes/medlog-page-header.php'; ?>

<!-- 🔥 CARD LIST -->
<div class="visits-shell">
    <div class="visits-toolbar">
        <div class="visits-toolbar-label">
            <span class="visits-timeline-icon" aria-hidden="true"></span>
            <div>
                <span class="visits-section-kicker">Timeline</span>
                <span class="visits-section-title">My visit activity</span>
            </div>
        </div>
    </div>

    <div class="visit-feed" id="visitFeed">
    <?php if (!empty($visits)): ?>
        <?php foreach ($visits as $index => $visit): ?>
            <?php
                $layoutClass = $index % 2 === 0 ? 'layout-left' : 'layout-right';
                $rowNum = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
                $visitJson = htmlspecialchars(json_encode($visit), ENT_QUOTES, 'UTF-8');
            ?>
            <article
                class="visit-card <?= $layoutClass ?>"
                style="--stagger-delay: <?= (($index % 12) * 70) ?>ms;"
                role="button"
                tabindex="0"
                data-visit="<?= $visitJson ?>"
            >
                <div class="visit-accent" aria-hidden="true"></div>
                <div class="visit-ribbon">
                    <span class="visit-index" aria-hidden="true"><?= $rowNum ?></span>

                    <div class="visit-ribbon-primary">
                        <span class="visit-name">My Visit</span>
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

                    <button type="button" class="visit-details-btn">View details</button>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="visit-empty">
            <p>No visit records yet.</p>
            <span class="visit-empty-hint">Your clinic visits will appear here.</span>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- 🔥 VIEW MODAL -->
<div id="viewModal" class="modal">
<div class="modal-content">
<span class="closeView">&times;</span>
<h2>Visit Details</h2>
<div id="viewContent"></div>
</div>
</div>

<script>
const viewModal = document.getElementById("viewModal");

document.querySelector(".closeView").onclick = () => {
    viewModal.classList.remove("show");
};

window.onclick = (e) => {
    if (e.target === viewModal) viewModal.classList.remove("show"); 
};

function esc(v) {
    const s = v === undefined || v === null ? "" : String(v);
    return s
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function openViewModal(data) {
    document.getElementById("viewContent").innerHTML = `
        <p><strong>Date:</strong> ${esc(data.visit_date)}</p>
        <p><strong>Recorded By:</strong> ${esc(data.recorded_by)}</p>
        <hr>
        <p><strong>Complaint:</strong> ${esc(data.complaint)}</p>
        <p><strong>Treatment:</strong> ${esc(data.medicines_used || 'None')}</p>
        <p><strong>Notes:</strong> ${esc(data.notes || 'None')}</p>
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