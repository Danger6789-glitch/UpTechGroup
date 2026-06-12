<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

// Vérifier qu'une vérification 2FA est en attente
if (empty($_SESSION['2fa_pending_user_id'])) {
    header('Location: /workspace/index.php'); exit;
}

$error = '';
$userId = (int)$_SESSION['2fa_pending_user_id'];
$db     = getDB();

// Récupérer infos utilisateur
$stmt = $db->prepare("SELECT prenom, nom, email FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: /workspace/index.php'); exit; }

// Traitement du formulaire
if ($_POST['action'] ?? '' === 'verify') {
    $code = trim($_POST['code'] ?? '');

    // Récupérer la session 2FA valide
    $stmt = $db->prepare("SELECT * FROM deux_fa_sessions WHERE user_id=? AND expire > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $session = $stmt->fetch();

    if (!$session) {
        $error = 'Le code a expiré. Veuillez recommencer la connexion.';
    } elseif ($session['attempts'] >= 5) {
        $error = 'Trop de tentatives. Veuillez recommencer la connexion.';
        $db->prepare("DELETE FROM deux_fa_sessions WHERE user_id=?")->execute([$userId]);
        unset($_SESSION['2fa_pending_user_id']);
    } elseif ($session['code'] !== $code) {
        $db->prepare("UPDATE deux_fa_sessions SET attempts=attempts+1 WHERE id=?")->execute([$session['id']]);
        $remaining = 4 - $session['attempts'];
        $error = "Code incorrect. Il vous reste {$remaining} tentative(s).";
    } else {
        // Code correct — finaliser la connexion
        $db->prepare("DELETE FROM deux_fa_sessions WHERE user_id=?")->execute([$userId]);

        $stmtU = $db->prepare("SELECT * FROM users WHERE id=? AND actif=1");
        $stmtU->execute([$userId]);
        $fullUser = $stmtU->fetch();

        $_SESSION['user_id']    = $fullUser['id'];
        $_SESSION['user_nom']   = $fullUser['prenom'] . ' ' . $fullUser['nom'];
        $_SESSION['user_role']  = $fullUser['role'];
        $_SESSION['user_email'] = $fullUser['email'];
        $_SESSION['login_time'] = time();
        unset($_SESSION['2fa_pending_user_id']);

        $db->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$userId]);
        header('Location: /workspace/dashboard.php'); exit;
    }
}

// Renvoyer le code si demandé
if ($_POST['action'] ?? '' === 'resend') {
    require_once __DIR__ . '/mailer.php';
    $db->prepare("DELETE FROM deux_fa_sessions WHERE user_id=?")->execute([$userId]);
    $code   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expire = date('Y-m-d H:i:s', time() + 600);
    $db->prepare("INSERT INTO deux_fa_sessions (user_id,code,expire) VALUES (?,?,?)")->execute([$userId,$code,$expire]);

    // Envoyer email
    $html = "<!DOCTYPE html><html><body style='font-family:Poppins,sans-serif;background:#0f0e1a;padding:32px'><div style='max-width:480px;margin:0 auto;background:#1a1930;border:1px solid rgba(54,169,225,.15);border-radius:16px;padding:32px;text-align:center'><div style='font-size:22px;font-weight:800;color:#fff;margin-bottom:6px'>UP TECH GROUP</div><div style='font-size:12px;color:#7a78a0;margin-bottom:24px;text-transform:uppercase;letter-spacing:2px'>Workspace</div><div style='font-size:14px;color:#b8b6cc;margin-bottom:20px'>Bonjour <strong style='color:#fff'>{$user['prenom']}</strong>, voici votre nouveau code de vérification :</div><div style='font-size:42px;font-weight:800;color:#36A9E1;letter-spacing:8px;font-family:monospace;background:rgba(54,169,225,.1);border-radius:12px;padding:16px 24px;margin:0 auto 20px'>{$code}</div><div style='font-size:12px;color:#7a78a0'>Ce code expire dans <strong style='color:#f0a500'>10 minutes</strong>.<br>Ne le partagez avec personne.</div></div></body></html>";
    mail($user['email'], '=?UTF-8?B?'.base64_encode('Nouveau code — UP TECH GROUP').'?=', $html, "From: workspace@uptech-group.com\r\nContent-Type: text/html; charset=UTF-8");

    $success = 'Un nouveau code a été envoyé à votre adresse email.';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UP TECH GROUP — Vérification</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--danger:#e05252;--success:#2ecc87;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 10% 0%,rgba(41,35,92,0.7) 0%,transparent 60%),radial-gradient(ellipse 60% 40% at 90% 100%,rgba(54,169,225,0.18) 0%,transparent 55%);pointer-events:none;}
.box{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:40px;width:100%;max-width:420px;position:relative;z-index:1;text-align:center;}
.logo{width:52px;height:52px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;}
.logo svg{width:26px;height:26px;fill:none;stroke:#fff;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
h1{font-size:22px;font-weight:700;color:#fff;margin-bottom:6px;}
.sub{font-size:13px;color:var(--muted);margin-bottom:6px;}
.email-badge{display:inline-block;background:rgba(54,169,225,.1);border:1px solid rgba(54,169,225,.2);color:var(--accent);padding:4px 14px;border-radius:99px;font-size:12px;font-weight:600;margin-bottom:28px;}
.code-inputs{display:flex;gap:10px;justify-content:center;margin-bottom:24px;}
.code-input{width:52px;height:60px;background:rgba(255,255,255,.04);border:2px solid var(--border);border-radius:12px;text-align:center;font-size:28px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;outline:none;transition:border-color .2s;}
.code-input:focus{border-color:var(--accent);}
.code-input.filled{border-color:rgba(54,169,225,.5);}
.btn{width:100%;background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:10px;padding:14px;color:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;margin-bottom:12px;transition:opacity .2s;}
.btn:hover{opacity:.9;}
.btn:disabled{opacity:.4;cursor:not-allowed;}
.resend-btn{background:none;border:none;color:var(--accent);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;text-decoration:underline;}
.back-link{display:block;margin-top:16px;font-size:13px;color:var(--muted);text-decoration:none;}
.back-link:hover{color:var(--danger);}
.alert{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:18px;text-align:left;}
.alert-error{background:rgba(224,82,82,.1);border:1px solid rgba(224,82,82,.3);color:#f08080;}
.alert-success{background:rgba(46,204,135,.1);border:1px solid rgba(46,204,135,.3);color:var(--success);}
.timer{font-size:12px;color:var(--muted);margin-bottom:16px;}
.timer span{color:var(--warning);font-weight:700;}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
  </div>
  <h1>Vérification en deux étapes</h1>
  <p class="sub">Un code à 6 chiffres a été envoyé à</p>
  <div class="email-badge"><?= htmlspecialchars($user['email']) ?></div>

  <?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" id="verifyForm">
    <input type="hidden" name="action" value="verify">
    <div class="code-inputs">
      <?php for($i=1;$i<=6;$i++): ?>
      <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" id="c<?=$i?>" onkeyup="handleInput(this,<?=$i?>)" onkeydown="handleBack(event,<?=$i?>)" onpaste="handlePaste(event)">
      <?php endfor; ?>
    </div>
    <input type="hidden" name="code" id="fullCode">
    <div class="timer">Le code expire dans <span id="countdown">10:00</span></div>
    <button type="submit" class="btn" id="submitBtn" disabled>Vérifier</button>
  </form>

  <form method="POST">
    <input type="hidden" name="action" value="resend">
    <button type="submit" class="resend-btn">Renvoyer un nouveau code</button>
  </form>

  <a class="back-link" href="/workspace/index.php">← Retour à la connexion</a>
</div>

<script>
// Gestion des inputs 6 chiffres
function handleInput(el, pos) {
  const val = el.value.replace(/\D/g,'');
  el.value = val;
  if(val) { el.classList.add('filled'); if(pos<6) document.getElementById('c'+(pos+1)).focus(); }
  else el.classList.remove('filled');
  updateCode();
}
function handleBack(e, pos) {
  if(e.key==='Backspace'&&!e.target.value&&pos>1) document.getElementById('c'+(pos-1)).focus();
}
function handlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
  for(let i=0;i<text.length;i++){const inp=document.getElementById('c'+(i+1));if(inp){inp.value=text[i];inp.classList.add('filled');}}
  updateCode();
  if(text.length===6)document.getElementById('submitBtn').focus();
}
function updateCode() {
  let code='';
  for(let i=1;i<=6;i++) code+=document.getElementById('c'+i).value;
  document.getElementById('fullCode').value=code;
  document.getElementById('submitBtn').disabled=code.length<6;
}

// Countdown 10 minutes
let seconds = 600;
const countdown = document.getElementById('countdown');
const interval = setInterval(() => {
  seconds--;
  if(seconds<=0){clearInterval(interval);countdown.textContent='Expiré';countdown.style.color='var(--danger)';document.getElementById('submitBtn').disabled=true;return;}
  const m=Math.floor(seconds/60),s=seconds%60;
  countdown.textContent=m+':'+(s<10?'0':'')+s;
  if(seconds<=60)countdown.style.color='var(--danger)';
},1000);

document.getElementById('c1').focus();
</script>
</body>
</html>
