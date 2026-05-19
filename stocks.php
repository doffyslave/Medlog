<?php
session_start();

$user = $_SESSION['user'] ?? null;

if (!$user || $user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

require 'Database/connection.php';

$colStmt = $conn->query('SHOW COLUMNS FROM stocks');
$stockColumns = $colStmt ? $colStmt->fetchAll(PDO::FETCH_COLUMN) : [];
$hasCreatedAt = is_array($stockColumns) && in_array('created_at', $stockColumns, true);

$stmt = $conn->query("
    SELECT stocks.*, medicines.medicine_name
    FROM stocks
    LEFT JOIN medicines ON stocks.med_id = medicines.med_id
    ORDER BY stocks.stock_id DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

$feedItems = [];
$totalTx = count($rows);
$adjustmentTx = 0;
$stockInToday = 0;

foreach ($rows as $row) {
    $qty = (int) ($row['quantity'] ?? 0);
    $type = $qty < 0 ? 'adjustment' : 'stock_in';
    $createdRaw = isset($row['created_at']) ? (string) $row['created_at'] : '';
    $createdDate = $createdRaw !== '' ? substr($createdRaw, 0, 10) : '';
    $exp = isset($row['expiration_date']) && $row['expiration_date'] !== '' && $row['expiration_date'] !== null
        ? (string) $row['expiration_date']
        : '';

    if ($qty < 0) {
        $adjustmentTx++;
    }

    if ($qty > 0) {
        if ($hasCreatedAt && $createdDate === $today) {
            $stockInToday++;
        } elseif (!$hasCreatedAt && $exp === $today) {
            $stockInToday++;
        }
    }

    $inWeek = false;
    if ($hasCreatedAt && $createdDate !== '') {
        $inWeek = ($createdDate >= $weekStart && $createdDate <= $weekEnd);
    } elseif (!$hasCreatedAt && $exp !== '') {
        $inWeek = ($exp >= $weekStart && $exp <= $weekEnd);
    }

    $isTodayChip = ($hasCreatedAt && $createdDate === $today)
        || (!$hasCreatedAt && $exp === $today && $qty > 0);

    $feedItems[] = [
        'stock_id' => (int) ($row['stock_id'] ?? 0),
        'med_id' => (int) ($row['med_id'] ?? 0),
        'medicine_name' => (string) ($row['medicine_name'] ?? 'Unknown'),
        'quantity' => $qty,
        'expiration_date' => $exp,
        'created_at' => $createdRaw !== '' ? $createdRaw : null,
        'type' => $type,
        'label' => $type === 'adjustment' ? 'Adjustment' : 'Stock In',
        'in_week' => $inWeek,
        'date_for_day_filter' => $hasCreatedAt ? $createdDate : $exp,
        'is_today' => $isTodayChip,
    ];
}

$itemsJson = json_encode($feedItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$medsStmt = $conn->query('SELECT med_id, medicine_name FROM medicines ORDER BY medicine_name ASC');
$medicinesList = $medsStmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<button type="button" id="openStockMovementModal" class="add-btn">+ Record movement</button>
<?php
$__stocksActions = ob_get_clean();
$medlogPageHeader = [
    'title' => 'Stocks inventory',
    'subtitle' => 'Audit trail for inbound supply and inventory corrections.',
    'icon' => 'stocks',
    'class' => 'medlog-page-header--stocks',
    'actions' => $__stocksActions,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stocks Inventory</title>
<link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
<link rel="stylesheet" href="Css/stocks-page.css?v=20260517-mobile-fix">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content stocks-page">

<?php include 'includes/medlog-page-header.php'; ?>

<div class="stocks-shell" id="stocksShell">
    <div class="stocks-stats">
        <article class="stocks-stat">
            <span class="stocks-stat__label">Total transactions</span>
            <strong class="stocks-stat__value"><?= (int) $totalTx ?></strong>
        </article>
        <article class="stocks-stat">
            <span class="stocks-stat__label">Stock in today</span>
            <strong class="stocks-stat__value"><?= (int) $stockInToday ?></strong>
            <?php if (!$hasCreatedAt): ?>
                <span class="stocks-stat__hint">Uses expiry date as proxy until <code>created_at</code> exists on <code>stocks</code>.</span>
            <?php endif; ?>
        </article>
        <article class="stocks-stat">
            <span class="stocks-stat__label">Adjustments</span>
            <strong class="stocks-stat__value"><?= (int) $adjustmentTx ?></strong>
            <span class="stocks-stat__hint">Historical deductions / corrections.</span>
        </article>
    </div>

    <section class="stocks-controls" aria-label="Search and filters">
        <label class="stocks-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="stockSearchInput" placeholder="Search medicine, type, date, quantityâ€¦" autocomplete="off">
        </label>
        <div class="stocks-filters" role="group" aria-label="Filter activity">
            <button type="button" class="stocks-filter-chip active" data-stock-filter="all">All activity</button>
            <button type="button" class="stocks-filter-chip" data-stock-filter="stock_in">Stock In</button>
            <button type="button" class="stocks-filter-chip" data-stock-filter="adjustment">Adjustments</button>
            <button type="button" class="stocks-filter-chip" data-stock-filter="today">Today</button>
            <button type="button" class="stocks-filter-chip" data-stock-filter="week">This week</button>
        </div>
    </section>

    <div class="stocks-layout">
        <div class="stocks-feed" id="stockFeed">
            <?php if (!empty($feedItems)): ?>
                <?php foreach ($feedItems as $index => $entry): ?>
                    <?php
                        $isOut = $entry['type'] === 'adjustment';
                        $cardClass = $isOut ? 'stock-card--out' : 'stock-card--in';
                        $badgeClass = $isOut ? 'stock-card__badge--out' : 'stock-card__badge--in';
                        $qtyClass = $isOut ? 'stock-card__qty--out' : 'stock-card__qty--in';
                        $qtyPrefix = $entry['quantity'] > 0 ? '+' : '';
                        $expDisplay = $entry['expiration_date'] !== '' ? $entry['expiration_date'] : 'â€”';
                        $tsDisplay = $entry['created_at']
                            ? date('g:i A Â· M j, Y', strtotime($entry['created_at']))
                            : ($hasCreatedAt ? 'â€”' : 'Not tracked');
                        $searchBlob = strtolower(
                            $entry['medicine_name'] . ' ' .
                            $entry['label'] . ' ' .
                            $entry['expiration_date'] . ' ' .
                            $entry['quantity'] . ' ' .
                            ($entry['created_at'] ?? '')
                        );
                        $payload = htmlspecialchars(json_encode($entry), ENT_QUOTES, 'UTF-8');
                    ?>
                    <article
                        class="stock-card <?= $cardClass ?>"
                        style="--stagger-delay: <?= (($index % 14) * 45) ?>ms;"
                        tabindex="0"
                        role="button"
                        data-stock="<?= $payload ?>"
                        data-type="<?= htmlspecialchars($entry['type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
                        data-today="<?= !empty($entry['is_today']) ? '1' : '0' ?>"
                        data-week="<?= !empty($entry['in_week']) ? '1' : '0' ?>"
                    >
                        <div class="stock-card__rail" aria-hidden="true"></div>
                        <div class="stock-card__body">
                            <div class="stock-card__top">
                                <span class="stock-card__med"><?= htmlspecialchars($entry['medicine_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="stock-card__badge <?= $badgeClass ?>"><?= htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="stock-card__qty <?= $qtyClass ?>"><?= $qtyPrefix ?><?= (int) $entry['quantity'] ?> units</div>
                            <div class="stock-card__meta">
                                <span><i class="fa-regular fa-calendar"></i> Expires <?= htmlspecialchars($expDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($tsDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                <span>#<?= (int) $entry['stock_id'] ?></span>
                            </div>
                        </div>
                        <div class="stock-card__chev" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stock-empty" id="stockEmptyDefault">
                    <p>No stock movements recorded yet.</p>
                </div>
            <?php endif; ?>
            <div class="stock-empty" id="stockEmptyFiltered" hidden>
                <p>No transactions match your search or filters.</p>
            </div>
        </div>
    </div>
</div>

</section>
</main>
</div>

<div class="stock-overlay" id="stockOverlay" aria-hidden="true"></div>

<aside class="stock-drawer" id="stockDrawer" aria-hidden="true" aria-modal="true" role="dialog" aria-label="Stock transaction details">
    <button type="button" class="stock-drawer__close" id="stockDrawerClose" aria-label="Close">&times;</button>
    <div class="stock-drawer__scroll" id="stockDrawerInner"></div>
</aside>

<div class="stock-modal" id="stockMovementModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="stockMovementTitle">
    <div class="stock-modal__card">
        <button type="button" class="stock-modal__close" data-stock-close-modal aria-label="Close">&times;</button>
        <h2 id="stockMovementTitle">Record stock movement</h2>
        <p class="stock-modal__subtitle">Log inbound supply or corrective adjustments. Historical quantities stay immutable after save.</p>

        <form method="POST" action="Database/add_stocks.php" class="stock-form" id="stockMovementForm">
            <div>
                <label for="movementType">Transaction type</label>
                <select name="transaction_type" id="movementType" required>
                    <option value="stock_in">Stock In</option>
                    <option value="adjustment">Adjustment</option>
                </select>
                <p class="stock-form__hint stock-form__hint--warn" id="adjustmentHint">
                    Use adjustments for corrections or stock deductions. Quantity will be saved as a negative movement.
                </p>
            </div>

            <div>
                <label for="movementMedicine">Medicine</label>
                <select name="med_id" id="movementMedicine" required>
                    <option value="">Select medicine</option>
                    <?php foreach ($medicinesList as $med): ?>
                        <option value="<?= (int) $med['med_id'] ?>"><?= htmlspecialchars($med['medicine_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="movementQty">Quantity</label>
                <input type="number" name="quantity" id="movementQty" min="1" step="1" required placeholder="Enter units">
                <p class="stock-form__hint" id="qtyHint">Positive numbers only â€” type chooses inbound vs adjustment.</p>
            </div>

            <div>
                <label for="movementExpiry">Expiration date</label>
                <input type="date" name="expiration_date" id="movementExpiry">
                <p class="stock-form__hint">Optional; recommended for new batches.</p>
            </div>

            <div>
                <label for="movementNotes">Notes / reason</label>
                <textarea name="movement_notes" id="movementNotes" rows="3" placeholder="Internal note (not stored until DB supports it)"></textarea>
                <p class="stock-notes-hint">Optional. Add a <code>notes</code> column to <code>stocks</code> if you want this persisted.</p>
            </div>

            <button type="submit" class="stock-form__submit">Save movement</button>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('stockOverlay');
    const drawer = document.getElementById('stockDrawer');
    const drawerInner = document.getElementById('stockDrawerInner');
    const movementModal = document.getElementById('stockMovementModal');
    const stocksShell = document.getElementById('stocksShell');
    const searchInput = document.getElementById('stockSearchInput');
    const filterChips = document.querySelectorAll('[data-stock-filter]');
    const stockFeed = document.getElementById('stockFeed');

    function getStockCards() {
        return stockFeed ? stockFeed.querySelectorAll('.stock-card') : document.querySelectorAll('.stock-card');
    }

    let cards = getStockCards();
    const emptyFiltered = document.getElementById('stockEmptyFiltered');

    let activeFilter = 'all';
    let returnFocusAfterDrawer = null;
    let returnFocusAfterModal = null;

    function moveFocusOutOf(el) {
        if (!el || !(el instanceof HTMLElement)) return;
        const active = document.activeElement;
        if (active && el.contains(active)) {
            active.blur();
        }
    }

    function restoreFocus(target) {
        if (target && document.body.contains(target) && typeof target.focus === 'function') {
            try {
                target.focus({ preventScroll: true });
            } catch (e) {
                target.focus();
            }
            return;
        }
        document.getElementById('openStockMovementModal')?.focus({ preventScroll: true });
    }

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function parseStock(card) {
        try {
            return JSON.parse(card.dataset.stock);
        } catch (e) {
            return null;
        }
    }

    function setOverlay(open) {
        if (!overlay) return;
        overlay.classList.toggle('show', open);
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function openDrawer(entry) {
        if (!drawer || !drawerInner || !entry) return;

        const qtyLabel = (entry.quantity > 0 ? '+' : '') + entry.quantity + ' units';
        let recorded = '';
        if (entry.created_at) {
            const parsed = new Date(String(entry.created_at).replace(' ', 'T'));
            recorded = Number.isNaN(parsed.getTime())
                ? esc(entry.created_at)
                : esc(parsed.toLocaleString());
        } else {
            recorded = 'Not tracked â€” add <code>created_at</code> on <code>stocks</code> for precise timestamps.';
        }
        const summary = entry.type === 'adjustment'
            ? 'Inventory correction / deduction recorded against formulary levels.'
            : 'Inbound supply recorded toward formulary totals.';

        const expValue = entry.expiration_date ? esc(entry.expiration_date) : '';

        drawerInner.innerHTML = `
            <div class="stock-drawer__title" id="stockDrawerHeading">${esc(entry.medicine_name)}</div>
            <div class="stock-drawer__grid">
                <div class="stock-drawer__row"><span>Transaction</span><strong>${esc(entry.label)}</strong></div>
                <div class="stock-drawer__row"><span>Quantity change</span><strong>${esc(qtyLabel)}</strong></div>
                <div class="stock-drawer__row"><span>Expiration</span><strong>${entry.expiration_date ? esc(entry.expiration_date) : 'â€”'}</strong></div>
                <div class="stock-drawer__row"><span>Recorded</span><strong>${recorded}</strong></div>
                <div class="stock-drawer__row"><span>Stock ID</span><strong>#${esc(entry.stock_id)}</strong></div>
                <div class="stock-drawer__row"><span>Summary</span><strong>${esc(summary)}</strong></div>
            </div>
            <form class="stock-drawer__form" method="POST" action="Database/edit_stocks.php">
                <h4>Correct expiry only</h4>
                <input type="hidden" name="id" value="${esc(entry.stock_id)}">
                <label for="drawerExpiry">Expiration date</label>
                <input type="date" name="expiration_date" id="drawerExpiry" value="${expValue}">
                <button type="submit" class="stock-drawer__submit">Update expiration</button>
                <p class="stock-notes-hint">Medicine, quantity, and transaction type remain frozen for audit integrity.</p>
            </form>
        `;

        drawer.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');
        drawer.setAttribute('aria-labelledby', 'stockDrawerHeading');
        setOverlay(true);
        requestAnimationFrame(() => {
            const closeBtn = document.getElementById('stockDrawerClose');
            if (closeBtn && typeof closeBtn.focus === 'function') {
                closeBtn.focus();
            }
        });
    }

    function closeDrawer() {
        if (!drawer) return;
        const backTo = returnFocusAfterDrawer;
        returnFocusAfterDrawer = null;
        moveFocusOutOf(drawer);
        restoreFocus(backTo);
        drawer.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');
        drawer.removeAttribute('aria-labelledby');
        if (!movementModal?.classList.contains('show')) {
            setOverlay(false);
        }
    }

    function openMovementModal() {
        if (!movementModal) return;
        movementModal.classList.add('show');
        movementModal.setAttribute('aria-hidden', 'false');
        setOverlay(true);
        requestAnimationFrame(() => {
            const firstField = document.getElementById('movementType');
            if (firstField && typeof firstField.focus === 'function') {
                firstField.focus();
            }
        });
    }

    function closeMovementModal() {
        if (!movementModal) return;
        const backTo = returnFocusAfterModal;
        returnFocusAfterModal = null;
        moveFocusOutOf(movementModal);
        restoreFocus(backTo);
        movementModal.classList.remove('show');
        movementModal.setAttribute('aria-hidden', 'true');
        if (!drawer?.classList.contains('show')) {
            setOverlay(false);
        }
    }

    document.getElementById('openStockMovementModal')?.addEventListener('click', (e) => {
        returnFocusAfterModal = e.currentTarget;
        openMovementModal();
    });

    document.querySelectorAll('[data-stock-close-modal]').forEach((btn) => {
        btn.addEventListener('click', closeMovementModal);
    });

    document.getElementById('stockDrawerClose')?.addEventListener('click', closeDrawer);

    overlay?.addEventListener('click', () => {
        if (movementModal?.classList.contains('show')) {
            closeMovementModal();
        } else if (drawer?.classList.contains('show')) {
            closeDrawer();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (movementModal?.classList.contains('show')) {
            e.preventDefault();
            closeMovementModal();
            return;
        }
        if (drawer?.classList.contains('show')) {
            e.preventDefault();
            closeDrawer();
        }
    });

    const movementType = document.getElementById('movementType');
    const adjustmentHint = document.getElementById('adjustmentHint');

    function syncMovementHints() {
        const isAdj = movementType && movementType.value === 'adjustment';
        adjustmentHint?.classList.toggle('is-visible', Boolean(isAdj));
    }

    movementType?.addEventListener('change', syncMovementHints);
    syncMovementHints();

    function applyFilters() {
        cards = getStockCards();
        const q = (searchInput?.value || '').toLowerCase().trim();
        let visible = 0;

        cards.forEach((card) => {
            const blob = (card.getAttribute('data-search') || '').toLowerCase();
            const type = card.getAttribute('data-type') || '';

            const chipOk =
                activeFilter === 'all' ||
                (activeFilter === 'stock_in' && type === 'stock_in') ||
                (activeFilter === 'adjustment' && type === 'adjustment') ||
                (activeFilter === 'today' && card.getAttribute('data-today') === '1') ||
                (activeFilter === 'week' && card.getAttribute('data-week') === '1');

            const searchOk = !q || blob.includes(q);
            const show = chipOk && searchOk;
            card.hidden = !show;
            if (show) visible++;
        });

        if (emptyFiltered) {
            const hasCards = cards.length > 0;
            emptyFiltered.hidden = visible > 0 || !hasCards;
        }
    }

    function onSearchChange() {
        applyFilters();
    }

    searchInput?.addEventListener('input', onSearchChange);
    searchInput?.addEventListener('search', onSearchChange);

    stocksShell?.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-stock-filter]');
        if (!chip || !stocksShell.contains(chip)) return;
        e.preventDefault();
        activeFilter = chip.getAttribute('data-stock-filter') || 'all';
        filterChips.forEach((c) => c.classList.remove('active'));
        chip.classList.add('active');
        applyFilters();
    });

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            returnFocusAfterDrawer = card;
            const entry = parseStock(card);
            if (entry) openDrawer(entry);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            returnFocusAfterDrawer = card;
            const entry = parseStock(card);
            if (entry) openDrawer(entry);
        });
    });

    function revealCards() {
        cards = getStockCards();
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0, rootMargin: '0px 0px 48px 0px' });

            cards.forEach((c) => obs.observe(c));
        } else {
            cards.forEach((c) => c.classList.add('is-visible'));
        }

        window.setTimeout(() => {
            getStockCards().forEach((c) => {
                if (!c.classList.contains('is-visible')) {
                    c.classList.add('is-visible');
                }
            });
        }, 2000);
    }

    revealCards();
    applyFilters();
})();
</script>

</body>
</html>












