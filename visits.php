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
      AND status = 'active'
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
    $roleSlug = strtolower(trim((string) ($visit['patient_role'] ?? '')));
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
                        <div class="medlog-search-select" id="visitPatientSelect" data-medlog-search-select data-placeholder="Search patient by name…">
                            <div class="medlog-search-select__control">
                                <input type="text" id="visitPatientSearch" class="medlog-search-select__input" autocomplete="off" placeholder="Search patient by name…" aria-autocomplete="list" aria-controls="visitPatientList" aria-expanded="false" role="combobox">
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
                        <textarea class="visit-add-modal__textarea" name="notes" id="visitNotes" placeholder="Observations, vitals, follow-up (optional)…" maxlength="4000"></textarea>
                    </div>
                </div>
                <div class="visit-add-modal__section">
                    <div class="visit-add-modal__section-head">
                        <h3 class="visit-add-modal__section-title">Medication</h3>
                        <span class="visit-add-modal__section-hint">Optional</span>
                    </div>
                    <div class="visit-add-modal__field">
                        <label class="visit-add-modal__label" for="visitMedicineSearch">Medicine</label>
                        <div class="medlog-search-select" id="visitMedicineSelect" data-medlog-search-select data-allow-clear="1" data-placeholder="Search medicine or leave blank…">
                            <div class="medlog-search-select__control">
                                <input type="text" id="visitMedicineSearch" class="medlog-search-select__input" autocomplete="off" placeholder="Search medicine or leave blank…" aria-autocomplete="list" aria-controls="visitMedicineList" aria-expanded="false" role="combobox">
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
                        <input class="visit-add-modal__input" type="number" name="quantity" id="visitQuantity" min="1" step="1" value="" placeholder="—" disabled>
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
        const placeholder = root.getAttribute("data-placeholder") || "Search…";
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
        document.getElementById("viewContent").innerHTML = `
        <p><strong>Patient:</strong> ${esc(data.name)}</p>
        <p><strong>Date:</strong> ${esc(data.visit_date)}</p>
        <p><strong>Recorded By:</strong> ${esc(data.recorded_by)}</p>
        <hr>
        <p><strong>Complaint:</strong> ${esc(data.complaint)}</p>
        <p><strong>Treatment:</strong> ${esc(data.medicines_used || "None")}</p>
        <p><strong>Notes:</strong> ${esc(data.notes || "None")}</p>

        <br>

        <a href="print_visit.php?id=${encodeURIComponent(String(data.visit_id))}" target="_blank" class="visit-print-link">
           🖨 Print Visit
        </a>
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

    document.querySelectorAll(".visit-filter-chip").forEach((chip) => {
        chip.addEventListener("click", () => {
            const filter = chip.dataset.filter || "all";
            document.querySelectorAll(".visit-filter-chip").forEach((c) => c.classList.remove("active"));
            chip.classList.add("active");
            document.querySelectorAll(".visit-card[data-visit]").forEach((card) => {
                const role = norm(card.dataset.patientRole);
                const show = filter === "all" || role === norm(filter);
                card.hidden = !show;
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
