<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>MedLog | Smart Clinic System</title>

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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

<link rel="stylesheet" href="./Css/homepage.css">

</head>

<body class="bg-light font-sans">

<!-- NAVBAR -->
<nav class="bg-primary text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl glow">MedLog</h1>

    <div class="hidden md:flex space-x-6">
      <a href="#about">About</a>
      <a href="#problems">Problems</a>
      <a href="#solution">Features</a>
    </div>

    <a href="auth/login.php"
       class="px-4 py-2 border border-soft rounded-lg hover:bg-secondary btn-hover">
      Login
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero-animated text-white py-28 text-center">

  <!-- PARTICLES -->
  <div class="particles">
    <span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="max-w-5xl mx-auto px-6 glass p-10 relative z-10" data-aos="fade-up">

    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight glow">
      Modern Clinic Management for Schools
    </h1>

    <p class="mt-6 text-lg text-soft">
      A centralized platform to manage student health records, clinic visits,
      and medicine inventory — all in one system.
    </p>

    <div class="mt-8">
      <a href="auth/login.php"
         class="px-6 py-3 bg-white text-primary rounded-xl btn-hover">
        Get Started
      </a>
    </div>

    <lottie-player
      src="https://assets10.lottiefiles.com/packages/lf20_0yfsb3a1.json"
      style="width:300px;height:300px;margin:auto;"
      loop autoplay>
    </lottie-player>

  </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-20 bg-white text-center">
  <h2 class="text-3xl font-bold text-primary mb-6" data-aos="fade-up">
    About MedLog
  </h2>

  <p class="max-w-3xl mx-auto text-gray-600" data-aos="fade-up">
    MedLog is a web-based school clinic system designed to simplify healthcare
    management. It helps track student health records, monitor clinic visits,
    and manage medicine inventory efficiently — reducing paperwork and improving decision-making.
  </p>
</section>

<!-- PROBLEMS -->
<section id="problems" class="py-20 text-center bg-light">
  <h2 class="text-3xl font-bold text-primary mb-10" data-aos="fade-up">
    Problems We Solve
  </h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6 px-6">

    <div class="glass-light p-6" data-aos="fade-up">
      <h3 class="font-bold">Manual Records</h3>
      <p class="text-gray-600 text-sm">Paper systems are slow and error-prone.</p>
    </div>

    <div class="glass-light p-6" data-aos="fade-up" data-aos-delay="100">
      <h3 class="font-bold">Inventory Tracking</h3>
      <p class="text-gray-600 text-sm">Difficult to monitor medicine usage.</p>
    </div>

    <div class="glass-light p-6" data-aos="fade-up" data-aos-delay="200">
      <h3 class="font-bold">Lack of Insights</h3>
      <p class="text-gray-600 text-sm">No data for better decisions.</p>
    </div>

  </div>
</section>

<!-- FEATURES -->
<section id="solution" class="py-20 bg-white text-center">
  <h2 class="text-3xl font-bold text-primary mb-12" data-aos="fade-up">
    Powerful Features
  </h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6 px-6">

    <div class="glass-light p-6" data-aos="zoom-in">
      <h3 class="font-bold mt-4">Health Records</h3>
      <p class="text-sm text-gray-600">Track student health history easily.</p>
    </div>

    <div class="glass-light p-6" data-aos="zoom-in" data-aos-delay="100">
      <h3 class="font-bold mt-4">Inventory System</h3>
      <p class="text-sm text-gray-600">Monitor medicines in real-time.</p>
    </div>

    <div class="glass-light p-6" data-aos="zoom-in" data-aos-delay="200">
      <h3 class="font-bold mt-4">Analytics Dashboard</h3>
      <p class="text-sm text-gray-600">Get insights and reports instantly.</p>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="py-20 text-center hero-animated text-white">
  <div class="glass p-10 max-w-xl mx-auto" data-aos="fade-up">

    <h2 class="text-3xl font-bold mb-4 glow">Start Using MedLog Today</h2>

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
AOS.init({
  once: true,
  duration: 1000
});
</script>

</body>
</html>