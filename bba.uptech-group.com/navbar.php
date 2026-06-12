<?php
$current = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);

// Logo depuis les paramètres
try {
    $logoStmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key='site_logo'");
    $logoStmt->execute();
    $logo = $logoStmt->fetchColumn();
} catch(Exception $e) { $logo = ''; }
?>
<nav class="navbar">
  <div class="navbar-inner">
    <a href="index.php" class="navbar-logo">
      <?php if ($logo && file_exists(__DIR__.'/assets/'.$logo)): ?>
        <img src="assets/<?=htmlspecialchars($logo)?>" alt="BBA">
      <?php else: ?>
        <span class="logo-text">BBA</span>
      <?php endif; ?>
    </a>
    <ul class="navbar-links">
      <li><a href="index.php" class="<?=$current==='index.php'?'active':''?>">Accueil</a></li>
      <li><a href="matches.php" class="<?=$current==='matches.php'?'active':''?>">Matchs</a></li>
      <li><a href="stats.php" class="<?=$current==='stats.php'?'active':''?>">Stats</a></li>
      <li><a href="equipes.php" class="<?=$current==='equipes.php'?'active':''?>">Équipes</a></li>
      <li><a href="joueurs.php" class="<?=$current==='joueurs.php'?'active':''?>">Joueurs</a></li>
    </ul>
    <div class="navbar-actions">
      <?php if ($isLoggedIn): ?>
        <a href="dashboard.php" class="btn-outline-nav">Dashboard</a>
        <a href="logout.php" class="btn-nav">D&eacute;connexion</a>
      <?php else: ?>
        <a href="login.php" class="btn-outline-nav">Connexion</a>
        <a href="rejoindre.php" class="btn-nav">Rejoindre</a>
      <?php endif; ?>
    </div>
    <button class="hamburger" onclick="toggleMenu()" aria-label="Menu">&#9776;</button>
  </div>
  <div class="mobile-menu" id="mobile-menu">
    <a href="index.php">Accueil</a>
    <a href="matches.php">Matchs</a>
    <a href="stats.php">Stats</a>
    <a href="equipes.php">Équipes</a>
    <a href="joueurs.php">Joueurs</a>
    <?php if ($isLoggedIn): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">D&eacute;connexion</a>
    <?php else: ?>
      <a href="login.php">Connexion</a>
      <a href="join-league.php">Rejoindre la ligue</a>
    <?php endif; ?>
  </div>
</nav>
