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

<style>
body { overflow-x:hidden; }

/* BACKGROUND FLOATING ICONS */
.floating-icons img {
  position:absolute;
  width:40px;
  opacity:0.15;
  animation: floatIcon 20s linear infinite;
}

@keyframes floatIcon {
  0% { transform: translateY(100vh) rotate(0deg); }
  100% { transform: translateY(-120vh) rotate(360deg); }
}

/* DIFFERENT POSITIONS */
.floating-icons img:nth-child(1){ left:10%; animation-duration:18s; }
.floating-icons img:nth-child(2){ left:25%; animation-duration:22s; }
.floating-icons img:nth-child(3){ left:40%; animation-duration:20s; }
.floating-icons img:nth-child(4){ left:60%; animation-duration:25s; }
.floating-icons img:nth-child(5){ left:75%; animation-duration:19s; }
.floating-icons img:nth-child(6){ left:90%; animation-duration:23s; }

/* HERO GRADIENT */
.hero {
  background: linear-gradient(-45deg,#0A1931,#1A3D63,#4A7FA7,#6EA8D9);
  background-size:400% 400%;
  animation: gradientMove 12s ease infinite;
}

@keyframes gradientMove {
  0%{background-position:0%}
  50%{background-position:100%}
  100%{background-position:0%}
}

/* GLASS */
.glass {
  background: rgba(0,0,0,0.35);
  backdrop-filter: blur(16px);
  border-radius: 20px;
}

/* HOVER */
.card:hover {
  transform: translateY(-10px) scale(1.03);
  transition:0.3s;
}

</style>
</head>

<body class="bg-light font-sans">

<!-- NAVBAR -->
<nav class="bg-primary text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl">MedLog</h1>
    <a href="auth/login.php" class="px-4 py-2 border border-soft rounded-lg hover:bg-secondary">Login</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-white py-32 text-center relative overflow-hidden">

<!-- FLOATING MEDICAL ICONS -->
<div class="floating-icons">
  <img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
  <img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png">
  <img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
  <img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
  <img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png">
  <img src="https://cdn-icons-png.flaticon.com/512/2785/2785819.png">
</div>

<div class="max-w-5xl mx-auto glass p-12 relative z-10">

<h1 class="text-6xl font-extrabold leading-tight">
Smart School Clinic System
</h1>

<p class="mt-6 text-lg text-soft">
Digitize records, monitor student health, track medicine inventory, and generate reports instantly.
</p>

<div class="mt-8">
<a href="auth/login.php" class="px-8 py-4 bg-white text-primary rounded-xl font-semibold">
Get Started
</a>
</div>

<!-- NEW MEDICAL LOTTIE -->
<!-- BETTER SCHOOL NURSE / MEDICAL LOTTIE -->
<lottie-player
  src="https://assets9.lottiefiles.com/packages/lf20_j1adxtyb.json"
  style="width:360px;height:360px;margin:auto;margin-top:40px;"
  loop autoplay>
</lottie-player>

</div>
</section>

<!-- FEATURES -->
<section class="py-24 text-center">
<h2 class="text-4xl font-bold text-primary mb-16">Core Features</h2>

<div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 px-6">

<div class="bg-white p-8 rounded-2xl shadow card">
<h3 class="font-bold text-xl mb-4">Student Health Records</h3>
<p>Securely store and manage student medical histories, allergies, and clinic visits.</p>
</div>

<div class="bg-white p-8 rounded-2xl shadow card">
<h3 class="font-bold text-xl mb-4">Medicine Inventory</h3>
<p>Track stock levels, expiration dates, and medicine usage in real-time.</p>
</div>

<div class="bg-white p-8 rounded-2xl shadow card">
<h3 class="font-bold text-xl mb-4">Analytics & Reports</h3>
<p>Generate insights and reports for better decision-making and compliance.</p>
</div>

</div>
</section>

<!-- EXTRA INFO SECTION -->
<section class="bg-primary text-white py-24 text-center">
<h2 class="text-4xl font-bold mb-8">Why MedLog?</h2>

<div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-10 px-6">

<div>
<h3 class="font-bold text-xl mb-2">⚡ Fast & Efficient</h3>
<p>Reduce paperwork and save time with digital workflows.</p>
</div>

<div>
<h3 class="font-bold text-xl mb-2">🔒 Secure Data</h3>
<p>Ensure student health information is protected and confidential.</p>
</div>

<div>
<h3 class="font-bold text-xl mb-2">📊 Smart Insights</h3>
<p>Understand trends in student health and clinic usage.</p>
</div>

<div>
<h3 class="font-bold text-xl mb-2">🏫 Built for Schools</h3>
<p>Designed specifically for school nurses and administrators.</p>
</div>

</div>
</section>

<footer class="bg-primary text-white text-center py-6 text-sm">
<p>© 2026 MedLog</p>
</footer>

</body>
</html>
