<?php
/**
 * Unified MedLog page header (title, subtitle, optional icon, actions, extra row).
 * Set $medlogPageHeader before including:
 *   'title' (string, required)
 *   'subtitle' (string, optional)
 *   'icon' (string): dashboard | patients | visits | appointments | stocks | medicines | reports | my-visits | profile
 *   'class' (string): extra classes on <header>
 *   'actions' (string): raw HTML for right column (buttons, etc.)
 *   'below' (string): raw HTML below subtitle (banners, etc.)
 */
$__mh = $medlogPageHeader ?? [];
$__title = trim((string) ($__mh['title'] ?? 'MedLog'));
$__subtitle = isset($__mh['subtitle']) ? (string) $__mh['subtitle'] : '';
$__actions = isset($__mh['actions']) ? (string) $__mh['actions'] : '';
$__below = isset($__mh['below']) ? (string) $__mh['below'] : '';
$__iconKey = strtolower((string) ($__mh['icon'] ?? 'default'));
$__extraClass = trim((string) ($__mh['class'] ?? ''));

$__icons = [
    'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
    'patients' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'visits' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M12 14h.01"/></svg>',
    'appointments' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
    'stocks' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05"/><path d="M12 22.08V12"/></svg>',
    'medicines' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 20.5L4 14l8-8 6.5 6.5"/><path d="M13 6l5 5"/><path d="M6 19l4-4"/></svg>',
    'reports' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 12l4-4 4 4 6-6"/></svg>',
    'my-visits' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
    'profile' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'default' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
];
$__svg = $__icons[$__iconKey] ?? $__icons['default'];
?>
<header class="medlog-page-header<?= $__extraClass !== '' ? ' ' . htmlspecialchars($__extraClass, ENT_QUOTES, 'UTF-8') : '' ?>">
    <div class="medlog-page-header__glow" aria-hidden="true"></div>
    <div class="medlog-page-header__inner">
        <div class="medlog-page-header__lead">
            <span class="medlog-page-header__icon"><?= $__svg ?></span>
            <div class="medlog-page-header__copy">
                <h1 class="medlog-page-header__title"><?= htmlspecialchars($__title, ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if ($__subtitle !== ''): ?>
                    <p class="medlog-page-header__subtitle"><?= htmlspecialchars($__subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($__below !== ''): ?>
                    <div class="medlog-page-header__below"><?= $__below ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($__actions !== ''): ?>
            <div class="medlog-page-header__actions"><?= $__actions ?></div>
        <?php endif; ?>
    </div>
</header>
