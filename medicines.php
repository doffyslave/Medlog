<?php
require 'Database/connection.php';
session_start();

$user = $_SESSION['user'] ?? null;
$role = strtolower((string) ($user['role'] ?? 'guest'));
$isAdmin = $role === 'admin';

$stmt = $conn->query("
    SELECT
        m.med_id,
        m.medicine_name,
        m.total_quantity,
        MAX(s.stock_id) AS latest_stock_id,
        MAX(s.expiration_date) AS latest_expiration,
        COUNT(s.stock_id) AS stock_events,
        COALESCE(SUM(CASE WHEN s.quantity > 0 THEN s.quantity ELSE 0 END), 0) AS lifetime_stock_in
    FROM medicines m
    LEFT JOIN stocks s ON s.med_id = m.med_id
    GROUP BY m.med_id, m.medicine_name, m.total_quantity
    ORDER BY m.medicine_name ASC
");
$rawMedicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recentCandidates = [];
foreach ($rawMedicines as $m) {
    $recentCandidates[] = [
        'med_id' => (int) $m['med_id'],
        'latest_stock_id' => (int) ($m['latest_stock_id'] ?? 0),
    ];
}
usort($recentCandidates, function ($a, $b) {
    return $b['latest_stock_id'] <=> $a['latest_stock_id'];
});
$recentMap = [];
foreach (array_slice($recentCandidates, 0, 6) as $recentItem) {
    if ($recentItem['latest_stock_id'] > 0) {
        $recentMap[$recentItem['med_id']] = true;
    }
}

$medicines = [];
$stats = [
    'total' => 0,
    'available' => 0,
    'limited' => 0,
    'out' => 0,
];
$lowStockItems = [];

foreach ($rawMedicines as $row) {
    $qty = (int) ($row['total_quantity'] ?? 0);
    $stockEvents = (int) ($row['stock_events'] ?? 0);
    $lifetimeStockIn = (int) ($row['lifetime_stock_in'] ?? 0);
    $latestExpiration = $row['latest_expiration'] ?: null;
    $today = date('Y-m-d');

    $statusKey = 'available';
    $statusAdmin = 'Available';
    $statusPublic = 'Available';
    if ($qty <= 0) {
        $statusKey = 'out';
        $statusAdmin = 'Out of stock';
        $statusPublic = 'Out of stock';
    } elseif ($qty <= 10) {
        $statusKey = 'limited';
        $statusAdmin = 'Low stock';
        $statusPublic = 'Limited';
    }

    $expiryState = 'none';
    if ($latestExpiration !== null) {
        if ($latestExpiration < $today) {
            $expiryState = 'expired';
        } elseif ($latestExpiration <= date('Y-m-d', strtotime('+30 days'))) {
            $expiryState = 'soon';
        } else {
            $expiryState = 'ok';
        }
    }

    $progress = max(0, min(100, (int) round(($qty / 50) * 100)));
    $description = 'Clinic-use medication entry for consultations and routine care.';
    $initial = strtoupper(substr((string) $row['medicine_name'], 0, 1));
    if ($initial === '') {
        $initial = 'M';
    }

    $entry = [
        'id' => (int) $row['med_id'],
        'name' => (string) $row['medicine_name'],
        'quantity' => $qty,
        'status_key' => $statusKey,
        'status_admin' => $statusAdmin,
        'status_public' => $statusPublic,
        'progress' => $progress,
        'description' => $description,
        'initial' => $initial,
        'latest_stock_id' => (int) ($row['latest_stock_id'] ?? 0),
        'latest_expiration' => $latestExpiration,
        'expiry_state' => $expiryState,
        'stock_events' => $stockEvents,
        'lifetime_stock_in' => $lifetimeStockIn,
        'recently_updated' => !empty($recentMap[(int) $row['med_id']]),
    ];
    $medicines[] = $entry;

    $stats['total']++;
    if ($statusKey === 'available') {
        $stats['available']++;
    } elseif ($statusKey === 'limited') {
        $stats['limited']++;
    } else {
        $stats['out']++;
    }

    if ($statusKey === 'limited' || $statusKey === 'out') {
        $lowStockItems[] = $entry;
    }
}

$medicinesJson = json_encode($medicines, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

ob_start();
if ($isAdmin) {
    echo '<button type="button" id="openAddMedicineBtn" class="add-btn">+ Add Medicine</button>';
}
$__medsActions = ob_get_clean();
$medlogPageHeader = [
    'title' => 'Medicines',
    'subtitle' => $isAdmin
        ? 'Premium inventory workspace for formulary and stock flow.'
        : 'Browse clinic medicines and availability information.',
    'icon' => 'medicines',
    'class' => 'medlog-page-header--medicines',
    'actions' => $__medsActions,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medicines</title>
<link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
<link rel="stylesheet" href="Css/medicines.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body<?= $role === 'student' ? ' class="medlog-student-shell"' : '' ?>>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<?php include 'includes/header.php'; ?>
<section class="content medicines-page">
<?php include 'includes/medlog-page-header.php'; ?>

<div class="meds-shell">
    <?php if ($isAdmin): ?>
        <div class="meds-stats">
            <article class="meds-stat">
                <span class="meds-stat__label">Total medicines</span>
                <strong class="meds-stat__value"><?= (int) $stats['total'] ?></strong>
            </article>
            <article class="meds-stat">
                <span class="meds-stat__label">Available</span>
                <strong class="meds-stat__value"><?= (int) $stats['available'] ?></strong>
            </article>
            <article class="meds-stat">
                <span class="meds-stat__label">Low stock</span>
                <strong class="meds-stat__value"><?= (int) $stats['limited'] ?></strong>
            </article>
            <article class="meds-stat">
                <span class="meds-stat__label">Out of stock</span>
                <strong class="meds-stat__value"><?= (int) $stats['out'] ?></strong>
            </article>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin && !empty($lowStockItems)): ?>
        <section class="meds-alerts" aria-label="Low stock alerts">
            <div class="meds-alerts__head">
                <span class="meds-alerts__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <h2>Low Stock Alerts</h2>
                    <p>Medicines requiring attention and replenishment.</p>
                </div>
            </div>
            <div class="meds-alerts__items">
                <?php foreach (array_slice($lowStockItems, 0, 5) as $alert): ?>
                    <button
                        type="button"
                        class="meds-alert-pill"
                        data-action="view-details"
                        data-id="<?= (int) $alert['id'] ?>"
                    >
                        <span><?= htmlspecialchars($alert['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= (int) $alert['quantity'] ?> left</strong>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="meds-controls" aria-label="Search and filters">
        <label class="meds-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="medicineSearch" placeholder="Search medicine..." autocomplete="off">
        </label>
        <div class="meds-filters" role="group" aria-label="Filter medicines">
            <button type="button" class="meds-filter-chip active" data-filter="all">All</button>
            <button type="button" class="meds-filter-chip" data-filter="available">Available</button>
            <button type="button" class="meds-filter-chip" data-filter="limited">Low Stock</button>
            <button type="button" class="meds-filter-chip" data-filter="out">Out of Stock</button>
            <button type="button" class="meds-filter-chip" data-filter="recent">Recently Updated</button>
        </div>
    </section>

    <section class="meds-grid" id="medicinesGrid">
        <?php foreach ($medicines as $med): ?>
            <article
                class="med-card"
                data-med-id="<?= (int) $med['id'] ?>"
                data-status="<?= htmlspecialchars($med['status_key'], ENT_QUOTES, 'UTF-8') ?>"
                data-recent="<?= $med['recently_updated'] ? '1' : '0' ?>"
                data-search="<?= htmlspecialchars(strtolower($med['name'] . ' ' . $med['description'] . ' ' . $med['status_admin']), ENT_QUOTES, 'UTF-8') ?>"
            >
                <div class="med-card__head">
                    <span class="med-card__icon"><?= htmlspecialchars($med['initial'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="med-status med-status--<?= htmlspecialchars($med['status_key'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($isAdmin ? $med['status_admin'] : $med['status_public'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <h3 class="med-card__name"><?= htmlspecialchars($med['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="med-card__desc"><?= htmlspecialchars($med['description'], ENT_QUOTES, 'UTF-8') ?></p>

                <?php if ($isAdmin): ?>
                    <div class="med-stock">
                        <div class="med-stock__top">
                            <span>Stock quantity</span>
                            <strong><?= (int) $med['quantity'] ?></strong>
                        </div>
                        <div class="med-progress" role="progressbar" aria-valuenow="<?= (int) $med['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                            <span style="width: <?= (int) $med['progress'] ?>%"></span>
                        </div>
                    </div>
                    <div class="med-card__actions">
                        <button type="button" class="med-btn med-btn--ghost" data-action="view-details" data-id="<?= (int) $med['id'] ?>">View Details</button>
                        <button type="button" class="med-btn med-btn--ghost" data-action="edit" data-id="<?= (int) $med['id'] ?>">Edit</button>
                        <button type="button" class="med-btn med-btn--primary" data-action="restock" data-id="<?= (int) $med['id'] ?>">Restock</button>
                    </div>
                <?php else: ?>
                    <div class="med-card__actions">
                        <button type="button" class="med-btn med-btn--ghost" data-action="view-details" data-id="<?= (int) $med['id'] ?>">View Details</button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="med-empty" id="medicinesEmpty" hidden>
        <p>No medicines matched your search or filters.</p>
    </div>
</div>

</section>
</main>
</div>

<div class="med-overlay" id="medOverlay" hidden></div>

<aside class="med-drawer" id="medicineDrawer" aria-hidden="true">
    <button type="button" class="med-drawer__close" data-close-drawer>&times;</button>
    <div class="med-drawer__content" id="medicineDrawerContent"></div>
</aside>

<?php if ($isAdmin): ?>
<div class="med-modal" id="addMedicineModal" aria-hidden="true">
    <div class="med-modal__card">
        <button type="button" class="med-modal__close" data-close-modal="addMedicineModal">&times;</button>
        <h2>Add Medicine</h2>
        <form method="POST" action="Database/add_medicine.php" class="med-form">
            <label>Medicine name</label>
            <input type="text" name="medicine_name" required maxlength="255" placeholder="Enter medicine name">
            <button type="submit" class="med-btn med-btn--primary">Save Medicine</button>
        </form>
    </div>
</div>

<div class="med-modal" id="editMedicineModal" aria-hidden="true">
    <div class="med-modal__card">
        <button type="button" class="med-modal__close" data-close-modal="editMedicineModal">&times;</button>
        <h2>Edit Medicine</h2>
        <form method="POST" action="Database/edit_medicine.php" class="med-form" id="editMedicineForm">
            <input type="hidden" name="id" id="editMedicineId">
            <label>Medicine name</label>
            <input type="text" name="medicine_name" id="editMedicineName" required maxlength="255">
            <button type="submit" class="med-btn med-btn--primary">Update Medicine</button>
        </form>
    </div>
</div>

<div class="med-modal" id="restockModal" aria-hidden="true">
    <div class="med-modal__card">
        <button type="button" class="med-modal__close" data-close-modal="restockModal">&times;</button>
        <h2>Restock Medicine</h2>
        <form method="POST" action="Database/add_stocks.php" class="med-form" id="restockForm">
            <label for="restockMedicine">Medicine</label>
            <select name="med_id" id="restockMedicine" required>
                <option value="">Select medicine</option>
                <?php foreach ($medicines as $med): ?>
                    <option value="<?= (int) $med['id'] ?>"><?= htmlspecialchars($med['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="restockQuantity">Quantity to add</label>
            <input type="number" name="quantity" id="restockQuantity" required min="1" step="1" placeholder="e.g. 20">

            <label for="restockExpiration">Expiration date</label>
            <input type="date" name="expiration_date" id="restockExpiration">

            <label for="restockNotes">Notes (optional)</label>
            <textarea id="restockNotes" rows="3" placeholder="Internal note for nurse/admin handoff"></textarea>
            <small class="med-hint">Notes are for on-screen workflow only and are not stored in current DB schema.</small>

            <button type="submit" class="med-btn med-btn--primary">Save Restock</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
    const MEDS = <?= $medicinesJson ?: '[]' ?>;

    const searchInput = document.getElementById('medicineSearch');
    const filterChips = document.querySelectorAll('.meds-filter-chip');
    const cards = document.querySelectorAll('.med-card');
    const emptyState = document.getElementById('medicinesEmpty');
    const overlay = document.getElementById('medOverlay');
    const drawer = document.getElementById('medicineDrawer');
    const drawerContent = document.getElementById('medicineDrawerContent');
    let activeFilter = 'all';

    function byId(id) {
        return MEDS.find((m) => String(m.id) === String(id)) || null;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function applyFilters() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;
        cards.forEach((card) => {
            const status = card.dataset.status || '';
            const recent = card.dataset.recent === '1';
            const searchBlob = card.dataset.search || '';

            const statusMatch =
                activeFilter === 'all' ||
                (activeFilter === 'recent' ? recent : status === activeFilter);
            const queryMatch = !q || searchBlob.includes(q);

            const show = statusMatch && queryMatch;
            card.hidden = !show;
            if (show) visibleCount += 1;
        });
        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    filterChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            activeFilter = chip.dataset.filter || 'all';
            filterChips.forEach((c) => c.classList.remove('active'));
            chip.classList.add('active');
            applyFilters();
        });
    });

    function openOverlay() {
        if (!overlay) return;
        overlay.hidden = false;
        requestAnimationFrame(() => overlay.classList.add('show'));
    }

    function closeOverlay() {
        if (!overlay) return;
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.hidden = true;
        }, 180);
    }

    function openDrawer(med) {
        if (!drawer || !drawerContent || !med) return;
        const expiryLabel = med.latest_expiration ? med.latest_expiration : 'No expiry data';
        const expiryBadge = med.expiry_state === 'expired'
            ? 'Expired'
            : med.expiry_state === 'soon'
                ? 'Expiring soon'
                : med.expiry_state === 'ok'
                    ? 'Valid'
                    : 'No expiry';

        drawerContent.innerHTML = `
            <div class="med-drawer__hero">
                <span class="med-card__icon">${escapeHtml(med.initial)}</span>
                <div>
                    <h3>${escapeHtml(med.name)}</h3>
                    <p>${escapeHtml(med.description)}</p>
                </div>
            </div>
            <div class="med-drawer__meta">
                <div><span>Status</span><strong>${escapeHtml(IS_ADMIN ? med.status_admin : med.status_public)}</strong></div>
                <div><span>Expiry</span><strong>${escapeHtml(expiryBadge)}</strong></div>
                <div><span>Latest expiry date</span><strong>${escapeHtml(expiryLabel)}</strong></div>
                ${IS_ADMIN ? `<div><span>Stock quantity</span><strong>${escapeHtml(med.quantity)}</strong></div>` : ''}
                ${IS_ADMIN ? `<div><span>Restock entries</span><strong>${escapeHtml(med.stock_events)}</strong></div>` : ''}
                ${IS_ADMIN ? `<div><span>Total restocked</span><strong>${escapeHtml(med.lifetime_stock_in)}</strong></div>` : ''}
            </div>
            ${IS_ADMIN ? `
                <div class="med-drawer__actions">
                    <button type="button" class="med-btn med-btn--ghost" data-action="edit" data-id="${escapeHtml(med.id)}">Edit</button>
                    <button type="button" class="med-btn med-btn--primary" data-action="restock" data-id="${escapeHtml(med.id)}">Restock</button>
                </div>
            ` : ''}
        `;

        openOverlay();
        drawer.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');
        closeOverlay();
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const action = button.dataset.action;
        const med = byId(button.dataset.id);
        if (!med) return;

        if (action === 'view-details') {
            openDrawer(med);
            return;
        }

        if (!IS_ADMIN) return;
        if (action === 'edit') {
            openEditModal(med);
        } else if (action === 'restock') {
            openRestockModal(med);
        }
    });

    document.querySelector('[data-close-drawer]')?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', () => {
        closeDrawer();
        closeModal('addMedicineModal');
        closeModal('editMedicineModal');
        closeModal('restockModal');
    });

    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        openOverlay();
        el.classList.add('show');
        el.setAttribute('aria-hidden', 'false');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        closeOverlay();
    }

    if (IS_ADMIN) {
        document.getElementById('openAddMedicineBtn')?.addEventListener('click', () => openModal('addMedicineModal'));
        document.querySelectorAll('[data-close-modal]').forEach((btn) => {
            btn.addEventListener('click', () => closeModal(btn.dataset.closeModal || ''));
        });
    }

    function openEditModal(med) {
        const idInput = document.getElementById('editMedicineId');
        const nameInput = document.getElementById('editMedicineName');
        if (!idInput || !nameInput) return;
        idInput.value = med.id;
        nameInput.value = med.name;
        openModal('editMedicineModal');
    }

    function openRestockModal(med) {
        const medSelect = document.getElementById('restockMedicine');
        if (medSelect) medSelect.value = String(med.id);
        openModal('restockModal');
    }

    applyFilters();
})();
</script>

</body>
</html>









