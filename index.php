<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedLog | Smart School Clinic Management System</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <!-- Homepage CSS -->
    <link rel="stylesheet" href="Css/homepage.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vanta -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.cells.min.js"></script>

    <!-- Lottie -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

    <style>
      body {
        font-family: 'Inter', system-ui;
        overflow-x: hidden;
      }

      /* GRADIENT HERO */
      .hero {
        background: linear-gradient(-45deg, #0A1931, #1A3D63, #4A7FA7, #6EA8D9);
        background-size: 400% 400%;
      }

      /* FLOATING ICONS */
      .floating-icons img {
        position: absolute;
        width: 50px;
        opacity: 0.12;
        animation: none !important;
      }

      .floating-icons img:nth-child(1) { left: 10%; }
      .floating-icons img:nth-child(2) { left: 25%; }
      .floating-icons img:nth-child(3) { left: 40%; }
      .floating-icons img:nth-child(4) { left: 60%; }
      .floating-icons img:nth-child(5) { left: 75%; }
      .floating-icons img:nth-child(6) { left: 90%; }

      /* CARDS */
      .card {
      }

      .card:hover {
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }

      /* GLOW SECTION */
      .glow-section {
        background: radial-gradient(circle at top, #1A3D63, #0A1931);
      }

      /* CTA BOX */
      .cta-box {
        background: linear-gradient(135deg, #4A7FA7, #6EA8D9);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
      }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">

<!-- NAVBAR -->
<header class="fixed top-0 left-0 w-full z-50 bg-[#071B34]/80 backdrop-blur-xl border-b border-white/10">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <!-- Brand -->
            <a href="#hero" class="navbar-logo" aria-label="MedLog home">
                <img src="Images/FinalLogo.png" alt="" class="navbar-logo__mark">
                <span class="navbar-logo__text">MedLog</span>
            </a>

            <!-- Nav -->
            <nav class="hidden md:flex items-center gap-8 text-white/85 font-medium">
                <a href="#features" class="hover:text-white">Features</a>
                <a href="#how-it-works" class="hover:text-white">How It Works</a>
                <a href="#showcase" class="hover:text-white">Showcase</a>
                <a href="#faq" class="hover:text-white">FAQ</a>
            </nav>

            <!-- CTA -->
            <a href="auth/login.php"
               class="px-6 py-3 rounded-xl border border-white/30 text-white font-semibold hover:bg-white hover:text-[#071B34] transition">
                Login
            </a>
        </div>
    </div>
</header>

<!-- HERO -->
<section id="hero"
         class="relative min-h-screen flex items-center overflow-hidden pt-32">

    <!-- VANTA BACKGROUND -->
    <div id="vanta-bg" class="absolute inset-0 z-0"></div>

    <!-- Overlay -->
    <div class="absolute inset-0 bg-[#071B34]/35 z-10"></div>

    <div class="relative z-20 max-w-7xl mx-auto px-6 lg:px-8 w-full">

        <div class="grid lg:grid-cols-2 gap-16 items-center">

            <!-- LEFT CONTENT -->
            <div data-aos="fade-right">

                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-lg border border-white/20 text-white text-sm font-medium mb-8">
                    <span>🏥</span>
                    <span>Built for Modern School Clinics</span>
                </div>

                <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-white leading-tight">
                    Smarter School Clinic Management
                </h1>

                <p class="mt-6 text-lg md:text-xl text-white/80 max-w-xl leading-relaxed">
                    Streamline student health records, appointment scheduling,
                    medicine inventory, and analytics reporting in one centralized platform.
                </p>

                <!-- Mini trust bullets -->
                <div class="mt-8 flex flex-wrap gap-4 text-white/90 text-sm md:text-base font-medium">
                    <div class="hero-pill">✓ Role-based Access</div>
                    <div class="hero-pill">✓ Microsoft 365 Login</div>
                    <div class="hero-pill">✓ Inventory Monitoring</div>
                </div>

                <!-- CTA -->
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="auth/login.php"
                       class="px-8 py-4 rounded-2xl bg-white text-[#071B34] font-bold shadow-xl hover:scale-105 transition">
                        Access System
                    </a>

                    <a href="#features"
                       class="px-8 py-4 rounded-2xl border border-white/30 text-white font-semibold backdrop-blur-md hover:bg-white/10 transition">
                        View Features
                    </a>
                </div>
            </div>

            <!-- RIGHT VISUAL -->
            <div class="relative hero-visual" data-aos="fade-left">

                <!-- Desktop mockup -->
                <img src="Images/DesktopMockup.png"
                     alt="Desktop MedLog Interface"
                     class="desktop-mockup">

                <!-- Phone overlay -->
                <img src="Images/PhoneMockup.png"
                     alt="Mobile MedLog Interface"
                     class="phone-mockup">

                <!-- Floating cards -->
                <div class="floating-card stat-card card-1">
                    <div class="text-2xl">📊</div>
                    <div>
                        <h4>2,340+</h4>
                        <p>Student Records</p>
                    </div>
                </div>

                <div class="floating-card stat-card card-2">
                    <div class="text-2xl">💊</div>
                    <div>
                        <h4>Stock Monitoring</h4>
                        <p>Low inventory alerts</p>
                    </div>
                </div>

                <div class="floating-card stat-card card-3">
                    <div class="text-2xl">📅</div>
                    <div>
                        <h4>Appointments</h4>
                        <p>Student scheduling</p>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<!-- TRUST STRIP -->
<section class="relative z-20 bg-white shadow-sm border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6">
        <div class="flex flex-wrap justify-center gap-8 text-slate-700 font-semibold text-sm md:text-base">

            <span>✔ Role-based Access</span>
            <span>✔ Microsoft 365 Authentication</span>
            <span>✔ Student Health Records</span>
            <span>✔ Inventory Monitoring</span>
            <span>✔ Appointment Scheduling</span>
            <span>✔ Analytics Reporting</span>

        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-28 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900">
                Everything Your School Clinic Needs
            </h2>
            <p class="mt-5 text-lg text-slate-600">
                Designed to simplify real clinic workflows while giving administrators
                full visibility and control.
            </p>
        </div>

        <!-- Bento Grid -->
        <div class="grid md:grid-cols-3 gap-6">

            <!-- BIG -->
            <div class="md:col-span-2 md:row-span-2 bg-white rounded-3xl p-10 shadow-xl border border-slate-100"
                 data-aos="fade-up">
                <div class="text-5xl mb-6">🩺</div>
                <h3 class="text-3xl font-bold text-slate-900">
                    Student Health Records
                </h3>
                <p class="mt-4 text-slate-600 text-lg leading-relaxed">
                    Securely manage student medical histories, visits, treatments,
                    emergency contacts, and clinic readiness information in one place.
                </p>
            </div>

            <!-- SMALL -->
            <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100"
                 data-aos="fade-up"
                 data-aos-delay="100">
                <div class="text-4xl mb-4">📅</div>
                <h3 class="text-xl font-bold">Appointments</h3>
                <p class="mt-3 text-slate-600">
                    Student appointment scheduling with admin approval.
                </p>
            </div>

            <!-- SMALL -->
            <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100"
                 data-aos="fade-up"
                 data-aos-delay="200">
                <div class="text-4xl mb-4">💊</div>
                <h3 class="text-xl font-bold">Inventory</h3>
                <p class="mt-3 text-slate-600">
                    Monitor medicine stock and prevent shortages.
                </p>
            </div>

            <!-- WIDE -->
            <div class="analytics-card md:col-span-3"
                 data-aos="fade-up"
                 data-aos-delay="300">
                <div class="analytics-card__content">
                    <div class="analytics-card__eyebrow">Clinic Intelligence</div>
                    <h3 class="text-3xl font-bold text-slate-950">Analytics & Reporting</h3>
                    <p class="mt-4 text-slate-600 text-lg leading-relaxed">
                        Generate insights, clinic statistics, treatment usage reports,
                        and operational summaries for smarter decision-making.
                    </p>
                </div>

                <div class="analytics-card__visual" aria-label="Animated analytics dashboard preview">
                    <div id="analytics-graph-lottie" class="analytics-card__lottie"></div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- PRODUCT SHOWCASE -->
<section id="showcase" class="py-28 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-14" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold">
                Built for Real Clinic Workflows
            </h2>
            <p class="mt-5 text-lg text-slate-600">
                Explore MedLog’s modern interface built for administrators and students.
            </p>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap justify-center gap-4 mb-10">
            <button class="showcase-tab active-tab" onclick="switchShowcase('dashboard')">Dashboard</button>
            <button class="showcase-tab" onclick="switchShowcase('patients')">Patients</button>
            <button class="showcase-tab" onclick="switchShowcase('appointments')">Appointments</button>
            <button class="showcase-tab" onclick="switchShowcase('reports')">Reports</button>
        </div>

        <!-- Preview -->
        <div class="showcase-preview" data-aos="zoom-in">
            <img id="showcase-image"
                 src="https://placehold.co/1400x800/e2e8f0/0f172a?text=Dashboard+Preview"
                 alt="Showcase Preview"
                 class="rounded-3xl shadow-2xl w-full">
        </div>

    </div>
</section>

<!-- HOW IT WORKS -->
<section id="how-it-works" class="py-28 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900">
                How MedLog Works
            </h2>
            <p class="mt-5 text-lg text-slate-600">
                A streamlined workflow built for real school clinic operations.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">

            <!-- Step 1 -->
            <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 text-center"
                 data-aos="fade-up">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-100 flex items-center justify-center text-3xl mb-6">
                    🔐
                </div>
                <h3 class="text-2xl font-bold text-slate-900">
                    Secure Login
                </h3>
                <p class="mt-4 text-slate-600 leading-relaxed">
                    Students and administrators securely access MedLog using
                    Microsoft 365 authentication and role-based access.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 text-center"
                 data-aos="fade-up"
                 data-aos-delay="150">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-100 flex items-center justify-center text-3xl mb-6">
                    🏥
                </div>
                <h3 class="text-2xl font-bold text-slate-900">
                    Manage Operations
                </h3>
                <p class="mt-4 text-slate-600 leading-relaxed">
                    Record visits, monitor medicine inventory,
                    manage appointments, and track student health data.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 text-center"
                 data-aos="fade-up"
                 data-aos-delay="300">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-orange-100 flex items-center justify-center text-3xl mb-6">
                    📊
                </div>
                <h3 class="text-2xl font-bold text-slate-900">
                    Generate Insights
                </h3>
                <p class="mt-4 text-slate-600 leading-relaxed">
                    Use analytics dashboards and reports to support smarter
                    clinic decision-making.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-28 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-14 items-center">

            <div data-aos="fade-right">
                <img src="Images/DesktopMockup.png"
                     alt="MedLog Dashboard"
                     class="about-mockup">
            </div>

            <div data-aos="fade-left">
                <div class="inline-flex px-4 py-2 rounded-full bg-slate-100 text-slate-700 font-semibold mb-6">
                    About MedLog
                </div>

                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
                    Designed for School Clinics That Need Better Systems
                </h2>

                <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                    MedLog is a web-based school clinic management platform
                    developed to digitize clinic workflows including health records,
                    medicine inventory, appointments, and reporting.
                </p>

                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Built with usability, accessibility, and operational efficiency in mind.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-28 bg-slate-50">
    <div class="max-w-5xl mx-auto px-6 lg:px-8">

        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900">
                Frequently Asked Questions
            </h2>
        </div>

        <div class="space-y-4">

            <details class="faq-card">
                <summary>Is MedLog mobile friendly?</summary>
                <p>
                    Yes. MedLog is designed to work across desktop and mobile devices.
                </p>
            </details>

            <details class="faq-card">
                <summary>Can administrators manage medicine inventory?</summary>
                <p>
                    Yes. Inventory monitoring and stock management are core admin features.
                </p>
            </details>

            <details class="faq-card">
                <summary>Does MedLog support Microsoft 365 login?</summary>
                <p>
                    Yes. Microsoft 365 authentication is integrated for supported users.
                </p>
            </details>

            <details class="faq-card">
                <summary>Can students request appointments?</summary>
                <p>
                    Yes. Students can request appointments directly through the system.
                </p>
            </details>

        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="py-28 bg-[#071B34] relative overflow-hidden">

    <div class="absolute inset-0 opacity-20">
        <div id="cta-vanta"></div>
    </div>

    <div class="relative z-20 max-w-5xl mx-auto px-6 lg:px-8 text-center">

        <h2 class="text-4xl md:text-6xl font-extrabold text-white leading-tight">
            Digitize Your School Clinic Today
        </h2>

        <p class="mt-6 text-lg md:text-xl text-white/80 max-w-3xl mx-auto">
            Modernize clinic operations with secure student health management,
            inventory monitoring, appointment workflows, and analytics.
        </p>

        <div class="mt-10">
            <a href="auth/login.php"
               class="inline-block px-10 py-5 rounded-2xl bg-white text-[#071B34] font-bold shadow-xl hover:scale-105 transition">
                Access MedLog
            </a>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="bg-slate-950 text-white py-10">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">

        <div class="text-white/80">
            © <?php echo date("Y"); ?> MedLog. All rights reserved.
        </div>

        <div class="flex gap-6 text-white/70">
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#showcase">Showcase</a>
            <a href="#faq">FAQ</a>
        </div>

    </div>
</footer>

<!-- SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
AOS.init({
    duration: 900,
    once: true
});

const analyticsGraph = document.getElementById('analytics-graph-lottie');

if (analyticsGraph && window.lottie) {
    lottie.loadAnimation({
        container: analyticsGraph,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: 'animations/GraphScene.json',
        rendererSettings: {
            progressiveLoad: true,
            hideOnTransparent: true,
            preserveAspectRatio: 'xMidYMid slice'
        }
    });
}

VANTA.CELLS({
    el: "#vanta-bg",
    mouseControls: true,
    touchControls: true,
    gyroControls: false,
    minHeight: 200,
    minWidth: 200,
    scale: 1,
    color1: 0x4c64cf,
    color2: 0x1389b1,
    speed: 1
});

VANTA.CELLS({
    el: "#cta-vanta",
    mouseControls: true,
    touchControls: true,
    gyroControls: false,
    minHeight: 200,
    minWidth: 200,
    scale: 1,
    color1: 0x4c64cf,
    color2: 0x1389b1,
    speed: 1
});

function switchShowcase(tab) {
    const img = document.getElementById('showcase-image');

    const previews = {
        dashboard: "https://placehold.co/1400x800/e2e8f0/0f172a?text=Dashboard+Preview",
        patients: "https://placehold.co/1400x800/e2e8f0/0f172a?text=Patients+Preview",
        appointments: "https://placehold.co/1400x800/e2e8f0/0f172a?text=Appointments+Preview",
        reports: "https://placehold.co/1400x800/e2e8f0/0f172a?text=Reports+Preview"
    };

    img.src = previews[tab];

    document.querySelectorAll('.showcase-tab').forEach(btn => {
        btn.classList.remove('active-tab');
    });

    event.target.classList.add('active-tab');
}
</script>

</body>
</html>
