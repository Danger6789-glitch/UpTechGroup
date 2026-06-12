<?php http_response_code(403); ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accès refusé — UP TECH GROUP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#0f0e1a;color:#e8e6f0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;text-align:center;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 30%,rgba(92,65,35,0.4) 0%,transparent 60%);pointer-events:none;}
.wrap{position:relative;z-index:1;max-width:480px;}
.code{font-family:'Space Mono',monospace;font-size:120px;font-weight:700;line-height:1;color:transparent;background:linear-gradient(135deg,#8a5e29,#f0a500);-webkit-background-clip:text;background-clip:text;margin-bottom:8px;letter-spacing:-4px;}
.title{font-size:22px;font-weight:700;color:#fff;margin-bottom:12px;}
.sub{font-size:14px;color:#7a78a0;line-height:1.7;margin-bottom:36px;}
.btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.btn-p{background:linear-gradient(135deg,#29235C,#36A9E1);border:none;border-radius:10px;padding:12px 24px;color:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;}
.logo{width:40px;height:40px;object-fit:contain;margin-bottom:32px;opacity:.7;}
</style>
</head>
<body>
<div class="wrap">
  <img src="/workspace/assets/logo.png" alt="UP TECH GROUP" class="logo">
  <div class="code">403</div>
  <div class="title">Accès refusé</div>
  <div class="sub">Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.</div>
  <div class="btns">
    <a class="btn-p" href="/workspace/dashboard.php">Retour au workspace</a>
  </div>
</div>
</body>
</html>
