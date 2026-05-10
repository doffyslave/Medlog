<!-- COLOR PALETTE APPLIED + REDESIGNED SECTIONS -->

<style>
  :root {
    --dark: #0A1931;
    --navy: #1A3D63;
    --blue: #4A7FA7;
    --light-blue: #B3CFE5;
    --bg: #F6FAFD;
  }

  body {
    background: var(--bg);
    color: var(--dark);
  }

  .section {
    padding: 80px 20px;
  }

  .gradient-bg {
    background: linear-gradient(135deg, var(--dark), var(--navy));
    color: white;
  }

  .card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: 0.3s;
  }

  .card:hover {
    transform: translateY(-8px);
  }

  .feature-card {
    background: linear-gradient(135deg, var(--light-blue), white);
  }

  .stats {
    display: flex;
    justify-content: center;
    gap: 60px;
    text-align: center;
  }

  .stats div h2 {
    color: var(--navy);
  }

  .cta {
    background: linear-gradient(135deg, var(--blue), var(--navy));
    color: white;
    padding: 60px;
    border-radius: 25px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  }

  .btn {
    background: white;
    color: var(--navy);
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: bold;
    transition: 0.3s;
  }

  .btn:hover {
    transform: scale(1.05);
    background: var(--light-blue);
  }
</style>

<!-- STATS SECTION (FIXED COLORS) -->
<section class="section">
  <div class="stats">
    <div>
      <h2>100%</h2>
      <p>Digital Records</p>
    </div>
    <div>
      <h2>24/7</h2>
      <p>Access</p>
    </div>
    <div>
      <h2>Real-Time</h2>
      <p>Tracking</p>
    </div>
    <div>
      <h2>Secure</h2>
      <p>Data</p>
    </div>
  </div>
</section>

<!-- FEATURES SECTION (NO MORE BORING WHITE) -->
<section class="section">
  <h2 style="text-align:center; margin-bottom:40px;">Everything You Need</h2>

  <div style="display:flex; gap:30px; justify-content:center; flex-wrap:wrap;">
    <div class="card feature-card">
      <h3>Health Records</h3>
      <p>Track student illnesses, allergies, visits, and treatment history.</p>
    </div>

    <div class="card feature-card">
      <h3>Inventory System</h3>
      <p>Monitor medicines, supplies, and expiration dates automatically.</p>
    </div>

    <div class="card feature-card">
      <h3>Reports & Analytics</h3>
      <p>Generate insights for administrators and better decisions.</p>
    </div>
  </div>
</section>

<!-- HOW IT WORKS (NOW VISUAL + STRUCTURED) -->
<section class="section" style="background: var(--light-blue);">
  <h2 style="text-align:center; margin-bottom:40px;">How MedLog Works</h2>

  <div style="display:flex; justify-content:center; gap:40px; flex-wrap:wrap;">
    <div class="card">
      <h3>1. Record</h3>
      <p>Input student health data.</p>
    </div>

    <div class="card">
      <h3>2. Track</h3>
      <p>Monitor medicine usage.</p>
    </div>

    <div class="card">
      <h3>3. Analyze</h3>
      <p>Generate reports instantly.</p>
    </div>
  </div>
</section>

<!-- ABOUT SECTION (DARK GRADIENT FIX) -->
<section class="section gradient-bg">
  <h2 style="text-align:center; margin-bottom:20px;">Built for School Nurses</h2>
  <p style="text-align:center; max-width:700px; margin:auto;">
    MedLog is designed to reduce paperwork, improve clinic efficiency, and ensure every student receives proper healthcare attention.
  </p>

  <div style="display:flex; justify-content:center; gap:20px; margin-top:30px;">
    <div class="card" style="background: rgba(255,255,255,0.1); color:white;">⚡ Faster Workflow</div>
    <div class="card" style="background: rgba(255,255,255,0.1); color:white;">🔒 Secure Data</div>
    <div class="card" style="background: rgba(255,255,255,0.1); color:white;">📊 Smart Insights</div>
  </div>
</section>

<!-- CTA SECTION (NOW POPS HARD) -->
<section class="section">
  <div class="cta">
    <h2 style="margin-bottom:20px;">Start Managing Your Clinic Smarter</h2>
    <button class="btn">Launch MedLog</button>
  </div>
</section>