<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MedLog | Smart Clinic System</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui']
      },
      colors: {
        primary: '#0A1931',
        secondary: '#1A3D63',
        accent: '#4A7FA7',
        soft: '#B3CFE5',
        light: '#F6FAFD',
      }
    }
  }
}
</script>

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- Lottie -->
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<!-- AOS -->
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

<!-- ✅ FIXED PATH -->
<link rel="stylesheet" href="Css/homepage.css">

</head>

<body class="bg-light font-sans">

<!-- NAVBAR -->
<nav class="bg-primary text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl">MedLog</h1>

    <!-- NAV LINKS -->
    <div class="hidden md:flex space-x-6">
      <a href="#problems">Problems</a>
      <a href="#solution">Features</a>
      <a href="#preview">Preview</a>
    </div>

    <a href="auth/login.php"
       class="px-4 py-2 border border-soft rounded-lg hover:bg-secondary btn-hover">
      Login
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero-animated text-white py-28 text-center">
  <div class="max-w-5xl mx-auto px-6 glass p-10" data-aos="fade-up">

    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight">
      Modern Clinic Management for Schools
    </h1>

    <p class="mt-6 text-lg text-soft">
      Replace paperwork with a smart, centralized system for student health,
      inventory, and analytics.
    </p>

    <div class="mt-8">
      <a href="auth/login.php?redirect=dashboard.php"
         class="px-6 py-3 bg-white text-primary rounded-xl btn-hover">
        Get Started
      </a>
    </div>

    <lottie-player
      src="https://assets10.lottiefiles.com/packages/lf20_0yfsb3a1.json"
      style="width:320px;height:320px;margin:auto;"
      loop autoplay>
    </lottie-player>

  </div>
</section>

<!-- PROBLEMS -->
<section id="problems" class="py-20 text-center bg-white">
  <h2 class="text-3xl font-bold text-primary mb-10" data-aos="fade-up">
    Clinic Problems We Solve
  </h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6 px-6">

    <div class="glass-light p-6" data-aos="fade-up">
      <h3 class="font-bold">Manual Records</h3>
      <p class="text-gray-600 text-sm">Paper-based tracking is slow and messy.</p>
    </div>

    <div class="glass-light p-6" data-aos="fade-up" data-aos-delay="100">
      <h3 class="font-bold">Inventory Issues</h3>
      <p class="text-gray-600 text-sm">Medicines are hard to monitor.</p>
    </div>

    <div class="glass-light p-6" data-aos="fade-up" data-aos-delay="200">
      <h3 class="font-bold">No Insights</h3>
      <p class="text-gray-600 text-sm">No reports for decision-making.</p>
    </div>

  </div>
</section>

<!-- SOLUTION -->
<section id="solution" class="py-20 bg-light text-center">
  <h2 class="text-3xl font-bold text-primary mb-12" data-aos="fade-up">
    One System. Everything You Need.
  </h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6 px-6">

    <div class="glass-light p-6" data-aos="zoom-in">
      <h3 class="font-bold mt-4">Health Records</h3>
    </div>

    <div class="glass-light p-6" data-aos="zoom-in" data-aos-delay="100">
      <h3 class="font-bold mt-4">Inventory</h3>
    </div>

    <div class="glass-light p-6" data-aos="zoom-in" data-aos-delay="200">
      <h3 class="font-bold mt-4">Analytics</h3>
    </div>

  </div>
</section>

<!-- PREVIEW -->
<section id="preview" class="py-24 bg-white text-center">
  <h2 class="text-3xl font-bold text-primary mb-12" data-aos="fade-up">
    See MedLog in Action
  </h2>

  <div class="max-w-5xl mx-auto glass-light p-6" data-aos="zoom-in">
    <img 
      src="https://cdn-icons-png.flaticon.com/512/4149/4149674.png"
      class="rounded-xl shadow-lg mx-auto block"
    >
  </div>
</section>

<!-- STATS -->
<section class="py-20 bg-secondary text-white text-center">
  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6">

    <div data-aos="fade-up">
      <h2 class="text-4xl font-bold">1000+</h2>
      <p class="text-soft">Records</p>
    </div>

    <div data-aos="fade-up" data-aos-delay="100">
      <h2 class="text-4xl font-bold">500+</h2>
      <p class="text-soft">Visits</p>
    </div>

    <div data-aos="fade-up" data-aos-delay="200">
      <h2 class="text-4xl font-bold">99%</h2>
      <p class="text-soft">Efficiency</p>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="py-20 text-center hero-animated text-white">
  <div class="glass p-10 max-w-xl mx-auto" data-aos="fade-up">

    <h2 class="text-3xl font-bold mb-4">Start Using MedLog Today</h2>

    <a href="auth/login.php"
       class="px-6 py-3 bg-white text-primary rounded-xl btn-hover">
      Get Started
    </a>

  </div>
</section>

<footer class="bg-primary text-white text-center py-6 text-sm">
  <p>© 2026 MedLog</p>
</footer>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  AOS.init({
    once: true,
    duration: 1000
  });
});
</script>

</body>
</html>