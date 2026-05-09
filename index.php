<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedLog | Smart School Clinic System</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<style>
body{font-family:'Inter',system-ui;overflow-x:hidden}

/* GRADIENT HERO */
.hero{
background:linear-gradient(-45deg,#0A1931,#1A3D63,#4A7FA7,#6EA8D9);
background-size:400% 400%;
animation:gradientMove 12s ease infinite;
}
@keyframes gradientMove{0%{background-position:0%}50%{background-position:100%}100%{background-position:0%}}

/* FLOATING ICONS */
.floating-icons img{
position:absolute;width:50px;opacity:0.12;
animation:floatIcon linear infinite;
}
@keyframes floatIcon{0%{transform:translateY(100vh) rotate(0)}100%{transform:translateY(-120vh) rotate(360deg)}}

/* positions */
.floating-icons img:nth-child(1){left:10%;animation-duration:18s}
.floating-icons img:nth-child(2){left:25%;animation-duration:22s}
.floating-icons img:nth-child(3){left:40%;animation-duration:20s}
.floating-icons img:nth-child(4){left:60%;animation-duration:25s}
.floating-icons img:nth-child(5){left:75%;animation-duration:19s}
.floating-icons img:nth-child(6){left:90%;animation-duration:23s}

/* CARD */
.card{transition:0.3s}
.card:hover{transform:translateY(-12px) scale(1.04)}

</style>
</head>

<body class="bg-[#F6FAFD]">

<!-- NAVBAR -->
<nav class="bg-[#0A1931] text-white sticky top-0 z-50">
<div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
<h1 class="font-bold text-xl">MedLog</h1>
<div class="hidden md:flex gap-6">
<a href="#features">Features</a>
<a href="#how">How it Works</a>
<a href="#about">About</a>
</div>
<a href="auth/login.php" class="border px-4 py-2 rounded-lg">Login</a>
</div>
</nav>

<!-- HERO -->
<section class="hero text-white py-32 text-center relative overflow-hidden">

<div class="floating-icons">
<img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
<img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
<img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
<img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png">
<img src="https://cdn-icons-png.flaticon.com/512/2785/2785819.png">
<img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png">
</div>

<div class="max-w-6xl mx-auto px-6">

<h1 class="text-6xl md:text-7xl font-extrabold leading-tight">
School Clinic Management Made Simple
</h1>

<p class="mt-6 text-xl text-[#B3CFE5] max-w-3xl mx-auto">
MedLog helps school nurses manage student health records, monitor clinic visits, track medicine inventory, and generate reports — all in one powerful system.
</p>

<div class="mt-10">
<a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold">
Get Started
</a>
</div>

<!-- BEST MATCH ANIMATION (HEALTHCARE DASHBOARD STYLE) -->
<lottie-player
src="https://assets1.lottiefiles.com/packages/lf20_iorpbol0.json"
style="width:420px;height:420px;margin:auto;margin-top:50px"
loop autoplay>
</lottie-player>

</div>
</section>

<!-- STATS SECTION -->
<section class="py-20 text-center">
<div class="max-w-6xl mx-auto grid md:grid-cols-4 gap-8">
<div>
<h3 class="text-4xl font-bold text-[#0A1931]">100%</h3>
<p>Digital Records</p>
</div>
<div>
<h3 class="text-4xl font-bold text-[#0A1931]">24/7</h3>
<p>Access</p>
</div>
<div>
<h3 class="text-4xl font-bold text-[#0A1931]">Real-Time</h3>
<p>Inventory Tracking</p>
</div>
<div>
<h3 class="text-4xl font-bold text-[#0A1931]">Secure</h3>
<p>Data Protection</p>
</div>
</div>
</section>

<!-- FEATURES -->
<section id="features" class="py-24 bg-white text-center">
<h2 class="text-4xl font-bold mb-16">Everything You Need</h2>

<div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-10 px-6">

<div class="p-8 shadow rounded-2xl card">
<h3 class="font-bold text-xl mb-4">Health Records</h3>
<p>Track student illnesses, allergies, visits, and treatment history in one place.</p>
</div>

<div class="p-8 shadow rounded-2xl card">
<h3 class="font-bold text-xl mb-4">Inventory System</h3>
<p>Monitor medicines, supplies, and expiration dates automatically.</p>
</div>

<div class="p-8 shadow rounded-2xl card">
<h3 class="font-bold text-xl mb-4">Reports & Analytics</h3>
<p>Generate insights for school administrators and health planning.</p>
</div>

</div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="py-24 text-center">
<h2 class="text-4xl font-bold mb-16">How MedLog Works</h2>

<div class="max-w-5xl mx-auto grid md:grid-cols-3 gap-10">
<div>
<h3 class="font-bold text-xl">1. Record</h3>
<p>Input student health data during clinic visits.</p>
</div>
<div>
<h3 class="font-bold text-xl">2. Track</h3>
<p>Monitor medicine usage and student trends.</p>
</div>
<div>
<h3 class="font-bold text-xl">3. Analyze</h3>
<p>Generate reports and insights instantly.</p>
</div>
</div>
</section>

<!-- ABOUT -->
<section id="about" class="bg-[#0A1931] text-white py-24 text-center">
<h2 class="text-4xl font-bold mb-6">Built for School Nurses</h2>
<p class="max-w-3xl mx-auto text-[#B3CFE5]">
MedLog is designed specifically for school clinics to reduce paperwork, improve efficiency, and ensure every student receives proper healthcare attention.
</p>
</section>

<!-- CTA -->
<section class="py-24 text-center">
<h2 class="text-4xl font-bold mb-6">Start Managing Your Clinic Smarter</h2>
<a href="auth/login.php" class="bg-[#0A1931] text-white px-10 py-4 rounded-xl text-lg">
Launch MedLog
</a>
</section>

<footer class="bg-[#0A1931] text-white text-center py-6">
<p>© 2026 MedLog</p>
</footer>

</body>
</html>