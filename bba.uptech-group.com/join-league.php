<?php require_once 'config.php';
if (isset($_SESSION['user_id'])) header('Location: dashboard.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rejoindre la BBA — Inscription équipe</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<style>
body{min-height:100vh;display:flex;flex-direction:column;}
.wrap{flex:1;padding:40px 24px 60px;}
.inner{max-width:620px;margin:0 auto;}
.page-title{font-family:'Anton',sans-serif;font-size:36px;letter-spacing:2px;color:var(--text);margin-bottom:6px;}
.page-sub{color:var(--muted);font-size:14px;margin-bottom:32px;line-height:1.6;}
.steps{display:flex;gap:0;margin-bottom:32px;}
.step{flex:1;text-align:center;position:relative;}
.step:not(:last-child)::after{content:'';position:absolute;top:14px;left:50%;width:100%;height:2px;background:var(--border);z-index:0;}
.step.done:not(:last-child)::after{background:var(--secondary);}
.step-circle{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 6px;position:relative;z-index:1;border:2px solid var(--border);background:#fff;color:var(--muted);}
.step.active .step-circle{border-color:var(--secondary);background:var(--secondary);color:#fff;}
.step.done .step-circle{border-color:var(--secondary);background:var(--secondary);color:#fff;}
.step-label{font-size:10px;font-weight:600;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase;}
.step.active .step-label,.step.done .step-label{color:var(--secondary);}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:28px;margin-bottom:16px;box-shadow:var(--card-shadow);}
.card-title{font-weight:700;font-size:16px;margin-bottom:20px;color:var(--text);}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--text);}
.field input,.field select,.field textarea{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;color:var(--text);background:var(--bg);outline:none;transition:border 0.2s;font-family:'Inter';}
.field textarea{resize:vertical;min-height:80px;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--secondary);background:#fff;}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.field-note{font-size:11px;color:var(--muted);margin-top:4px;}
.required{color:var(--primary);}
.btn{padding:11px 24px;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:'Inter';transition:all 0.15s;}
.btn-primary{background:var(--secondary);color:#fff;}
.btn-primary:hover{background:#163580;}
.btn-outline{background:transparent;color:var(--text);border:1.5px solid var(--border);}
.btn-outline:hover{border-color:var(--secondary);color:var(--secondary);}
.nav-btns{display:flex;justify-content:space-between;margin-top:24px;padding-top:20px;border-top:1px solid var(--border);}
.alert{padding:12px 16px;border-radius:9px;font-size:13px;margin-bottom:20px;display:none;}
.alert-error{background:rgba(200,16,46,0.08);border:1px solid rgba(200,16,46,0.2);color:var(--primary);}
.color-picker-wrap{display:flex;gap:10px;align-items:center;margin-top:6px;}
.color-preview{width:36px;height:36px;border-radius:8px;border:2px solid var(--border);}
.color-swatches{display:flex;gap:6px;flex-wrap:wrap;}
.swatch{width:28px;height:28px;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:all 0.15s;}
.swatch.selected{border-color:var(--text);transform:scale(1.1);}
.confirm-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px;}
.confirm-row:last-child{border-bottom:none;}
.confirm-label{color:var(--muted);}
.confirm-val{font-weight:600;color:var(--text);}
.success-page{text-align:center;padding:60px 24px;display:none;}
.success-icon{width:72px;height:72px;border-radius:50%;background:rgba(29,66,138,0.1);border:2px solid var(--secondary);display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 24px;}
.success-title{font-family:'Anton',sans-serif;font-size:36px;letter-spacing:2px;margin-bottom:12px;color:var(--text);}
.success-text{color:var(--muted);font-size:14px;line-height:1.8;max-width:440px;margin:0 auto 28px;}
@media(max-width:600px){.field-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="wrap">
  <div class="inner" id="main-form">
    <div class="page-title">Rejoindre la BBA</div>
    <p class="page-sub">Formulaire de candidature pour une équipe &mdash; Validation par le commissaire de la ligue sous 72h</p>

    <div class="steps">
      <div class="step active" id="step-1"><div class="step-circle">1</div><div class="step-label">Équipe</div></div>
      <div class="step" id="step-2"><div class="step-circle">2</div><div class="step-label">Responsable</div></div>
      <div class="step" id="step-3"><div class="step-circle">3</div><div class="step-label">Confirmation</div></div>
    </div>

    <div class="alert alert-error" id="alert-error"></div>

    <!-- STEP 1 : EQUIPE -->
    <div id="panel-1">
      <div class="card">
        <div class="card-title">Informations de l&apos;&eacute;quipe</div>
        <div class="field-row">
          <div class="field"><label>Nom de l&apos;&eacute;quipe <span class="required">*</span></label><input type="text" id="t-name" placeholder="Ex : Team Thunder"></div>
          <div class="field"><label>Ville <span class="required">*</span></label><input type="text" id="t-city" placeholder="Lom&eacute;" value="Lom&eacute;"></div>
        </div>
        <div class="field">
          <label>Couleur principale de l&apos;&eacute;quipe</label>
          <div class="color-picker-wrap">
            <div class="color-preview" id="color-preview" style="background:#1D428A"></div>
            <div class="color-swatches">
              <?php
              $colors = ['#1D428A','#C8102E','#006837','#FCD116','#552583','#F58426','#00538C','#CE1126','#000000','#5c3317'];
              foreach ($colors as $c): ?>
              <div class="swatch <?=$c==='#1D428A'?'selected':''?>" style="background:<?=$c?>" data-color="<?=$c?>" onclick="selectColor('<?=$c?>', this)"></div>
              <?php endforeach; ?>
            </div>
            <input type="color" id="t-color" value="#1D428A" style="width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;" onchange="updateColor(this.value)">
          </div>
          <input type="hidden" id="t-color-val" value="#1D428A">
        </div>
        <div class="field">
          <label>Description de l&apos;&eacute;quipe</label>
          <textarea id="t-desc" placeholder="Pr&eacute;sentez votre &eacute;quipe, vos ambitions, votre histoire..."></textarea>
        </div>
      </div>
      <div class="nav-btns"><span></span><button class="btn btn-primary" onclick="goStep(2)">Suivant &rarr;</button></div>
    </div>

    <!-- STEP 2 : RESPONSABLE -->
    <div id="panel-2" style="display:none">
      <div class="card">
        <div class="card-title">Informations du responsable</div>
        <div class="field-row">
          <div class="field"><label>Pr&eacute;nom et Nom <span class="required">*</span></label><input type="text" id="m-name" placeholder="Jean Dupont"></div>
          <div class="field"><label>Email <span class="required">*</span></label><input type="email" id="m-email" placeholder="jean@email.com"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>T&eacute;l&eacute;phone <span class="required">*</span></label><input type="tel" id="m-phone" placeholder="+228 90 00 00 00"></div>
          <div class="field"><label>WhatsApp <span class="required">*</span></label><input type="tel" id="m-whatsapp" placeholder="+228 90 00 00 00"><div class="field-note">Les joueurs enverront leur CNI &agrave; ce num&eacute;ro</div></div>
        </div>
        <div style="background:rgba(29,66,138,0.06);border:1px solid rgba(29,66,138,0.15);border-radius:8px;padding:14px;font-size:12px;color:var(--secondary);line-height:1.8;">
          <strong>Information :</strong> Votre candidature sera examin&eacute;e par le commissaire de la BBA. Si elle est accept&eacute;e, vous recevrez vos identifiants de connexion et pourrez g&eacute;rer votre &eacute;quipe depuis le dashboard.
        </div>
      </div>
      <div class="nav-btns">
        <button class="btn btn-outline" onclick="goStep(1)">&larr; Retour</button>
        <button class="btn btn-primary" onclick="goStep(3)">Suivant &rarr;</button>
      </div>
    </div>

    <!-- STEP 3 : CONFIRMATION -->
    <div id="panel-3" style="display:none">
      <div class="card">
        <div class="card-title">R&eacute;capitulatif de la candidature</div>
        <div id="confirm-summary"></div>
        <p style="font-size:12px;color:var(--muted);margin-top:16px;line-height:1.7;">
          En soumettant cette candidature, vous acceptez les r&egrave;gles de la BBA et vous engagez &agrave; participer activement &agrave; la ligue.
        </p>
      </div>
      <div class="nav-btns">
        <button class="btn btn-outline" onclick="goStep(2)">&larr; Retour</button>
        <button class="btn btn-primary" id="btn-submit" onclick="submitForm()">Soumettre la candidature</button>
      </div>
    </div>
  </div>

  <!-- SUCCESS -->
  <div class="success-page" id="success-page">
    <div class="success-icon">&#10003;</div>
    <div class="success-title">Candidature envoy&eacute;e !</div>
    <p class="success-text">
      Votre dossier a bien &eacute;t&eacute; re&ccedil;u par le commissaire de la BBA. Vous recevrez une r&eacute;ponse sous 72h &agrave; l&apos;adresse email indiqu&eacute;e.
    </p>
    <a href="index.php" class="btn btn-primary">Retour &agrave; l&apos;accueil</a>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
let currentStep = 1;

function selectColor(color, el) {
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('color-preview').style.background = color;
  document.getElementById('t-color-val').value = color;
  document.getElementById('t-color').value = color;
}

function updateColor(color) {
  document.getElementById('color-preview').style.background = color;
  document.getElementById('t-color-val').value = color;
  document.querySelectorAll('.swatch').forEach(s => s.classList.remove('selected'));
}

function showAlert(msg) {
  const el = document.getElementById('alert-error');
  el.textContent = msg; el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 5000);
}

function goStep(n) {
  if (n > 1) {
    if (!document.getElementById('t-name').value.trim()) { showAlert('Le nom de l\'équipe est obligatoire.'); return; }
    if (!document.getElementById('t-city').value.trim()) { showAlert('La ville est obligatoire.'); return; }
  }
  if (n > 2) {
    if (!document.getElementById('m-name').value.trim()) { showAlert('Le nom du responsable est obligatoire.'); return; }
    if (!document.getElementById('m-email').value.trim()) { showAlert('L\'email est obligatoire.'); return; }
    if (!document.getElementById('m-phone').value.trim()) { showAlert('Le téléphone est obligatoire.'); return; }
    if (!document.getElementById('m-whatsapp').value.trim()) { showAlert('Le WhatsApp est obligatoire.'); return; }
    const color = document.getElementById('t-color-val').value;
    document.getElementById('confirm-summary').innerHTML = [
      ['Nom de l\'équipe', document.getElementById('t-name').value],
      ['Ville', document.getElementById('t-city').value],
      ['Couleur', '<span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:'+color+';vertical-align:middle;margin-right:6px"></span>'+color],
      ['Description', document.getElementById('t-desc').value || 'Non renseignée'],
      ['Responsable', document.getElementById('m-name').value],
      ['Email', document.getElementById('m-email').value],
      ['Téléphone', document.getElementById('m-phone').value],
      ['WhatsApp', document.getElementById('m-whatsapp').value],
    ].map(([l,v]) => '<div class="confirm-row"><span class="confirm-label">'+l+'</span><span class="confirm-val">'+v+'</span></div>').join('');
  }
  document.getElementById('panel-' + currentStep).style.display = 'none';
  document.getElementById('step-' + currentStep).className = 'step done';
  currentStep = n;
  document.getElementById('panel-' + currentStep).style.display = 'block';
  document.getElementById('step-' + currentStep).className = 'step active';
  window.scrollTo(0, 0);
}

async function submitForm() {
  const btn = document.getElementById('btn-submit');
  btn.textContent = 'Envoi...'; btn.disabled = true;
  const form = new FormData();
  form.append('action', 'request_team');
  form.append('team_name', document.getElementById('t-name').value);
  form.append('city', document.getElementById('t-city').value);
  form.append('color', document.getElementById('t-color-val').value);
  form.append('description', document.getElementById('t-desc').value);
  form.append('manager_name', document.getElementById('m-name').value);
  form.append('manager_email', document.getElementById('m-email').value);
  form.append('manager_phone', document.getElementById('m-phone').value);
  form.append('whatsapp', document.getElementById('m-whatsapp').value);
  const res = await fetch('team_api.php', {method:'POST', body:form});
  const json = await res.json();
  if (json.success) {
    document.getElementById('main-form').style.display = 'none';
    document.getElementById('success-page').style.display = 'block';
  } else {
    showAlert(json.message || 'Erreur lors de l\'envoi.');
    btn.textContent = 'Soumettre la candidature'; btn.disabled = false;
  }
}
</script>
</body>
</html>
