<?php require_once 'config.php';
if (isset($_SESSION['user_id'])) header('Location: dashboard.php');
// Récupérer les infos des équipes
$teams_stmt = $pdo->query("SELECT id, name, whatsapp FROM teams");
$teams = $teams_stmt->fetchAll(PDO::FETCH_ASSOC);
$teams_data = [];
foreach ($teams as $t) $teams_data[$t['id']] = $t;
?>
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
.register-wrap{flex:1;padding:40px 24px 60px;}
.register-inner{max-width:580px;margin:0 auto;}
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
.step.active .step-label{color:var(--secondary);}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:28px;margin-bottom:16px;box-shadow:var(--card-shadow);}
.card-title{font-weight:700;font-size:16px;margin-bottom:20px;color:var(--text);}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:var(--text);}
.field input,.field select{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:14px;color:var(--text);background:var(--bg);outline:none;transition:border 0.2s;font-family:'Inter';}
.field input:focus,.field select:focus{border-color:var(--secondary);background:#fff;}
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
.alert-success{background:rgba(29,66,138,0.08);border:1px solid rgba(29,66,138,0.2);color:var(--secondary);}
.team-choice{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;}
.team-btn{padding:20px 16px;border:2px solid var(--border);border-radius:12px;cursor:pointer;text-align:center;transition:all 0.15s;background:#fff;}
.team-btn:hover{border-color:var(--secondary);}
.team-btn.selected{border-color:var(--secondary);background:rgba(29,66,138,0.05);}
.team-btn-logo{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Anton',sans-serif;font-size:18px;margin:0 auto 10px;overflow:hidden;}
.team-btn-logo img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.team-btn-name{font-weight:700;font-size:15px;color:var(--text);}
.team-btn-city{font-size:11px;color:var(--muted);margin-top:2px;}
.confirm-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px;}
.confirm-row:last-child{border-bottom:none;}
.confirm-label{color:var(--muted);}
.confirm-val{font-weight:600;color:var(--text);}
.whatsapp-box{background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.3);border-radius:10px;padding:16px;margin-top:16px;}
.whatsapp-box-title{font-weight:700;font-size:13px;color:#128c7e;margin-bottom:8px;}
.whatsapp-box-text{font-size:13px;color:var(--muted);line-height:1.7;}
.whatsapp-btn{display:inline-flex;align-items:center;gap:8px;margin-top:10px;padding:8px 16px;background:#25d366;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;}
.success-page{text-align:center;padding:60px 24px;display:none;}
.success-icon{width:72px;height:72px;border-radius:50%;background:rgba(29,66,138,0.1);border:2px solid var(--secondary);display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 24px;}
.success-title{font-family:'Anton',sans-serif;font-size:36px;letter-spacing:2px;margin-bottom:12px;color:var(--text);}
.success-text{color:var(--muted);font-size:14px;line-height:1.8;max-width:440px;margin:0 auto 28px;}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="register-wrap">
  <div class="register-inner" id="main-form">
    <div class="page-title">Rejoindre la BBA</div>
    <p class="page-sub">Inscription joueur &mdash; V&eacute;rification d&apos;identit&eacute; requise &mdash; Validation par le responsable d&apos;&eacute;quipe</p>

    <div class="steps">
      <div class="step active" id="step-1"><div class="step-circle">1</div><div class="step-label">Identit&eacute;</div></div>
      <div class="step" id="step-2"><div class="step-circle">2</div><div class="step-label">Equipe</div></div>
      <div class="step" id="step-3"><div class="step-circle">3</div><div class="step-label">CNI</div></div>
      <div class="step" id="step-4"><div class="step-circle">4</div><div class="step-label">Confirmation</div></div>
    </div>

    <div class="alert alert-error" id="alert-error"></div>

    <!-- STEP 1 : IDENTITE -->
    <div id="panel-1">
      <div class="card">
        <div class="card-title">Informations personnelles</div>
        <div class="field-row">
          <div class="field"><label>Pr&eacute;nom <span class="required">*</span></label><input type="text" id="f-prenom" placeholder="Jean"></div>
          <div class="field"><label>Nom <span class="required">*</span></label><input type="text" id="f-nom" placeholder="Dupont"></div>
        </div>
        <div class="field"><label>Email <span class="required">*</span></label><input type="email" id="f-email" placeholder="jean@email.com"></div>
        <div class="field"><label>T&eacute;l&eacute;phone <span class="required">*</span></label><input type="tel" id="f-phone" placeholder="+228 90 00 00 00"></div>
        <div class="field-row">
          <div class="field"><label>Date de naissance <span class="required">*</span></label><input type="date" id="f-dob"></div>
          <div class="field"><label>Taille <span class="required">*</span></label><input type="text" id="f-height" placeholder="Ex: 1m85"><div class="field-note">Format : 1m85</div></div>
        </div>
        <div class="field"><label>Nationalit&eacute;</label><input type="text" id="f-nationality" placeholder="Togolaise" value="Togolaise"></div>
        <div class="field"><label>Mot de passe <span class="required">*</span></label><input type="password" id="f-password" placeholder="Minimum 8 caract&egrave;res"><div class="field-note">Minimum 8 caract&egrave;res</div></div>
      </div>
      <div class="nav-btns"><span></span><button class="btn btn-primary" onclick="goStep(2)">Suivant &rarr;</button></div>
    </div>

    <!-- STEP 2 : EQUIPE -->
    <div id="panel-2" style="display:none">
      <div class="card">
        <div class="card-title">Choix de l&apos;&eacute;quipe et poste</div>
        <div class="field">
          <label>Equipe souhait&eacute;e <span class="required">*</span></label>
          <div class="team-choice">
            <?php foreach ($teams as $t): ?>
            <div class="team-btn" id="btn-<?=$t['id']?>" onclick="selectTeam('<?=$t['id']?>')">
              <div class="team-btn-logo" style="background:<?=$t['id']==='solo'?'rgba(0,104,55,0.1)':'rgba(206,17,38,0.1)'?>;border:2px solid <?=$t['id']==='solo'?'#006837':'#CE1126'?>;color:<?=$t['id']==='solo'?'#006837':'#CE1126'?>">
                <?php if (!empty($t['logo']) && file_exists(__DIR__.'/assets/'.$t['logo'])): ?>
                  <img src="assets/<?=$t['logo']?>" alt="<?=$t['name']?>">
                <?php else: ?>
                  <?=strtoupper(substr($t['id'],0,2))?>
                <?php endif; ?>
              </div>
              <div class="team-btn-name"><?=$t['name']?></div>
              <div class="team-btn-city">Lom&eacute;, Togo</div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label>Poste <span class="required">*</span></label>
            <select id="f-position">
              <option value="">Choisir...</option>
              <option value="PG">PG &mdash; Meneur</option>
              <option value="SG">SG &mdash; Arri&egrave;re</option>
              <option value="SF">SF &mdash; Ailier</option>
              <option value="PF">PF &mdash; Ailier fort</option>
              <option value="C">C &mdash; Pivot</option>
            </select>
          </div>
          <div class="field">
            <label>Num&eacute;ro de maillot</label>
            <input type="number" id="f-number" placeholder="0 - 99" min="0" max="99">
          </div>
        </div>
        <div style="background:rgba(29,66,138,0.06);border:1px solid rgba(29,66,138,0.15);border-radius:8px;padding:12px;font-size:12px;color:var(--secondary);line-height:1.7;">
          Ta demande sera examin&eacute;e par le responsable de l&apos;&eacute;quipe choisie. Il peut accepter ou refuser ta candidature.
        </div>
      </div>
      <div class="nav-btns">
        <button class="btn btn-outline" onclick="goStep(1)">&larr; Retour</button>
        <button class="btn btn-primary" onclick="goStep(3)">Suivant &rarr;</button>
      </div>
    </div>

    <!-- STEP 3 : CNI -->
    <div id="panel-3" style="display:none">
      <div class="card">
        <div class="card-title">V&eacute;rification d&apos;identit&eacute;</div>
        <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.7;">
          Pour valider ton inscription, tu dois envoyer une photo lisible de ta <strong>CNI ou passeport</strong> directement par WhatsApp au responsable de l&apos;&eacute;quipe que tu as choisie.
        </p>
        <div class="whatsapp-box" id="whatsapp-info">
          <div class="whatsapp-box-title">Contact du responsable</div>
          <div class="whatsapp-box-text" id="whatsapp-text">Choisissez une &eacute;quipe &agrave; l&apos;&eacute;tape pr&eacute;c&eacute;dente pour voir le contact.</div>
        </div>
        <div style="margin-top:16px;background:rgba(252,209,22,0.1);border:1px solid rgba(252,209,22,0.4);border-radius:8px;padding:14px;font-size:12px;color:#92600a;line-height:1.7;">
          <strong>Important :</strong> Ton compte ne sera activ&eacute; qu&apos;apr&egrave;s validation de ta pi&egrave;ce d&apos;identit&eacute; par le responsable. Tu recevras une confirmation d&egrave;s que ton dossier sera trait&eacute;.
        </div>
      </div>
      <div class="nav-btns">
        <button class="btn btn-outline" onclick="goStep(2)">&larr; Retour</button>
        <button class="btn btn-primary" onclick="goStep(4)">Suivant &rarr;</button>
      </div>
    </div>

    <!-- STEP 4 : CONFIRMATION -->
    <div id="panel-4" style="display:none">
      <div class="card">
        <div class="card-title">Confirmation du dossier</div>
        <div id="confirm-summary"></div>
        <p style="font-size:12px;color:var(--muted);margin-top:16px;line-height:1.7;">
          En soumettant ce dossier, tu acceptes les r&egrave;gles de la BBA et que tes informations soient v&eacute;rifi&eacute;es par les responsables.
        </p>
      </div>
      <div class="nav-btns">
        <button class="btn btn-outline" onclick="goStep(3)">&larr; Retour</button>
        <button class="btn btn-primary" id="btn-submit" onclick="submitForm()">Soumettre le dossier</button>
      </div>
    </div>
  </div>

  <!-- SUCCESS -->
  <div class="success-page" id="success-page">
    <div class="success-icon">&#10003;</div>
    <div class="success-title">Demande envoy&eacute;e !</div>
    <p class="success-text">
      Ton dossier a bien &eacute;t&eacute; re&ccedil;u. Le responsable de <strong id="success-team"></strong> va examiner ta candidature.<br><br>
      <strong>N&apos;oublie pas</strong> d&apos;envoyer ta CNI par WhatsApp pour activer ton compte. Tu recevras une confirmation d&egrave;s que ton dossier sera trait&eacute;.
    </p>
    <div id="success-whatsapp"></div>
    <br><br>
    <a href="login.php" class="btn btn-primary">Se connecter</a>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="app.js"></script>
<script>
let currentStep = 1;
let selectedTeam = '';
const teamsData = <?=json_encode($teams_data)?>;

function selectTeam(team) {
  selectedTeam = team;
  document.querySelectorAll('.team-btn').forEach(b => b.classList.remove('selected'));
  document.getElementById('btn-' + team).classList.add('selected');
  updateWhatsapp();
}

function updateWhatsapp() {
  if (!selectedTeam || !teamsData[selectedTeam]) return;
  const t = teamsData[selectedTeam];
  const wa = t.whatsapp || 'Non renseign&eacute;';
  const waLink = t.whatsapp ? 'https://wa.me/' + t.whatsapp.replace(/\D/g,'') : '#';
  document.getElementById('whatsapp-text').innerHTML =
    'Envoie ta CNI au responsable de <strong>' + t.name + '</strong> :<br>' +
    '<strong>WhatsApp :</strong> ' + wa;
  const box = document.getElementById('whatsapp-info');
  if (t.whatsapp) {
    box.innerHTML += '<a href="' + waLink + '" target="_blank" class="whatsapp-btn">Ouvrir WhatsApp</a>';
  }
}

function showAlert(msg) {
  const el = document.getElementById('alert-error');
  el.textContent = msg; el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 5000);
}

function goStep(n) {
  if (n > 1) {
    const prenom = document.getElementById('f-prenom').value.trim();
    const nom = document.getElementById('f-nom').value.trim();
    const email = document.getElementById('f-email').value.trim();
    const phone = document.getElementById('f-phone').value.trim();
    const dob = document.getElementById('f-dob').value;
    const height = document.getElementById('f-height').value.trim();
    const pwd = document.getElementById('f-password').value;
    if (!prenom||!nom||!email||!phone||!dob||!height||!pwd) { showAlert('Remplis tous les champs obligatoires.'); return; }
    if (pwd.length < 8) { showAlert('Le mot de passe doit faire au moins 8 caracteres.'); return; }
  }
  if (n > 2) {
    if (!selectedTeam) { showAlert('Choisis une equipe.'); return; }
    if (!document.getElementById('f-position').value) { showAlert('Choisis ton poste.'); return; }
  }
  if (n === 4) {
    const teamName = teamsData[selectedTeam] ? teamsData[selectedTeam].name : selectedTeam;
    const items = [
      ['Prenom', document.getElementById('f-prenom').value],
      ['Nom', document.getElementById('f-nom').value],
      ['Email', document.getElementById('f-email').value],
      ['Telephone', document.getElementById('f-phone').value],
      ['Date de naissance', document.getElementById('f-dob').value],
      ['Taille', document.getElementById('f-height').value],
      ['Equipe', teamName],
      ['Poste', document.getElementById('f-position').value],
      ['Numero', '#' + (document.getElementById('f-number').value || 'Non precise')],
    ];
    document.getElementById('confirm-summary').innerHTML = items.map(([l,v]) =>
      '<div class="confirm-row"><span class="confirm-label">' + l + '</span><span class="confirm-val">' + v + '</span></div>'
    ).join('');
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
  form.append('action', 'register');
  form.append('name', document.getElementById('f-prenom').value + ' ' + document.getElementById('f-nom').value);
  form.append('email', document.getElementById('f-email').value);
  form.append('phone', document.getElementById('f-phone').value);
  form.append('dob', document.getElementById('f-dob').value);
  form.append('height', document.getElementById('f-height').value);
  form.append('nationality', document.getElementById('f-nationality').value);
  form.append('team', selectedTeam);
  form.append('position', document.getElementById('f-position').value);
  form.append('number', document.getElementById('f-number').value);
  form.append('password', document.getElementById('f-password').value);
  const res = await fetch('auth.php', {method:'POST', body:form});
  const json = await res.json();
  if (json.success) {
    document.getElementById('main-form').style.display = 'none';
    document.getElementById('success-page').style.display = 'block';
    const t = teamsData[selectedTeam];
    document.getElementById('success-team').textContent = t ? t.name : selectedTeam;
    if (t && t.whatsapp) {
      const waLink = 'https://wa.me/' + t.whatsapp.replace(/\D/g,'');
      document.getElementById('success-whatsapp').innerHTML =
        '<a href="' + waLink + '" target="_blank" class="whatsapp-btn">Envoyer ma CNI sur WhatsApp</a>';
    }
  } else {
    showAlert(json.message);
    btn.textContent = 'Soumettre le dossier'; btn.disabled = false;
  }
}
</script>
</body>
</html>
