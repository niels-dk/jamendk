<div class="container home-wrap">
  <!-- Top header: logo outside hero + actions -->
  <header class="home-header">
    <div class="home-brand">
      <div class="logo-mark">
        <?php for ($i = 0; $i < 9; $i++) echo '<span></span>'; ?>
      </div>
      <strong class="brand-title">DreamBoard</strong>
    </div>

    <nav class="home-actions">
      <!-- Correct routes -->
      <a class="btn btn-primary" href="/login">Login</a>
      <a class="btn btn-ghost" href="/register">+ New Creator</a>
    </nav>
  </header>

  <!-- Hero -->
  <section class="card hero hero-offset">
    <h1 class="h1">The Future Begins in the<br>Space Between Ideas</h1>
    <p class="sub">Give your dreams a place to grow.</p>

    <div class="cta">
      <a class="btn btn-primary" href="/dreams/new">+ New Dream</a>
      <a class="btn btn-ghost" href="/dashboard">Go to dashboard</a>
    </div>

    <div class="home-tagline">Dream • Visualise • Plan • Create</div>
  </section>
</div>
