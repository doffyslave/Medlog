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

<link rel="stylesheet" href="Css/homepage.css">

</head>

<body class="bg-light font-sans">

<!-- NAVBAR -->
<nav class="bg-primary text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl">MedLog</h1>

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

  <div class="max-w-5xl mx-auto px-6 glass p-10 relative z-10">

    <h1 class="text-5xl md:text-6xl font-extrabold leading-tight">
      Modern Clinic Management for Schools
    </h1>

    <p class="mt-6 text-lg text-soft">
      Manage student health records, clinic visits, and inventory in one system.
    </p>

    <div class="mt-8">
      <a href="auth/login.php"
         class="px-6 py-3 bg-white text-primary rounded-xl btn-hover">
        Get Started
      </a>
    </div>

    <!-- 👇 LOWERED ANIMATION -->
    <lottie-player
      class="lottie-adjust"
      src="https://assets10.lottiefiles.com/packages/lf20_0yfsb3a1.json"
      style="width:300px;height:300px;margin:auto;"
      loop autoplay>
    </lottie-player>

  </div>
</section>

<!-- ABOUT -->
<section id="about" class="py-20 bg-white text-center">
  <h2 class="text-3xl font-bold text-primary mb-6">About MedLog</h2>
  <p class="max-w-3xl mx-auto text-gray-600">
    MedLog helps school clinics digitize records, track medicine, and analyze student health data efficiently.
  </p>
</section>

<!-- FEATURES -->
<section id="solution" class="py-20 bg-light text-center">
  <h2 class="text-3xl font-bold text-primary mb-12">Features</h2>

  <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6 px-6">

    <div class="glass-light p-6">
      <h3 class="font-bold">Health Records</h3>
    </div>

    <div class="glass-light p-6">
      <h3 class="font-bold">Inventory</h3>
    </div>

    <div class="glass-light p-6">
      <h3 class="font-bold">Analytics</h3>
    </div>

  </div>
</section>

<footer class="bg-primary text-white text-center py-6 text-sm">
  <p>© 2026 MedLog</p>
</footer>

</body>
</html>