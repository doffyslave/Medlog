<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MedLog | Smart School Clinic System</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<style>
html { scroll-behavior: smooth; }

body { font-family: 'Inter', system-ui; overflow-x: hidden; background:#F6FAFD; }

/* GRADIENT HERO */
.hero{
  background:linear-gradient(-45deg,#0A1931,#1A3D63,#4A7FA7,#6EA8D9);
  background-size:400% 400%;
  animation:gradientMove 12s ease infinite;
  position:relative;
  overflow:hidden;
}
@keyframes gradientMove{
  0%{background-position:0% 50%}
  50%{background-position:100% 50%}
  100%{background-position:0% 50%}
}

/* PARTICLES (RESTORED) */
.particles span {
  position:absolute;
  width:6px;
  height:6px;
  background:rgba(255,255,255,0.6);
  border-radius:50%;
  animation:float 20s linear infinite;
  bottom:-50px;
}
@keyframes float{
  0%{transform:translateY(0);opacity:0}
  20%{opacity:1}
  100%{transform:translateY(-100vh);opacity:0}
}
.particles span:nth-child(1){left:10%;animation-duration:18s}
.particles span:nth-child(2){left:20%;animation-duration:22s}
.particles span:nth-child(3){left:30%;animation-duration:17s}
.particles span:nth-child(4){left:40%;animation-duration:25s}
.particles span:nth-child(5){left:50%;animation-duration:19s}
.particles span:nth-child(6){left:60%;animation-duration:21s}
.particles span:nth-child(7){left:70%;animation-duration:23s}
.particles span:nth-child(8){left:80%;animation-duration:20s}
.particles span:nth-child(9){left:90%;animation-duration:18s}

/* FLOATING ICONS (NOW WITH DEPTH LAYERS) */
.floating-icons{position:absolute;inset:0;overflow:hidden;pointer-events:none}
.layer{position:absolute;width:100%;height:100%}

.layer.back img{
  position:absolute;width:60px;opacity:0.08;filter:blur(2px);
  animation:floatBack linear infinite;
}
.layer.mid img{
  position:absolute;width:50px;opacity:0.12;filter:blur(1px);
  animation:floatMid linear infinite;
}
.layer.front img{
  position:absolute;width:45px;opacity:0.18;
  animation:floatFront linear infinite;
}

@keyframes floatBack{0%{transform:translateY(100vh)}100%{transform:translateY(-120vh)}}
@keyframes floatMid{0%{transform:translateY(100vh) rotate(0)}100%{transform:translateY(-120vh) rotate(360deg)}}
@keyframes floatFront{0%{transform:translateY(100vh) rotate(0)}100%{transform:translateY(-120vh) rotate(-360deg)}}

.layer img:nth-child(1){left:10%}
.layer img:nth-child(2){left:25%}
.layer img:nth-child(3){left:40%}
.layer img:nth-child(4){left:60%}
.layer img:nth-child(5){left:75%}
.layer img:nth-child(6){left:90%}

.back img:nth-child(1){animation-duration:28s}
.back img:nth-child(2){animation-duration:32s}
.back img:nth-child(3){animation-duration:30s}
.mid img:nth-child(1){animation-duration:20s}
.mid img:nth-child(2){animation-duration:22s}
.mid img:nth-child(3){animation-duration:18s}
.front img:nth-child(1){animation-duration:14s}
.front img:nth-child(2){animation-duration:16s}
.front img:nth-child(3){animation-duration:15s}

/* CARDS */
.card{transition:0.35s}
.card:hover{transform:translateY(-14px) scale(1.05);box-shadow:0 20px 40px rgba(0,0,0,0.15)}

/* GLASS (RESTORED) */
.glass{background:rgba(0,0,0,0.35);backdrop-filter:blur(16px);border-radius:20px;border:1px solid rgba(255,255,255,0.2)}
.glass-light{background:rgba(255,255,255,0.8);backdrop-filter:blur(12px);border-radius:20px;transition:0.3s}
.glass-light:hover{transform:translateY(-8px) scale(1.03)}

/* CTA */
.cta-box{background:linear-gradient(135deg,#4A7FA7,#6EA8D9);border-radius:20px;padding:40px;box-shadow:0 20px 50px rgba(0,0,0,0.2)}
.btn-hover{transition:0.25s}
.btn-hover:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(74,127,167,0.4)}

/* LOTTIE */
.lottie-adjust{margin-top:80px}
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
<a href="auth/login.php" class="border px-4 py-2 rounded-lg hover:bg-white hover:text-[#0A1931] transition">Login</a>
</div>
</nav>

<!-- HERO -->
<section class="hero text-white py-32 text-center relative overflow-hidden">

<!-- PARTICLES RESTORED -->
<div class="particles">
<span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
</div>

<!-- FLOATING ICONS WITH DEPTH -->
<div class="floating-icons">
  <div class="layer back">
    <img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
  </div>
  <div class="layer mid">
    <img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png">
    <img src="https://cdn-icons-png.flaticon.com/512/2785/2785819.png">
    <img src="https://cdn-icons-png.flaticon.com/512/2966/2966327.png">
  </div>
  <div class="layer front">
    <img src="https://cdn-icons-png.flaticon.com/512/2966/2966480.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3209/3209265.png">
    <img src="https://cdn-icons-png.flaticon.com/512/3771/3771417.png">
  </div>
</div>

<div class="max-w-6xl mx-auto px-6">
<h1 class="text-6xl md:text-7xl font-extrabold leading-tight" data-aos="fade-up">
School Clinic Management Made Simple
</h1>

<p class="mt-6 text-xl text-[#B3CFE5] max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="200">
MedLog helps school nurses manage student health records, monitor clinic visits, track medicine inventory, and generate reports — all in one powerful system.
</p>

<div class="mt-10" data-aos="zoom-in" data-aos-delay="400">
<a href="auth/login.php" class="bg-white text-[#0A1931] px-10 py-4 rounded-xl text-lg font-semibold shadow-lg hover:scale-105 transition">
Get Started
</a>
</div>

<lottie-player src="https://assets7.lottiefiles.com/packages/lf20_tutvdkg0.json"
style="width:420px;height:420px;margin:auto;margin-top:50px"
loop autoplay></lottie-player>
</div>
</section>

<!-- REST OF YOUR SECTIONS REMAIN UNCHANGED -->

<script>AOS.init({duration:1000,once:true});</script>
</body>
</html>