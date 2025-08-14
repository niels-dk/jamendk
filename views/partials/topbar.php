<!--div class="container">
  <a href="/" style="font-weight:600;font-size:1.05rem">Jamen</a>
  <nav>
    <a href="/dashboard">Dashboard</a>
    <a href="/dreams/new" class="btn" style="margin-left:.8rem">+ New Dream</a>
  </nav>
</div-->

<!-- Top header: logo outside hero + actions -->
  <header class="home-header">
    <div class="home-brand">
      <a href="/"><div class="logo-mark">
       <?php for ($i = 0; $i < 9; $i++) echo '<span></span>'; ?>
      </div></a>
      <strong class="brand-title">DreamBoard</strong>
    </div>

    <nav class="home-actions">
      <!-- Correct routes -->
      <a class="btn btn-primary" href="/login">Login</a>
      <a class="btn btn-ghost" href="/register">+ New Creator</a>
    </nav>
  </header>