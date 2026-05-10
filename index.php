<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>MedLog | Smart School Clinic System</title>

<!-- Fonts & Libraries -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet" />
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<style>
html {
  scroll-behavior: smooth;
}

body {
  font-family: 'Inter', system-ui;
  overflow-x: hidden;
  background: #F6FAFD;
}

/* HERO GRADIENT */
.hero {
  background: linear-gradient(-45deg,#0A1931,#1A3D63,#4A7FA7,#6EA8D9);
  background-size: 400% 400%;
  animation: gradientMove 14s ease infinite;
  position: relative;
  overflow: hidden;
}

@keyframes gradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* FLOATING ICONS - optimized */
.floating-icons img {
  position: absolute;
  width: 45px;
  opacity: 0.1;
  animation: floatUp linear infinite;
}

@keyframes floatUp {
  0% { transform: translateY(100vh) rotate(0deg); }
  100% { transform: translateY(-120vh) rotate(360deg); }
}

/* CARDS */
.card {
  transition: 0.3s ease;
  border: 1px solid rgba(0,0,0,0.05);
}

.card:hover {
  transform: translateY(-10px);
  box-shadow: 0 18px 40px rgba(0,0,0,0.12);
}

/* CTA */
.cta-box {
  background: linear-gradient(135deg,#4A7FA7,#6EA8D9);
  border-radius: 24px;
  padding: 50px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.25);
}

/* BUTTON */
.btn {
  transition: 0.25s ease;
}

.btn:hover {
  transform: translateY(-3px);
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="bg-[#0A1931] text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl tracking-wide">MedLog</h1>
    <div class="hidden md:flex gap-6 text-sm">
      <a href="#features" class="hover:text-blue-300">Features</a>
      <a href="#how" class="hover:text-blue-300">How it Works</a>
      <a href="#about" class="hover:text-blue-300">About</a>
    </div>
    <a href="auth/login.php" class="btn border px-4 py-2 rounded-lg hover:bg-white hover:text-[#0A1931]">
      Login
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-white py-28 text-center">

  <div class="floating-icons">
    <img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
    <img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png">
    <img src="https://cdn-icons-png.flaticon.com/512/2785/2785819.png">
  </div>

  <div class="max-w-6xl mx-auto px-6">
    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight" data-aos="fade-up">
      Smarter School Clinic Management
    </h1>

    <p class="mt-6 text-lg text-blue-100 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="150">
      MedLog simplifies student health records, clinic visits, medicine tracking, and reporting in one system.
    </p>

    <div class="mt-10" data-aos="zoom-in" data-aos-delay="300">
      <a href="auth/login.php" class="bg-white text-[#0A1931] px-8 py-4 rounded-xl font-semibold shadow-lg btn">
        Get Started
      </a>
    </div>

    <div class="mt-12">
      <lottie-player
        src="https://assets7.lottiefiles.com/packages/lf20_tutvdkg0.json"
        style="width:360px;height:360px;margin:auto"
        loop autoplay>
      </lottie-player>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="py-20 text-center bg-white">
  <div class="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
    <div><h3 class="text-4xl font-bold">100%</h3><p>Digital Records</p></div>
    <div><h3 class="text-4xl font-bold">24/7</h3><p>Access</p></div>
    <div><h3 class="text-4xl font-bold">Real-Time</h3><p>Tracking</p></div>
    <div><h3 class="text-4xl font-bold">Secure</h3><p>Data</p></div>
  </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-24 text-center">
  <h2 class="text-4xl font-bold mb-16" data-aos="fade-up">Everything You Need</h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 px-6">

    <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up">
      <h3 class="font-bold text-xl mb-3">Health Records</h3>
      <p>Manage student illnesses, allergies, and clinic history efficiently.</p>
    </div>

    <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up" data-aos-delay="150">
      <h3 class="font-bold text-xl mb-3">Inventory System</h3>
      <p>Track medicines, supplies, and expiration dates automatically.</p>
    </div>

    <div class="p-8 bg-white rounded-2xl card" data-aos="fade-up" data-aos-delay="300">
      <h3 class="font-bold text-xl mb-3">Analytics</h3>
      <p>Generate reports and insights for better decision-making.</p>
    </div>

  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="py-24 text-center bg-white">
  <h2 class="text-4xl font-bold mb-16" data-aos="fade-up">How It Works</h2>

  <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-10">
    <div data-aos="zoom-in">
      <h3 class="font-bold">1. Record</h3>
      <p>Input student health data.</p>
    </div>

    <div data-aos="zoom-in" data-aos-delay="150">
      <h3 class="font-bold">2. Track</h3>
      <p>Monitor clinic activities.</p>
    </div>

    <div data-aos="zoom-in" data-aos-delay="300">
      <h3 class="font-bold">3. Analyze</h3>
      <p>Generate insights instantly.</p>
    </div>
  </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-28 text-center text-white" style="background: radial-gradient(circle at top,#1A3D63,#0A1931)">
  <h2 class="text-4xl font-bold mb-6" data-aos="fade-up">Built for School Nurses</h2>
  <p class="max-w-3xl mx-auto text-blue-200 mb-10" data-aos="fade-up" data-aos-delay="150">
    MedLog reduces paperwork and improves clinic efficiency through smart digital tools.
  </p>

  <div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-8">
    <div class="bg-white/10 p-6 rounded-xl">⚡ Faster Workflow</div>
    <div class="bg-white/10 p-6 rounded-xl">🔒 Secure Data</div>
    <div class="bg-white/10 p-6 rounded-xl">📊 Smart Insights</div>
  </div>
</section>

<!-- CTA -->
<section class="py-24 text-center">
  <div class="max-w-4xl mx-auto cta-box" data-aos="zoom-in">
    <h2 class="text-4xl font-bold text-white mb-6">Start Using MedLog Today</h2>
    <a href="auth/login.php" class="bg-white text-[#0A1931] px-8 py-4 rounded-xl font-semibold btn">
      Launch System
    </a>
  </div>
</section>

<footer class="bg-[#0A1931] text-white text-center py-6">
  <p>© 2026 MedLog. All rights reserved.</p>
</footer>

<script>
AOS.init({ duration: 900, once: true });
</script>

</body>
</html>
