<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>MedLog | Smart School Clinic System</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet" />
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<style>
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui;overflow-x:hidden;background:#F6FAFD}

/* HERO */
.hero{
  background:linear-gradient(-45deg,#0A1931,#1A3D63,#4A7FA7,#6EA8D9);
  background-size:400% 400%;
  animation:gradientMove 14s ease infinite;
  position:relative;
  overflow:hidden;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
}
@keyframes gradientMove{
  0%{background-position:0% 50%}
  50%{background-position:100% 50%}
  100%{background-position:0% 50%}
}

/* FLOATING LAYERS (DEPTH EFFECT) */
.floating-icons{
  position:absolute;
  inset:0;
  pointer-events:none;
}

.layer{
  position:absolute;
  width:100%;
  height:100%;
}

/* BACK LAYER (slow + blur) */
.layer.back img{
  position:absolute;
  width:60px;
  opacity:0.08;
  filter:blur(2px);
  animation:floatBack linear infinite;
}
@keyframes floatBack{
  0%{transform:translateY(100vh) scale(0.8)}
  100%{transform:translateY(-120vh) scale(0.8)}
}

/* MID LAYER */
.layer.mid img{
  position:absolute;
  width:50px;
  opacity:0.12;
  filter:blur(1px);
  animation:floatMid linear infinite;
}
@keyframes floatMid{
  0%{transform:translateY(100vh) rotate(0deg)}
  100%{transform:translateY(-120vh) rotate(360deg)}
}

/* FRONT LAYER (fast + sharp) */
.layer.front img{
  position:absolute;
  width:40px;
  opacity:0.18;
  animation:floatFront linear infinite;
}
@keyframes floatFront{
  0%{transform:translateY(100vh) rotate(0deg)}
  100%{transform:translateY(-120vh) rotate(-360deg)}
}

/* POSITIONS + SPEEDS */
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
.card{transition:0.3s;border:1px solid rgba(0,0,0,0.05)}
.card:hover{transform:translateY(-10px);box-shadow:0 18px 40px rgba(0,0,0,0.12)}

/* CTA */
.cta-box{
  background:linear-gradient(135deg,#4A7FA7,#6EA8D9);
  border-radius:24px;
  padding:50px;
  box-shadow:0 25px 60px rgba(0,0,0,0.25);
}
.btn{transition:0.25s}
.btn:hover{transform:translateY(-3px)}
</style>
</head>

<body>

<nav class="bg-[#0A1931] text-white sticky top-0 z-50 shadow">
  <div class="max-w-7xl mx-auto flex justify-between items-center px-6 py-4">
    <h1 class="font-bold text-xl">MedLog</h1>
    <div class="hidden md:flex gap-6 text-sm">
      <a href="#features">Features</a>
      <a href="#how">How it Works</a>
      <a href="#about">About</a>
    </div>
    <a href="auth/login.php" class="btn border px-4 py-2 rounded-lg hover:bg-white hover:text-[#0A1931]">Login</a>
  </div>
</nav>

<section class="hero text-white text-center">

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

  <div class="max-w-6xl mx-auto px-6 py-24 relative z-10">
    <h1 class="text-5xl md:text-7xl font-extrabold">Smarter School Clinic Management</h1>
    <p class="mt-6 text-lg text-blue-100 max-w-3xl mx-auto">
      MedLog simplifies clinic operations.
    </p>
  </div>

</section>

<script>
AOS.init({duration:900,once:true});
</script>

</body>
</html>
