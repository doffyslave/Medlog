<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MedLog - School Health System</title>

  <!-- FONT -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link rel="stylesheet" href="Css/homepage.css">

  <!-- LOTTIE -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
  <div class="logo">MedLog</div>

  <nav class="nav-links" id="navLinks">
    <a href="#">Home</a>
    <a href="#features">Features</a>
    <a href="#preview">Preview</a>
    <a href="#testimonials">Testimonials</a>
    <a href="#" class="btn">Get Started</a>
  </nav>

  <div class="hamburger" onclick="toggleMenu()">☰</div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-text">
    <h1>Smarter Student Health Management</h1>
    <p>MedLog helps schools track, manage, and improve student health records with ease and reliability.</p>
    <a href="#" class="btn-primary">Start Managing Today</a>
  </div>

  <div class="hero-img">
    <lottie-player src="https://assets2.lottiefiles.com/packages/lf20_0yfsb3a1.json"
      background="transparent" speed="1" loop autoplay>
    </lottie-player>
  </div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
  <h2>Why Choose MedLog?</h2>

  <div class="feature-grid">
    <div class="card">
      <h3>Easy Record Tracking</h3>
      <p>Quickly log and access student health data anytime.</p>
    </div>

    <div class="card">
      <h3>Real-Time Updates</h3>
      <p>Instant updates for better monitoring and response.</p>
    </div>

    <div class="card">
      <h3>Secure & Reliable</h3>
      <p>Data is protected and safely stored.</p>
    </div>
  </div>
</section>

<!-- DASHBOARD PREVIEW -->
<section class="preview" id="preview">
  <h2>See MedLog in Action</h2>
  <p>A simple and powerful dashboard designed for school staff.</p>

  <div class="mockup">
    <img src="Images/dashboard.png" alt="MedLog dashboard interface preview">
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials" id="testimonials">
  <h2>What Users Say</h2>

  <div class="testimonial-grid">
    <div class="testimonial">
      <p>"MedLog made managing student health records so much easier."</p>
      <span>- School Nurse</span>
    </div>

    <div class="testimonial">
      <p>"Simple, fast, and very helpful for daily operations."</p>
      <span>- Staff Member</span>
    </div>

    <div class="testimonial">
      <p>"A reliable system that improves student care."</p>
      <span>- Administrator</span>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <h2>Start Improving Student Health Management Today</h2>
  <a href="#" class="btn-primary">Get Started</a>
</section>

<!-- FOOTER -->
<footer class="footer">
  <p>© 2026 MedLog. All rights reserved.</p>
</footer>

<script>
function toggleMenu() {
  document.getElementById("navLinks").classList.toggle("active");
}
</script>

</body>
</html>