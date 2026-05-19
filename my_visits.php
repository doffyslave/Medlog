<?php
session_start();
require 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];
$role = strtolower(trim((string) ($user['role'] ?? 'guest')));

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

<link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
<link rel="stylesheet" href="Css/visits.css?v=20260519-my-visits-blue-bg-desktop">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body<?= $role === 'student' ? ' class="medlog-student-shell"' : '' ?>>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<section class="content visits-page">

<?php include 'includes/medlog-page-header.php'; ?>

<div class="visits-shell visits-shell--timeline visits-shell--student">
    <div class="visits-toolbar visits-toolbar--student">
        <div class="visits-toolbar-label visits-toolbar-label--student">
            <span class="visits-timeline-icon visits-timeline-icon--outline" aria-hidden="true">
                <i class="fa-regular fa-clock"></i>
            </span>
            <div>
                <span class="visits-toolbar-title">Timeline</span>
                <span class="visits-toolbar-subtitle">My visit activity</span>
            </div>
        </div>
    </div>

    <div class="visit-feed visit-feed--timeline visit-feed--student" id="visitFeed">
    <?php if (!empty($visits)): ?>
        <?php foreach ($visits as $index => $visit): ?>
            <?php
                $visitJson = htmlspecialchars(json_encode($visit), ENT_QUOTES, 'UTF-8');
            ?>
            <article
                class="visit-card visit-card--timeline visit-card--student theme-blue"
                style="--stagger-delay: <?= (($index % 12) * 70) ?>ms;"
                role="button"
                tabindex="0"
                data-visit="<?= $visitJson ?>"
            >
                <div class="visit-card-shell">
                    <div class="visit-mobile-date-strip"><span class="visit-card-icon visit-card-icon--calendar" aria-hidden="true"><i class="fa-regular fa-calendar-check"></i></span><span><?= date('M j, Y', strtotime($visit['visit_date'])) ?></span><span class="visit-mobile-dot">&bull;</span><span><?= date('g:i A', strtotime($visit['visit_date'])) ?></span></div>
                    <div class="visit-card-icon visit-card-icon--calendar" aria-hidden="true">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>

                    <div class="visit-desktop-row">
                        <div class="visit-desktop-date">
                            <strong><?= date('M j, Y', strtotime($visit['visit_date'])) ?></strong>
                            <span><i class="fa-regular fa-clock" aria-hidden="true"></i><?= date('g:i A', strtotime($visit['visit_date'])) ?></span>
                        </div>
                        <div class="visit-desktop-info">
                            <span class="visit-info-label">Complaint</span>
                            <strong><i class="fa-regular fa-file-lines" aria-hidden="true"></i><?= htmlspecialchars($visit['complaint']) ?></strong>
                        </div>
                        <div class="visit-desktop-info">
                            <span class="visit-info-label">Treatment</span>
                            <strong><i class="fa-solid fa-link" aria-hidden="true"></i><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></strong>
                        </div>
                        <div class="visit-desktop-info">
                            <span class="visit-info-label">Recorded by</span>
                            <strong><i class="fa-regular fa-user" aria-hidden="true"></i><?= htmlspecialchars($visit['recorded_by']) ?></strong>
                        </div>
                        <div class="visit-card-actions visit-card-actions--desktop">
                            <button type="button" class="visit-details-btn">
                                <span>View details</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="visit-card-body">
                        <div class="visit-card-main">
                            <div class="visit-card-heading">
                                <h3 class="visit-name">My Visit</h3>
                                <div class="visit-stacked-details">
                                    <span class="visit-inline-item visit-inline-item--complaint">
                                        <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                        <span class="visit-info-copy"><span class="visit-info-label">Complaint</span><strong><?= htmlspecialchars($visit['complaint']) ?></strong></span>
                                    </span>
                                    <span class="visit-inline-item visit-inline-item--treatment">
                                        <i class="fa-solid fa-link" aria-hidden="true"></i>
                                        <span class="visit-info-copy"><span class="visit-info-label">Treatment</span><strong><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></strong></span>
                                    </span>
                                </div>
                            </div>

                            <div class="visit-divider" aria-hidden="true"></div>

                            <div class="visit-meta-row">
                                <span class="visit-meta-item visit-meta-item--date"><i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    <span><?= date('M j, Y', strtotime($visit['visit_date'])) ?></span>
                                </span>
                                <span class="visit-meta-item visit-meta-item--time"><i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <span><?= date('g:i A', strtotime($visit['visit_date'])) ?></span>
                                </span>
                                <span class="visit-meta-item">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                    <span class="visit-info-copy"><span class="visit-info-label">Recorded by</span><strong><?= htmlspecialchars($visit['recorded_by']) ?></strong></span>
                                </span>
                            </div>
                        </div>

                        <div class="visit-card-actions">
                            <button type="button" class="visit-details-btn">
                                <span>View details</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
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

</section>
</main>
</div>

<div id="viewModal" class="modal visit-details-modal">
<div class="modal-content visit-details-modal__dialog">
<button type="button" class="closeView visit-details-modal__close" aria-label="Close">&times;</button>
<h2>Visit Details</h2>
<div id="viewContent" class="visit-details-modal__content"></div>
<div class="visit-details-modal__footer">
    <button type="button" class="closeView visit-details-modal__button">Close</button>
</div>
</div>
</div>

<script>
const viewModal = document.getElementById("viewModal");

document.querySelectorAll(".closeView").forEach((btn) => {
    btn.addEventListener("click", () => {
        viewModal.classList.remove("show");
    });
});

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
    const formatVisitDate = (value) => {
        const raw = value === undefined || value === null ? "" : String(value);
        const parsed = new Date(raw.replace(" ", "T"));
        if (Number.isNaN(parsed.getTime())) return esc(raw);
        const dateText = new Intl.DateTimeFormat("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric"
        }).format(parsed);
        const timeText = new Intl.DateTimeFormat("en-US", {
            hour: "numeric",
            minute: "2-digit"
        }).format(parsed);
        return `${esc(dateText)} &#8226; ${esc(timeText)}`;
    };
    document.getElementById("viewContent").innerHTML = `
        <div class="visit-details-modal__rows">
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Date &amp; Time</span>
                <strong class="visit-details-modal__value">${formatVisitDate(data.visit_date)}</strong>
            </div>
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Recorded By</span>
                <strong class="visit-details-modal__value">${esc(data.recorded_by)}</strong>
            </div>
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Complaint</span>
                <strong class="visit-details-modal__value">${esc(data.complaint || "None")}</strong>
            </div>
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Treatment</span>
                <strong class="visit-details-modal__value">${esc(data.medicines_used || "None")}</strong>
            </div>
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-regular fa-rectangle-list" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Notes</span>
                <strong class="visit-details-modal__value">${esc(data.notes || "None")}</strong>
            </div>
        </div>
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



















































