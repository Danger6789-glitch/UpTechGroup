<?php require_once 'config.php';
if (isset($_SESSION['user_id'])) header('Location: dashboard.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — BBA</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<style>
body{min-height:100vh;display:flex;flex-direction:column;}
.login-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 24px;}
.login-box{width:100%;max-width:420px;}
.login-logo{text-align:center;margin-bottom:32px;}
.login-emblem{width:52px;height:52px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;font-family:'Bebas Neue';font-size:18px;color:#fff;margin:0 auto 12px;}
.login-title{font-family:'Bebas Neue';font-size:28px;letter-spacing:2px;color:var(--text);}
.login-sub{color:var(--muted);font-size:13px;margin-top:4px;}
.login-card{background:var(--white);border:1px solid var(--border);border-radius:16px;padding:32px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--text);}
.field input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;color:var(--text);background:var(--bg);outline:none;transition:border 0.2s;font-family:'Inter';}
.field input:focus{border-color:var(--green);background:#fff;}
.btn-submit{width:100%;padding:13px;background:var(--green);color:#fff;border:none;border-radius:9px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter';transition:background 0.2s;margin-top:4px;}
.btn-submit:hover{background:#005229;}
.error-box{background:rgba(206,17,38,0.08);border:1px solid rgba(206,17,38,0.2);color:var(--red);padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;}
.login-bottom{text-align:center;margin-top:20px;font-size:13px;color:var(--muted);}
.login-bottom a{color:var(--green);font-weight:600;}
.login-back{text-align:center;margin-top:12px;}
.login-back a{font-size:13px;color:var(--muted);}
</style>
</head>
<body>
<div class="togo-strip"></div>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <div class="login-emblem">BBA</div>
      <div class="login-title">Connexion</div>
      <div class="login-sub">Acces reserve aux membres BBA</div>
    </div>
    <div class="login-card">
      <div class="error-box" id="error-msg"></div>
      <div class="field"><label>Email</label><input type="email" id="email" placeholder="ton@email.com"></div>
      <div class="field"><label>Mot de passe</label><input type="password" id="password" placeholder="••••••••"></div>
      <button class="btn-submit" id="btn-login">Se connecter</button>
    </div>
    <div class="login-bottom">Pas encore membre ? <a href="register.php">Rejoindre la ligue</a></div>
    <div class="login-back"><a href="index.php">Retour au site</a></div>
  </div>
</div>
<script>
document.getElementById('btn-login').addEventListener('click',async()=>{
  const email=document.getElementById('email').value.trim();
  const password=document.getElementById('password').value;
  const errEl=document.getElementById('error-msg');
  errEl.style.display='none';
  if(!email||!password){errEl.textContent='Remplis tous les champs.';errEl.style.display='block';return;}
  const btn=document.getElementById('btn-login');
  btn.textContent='Connexion...';btn.disabled=true;
  const form=new FormData();
  form.append('action','login');form.append('email',email);form.append('password',password);
  const res=await fetch('auth.php',{method:'POST',body:form});
  const json=await res.json();
  if(json.success){window.location.href='dashboard.php';}
  else{errEl.textContent=json.message;errEl.style.display='block';btn.textContent='Se connecter';btn.disabled=false;}
});
document.getElementById('password').addEventListener('keypress',e=>{if(e.key==='Enter')document.getElementById('btn-login').click();});
</script>
</body>
</html>
