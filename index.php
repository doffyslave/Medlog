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
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(10, 25, 49, 0.35);
      z-index: 1;
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
<section id="hero-bg" class="hero text-white py-32 text-center relative overflow-hidden">


  <div class="max-w-6xl mx-auto px-6 relative z-10">

    <!-- MAIN TITLE -->
    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight" data-aos="fade-up">
      Official School Clinic Management System
    </h1>

    <!-- SUBTITLE -->
    <p class="mt-6 text-xl text-[#B3CFE5] max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="200">
      A centralized system designed for school nurses and administrators to securely manage student health records, clinic visits, and medical reports.
    </p>

    <!-- TRUST LINE -->
    <div class="mt-4 text-sm text-[#B3CFE5] opacity-80" data-aos="fade-up" data-aos-delay="300">
      ✔ Built for school clinic use • ✔ Secure student data • ✔ Admin-ready reporting system
    </div>

    <!-- BUTTON -->
    <div class="mt-10" data-aos="zoom-in" data-aos-delay="400">
      <a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold shadow-lg hover:scale-105 transition">
        Access School System
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
  <section class="py-20 bg-[#F6FAFD]" data-aos="fade-up">
  <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-lg p-10 grid md:grid-cols-4 gap-8 text-left">

    <div class="flex items-start gap-4">
      <div class="bg-[#E8F1F8] p-4 rounded-xl text-2xl">📄</div>
      <div>
        <h3 class="text-xl font-bold text-[#0A1931]">100%</h3>
        <p class="text-sm text-gray-500">Digital Records</p>
      </div>
    </div>

    <div class="flex items-start gap-4">
      <div class="bg-[#E8F1F8] p-4 rounded-xl text-2xl">⏱</div>
      <div>
        <h3 class="text-xl font-bold text-[#0A1931]">24/7</h3>
        <p class="text-sm text-gray-500">Access</p>
      </div>
    </div>

    <div class="flex items-start gap-4">
      <div class="bg-[#E8F1F8] p-4 rounded-xl text-2xl">📊</div>
      <div>
        <h3 class="text-xl font-bold text-[#0A1931]">Real-Time</h3>
        <p class="text-sm text-gray-500">Tracking</p>
      </div>
    </div>

    <div class="flex items-start gap-4">
      <div class="bg-[#E8F1F8] p-4 rounded-xl text-2xl">🔒</div>
      <div>
        <h3 class="text-xl font-bold text-[#0A1931]">Secure</h3>
        <p class="text-sm text-gray-500">Data Protection</p>
      </div>
    </div>

  </div>
</section>

  <!-- FEATURES -->
  <section class="py-24 text-center">
  <h2 class="text-4xl font-bold mb-16">Features</h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10">

    <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
      <div class="w-16 h-16 bg-gradient-to-br from-[#4A7FA7] to-[#1A3D63] text-white flex items-center justify-center rounded-xl mb-4">🩺</div>
      <h3 class="font-bold text-lg mb-2">Student Health Records</h3>
      <p class="text-gray-600">Track illnesses and student visits.</p>
    </div>

    <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
      <div class="w-16 h-16 bg-gradient-to-br from-[#4A7FA7] to-[#1A3D63] text-white flex items-center justify-center rounded-xl mb-4">💊</div>
      <h3 class="font-bold text-lg mb-2">Clinic Medication Tracking</h3>
      <p class="text-gray-600">Monitor medicines and treatments.</p>
    </div>

    <div class="p-8 bg-white rounded-2xl shadow-lg hover:shadow-xl transition">
      <div class="w-16 h-16 bg-gradient-to-br from-[#4A7FA7] to-[#1A3D63] text-white flex items-center justify-center rounded-xl mb-4">📈</div>
      <h3 class="font-bold text-lg mb-2">Administrative Health Reports</h3>
      <p class="text-gray-600">Generate real-time reports.</p>
    </div>

  </div>
</section>

<!-- TRUST -->
 <section class="py-24 bg-[#0A1931] text-white text-center">
  <h2 class="text-4xl font-bold mb-10">Secure & School-Ready</h2>

  <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-8 px-6">

    <div class="bg-white/10 p-6 rounded-xl">
      🔒 Role-based Access Control
    </div>

    <div class="bg-white/10 p-6 rounded-xl">
      🗂️ Centralized Student Records
    </div>

    <div class="bg-white/10 p-6 rounded-xl">
      📊 Audit-ready Reports for Schools
    </div>

  </div>
</section>

  <!-- HOW -->
  <section class="py-24 bg-[#EAF3FA] text-center">
  <h2 class="text-4xl font-bold mb-16">How It Works</h2>

  <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-10">

    <div>
      <div class="text-3xl mb-3">📝</div>
      <h3 class="font-bold">Input Data</h3>
      <p class="text-gray-600">Nurses log student health info.</p>
    </div>

    <div>
      <div class="text-3xl mb-3">💾</div>
      <h3 class="font-bold">Store</h3>
      <p class="text-gray-600">Data is securely saved.</p>
    </div>

    <div>
      <div class="text-3xl mb-3">📊</div>
      <h3 class="font-bold">Analyze</h3>
      <p class="text-gray-600">Generate reports instantly.</p>
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
      <h2 class="text-4xl font-bold text-white mb-6">Ready to Deploy in Your School Clinic</h2>

      <a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold hover:scale-105 transition">
        Launch MedLog
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-[#0A1931] text-white text-center py-6">
    <p>MedLog School Clinic System  
For educational institution use only  
© 2026 MedLog. All rights reserved.</p>
  </footer>

  <script>
  AOS.init({
    duration: 900,
    once: false,
    offset: 120,
    easing: 'ease-out-cubic'
  });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.cells.min.js"></script>

<script>
VANTA.CELLS({
  el: "#hero-bg",
  mouseControls: true,
  touchControls: true,
  gyroControls: false,
  minHeight: 200,
  minWidth: 200,
  scale: 1.0,
  color1: 0x4c64cf,
  color2: 0x1389b1,
  size: 1.5,
  speed: 1
});
</script>

</body>
</html>