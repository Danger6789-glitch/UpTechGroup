<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rejoindre la BBA</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<style>
body{min-height:100vh;display:flex;flex-direction:column;}
.wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:60px 24px;}
.inner{max-width:800px;width:100%;text-align:center;}
.page-label{font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--primary);margin-bottom:16px;}
.page-title{font-family:'Anton',sans-serif;font-size:clamp(36px,6vw,60px);letter-spacing:2px;color:var(--text);margin-bottom:12px;}
.page-sub{color:var(--muted);font-size:15px;line-height:1.7;max-width:520px;margin:0 auto 48px;}
.choice-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
.choice-card{background:#fff;border:2px solid var(--border);border-radius:16px;padding:36px 28px;cursor:pointer;transition:all 0.2s;text-decoration:none;display:block;}
.choice-card:hover{border-color:var(--secondary);transform:translateY(-4px);box-shadow:0 8px 32px rgba(29,66,138,0.12);}
.choice-card.red:hover{border-color:var(--primary);box-shadow:0 8px 32px rgba(200,16,46,0.12);}
.choice-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 20px;}
.choice-title{font-family:'Anton',sans-serif;font-size:24px;letter-spacing:1px;margin-bottom:10px;color:var(--text);}
.choice-desc{font-size:13px;color:var(--muted);line-height:1.7;margin-bottom:20px;}
.choice-btn{display:inline-block;padding:10px 24px;border-radius:8px;font-size:13px;font-weight:700;font-family:'Inter';}
.btn-blue{background:var(--secondary);color:#fff;}
.btn-red{background:var(--primary);color:#fff;}
.choice-note{font-size:11px;color:var(--muted);margin-top:10px;}
@media(max-width:600px){.choice-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="wrap">
  <div class="inner">
    <div class="page-label">BBA &mdash; Bateauvi Basketball Association</div>
    <h1 class="page-title">Rejoindre la ligue</h1>
    <p class="page-sub">Vous &ecirc;tes une &eacute;quipe qui souhaite int&eacute;grer la BBA, ou un joueur qui veut rejoindre une &eacute;quipe existante ?</p>

    <div class="choice-grid">
      <a href="join-league.php" class="choice-card">
        <div class="choice-icon" style="background:rgba(29,66,138,0.1);font-family:'Anton',sans-serif;font-size:22px;color:var(--secondary)">BBA</div>
        <div class="choice-title">Je suis une &eacute;quipe</div>
        <div class="choice-desc">
          Votre &eacute;quipe souhaite int&eacute;grer la BBA et participer &agrave; la ligue. Soumettez votre candidature et le commissaire l&apos;examinera sous 72h.
        </div>
        <span class="choice-btn btn-blue">Inscrire mon &eacute;quipe</span>
        <div class="choice-note">Formulaire en 3 &eacute;tapes &mdash; R&eacute;ponse sous 72h</div>
      </a>

      <a href="register.php" class="choice-card red">
        <div class="choice-icon" style="background:rgba(200,16,46,0.1);font-family:'Anton',sans-serif;font-size:22px;color:var(--primary)">#</div>
        <div class="choice-title">Je suis un joueur</div>
        <div class="choice-desc">
          Vous souhaitez rejoindre une &eacute;quipe d&eacute;j&agrave; membre de la BBA. Cr&eacute;ez votre profil joueur et attendez la validation du responsable.
        </div>
        <span class="choice-btn btn-red">Cr&eacute;er mon profil</span>
        <div class="choice-note">Formulaire en 4 &eacute;tapes &mdash; V&eacute;rification CNI requise</div>
      </a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>