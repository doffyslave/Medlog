<?php
session_start();
include 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_id = (int) $user['user_id'];

if (($user['role'] ?? '') !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    session_unset();
    session_destroy();
    header('Location: index.php?error=account_deleted');
    exit();
}

$stmt = $conn->prepare('
    SELECT COUNT(*) as total_visits, MAX(visit_date) as last_visit
    FROM visits
    WHERE user_id = ?
');
$stmt->execute([$user_id]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_visits' => 0, 'last_visit' => null];

$email = (string) $userData['email'];
preg_match('/\.(\d+)@/', $email, $matches);
$studentNumber = $matches[1] ?? null;
$studentID = $studentNumber
    ? '02-000' . substr($studentNumber, 0, 1) . '-' . substr($studentNumber, 1)
    : 'N/A';

if (empty($_SESSION['profile_csrf']) || !is_string($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}
$profileCsrf = $_SESSION['profile_csrf'];

function ml_profile_initials(string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name));
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) >= 2) {
        $a = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1, 'UTF-8') : substr($parts[0], 0, 1);
        $last = $parts[count($parts) - 1];
        $b = function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1);
        return strtoupper($a . $b);
    }
    $two = function_exists('mb_substr') ? mb_substr($name, 0, 2, 'UTF-8') : substr($name, 0, 2);
    return strtoupper($two);
}

$initials = ml_profile_initials((string) ($userData['name'] ?? ''));
$lastLoginRaw = $userData['last_login'] ?? null;
$lastLoginFmt = $lastLoginRaw ? date('M j, Y g:i A', strtotime((string) $lastLoginRaw)) : '—';
$accountStatus = 'Active';
if (!empty($userData['status'])) {
    $st = strtolower((string) $userData['status']);
    if ($st !== 'active') {
        $accountStatus = ucfirst((string) $userData['status']);
    }
}

$medlogPageHeader = [
    'title' => 'Profile',
    'subtitle' => 'Your student clinic profile, medical readiness, and account details.',
    'icon' => 'profile',
    'class' => 'medlog-page-header--profile',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | MedLog</title>
    <link rel="stylesheet" href="Css/layout.css">
    <link rel="stylesheet" href="Css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="medlog-student-shell">

<div class="dashboard">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="content profile-page">

            <?php include 'includes/medlog-page-header.php'; ?>

            <div id="profileToast" class="profile-toast" role="status" aria-live="polite" hidden></div>

            <div class="profile-layout">

                <!-- Hero -->
                <article class="profile-hero medlog-card-elevated">
                    <div class="profile-hero__cover" role="img" aria-label="Profile cover"></div>
                    <div class="profile-hero__body">
                        <div class="profile-hero__avatar-wrap">
                            <div class="profile-hero__avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="profile-hero__main">
                            <div class="profile-hero__title-row">
                                <h2 class="profile-hero__name"><?= htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <span class="profile-badge profile-badge--role">Student</span>
                            </div>
                            <p class="profile-hero__meta">
                                <span class="profile-hero__id"><i class="fa-solid fa-id-card" aria-hidden="true"></i> <?= htmlspecialchars($studentID, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="profile-hero__dot" aria-hidden="true"></span>
                                <span class="profile-hero__email"><i class="fa-solid fa-envelope" aria-hidden="true"></i> <?= htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                            <?php if (!empty($userData['course']) || !empty($userData['year_level'])): ?>
                                <p class="profile-hero__course">
                                    <?= htmlspecialchars(trim(($userData['course'] ?? '') . (($userData['course'] ?? '') && ($userData['year_level'] ?? '') ? ' · ' : '') . ($userData['year_level'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                            <div class="profile-hero__actions">
                                <button type="button" class="profile-btn profile-btn--primary" id="openEditProfile">
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit profile
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="profile-hero__stats">
                        <div class="profile-hero__stat">
                            <span class="profile-hero__stat-label">Total visits</span>
                            <strong class="profile-hero__stat-value"><?= (int) ($visit['total_visits'] ?? 0) ?></strong>
                        </div>
                        <div class="profile-hero__stat">
                            <span class="profile-hero__stat-label">Last visit</span>
                            <strong class="profile-hero__stat-value profile-hero__stat-value--sub">
                                <?php
                                $lv = $visit['last_visit'] ?? null;
                                echo $lv
                                    ? htmlspecialchars(date('M j, Y', strtotime((string) $lv)), ENT_QUOTES, 'UTF-8')
                                    : 'None yet';
                                ?>
                            </strong>
                        </div>
                        <a class="profile-hero__stat-link" href="my_visits.php">View records <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>

                <div class="profile-grid">

                    <div class="profile-grid__col profile-grid__col--wide">

                        <!-- Medical readiness -->
                        <article class="profile-panel profile-panel--alert medlog-card-elevated">
                            <header class="profile-panel__head">
                                <span class="profile-panel__icon"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i></span>
                                <div>
                                    <h3 class="profile-panel__title">Medical readiness</h3>
                                    <p class="profile-panel__sub">Information the clinic may rely on during a visit.</p>
                                </div>
                            </header>
                            <div class="profile-readiness">
                                <div class="profile-readiness__block profile-readiness__block--highlight">
                                    <span class="profile-readiness__label">Allergies</span>
                                    <p class="profile-readiness__value" id="dispAllergies"><?= $userData['allergies'] !== null && $userData['allergies'] !== '' ? nl2br(htmlspecialchars((string) $userData['allergies'], ENT_QUOTES, 'UTF-8')) : '<span class="profile-muted">None reported</span>' ?></p>
                                </div>
                                <div class="profile-readiness__row">
                                    <div class="profile-readiness__block">
                                        <span class="profile-readiness__label">Emergency contact</span>
                                        <p class="profile-readiness__value" id="dispEmName"><?= htmlspecialchars((string) ($userData['emergency_contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) $userData['emergency_contact_name'], ENT_QUOTES, 'UTF-8') : '<span class="profile-muted">—</span>' ?></p>
                                    </div>
                                    <div class="profile-readiness__block profile-readiness__block--phone">
                                        <span class="profile-readiness__label">Emergency number</span>
                                        <p class="profile-readiness__value" id="dispEmNum"><?= htmlspecialchars((string) ($userData['emergency_contact_number'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) $userData['emergency_contact_number'], ENT_QUOTES, 'UTF-8') : '<span class="profile-muted">—</span>' ?></p>
                                    </div>
                                </div>
                                <div class="profile-readiness__block">
                                    <span class="profile-readiness__label">Your phone</span>
                                    <p class="profile-readiness__value" id="dispPhone"><?= htmlspecialchars((string) ($userData['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) $userData['phone_number'], ENT_QUOTES, 'UTF-8') : '<span class="profile-muted">—</span>' ?></p>
                                </div>
                            </div>
                        </article>

                        <!-- Quick actions -->
                        <article class="profile-panel medlog-card-elevated">
                            <header class="profile-panel__head">
                                <span class="profile-panel__icon profile-panel__icon--neutral"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
                                <div>
                                    <h3 class="profile-panel__title">Quick actions</h3>
                                    <p class="profile-panel__sub">Jump to common clinic tasks.</p>
                                </div>
                            </header>
                            <div class="profile-actions">
                                <a class="profile-action-card" href="my_visits.php">
                                    <span class="profile-action-card__icon"><i class="fa-solid fa-file-waveform" aria-hidden="true"></i></span>
                                    <span class="profile-action-card__text">
                                        <strong>Medical records</strong>
                                        <small>Visit history &amp; details</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right profile-action-card__chev" aria-hidden="true"></i>
                                </a>
                                <a class="profile-action-card" href="appointments.php">
                                    <span class="profile-action-card__icon"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i></span>
                                    <span class="profile-action-card__text">
                                        <strong>Book appointment</strong>
                                        <small>Schedule with the clinic</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right profile-action-card__chev" aria-hidden="true"></i>
                                </a>
                                <a class="profile-action-card" href="medicines.php">
                                    <span class="profile-action-card__icon"><i class="fa-solid fa-pills" aria-hidden="true"></i></span>
                                    <span class="profile-action-card__text">
                                        <strong>Medicines</strong>
                                        <small>Formulary &amp; availability</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right profile-action-card__chev" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    </div>

                    <div class="profile-grid__col profile-grid__col--side">

                        <!-- Account -->
                        <article class="profile-panel medlog-card-elevated">
                            <header class="profile-panel__head">
                                <span class="profile-panel__icon profile-panel__icon--neutral"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                                <div>
                                    <h3 class="profile-panel__title">Account</h3>
                                    <p class="profile-panel__sub">Read-only identifiers</p>
                                </div>
                            </header>
                            <dl class="profile-kv">
                                <div class="profile-kv__row">
                                    <dt>Student ID</dt>
                                    <dd><?= htmlspecialchars($studentID, ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>STI email</dt>
                                    <dd class="profile-kv__dd-break"><?= htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>Role</dt>
                                    <dd><?= htmlspecialchars(ucfirst((string) $userData['role']), ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>Account type</dt>
                                    <dd><span class="profile-ms-badge"><i class="fa-brands fa-microsoft" aria-hidden="true"></i> Microsoft 365</span></dd>
                                </div>
                            </dl>
                        </article>

                        <!-- Security -->
                        <article class="profile-panel profile-panel--security medlog-card-elevated">
                            <header class="profile-panel__head">
                                <span class="profile-panel__icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                <div>
                                    <h3 class="profile-panel__title">Security &amp; status</h3>
                                    <p class="profile-panel__sub">Sign-in and account state</p>
                                </div>
                            </header>
                            <dl class="profile-kv profile-kv--security">
                                <div class="profile-kv__row">
                                    <dt>Login provider</dt>
                                    <dd>Microsoft 365</dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>Last login</dt>
                                    <dd><?= htmlspecialchars($lastLoginFmt, ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>Account status</dt>
                                    <dd><span class="profile-status-pill"><?= htmlspecialchars($accountStatus, ENT_QUOTES, 'UTF-8') ?></span></dd>
                                </div>
                            </dl>
                        </article>

                        <!-- Academic (read-only here; edit in modal) -->
                        <article class="profile-panel medlog-card-elevated">
                            <header class="profile-panel__head">
                                <span class="profile-panel__icon profile-panel__icon--neutral"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>
                                <div>
                                    <h3 class="profile-panel__title">Program</h3>
                                    <p class="profile-panel__sub">Course &amp; year (editable via Edit profile)</p>
                                </div>
                            </header>
                            <dl class="profile-kv">
                                <div class="profile-kv__row">
                                    <dt>Course</dt>
                                    <dd id="dispCourse"><?= htmlspecialchars((string) ($userData['course'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) $userData['course'], ENT_QUOTES, 'UTF-8') : '—' ?></dd>
                                </div>
                                <div class="profile-kv__row">
                                    <dt>Year level</dt>
                                    <dd id="dispYear"><?= htmlspecialchars((string) ($userData['year_level'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) $userData['year_level'], ENT_QUOTES, 'UTF-8') : '—' ?></dd>
                                </div>
                            </dl>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Edit modal -->
<div id="editProfileModal" class="profile-modal" aria-hidden="true" hidden>
    <div class="profile-modal__backdrop" data-close-modal></div>
    <div class="profile-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
        <header class="profile-modal__header">
            <h2 id="editProfileTitle">Edit profile</h2>
            <button type="button" class="profile-modal__close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <form id="editProfileForm" class="profile-modal__form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($profileCsrf, ENT_QUOTES, 'UTF-8') ?>">

            <label class="profile-field">
                <span class="profile-field__label">Course</span>
                <input type="text" name="course" class="profile-field__input" maxlength="160" value="<?= htmlspecialchars((string) ($userData['course'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="organization-title">
            </label>
            <p class="profile-field__error" data-err-for="course" hidden></p>

            <label class="profile-field">
                <span class="profile-field__label">Year level</span>
                <input type="text" name="year_level" class="profile-field__input" maxlength="80" value="<?= htmlspecialchars((string) ($userData['year_level'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </label>
            <p class="profile-field__error" data-err-for="year_level" hidden></p>

            <label class="profile-field">
                <span class="profile-field__label">Phone number</span>
                <input type="tel" name="phone_number" class="profile-field__input" maxlength="40" value="<?= htmlspecialchars((string) ($userData['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel">
            </label>
            <p class="profile-field__error" data-err-for="phone_number" hidden></p>

            <label class="profile-field">
                <span class="profile-field__label">Emergency contact name</span>
                <input type="text" name="emergency_contact_name" class="profile-field__input" maxlength="120" value="<?= htmlspecialchars((string) ($userData['emergency_contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="name">
            </label>

            <label class="profile-field">
                <span class="profile-field__label">Emergency contact number</span>
                <input type="tel" name="emergency_contact_number" class="profile-field__input" maxlength="40" value="<?= htmlspecialchars((string) ($userData['emergency_contact_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel">
            </label>
            <p class="profile-field__error" data-err-for="emergency_contact_number" hidden></p>

            <label class="profile-field">
                <span class="profile-field__label">Allergies &amp; reactions</span>
                <textarea name="allergies" class="profile-field__textarea" rows="4" maxlength="1000" placeholder="List known allergies, medications, or foods to avoid."><?= htmlspecialchars((string) ($userData['allergies'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <p class="profile-modal__footnote">Email, role, and student ID cannot be changed here. Use your STI Microsoft account for email issues.</p>

            <div class="profile-modal__actions">
                <button type="button" class="profile-btn profile-btn--ghost" data-close-modal>Cancel</button>
                <button type="submit" class="profile-btn profile-btn--primary" id="saveProfileBtn">
                    <span class="profile-btn__label">Save changes</span>
                    <span class="profile-btn__spinner" hidden><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('editProfileModal');
    var openBtn = document.getElementById('openEditProfile');
    var form = document.getElementById('editProfileForm');
    var toast = document.getElementById('profileToast');
    var saveBtn = document.getElementById('saveProfileBtn');
    var spinner = saveBtn ? saveBtn.querySelector('.profile-btn__spinner') : null;
    var label = saveBtn ? saveBtn.querySelector('.profile-btn__label') : null;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function showToast(msg, isErr) {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        toast.classList.toggle('profile-toast--error', !!isErr);
        toast.classList.add('profile-toast--show');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            toast.classList.remove('profile-toast--show');
            setTimeout(function () { toast.hidden = true; }, 300);
        }, 4200);
    }

    function openModal() {
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('profile-modal-open');
        var first = form && form.querySelector('input[name="course"]');
        if (first) first.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('profile-modal-open');
        modal.hidden = true;
    }

    function clearFieldErrors() {
        if (!form) return;
        form.querySelectorAll('.profile-field__error').forEach(function (el) {
            el.hidden = true;
            el.textContent = '';
        });
        form.querySelectorAll('.profile-field__input--invalid, .profile-field__textarea--invalid').forEach(function (el) {
            el.classList.remove('profile-field__input--invalid', 'profile-field__textarea--invalid');
        });
    }

    function setBusy(busy) {
        if (!saveBtn) return;
        saveBtn.disabled = busy;
        if (spinner) spinner.hidden = !busy;
        if (label) label.hidden = busy;
    }

    function validateClient() {
        clearFieldErrors();
        var ok = true;
        var phone = form.phone_number.value.trim();
        var emNum = form.emergency_contact_number.value.trim();
        var re = /^[\d\s\-\+\(\)\.]{7,40}$/;
        if (phone && !re.test(phone)) {
            ok = false;
            var ep = form.querySelector('[data-err-for="phone_number"]');
            if (ep) { ep.textContent = 'Use digits and common phone symbols only.'; ep.hidden = false; }
            form.phone_number.classList.add('profile-field__input--invalid');
        }
        if (emNum && !re.test(emNum)) {
            ok = false;
            var e2 = form.querySelector('[data-err-for="emergency_contact_number"]');
            if (e2) { e2.textContent = 'Use digits and common phone symbols only.'; e2.hidden = false; }
            form.emergency_contact_number.classList.add('profile-field__input--invalid');
        }
        return ok;
    }

    function updateDomFromPayload(d) {
        function setHtml(id, html) {
            var el = document.getElementById(id);
            if (el) el.innerHTML = html;
        }
        function disp(v) {
            return v ? esc(String(v)) : '<span class="profile-muted">—</span>';
        }
        setHtml('dispCourse', d.course ? esc(d.course) : '—');
        setHtml('dispYear', d.year_level ? esc(d.year_level) : '—');
        setHtml('dispPhone', disp(d.phone_number));
        setHtml('dispEmName', disp(d.emergency_contact_name));
        setHtml('dispEmNum', disp(d.emergency_contact_number));
        var al = d.allergies && String(d.allergies).trim()
            ? esc(String(d.allergies)).replace(/\n/g, '<br>')
            : '<span class="profile-muted">None reported</span>';
        setHtml('dispAllergies', al);
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    modal && modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateClient()) return;
            setBusy(true);
            var fd = new FormData(form);
            fetch('update_profile.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    return r.text().then(function (t) {
                        var j = {};
                        try {
                            j = t ? JSON.parse(t) : {};
                        } catch (e) {
                            j = { success: false, message: 'Unexpected server response.' };
                        }
                        return { ok: r.ok, j: j };
                    });
                })
                .then(function (res) {
                    setBusy(false);
                    var j = res.j;
                    if (j && j.success) {
                        if (j.csrf_token) {
                            var h = form.querySelector('input[name="csrf_token"]');
                            if (h) h.value = j.csrf_token;
                        }
                        if (j.data) updateDomFromPayload(j.data);
                        showToast(j.message || 'Saved.', false);
                        closeModal();
                    } else {
                        if (j.errors) {
                            Object.keys(j.errors).forEach(function (key) {
                                var ep = form.querySelector('[data-err-for="' + key + '"]');
                                if (ep) {
                                    ep.textContent = j.errors[key];
                                    ep.hidden = false;
                                }
                                var inp = form.querySelector('[name="' + key + '"]');
                                if (inp) {
                                    if (inp.tagName === 'TEXTAREA') {
                                        inp.classList.add('profile-field__textarea--invalid');
                                    } else {
                                        inp.classList.add('profile-field__input--invalid');
                                    }
                                }
                            });
                        }
                        showToast(j.message || 'Could not save.', true);
                    }
                })
                .catch(function () {
                    setBusy(false);
                    showToast('Network error. Try again.', true);
                });
        });
    }
})();
</script>

</body>
</html>
