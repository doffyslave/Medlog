<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MedLog | Smart School Clinic System</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- Lottie -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

  <style>
    body {
      font-family: 'Inter', system-ui;
      overflow-x: hidden;
    }

    /* GRADIENT HERO */
    .hero {
      background: linear-gradient(-45deg, #0A1931, #1A3D63, #4A7FA7, #6EA8D9);
      background-size: 400% 400%;
      animation: gradientMove 12s ease infinite;
    }

    @keyframes gradientMove {
      0% { background-position: 0%; }
      50% { background-position: 100%; }
      100% { background-position: 0%; }
    }

    /* FLOATING ICONS */
    .floating-icons img {
      position: absolute;
      width: 50px;
      opacity: 0.12;
      animation: floatIcon linear infinite;
    }

    @keyframes floatIcon {
      0% { transform: translateY(100vh) rotate(0); }
      100% { transform: translateY(-120vh) rotate(360deg); }
    }

    .floating-icons img:nth-child(1) { left: 10%; animation-duration: 18s; }
    .floating-icons img:nth-child(2) { left: 25%; animation-duration: 22s; }
    .floating-icons img:nth-child(3) { left: 40%; animation-duration: 20s; }
    .floating-icons img:nth-child(4) { left: 60%; animation-duration: 25s; }
    .floating-icons img:nth-child(5) { left: 75%; animation-duration: 19s; }
    .floating-icons img:nth-child(6) { left: 90%; animation-duration: 23s; }

    /* CARDS */
    .card {
      transition: 0.35s;
    }

    .card:hover {
      transform: translateY(-14px) scale(1.05);
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

<body class="bg-[#F6FAFD]">

  <!-- NAVBAR -->
  <nav class="bg-[#0A1931] text-white sticky top-0 z-50 shadow">
    <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
      <h1 class="font-bold text-xl">MedLog</h1>

      <div class="hidden md:flex gap-6">
        <a href="#features">Features</a>
        <a href="#how">How it Works</a>
        <a href="#about">About</a>
      </div>

      <a href="auth/login.php" class="border px-4 py-2 rounded-lg hover:bg-white hover:text-[#0A1931] transition">
        Login
      </a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero text-white py-32 text-center relative overflow-hidden">

    <!-- FLOATING ICONS -->
    <div class="floating-icons">
      <img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
      <img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
      <img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
      <img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png">
      <img src="https://cdn-icons-png.flaticon.com/512/2785/2785819.png">
      <img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png">
    </div>

    <div class="max-w-6xl mx-auto px-6">

      <h1 class="text-6xl md:text-7xl font-extrabold leading-tight" data-aos="fade-up">
        School Clinic Management Made Simple
      </h1>

      <p class="mt-6 text-xl text-[#B3CFE5] max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="200">
        MedLog helps school nurses manage student health records, monitor clinic visits, track medicine inventory, and generate reports, all in one powerful system.
      </p>

      <div class="mt-10" data-aos="zoom-in" data-aos-delay="400">
        <a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold shadow-lg hover:scale-105 transition">
          Get Started
        </a>
      </div>

      <!-- LOTTIE -->
      <lottie-player
        src="https://assets7.lottiefiles.com/packages/lf20_tutvdkg0.json"
        style="width:420px;height:420px;margin:auto;margin-top:50px"
        loop
        autoplay>
      </lottie-player>

    </div>
  </section>

  <!-- STATS -->
  <section class="py-20 text-center bg-white" data-aos="fade-up">
    <div class="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
      <div><h3 class="text-4xl font-bold">100%</h3><p>Digital Records</p></div>
      <div><h3 class="text-4xl font-bold">24/7</h3><p>Access</p></div>
      <div><h3 class="text-4xl font-bold">Real-Time</h3><p>Tracking</p></div>
      <div><h3 class="text-4xl font-bold">Secure</h3><p>Data</p></div>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="features" class="py-24 bg-[#F6FAFD] text-center">
    <h2 class="text-4xl font-bold mb-16" data-aos="fade-up">Everything You Need</h2>

    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 px-6">

      <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up">
        <h3 class="font-bold text-xl mb-4">Health Records</h3>
        <p>Track student illnesses, allergies, visits, and treatment history.</p>
      </div>

      <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up" data-aos-delay="150">
        <h3 class="font-bold text-xl mb-4">Inventory System</h3>
        <p>Monitor medicines, supplies, and expiration dates automatically.</p>
      </div>

      <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up" data-aos-delay="300">
        <h3 class="font-bold text-xl mb-4">Reports & Analytics</h3>
        <p>Generate insights for administrators and better decisions.</p>
      </div>

    </div>
  </section>

  <!-- HOW -->
  <section id="how" class="py-24 text-center bg-white">
    <h2 class="text-4xl font-bold mb-16" data-aos="fade-up">How MedLog Works</h2>

    <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-10">
      <div data-aos="zoom-in">
        <h3 class="font-bold">1. Record</h3>
        <p>Input student health data.</p>
      </div>

      <div data-aos="zoom-in" data-aos-delay="150">
        <h3 class="font-bold">2. Track</h3>
        <p>Monitor medicine usage.</p>
      </div>

      <div data-aos="zoom-in" data-aos-delay="300">
        <h3 class="font-bold">3. Analyze</h3>
        <p>Generate reports instantly.</p>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="glow-section text-white py-28 text-center relative">
    <h2 class="text-4xl font-bold mb-6" data-aos="fade-up">Built for School Nurses</h2>

    <p class="max-w-3xl mx-auto text-[#B3CFE5] mb-10" data-aos="fade-up" data-aos-delay="200">
      MedLog is designed to reduce paperwork, improve clinic efficiency, and ensure every student receives proper healthcare attention.
    </p>

    <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-8">
      <div class="bg-white/10 p-6 rounded-xl backdrop-blur" data-aos="fade-up">⚡ Faster Workflow</div>
      <div class="bg-white/10 p-6 rounded-xl backdrop-blur" data-aos="fade-up" data-aos-delay="150">🔒 Secure Data</div>
      <div class="bg-white/10 p-6 rounded-xl backdrop-blur" data-aos="fade-up" data-aos-delay="300">📊 Smart Insights</div>
    </div>
  </section>

  <!-- CTA -->
  <section class="py-24 text-center bg-[#F6FAFD]">
    <div class="max-w-4xl mx-auto cta-box" data-aos="zoom-in">
      <h2 class="text-4xl font-bold text-white mb-6">Start Managing Your Clinic Smarter</h2>

      <a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold hover:scale-105 transition">
        Launch MedLog
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-[#0A1931] text-white text-center py-6">
    <p>© 2026 MedLog</p>
  </footer>

  <script>
    AOS.init({ duration: 1000, once: true });
  </script>

</body>
</html>