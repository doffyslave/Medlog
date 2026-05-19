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

$patientPickerRows = $conn->query("
    SELECT user_id, name
    FROM users
    WHERE LOWER(TRIM(COALESCE(role, ''))) <> 'admin'
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$patientPickerOptions = [];
foreach ($patientPickerRows as $pr) {
    $patientPickerOptions[] = [
        'id' => (string) $pr['user_id'],
        'label' => (string) $pr['name'],
    ];
}

$medicinePickerRows = $conn->query("
    SELECT med_id, medicine_name FROM medicines ORDER BY medicine_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$medicinePickerOptions = [];
foreach ($medicinePickerRows as $mr) {
    $medicinePickerOptions[] = [
        'id' => (string) $mr['med_id'],
        'label' => (string) $mr['medicine_name'],
    ];
}

$patientPickerJson = json_encode(
    $patientPickerOptions,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
$medicinePickerJson = json_encode(
    $medicinePickerOptions,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

ob_start();
?>
<button type="button" id="openVisitModal" class="add-btn medlog-hero-action-btn">
    <i class="fa-solid fa-plus" aria-hidden="true"></i>
    <span>Add New Visit</span>
</button>
<?php
$__visitHeaderActions = ob_get_clean();

$__visitBelow = '';
if ($user_id && !empty($selectedUser)) {
    $__visitBelow = '<div class="medlog-page-header__banner">'
        . '<span>Showing visits for <strong>' . htmlspecialchars($selectedUser['name'], ENT_QUOTES, 'UTF-8') . '</strong></span>'
        . '<a href="visits.php" class="filter-btn">&larr; All visits</a>'
        . '</div>';
}

$medlogPageHeader = [
    'title' => 'Clinic Visits',
    'subtitle' => 'Manage clinic visits ' . "\xE2\x80\x94" . ' view schedules, monitor records, and organize visits efficiently.',
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visits - MedLog</title>

<link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
<link rel="stylesheet" href="Css/visits.css?v=20260519-admin-mobile-page">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content visits-page">

<?php include 'includes/medlog-page-header.php'; ?>

<div class="visits-shell visits-shell--timeline">
    <div class="visits-toolbar visits-toolbar--stacked">
        <div class="visits-toolbar-copy">
            <h2 class="visits-toolbar-title">Recent activity</h2>
        </div>
        <div class="visits-filter-chips" role="group" aria-label="Filter by patient role">
            <button type="button" class="visit-filter-chip active" data-filter="all">All</button>
            <button type="button" class="visit-filter-chip" data-filter="student">Students</button>
            <button type="button" class="visit-filter-chip" data-filter="teacher">Teachers</button>
        </div>
    </div>

    <div class="visit-feed visit-feed--timeline" id="visitFeed">
<?php if (!empty($visits)): ?>
<?php foreach ($visits as $index => $visit): ?>
<?php
        $roleSlug = strtolower(trim((string) ($visit['patient_role'] ?? '')));
    $roleKey = preg_replace('/[^a-z]/', '', $roleSlug);
    if ($roleKey === 'students') {
        $roleKey = 'student';
    } elseif ($roleKey === 'teachers') {
        $roleKey = 'teacher';
    }
    $roleLabel = $roleKey !== '' ? ucfirst($roleKey) : 'Guest';
    $displayName = (string) ($visit['name'] ?? '');
    $roleSuffix = ($roleLabel !== '' && stripos($displayName, '(' . $roleLabel . ')') === false)
        ? ' <span class="visit-role-inline">(' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . ')</span>'
        : '';
    $visitJson = htmlspecialchars(json_encode($visit), ENT_QUOTES, 'UTF-8');

    $roleCardClass = in_array($roleKey, ['student', 'teacher'], true) ? ' visit-card--student visit-card--role-mobile' : '';
    $themeClass = 'theme-blue';
    $iconClass = 'fa-regular fa-user';

    if ($roleKey === 'teacher') {
        $themeClass = 'theme-green';
        $iconClass = 'fa-solid fa-graduation-cap';
    } elseif ($roleKey === 'student') {
        $themeClass = ($index % 3 === 0) ? 'theme-purple' : (($index % 3 === 1) ? 'theme-blue' : 'theme-amber');
        $iconClass = 'fa-regular fa-user';
    }
?>
        <article
            class="visit-card visit-card--timeline <?= $themeClass ?><?= $roleCardClass ?>"
            style="--stagger-delay: <?= (($index % 12) * 70) ?>ms;"
            data-patient-role="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>"
            role="button"
            tabindex="0"
            data-visit="<?= $visitJson ?>"
        >
            <div class="visit-card-shell">
                <div class="visit-mobile-date-strip"><span class="visit-card-icon visit-card-icon--calendar" aria-hidden="true"><i class="fa-regular fa-calendar-check"></i></span><span><?= date('M j, Y', strtotime($visit['visit_date'])) ?></span><span class="visit-mobile-dot">&bull;</span><span><?= date('g:i A', strtotime($visit['visit_date'])) ?></span></div>
                <div class="visit-card-icon" aria-hidden="true">
                    <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>


                <div class="visit-card-body">
                    <div class="visit-card-main">
                        <div class="visit-card-heading">
                            <h3 class="visit-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?><?= $roleSuffix ?></h3>
                            <div class="visit-inline-details visit-stacked-details">
                                <span class="visit-inline-item visit-inline-item--complaint">
                                    <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                    <span class="visit-info-copy"><span class="visit-info-label">Complaint</span><strong><?= htmlspecialchars($visit['complaint']) ?></strong></span>
                                </span>
                                <span class="visit-inline-sep" aria-hidden="true">|</span>
                                <span class="visit-inline-item visit-inline-item--treatment">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    <span class="visit-info-copy"><span class="visit-info-label">Treatment</span><strong><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></strong></span>
                                </span>
                            </div>
                        </div>

                        <div class="visit-meta-row">
                            <span class="visit-meta-item">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span><?= date('M j, Y', strtotime($visit['visit_date'])) ?></span>
                            </span>
                            <span class="visit-meta-item">
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
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
            <span class="visit-empty-hint">Add a visit to populate this timeline.</span>
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
    <a id="visitPrintLink" class="visit-print-link" href="#" target="_blank" rel="noopener">Print</a>
    <button type="button" class="closeView visit-details-modal__button">Close</button>
</div>
</div>
</div>

<div id="visitModal" class="modal visit-add-modal" aria-hidden="true">
    <div class="modal-content visit-add-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="visitAddTitle">
        <div class="visit-add-modal__hero">
            <button type="button" class="visit-add-modal__close closeVisit" aria-label="Close">&times;</button>
            <span class="visit-add-modal__eyebrow">Clinical record</span>
            <h2 id="visitAddTitle" class="visit-add-modal__title">Add visit</h2>
            <p class="visit-add-modal__subtitle">Log an encounter, complaint, and optional medication. Medicine is only required when dispensing from stock.</p>
        </div>
        <form id="addVisitForm" class="visit-add-modal__form" action="Database/add_visit.php" method="POST">
            <div class="visit-add-modal__body">
                <div class="visit-add-modal__section">
                    <div class="visit-add-modal__section-head">
                        <h3 class="visit-add-modal__section-title">Patient &amp; visit</h3>
                    </div>
                    <div class="visit-add-modal__field">
                        <label class="visit-add-modal__label" for="visitPatientSearch">Patient <span class="req">*</span></label>
                        <div class="medlog-search-select" id="visitPatientSelect" data-medlog-search-select data-placeholder="Search patient by name">
                            <div class="medlog-search-select__control">
                                <input type="text" id="visitPatientSearch" class="medlog-search-select__input" autocomplete="off" placeholder="Search patient by name" aria-autocomplete="list" aria-controls="visitPatientList" aria-expanded="false" role="combobox">
                                <span class="medlog-search-select__chevron" aria-hidden="true"></span>
                                <input type="hidden" name="user_id" id="visitPatientValue" value="" required>
                                <ul class="medlog-search-select__dropdown" id="visitPatientList" role="listbox" hidden></ul>
                            </div>
                        </div>
                    </div>
                    <div class="visit-add-modal__field">
                        <label class="visit-add-modal__label" for="visitComplaint">Chief complaint <span class="req">*</span></label>
                        <input class="visit-add-modal__input" type="text" name="complaint" id="visitComplaint" placeholder="e.g. Headache, minor cut, wellness check" required maxlength="500">
                    </div>
                    <div class="visit-add-modal__field">
                        <label class="visit-add-modal__label" for="visitNotes">Clinical notes</label>
                        <textarea class="visit-add-modal__textarea" name="notes" id="visitNotes" placeholder="Observations, vitals, follow-up (optional)" maxlength="4000"></textarea>
                    </div>
                </div>
                <div class="visit-add-modal__section">
                    <div class="visit-add-modal__section-head">
                        <h3 class="visit-add-modal__section-title">Medication</h3>
                        <span class="visit-add-modal__section-hint">Optional</span>
                    </div>
                    <div class="visit-add-modal__field">
                        <label class="visit-add-modal__label" for="visitMedicineSearch">Medicine</label>
                        <div class="medlog-search-select" id="visitMedicineSelect" data-medlog-search-select data-allow-clear="1" data-placeholder="Search medicine or leave blank">
                            <div class="medlog-search-select__control">
                                <input type="text" id="visitMedicineSearch" class="medlog-search-select__input" autocomplete="off" placeholder="Search medicine or leave blank" aria-autocomplete="list" aria-controls="visitMedicineList" aria-expanded="false" role="combobox">
                                <span class="medlog-search-select__chevron" aria-hidden="true"></span>
                                <input type="hidden" name="med_id" id="visitMedicineValue" value="">
                                <ul class="medlog-search-select__dropdown" id="visitMedicineList" role="listbox" hidden></ul>
                            </div>
                            <div class="medlog-search-select__actions">
                                <button type="button" class="medlog-search-select__clear" id="visitMedicineClear" hidden>Clear medicine</button>
                            </div>
                        </div>
                    </div>
                    <div class="visit-add-modal__field visit-add-modal__quantity-wrap is-disabled" id="visitQuantityWrap">
                        <label class="visit-add-modal__label" for="visitQuantity">Quantity dispensed</label>
                        <input class="visit-add-modal__input" type="number" name="quantity" id="visitQuantity" min="1" step="1" value="" placeholder="Optional" disabled>
                    </div>
                </div>
            </div>
            <div class="visit-add-modal__footer">
                <button type="submit" class="visit-add-modal__submit">Save visit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const MEDLOG_PATIENT_ITEMS = <?= $patientPickerJson ?: '[]' ?>;
    const MEDLOG_MEDICINE_ITEMS = <?= $medicinePickerJson ?: '[]' ?>;

    const visitModal = document.getElementById("visitModal");
    const viewModal = document.getElementById("viewModal");
    const printLink = document.getElementById("visitPrintLink");
    const addVisitForm = document.getElementById("addVisitForm");
    const medHidden = document.getElementById("visitMedicineValue");
    const qtyInput = document.getElementById("visitQuantity");
    const qtyWrap = document.getElementById("visitQuantityWrap");
    const medClearBtn = document.getElementById("visitMedicineClear");

    function setModalOpen(modal, open) {
        if (!modal) return;
        modal.classList.toggle("show", open);
        modal.setAttribute("aria-hidden", open ? "false" : "true");
    }

    document.getElementById("openVisitModal").onclick = () => setModalOpen(visitModal, true);

    document.querySelectorAll(".closeVisit").forEach((btn) => {
        btn.addEventListener("click", () => setModalOpen(visitModal, false));
    });

    document.querySelectorAll(".closeView").forEach((btn) => {
        btn.addEventListener("click", () => setModalOpen(viewModal, false));
    });

    window.addEventListener("click", (e) => {
        if (e.target === visitModal) setModalOpen(visitModal, false);
        if (e.target === viewModal) setModalOpen(viewModal, false);
    });

    function syncMedicineQuantityUi() {
        const hasMed = !!(medHidden && medHidden.value.trim());
        if (qtyWrap && qtyInput) {
            qtyWrap.classList.toggle("is-disabled", !hasMed);
            qtyInput.disabled = !hasMed;
            if (!hasMed) {
                qtyInput.value = "";
                qtyInput.removeAttribute("required");
            } else {
                qtyInput.setAttribute("required", "required");
                qtyInput.setAttribute("min", "1");
            }
        }
        if (medClearBtn) {
            medClearBtn.hidden = !hasMed;
        }
    }

    if (medHidden) {
        medHidden.addEventListener("change", syncMedicineQuantityUi);
    }
    if (medClearBtn) {
        medClearBtn.addEventListener("click", () => {
            const root = document.getElementById("visitMedicineSelect");
            const input = root && root.querySelector(".medlog-search-select__input");
            if (medHidden) medHidden.value = "";
            if (input) input.value = "";
            medHidden && medHidden.dispatchEvent(new Event("change", { bubbles: true }));
        });
    }

    if (addVisitForm) {
        addVisitForm.addEventListener("submit", (e) => {
            const hasMed = !!(medHidden && medHidden.value.trim());
            if (hasMed) {
                const q = parseInt(String(qtyInput && qtyInput.value), 10);
                if (!q || q < 1) {
                    e.preventDefault();
                    qtyInput && qtyInput.focus();
                    return;
                }
            }
        });
    }

    function norm(s) {
        return String(s || "")
            .toLowerCase()
            .trim();
    }

    function mountSearchSelect(root, items) {
        if (!root || !items) return;
        const placeholder = root.getAttribute("data-placeholder") || "Search";
        const hidden = root.querySelector('input[type="hidden"][name]');
        const input = root.querySelector(".medlog-search-select__input");
        const list = root.querySelector(".medlog-search-select__dropdown");
        if (!hidden || !input || !list) return;

        let filtered = items.slice();
        let activeIndex = -1;

        function setOpen(open) {
            root.classList.toggle("is-open", open);
            list.hidden = !open;
            input.setAttribute("aria-expanded", open ? "true" : "false");
        }

        function filterItems(q) {
            const n = norm(q);
            if (!n) return items.slice();
            return items.filter((it) => norm(it.label).includes(n));
        }

        function renderList() {
            list.innerHTML = "";
            const slice = filtered.slice(0, 200);
            slice.forEach((it, i) => {
                const li = document.createElement("li");
                li.setAttribute("role", "option");
                li.className = "medlog-search-select__option";
                li.textContent = it.label;
                li.dataset.id = it.id;
                if (i === activeIndex) li.classList.add("is-active");
                li.addEventListener("mousedown", (ev) => {
                    ev.preventDefault();
                    pick(it);
                });
                list.appendChild(li);
            });
            if (!slice.length) {
                const li = document.createElement("li");
                li.className = "medlog-search-select__empty";
                li.textContent = "No matches";
                list.appendChild(li);
            }
        }

        function pick(it) {
            if (it) {
                hidden.value = it.id;
                input.value = it.label;
            } else {
                hidden.value = "";
                input.value = "";
            }
            hidden.dispatchEvent(new Event("change", { bubbles: true }));
            setOpen(false);
            activeIndex = -1;
        }

        function openList() {
            const selected = items.find((x) => String(x.id) === String(hidden.value));
            if (selected && !norm(input.value)) {
                input.value = selected.label;
            }
            filtered = filterItems(input.value);
            activeIndex = filtered.length ? 0 : -1;
            renderList();
            setOpen(true);
        }

        input.setAttribute("placeholder", placeholder);

        input.addEventListener("focus", () => {
            openList();
        });

        input.addEventListener("input", () => {
            const selected = items.find((x) => String(x.id) === String(hidden.value));
            if (!selected || input.value !== selected.label) {
                hidden.value = "";
                hidden.dispatchEvent(new Event("change", { bubbles: true }));
            }
            filtered = filterItems(input.value);
            activeIndex = filtered.length ? 0 : -1;
            renderList();
            setOpen(true);
        });

        input.addEventListener("keydown", (ev) => {
            if (ev.key === "Escape") {
                ev.stopPropagation();
                setOpen(false);
                input.blur();
                return;
            }
            if (ev.key === "ArrowDown") {
                ev.preventDefault();
                if (list.hidden) openList();
                if (filtered.length) {
                    activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
                    renderList();
                    scrollActiveIntoView();
                }
                return;
            }
            if (ev.key === "ArrowUp") {
                ev.preventDefault();
                if (filtered.length) {
                    activeIndex = Math.max(activeIndex - 1, 0);
                    renderList();
                    scrollActiveIntoView();
                }
                return;
            }
            if (ev.key === "Enter") {
                if (!list.hidden && filtered[activeIndex]) {
                    ev.preventDefault();
                    pick(filtered[activeIndex]);
                }
            }
        });

        function scrollActiveIntoView() {
            const el = list.querySelector(".medlog-search-select__option.is-active");
            if (el) el.scrollIntoView({ block: "nearest" });
        }

        document.addEventListener("click", (ev) => {
            if (!root.contains(ev.target)) setOpen(false);
        });
    }

    mountSearchSelect(document.getElementById("visitPatientSelect"), MEDLOG_PATIENT_ITEMS);
    mountSearchSelect(document.getElementById("visitMedicineSelect"), MEDLOG_MEDICINE_ITEMS);

    syncMedicineQuantityUi();

    function openViewModal(data) {
        const esc = (v) => {
            const s = v === undefined || v === null ? "" : String(v);
            return s
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        };
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
        const patientName = data.name || "Unknown patient";
        const patientRole = data.patient_role ? ` (${esc(data.patient_role)})` : "";
        if (printLink) {
            printLink.href = data.visit_id ? `print_visit.php?id=${encodeURIComponent(data.visit_id)}` : "#";
            printLink.toggleAttribute("aria-disabled", !data.visit_id);
        }
        document.getElementById("viewContent").innerHTML = `
        <div class="visit-details-modal__rows">
            <div class="visit-details-modal__row">
                <span class="visit-details-modal__icon"><i class="fa-regular fa-id-card" aria-hidden="true"></i></span>
                <span class="visit-details-modal__label">Patient</span>
                <strong class="visit-details-modal__value">${esc(patientName)}${patientRole}</strong>
            </div>
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

        setModalOpen(viewModal, true);
    }

    function parseVisitDataset(card) {
        try {
            return JSON.parse(card.dataset.visit);
        } catch (e) {
            return null;
        }
    }

    document.querySelectorAll(".visit-card[data-visit]").forEach((card) => {
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

    document.querySelectorAll(".visit-details-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const card = btn.closest(".visit-card");
            if (!card) return;
            const data = parseVisitDataset(card);
            if (data) openViewModal(data);
        });
    });

    function normalizeRoleForFilter(value) {
        let role = norm(value).replace(/[^a-z]/g, "");
        if (role === "students") role = "student";
        if (role === "teachers") role = "teacher";
        return role;
    }

    document.querySelectorAll(".visit-filter-chip").forEach((chip) => {
        chip.addEventListener("click", () => {
            const filter = normalizeRoleForFilter(chip.dataset.filter || "all");
            document.querySelectorAll(".visit-filter-chip").forEach((c) => c.classList.remove("active"));
            chip.classList.add("active");
            document.querySelectorAll(".visit-card[data-visit]").forEach((card) => {
                const role = normalizeRoleForFilter(card.dataset.patientRole);
                card.hidden = !(filter === "all" || role === filter);
            });
        });
    });

    const visitCards = document.querySelectorAll(".visit-card");

    if ("IntersectionObserver" in window) {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                });
            },
            {
                threshold: 0.12,
                rootMargin: "0px 0px -24px 0px",
            }
        );

        visitCards.forEach((card) => revealObserver.observe(card));
    } else {
        visitCards.forEach((card) => card.classList.add("is-visible"));
    }
})();
</script>

</body>
</html>