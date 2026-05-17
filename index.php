<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedLog | Smart School Clinic System</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<!-- Lottie -->
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<!-- AOS -->
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<style>
html {
  scroll-behavior: smooth;
}

/* =========================
   BASE UI IMPROVEMENTS
========================= */

body {
  font-family: 'Inter', system-ui;
  overflow-x: hidden;
  background: linear-gradient(180deg, #F6FAFD 0%, #EAF3FA 100%);
  color: #0A1931;
}

/* Smooth global feel */
* {
  transition: all 0.3s ease;
}

/* =========================
   SCROLL PROGRESS BAR
========================= */
#progressBar {
  position: fixed;
  top: 0;
  left: 0;
  height: 3px;
  width: 0%;
  background: linear-gradient(90deg, #4A7FA7, #6EA8D9);
  z-index: 9999;
}

/* =========================
   NAVBAR GLASS EFFECT
========================= */
nav {
  backdrop-filter: blur(14px);
  background: rgba(10, 25, 49, 0.85);
  transition: 0.3s ease;
}

/* =========================
   HERO GRADIENT ANIMATION
========================= */
.hero {
  background: linear-gradient(-45deg, #0A1931, #1A3D63, #4A7FA7, #6EA8D9);
  background-size: 400% 400%;
  animation: gradientMove 14s ease infinite;
}

@keyframes gradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* =========================
   FLOATING ICONS (SMOOTHER)
========================= */
.floating-icons img {
  position: absolute;
  width: 45px;
  opacity: 0.12;
  animation: floatIcon linear infinite;
  filter: blur(0.2px);
}

@keyframes floatIcon {
  0% { transform: translateY(110vh) rotate(0deg); }
  100% { transform: translateY(-120vh) rotate(360deg); }
}

/* =========================
   CARD DESIGN UPGRADE
========================= */
.card {
  border-radius: 18px;
  transition: 0.4s cubic-bezier(.2,.8,.2,1);
  will-change: transform;
}

.card:hover {
  transform: translateY(-14px) scale(1.05);
  box-shadow: 0 25px 60px rgba(0,0,0,0.18);
}

/* =========================
   GLASS IMPROVEMENT
========================= */
.glass {
  background: rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(18px);
  border-radius: 22px;
  border: 1px solid rgba(255,255,255,0.15);
}

.glass-light {
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(16px);
  border-radius: 22px;
}

/* =========================
   BUTTON MICROINTERACTIONS
========================= */
button, a {
  transition: all 0.25s ease;
}

button:hover, a:hover {
  transform: translateY(-3px);
}

/* =========================
   SECTION SPACING POLISH
========================= */
section {
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

/* =========================
   ICON DEPTH
========================= */
div[class*="bg-[#E8F1F8]"] {
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);
}

/* =========================
   CTA GLOW IMPROVEMENT
========================= */
.cta-box {
  background: linear-gradient(135deg, #4A7FA7, #6EA8D9);
  border-radius: 24px;
  padding: 50px;
  box-shadow: 0 30px 80px rgba(0,0,0,0.25);
}
</style>
</head>

<body>

<!-- PROGRESS BAR -->
<div id="progressBar"></div>

<!-- NAVBAR -->
<nav class="text-white sticky top-0 z-50 shadow-lg">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-extrabold text-xl tracking-wide">MedLog</h1>

    <div class="hidden md:flex gap-8 text-sm opacity-90">
      <a href="#features">Features</a>
      <a href="#how">How it Works</a>
      <a href="#about">About</a>
    </div>

    <a href="auth/login.php"
       class="border px-4 py-2 rounded-lg hover:bg-white hover:text-[#0A1931]">
      Login
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-white py-32 text-center relative overflow-hidden">

  <!-- PARTICLES -->
  <div class="particles">
  <span style="--i:1"></span>
  <span style="--i:2"></span>
  <span style="--i:3"></span>
  <span style="--i:4"></span>
  <span style="--i:5"></span>
  <span style="--i:6"></span>
  <span style="--i:7"></span>
  <span style="--i:8"></span>
  <span style="--i:9"></span>
</div>
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

    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight" data-aos="fade-up">
      Official School Clinic Management System
    </h1>

    <p class="mt-6 text-xl text-[#B3CFE5] max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="200">
      A centralized system designed for school nurses and administrators to securely manage student health records, clinic visits, and medical reports.
    </p>

    <div class="mt-10" data-aos="zoom-in" data-aos-delay="400">
      <a href="auth/login.php"
         class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold shadow-lg hover:scale-105">
        Access School System
      </a>
    </div>

    <lottie-player
      src="https://assets7.lottiefiles.com/packages/lf20_tutvdkg0.json"
      style="width:420px;height:420px;margin:auto;margin-top:60px"
      loop autoplay>
    </lottie-player>

  </div>
</section>

<!-- STATS -->
<section class="py-24 bg-[#F6FAFD]">
  <div class="max-w-6xl mx-auto">

    <h2 class="text-center text-3xl font-bold mb-12" data-aos="fade-up">
      System Overview
    </h2>

    <div class="grid md:grid-cols-4 gap-6">

      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-2">
        <div class="text-3xl">📄</div>
        <h3 class="text-2xl font-extrabold mt-3">100%</h3>
        <p class="text-gray-500">Digital Records</p>
        <div class="mt-4 h-1 bg-gray-100 rounded-full">
          <div class="h-1 bg-[#4A7FA7] rounded-full w-full"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-2">
        <div class="text-3xl">⏱</div>
        <h3 class="text-2xl font-extrabold mt-3">24/7</h3>
        <p class="text-gray-500">System Access</p>
        <div class="mt-4 h-1 bg-gray-100 rounded-full">
          <div class="h-1 bg-[#6EA8D9] rounded-full w-full"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-2">
        <div class="text-3xl">📊</div>
        <h3 class="text-2xl font-extrabold mt-3">Live</h3>
        <p class="text-gray-500">Health Tracking</p>
        <div class="mt-4 h-1 bg-gray-100 rounded-full">
          <div class="h-1 bg-[#1A3D63] rounded-full w-4/5"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl hover:-translate-y-2">
        <div class="text-3xl">🔒</div>
        <h3 class="text-2xl font-extrabold mt-3">Secure</h3>
        <p class="text-gray-500">Data Protection</p>
        <div class="mt-4 h-1 bg-gray-100 rounded-full">
          <div class="h-1 bg-green-500 rounded-full w-full"></div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="py-24 text-center">
<!-- FEATURES -->
<section id="features" class="py-28 text-center bg-white relative overflow-hidden">

  <div class="max-w-6xl mx-auto px-6">

    <p class="text-gray-500 mb-4" data-aos="fade-up">
      Everything your school clinic needs in one system
    </p>

    <h2 class="text-4xl font-extrabold mb-14" data-aos="fade-up">
      Features
    </h2>

    <div class="grid md:grid-cols-3 gap-10">

      <!-- CARD 1 -->
      <div class="group bg-white border border-gray-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl hover:-translate-y-3">
        <div class="text-5xl">🩺</div>
        <h3 class="font-bold text-xl mt-5">Student Health Records</h3>
        <p class="text-gray-500 mt-3">
          Store complete student medical history securely and access it instantly.
        </p>
      </div>

      <!-- CARD 2 -->
      <div class="group bg-white border border-gray-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl hover:-translate-y-3">
        <div class="text-5xl">💊</div>
        <h3 class="font-bold text-xl mt-5">Medication Tracking</h3>
        <p class="text-gray-500 mt-3">
          Track medicines, treatments, and clinic supply usage in real time.
        </p>
      </div>

      <!-- CARD 3 -->
      <div class="group bg-white border border-gray-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl hover:-translate-y-3">
        <div class="text-5xl">📈</div>
        <h3 class="font-bold text-xl mt-5">Smart Health Reports</h3>
        <p class="text-gray-500 mt-3">
          Generate automatic reports for school administrators and nurses.
        </p>
      </div>

    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-24 text-center">
  <div class="cta-box text-white max-w-4xl mx-auto">
    <h2 class="text-4xl font-bold mb-6">Ready to Deploy in Your School Clinic</h2>
    <a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl font-semibold">
      Launch MedLog
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="bg-[#0A1931] text-white text-center py-6">
  MedLog © 2026
</footer>

<script>
AOS.init({ duration: 900, once: false });

window.addEventListener("scroll", () => {
  const scroll = document.documentElement.scrollTop;
  const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  document.getElementById("progressBar").style.width = (scroll / height) * 100 + "%";
});
</script>

</body>
</html>