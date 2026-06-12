<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
if (!isManager()) { header('Location: /workspace/dashboard.php'); exit; }
$user = currentUser();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>UP TECH GROUP — Facturation</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#29235C;--accent:#36A9E1;--bg:#0f0e1a;--bg2:#13122a;--bg3:#1e1d35;--card:#1a1930;--border:rgba(54,169,225,0.15);--text:#e8e6f0;--muted:#7a78a0;--success:#2ecc87;--warning:#f0a500;--danger:#e05252;--purple:#9b8fff;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 0% 0%,rgba(41,35,92,0.5) 0%,transparent 60%);pointer-events:none;}

.topbar{position:sticky;top:0;z-index:100;background:rgba(19,18,42,0.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;padding:0 24px;gap:16px;}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--accent);background:rgba(54,169,225,.08);}
.back-btn svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.topbar-title{flex:1;font-size:15px;font-weight:700;color:#fff;}
.new-btn{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:8px 18px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}

.page{max-width:1100px;margin:0 auto;padding:24px 20px 48px;position:relative;z-index:1;}

/* KPIs */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;}
.kpi-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;}
.kpi-icon svg{width:15px;height:15px;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.kpi-val{font-size:22px;font-weight:800;color:#fff;font-family:'Space Mono',monospace;letter-spacing:-0.5px;}
.kpi-label{font-size:11px;color:var(--muted);margin-top:3px;}

/* TABS */
.tabs{display:flex;gap:4px;background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:16px;}
.tab{flex:1;padding:8px;border-radius:7px;font-size:12px;font-weight:600;color:var(--muted);cursor:pointer;border:none;background:none;font-family:'Poppins',sans-serif;transition:all .2s;text-align:center;}
.tab.active{background:var(--card);color:#fff;}

/* FILTER BAR */
.filter-bar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.filter-select{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:12px;outline:none;}
.search-input{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:'Poppins',sans-serif;font-size:12px;outline:none;flex:1;min-width:180px;}
.search-input::placeholder{color:var(--muted);}

/* TABLE */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:18px;}
.tbl-wrap{overflow-x:auto;}
.tbl{width:100%;border-collapse:collapse;min-width:640px;}
.tbl th{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;padding:0 10px 10px;text-align:left;border-bottom:1px solid var(--border);}
.tbl td{font-size:12px;padding:12px 10px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
.tbl tr:last-child td{border:none;}
.tbl tr:hover td{background:rgba(54,169,225,.03);}
.mono{font-family:'Space Mono',monospace;font-size:11px;}

/* BADGES */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;}
.type-devis{background:rgba(54,169,225,.15);color:var(--accent);}
.type-facture{background:rgba(46,204,135,.15);color:var(--success);}
.type-avoir{background:rgba(155,143,255,.15);color:var(--purple);}
.st-brouillon{background:rgba(122,120,160,.15);color:var(--muted);}
.st-envoye{background:rgba(54,169,225,.15);color:var(--accent);}
.st-accepte{background:rgba(155,143,255,.15);color:var(--purple);}
.st-paye{background:rgba(46,204,135,.15);color:var(--success);}
.st-annule{background:rgba(224,82,82,.15);color:var(--danger);}

/* ACTIONS */
.action-btns{display:flex;gap:4px;}
.action-btn{background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:5px 8px;cursor:pointer;color:var(--muted);font-size:11px;font-family:'Poppins',sans-serif;transition:all .2s;white-space:nowrap;}
.action-btn:hover{border-color:var(--accent);color:var(--accent);}
.action-btn.danger:hover{border-color:var(--danger);color:var(--danger);}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:16px;width:100%;max-width:680px;max-height:92vh;overflow-y:auto;}
.modal-head{padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--bg2);z-index:1;border-bottom:1px solid var(--border);padding-bottom:14px;}
.modal-head h3{font-size:16px;font-weight:700;color:#fff;}
.modal-close{background:var(--bg3);border:1px solid var(--border);border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);}
.modal-close svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;}
.modal-body{padding:20px 24px;}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.field{margin-bottom:14px;}
label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;display:block;margin-bottom:5px;}
input,select,textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;padding:10px 13px;color:var(--text);font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:border-color .2s;-webkit-appearance:none;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);}
select option{background:var(--bg2);}
.modal-foot{padding:0 24px 24px;display:flex;gap:10px;justify-content:flex-end;position:sticky;bottom:0;background:var(--bg2);padding-top:14px;border-top:1px solid var(--border);}
.btn-p{background:linear-gradient(135deg,var(--primary),var(--accent));border:none;border-radius:9px;padding:10px 24px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-s{background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:10px 18px;color:var(--muted);font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}

/* LIGNES EDITOR */
.lignes-editor{margin-top:16px;}
.lignes-header{display:grid;grid-template-columns:3fr 1fr 1fr 1.5fr 0.8fr 0.5fr;gap:6px;margin-bottom:6px;}
.lignes-header span{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;}
.ligne-row{display:grid;grid-template-columns:3fr 1fr 1fr 1.5fr 0.8fr 0.5fr;gap:6px;margin-bottom:6px;align-items:center;}
.ligne-row input{padding:8px 10px;font-size:12px;}
.ligne-del{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.2);border-radius:6px;padding:7px;cursor:pointer;color:var(--danger);display:flex;align-items:center;justify-content:center;}
.ligne-del svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
.add-ligne-btn{background:rgba(54,169,225,.08);border:1px dashed rgba(54,169,225,.3);border-radius:8px;padding:9px;width:100%;text-align:center;color:var(--accent);font-size:12px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;margin-top:4px;transition:all .2s;}
.add-ligne-btn:hover{background:rgba(54,169,225,.14);}
.totaux-preview{background:var(--bg3);border-radius:10px;padding:14px;margin-top:14px;}
.tot-row{display:flex;justify-content:space-between;font-size:12px;padding:4px 0;}
.tot-row.ttc{font-size:15px;font-weight:800;color:#fff;border-top:1px solid var(--border);padding-top:10px;margin-top:6px;}
.tot-val{font-family:'Space Mono',monospace;color:var(--accent);}
.tot-row.ttc .tot-val{color:var(--success);}

/* TOAST */
#toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:11px 20px;font-size:13px;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;}
#toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
#toast.success{border-color:rgba(46,204,135,.4);color:var(--success);}
#toast.error{border-color:rgba(224,82,82,.4);color:var(--danger);}

::-webkit-scrollbar{width:4px;height:4px;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px;}

@media(max-width:768px){
  .kpi-grid{grid-template-columns:repeat(2,1fr);}
  .field-row{grid-template-columns:1fr;}
  .lignes-header,.ligne-row{grid-template-columns:2fr 0.8fr 1fr 0.5fr;}
  .page{padding:16px 12px 48px;}
}
</style>
</head>
<body>

<div class="topbar">
  <a class="back-btn" href="dashboard.php">
    <svg viewBox="0 0 24 24"><polyline points="15,18 9,12 15,6"/></svg>
    Workspace
  </a>
  <div class="topbar-title">Facturation</div>
  <button class="new-btn" onclick="openNew()">+ Nouveau document</button>
</div>

<div class="page">

  <!-- KPIs -->
  <div class="kpi-grid" id="kpiGrid">
    <?php for($i=0;$i<4;$i++): ?><div class="kpi-card" style="opacity:.3"><div style="height:70px"></div></div><?php endfor; ?>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab active" onclick="setType('',this)">Tous</button>
    <button class="tab" onclick="setType('Devis',this)">Devis</button>
    <button class="tab" onclick="setType('Facture',this)">Factures</button>
    <button class="tab" onclick="setType('Avoir',this)">Avoirs</button>
  </div>

  <!-- FILTERS -->
  <div class="filter-bar">
    <input class="search-input" id="searchInput" placeholder="Rechercher par numéro, client…" oninput="filterTable()">
    <select class="filter-select" id="statutFilter" onchange="loadListe()">
      <option value="">Tous les statuts</option>
      <option>Brouillon</option><option>Envoyé</option><option>Accepté</option>
      <option>Payé</option><option>Annulé</option>
    </select>
  </div>

  <!-- TABLE -->
  <div class="card">
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr>
          <th>Numéro</th><th>Type</th><th>Client</th>
          <th>Montant TTC</th><th>Date</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody id="factureTable"></tbody>
      </table>
    </div>
  </div>

</div>

<!-- MODAL NOUVEAU / MODIFIER -->
<div class="modal-overlay" id="modalDoc">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalDocTitle">Nouveau document</h3>
      <div class="modal-close" onclick="closeModal('modalDoc')"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
    </div>
    <div class="modal-body">
      <input type="hidden" id="docId">
      <div class="field-row">
        <div class="field"><label>Type *</label>
          <select id="docType">
            <option>Devis</option><option>Facture</option><option>Avoir</option>
          </select>
        </div>
        <div class="field"><label>Devise</label>
          <select id="docDevise">
            <option value="FCFA">FCFA — Franc CFA</option>
            <option value="EUR">EUR — Euro</option>
            <option value="USD">USD — Dollar US</option>
            <option value="GBP">GBP — Livre Sterling</option>
            <option value="XOF">XOF — Franc CFA UEMOA</option>
          </select>
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label>Client</label><select id="docClient"><option value="">— Aucun —</option></select></div>
        <div class="field"><label>Projet lié</label><select id="docProjet"><option value="">— Aucun —</option></select></div>
      </div>
      <div class="field"><label>Objet / Titre du document</label><input type="text" id="docObjet" placeholder="Ex: Développement site web e-commerce"></div>
      <div class="field-row">
        <div class="field"><label>Date d'émission *</label><input type="date" id="docDateEmis"></div>
        <div class="field"><label>Date d'échéance</label><input type="date" id="docDateEch"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Remise globale (%)</label><input type="number" id="docRemise" value="0" min="0" max="100" step="0.5" oninput="updateTotaux()"></div>
        <div class="field"><label>TVA (%)</label><input type="number" id="docTva" value="0" min="0" max="30" step="0.5" oninput="updateTotaux()"></div>
      </div>

      <!-- LIGNES -->
      <div class="lignes-editor">
        <label>Lignes du document</label>
        <div class="lignes-header">
          <span>Description</span><span>Qté</span><span>Unité</span><span>Prix unit.</span><span>Remise %</span><span></span>
        </div>
        <div id="lignesContainer"></div>
        <button class="add-ligne-btn" onclick="addLigne()">+ Ajouter une ligne</button>
      </div>

      <!-- TOTAUX PREVIEW -->
      <div class="totaux-preview">
        <div class="tot-row"><span style="color:var(--muted)">Total HT</span><span class="tot-val" id="prevHT">0 FCFA</span></div>
        <div class="tot-row" id="prevRemiseRow" style="display:none"><span style="color:var(--danger)">Remise</span><span class="tot-val" style="color:var(--danger)" id="prevRemise">0 FCFA</span></div>
        <div class="tot-row" id="prevTvaRow" style="display:none"><span style="color:var(--muted)">TVA</span><span class="tot-val" id="prevTva">0 FCFA</span></div>
        <div class="tot-row ttc"><span>TOTAL</span><span class="tot-val" id="prevTTC">0 FCFA</span></div>
      </div>

      <div class="field" style="margin-top:14px"><label>Notes</label><textarea id="docNotes" rows="2" placeholder="Informations complémentaires pour le client…"></textarea></div>
      <div class="field"><label>Conditions de paiement</label><textarea id="docConditions" rows="2">Paiement à réception. Tout retard de paiement entraînera des pénalités.</textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn-s" onclick="closeModal('modalDoc')">Annuler</button>
      <button class="btn-p" onclick="saveDoc()">Enregistrer</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
let currentType='', allDocs=[], lignes=[], currentDevise='FCFA';

// ===== API =====
async function api(p){const fd=new FormData();Object.entries(p).forEach(([k,v])=>fd.append(k,v));const r=await fetch('facturation_api.php',{method:'POST',body:fd});return r.json();}
async function apiGet(p){const r=await fetch('facturation_api.php?'+new URLSearchParams(p));return r.json();}

function toast(msg,type='success'){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+type;setTimeout(()=>t.className='',3500);}
function fmt(n,d=currentDevise){return parseInt(n||0).toLocaleString('fr-FR')+' '+(d||'FCFA');}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));

// ===== LOAD STATS =====
async function loadStats(){
  const s=await apiGet({action:'stats'});
  document.getElementById('kpiGrid').innerHTML=[
    {ico:'<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',val:s.total,label:'Total documents',color:'#36A9E1',bg:'rgba(54,169,225,.12)'},
    {ico:'<polyline points="20 6 9 17 4 12"/>',val:s.payees,label:'Payées',color:'#2ecc87',bg:'rgba(46,204,135,.12)'},
    {ico:'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',val:s.attente,label:'En attente',color:'#f0a500',bg:'rgba(240,165,0,.12)'},
    {ico:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',val:parseInt(s.ca_fact).toLocaleString('fr-FR'),label:'CA encaissé (FCFA)',color:'#2ecc87',bg:'rgba(46,204,135,.12)'},
  ].map(k=>`<div class="kpi-card"><div class="kpi-icon" style="background:${k.bg}"><svg viewBox="0 0 24 24" fill="none" stroke="${k.color}" stroke-width="1.8" stroke-linecap="round">${k.ico}</svg></div><div class="kpi-val">${k.val}</div><div class="kpi-label">${k.label}</div></div>`).join('');
}

// ===== LOAD LISTE =====
async function loadListe(){
  const statut=document.getElementById('statutFilter').value;
  allDocs=await apiGet({action:'liste',type:currentType,statut});
  renderTable(allDocs);
}

function setType(t,btn){
  currentType=t;
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  loadListe();
}

function filterTable(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  renderTable(allDocs.filter(d=>d.numero.toLowerCase().includes(q)||(d.client_nom||'').toLowerCase().includes(q)));
}

const STATUT_CLASS={'Brouillon':'st-brouillon','Envoyé':'st-envoye','Accepté':'st-accepte','Payé':'st-paye','Annulé':'st-annule','Refusé':'st-annule'};
const TYPE_CLASS={'Devis':'type-devis','Facture':'type-facture','Avoir':'type-avoir'};

function renderTable(docs){
  const tbody=document.getElementById('factureTable');
  if(!docs.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">Aucun document trouvé</td></tr>';return;}
  tbody.innerHTML=docs.map(d=>`
    <tr>
      <td class="mono" style="font-weight:700;color:#fff">${d.numero}</td>
      <td><span class="badge ${TYPE_CLASS[d.type]||'type-facture'}">${d.type}</span></td>
      <td>${d.client_nom||'—'}</td>
      <td class="mono">${parseInt(d.montant_ttc).toLocaleString('fr-FR')} ${d.devise}</td>
      <td style="color:var(--muted);font-size:11px">${d.date_emission}</td>
      <td><span class="badge ${STATUT_CLASS[d.statut]||'st-brouillon'}">${d.statut}</span></td>
      <td>
        <div class="action-btns">
          <a class="action-btn" href="facture_pdf.php?id=${d.id}" target="_blank">PDF</a>
          <button class="action-btn" onclick="editDoc(${d.id})">Modifier</button>
          <button class="action-btn" onclick="changerStatut(${d.id},'${d.statut}')">Statut</button>
          ${d.type==='Facture'?`<button class="action-btn" onclick="creerAvoir(${d.id})">Avoir</button>`:''}
          <button class="action-btn danger" onclick="supprimerDoc(${d.id})">Suppr.</button>
        </div>
      </td>
    </tr>`).join('');
}

// ===== NOUVEAU DOCUMENT =====
async function openNew(){
  document.getElementById('docId').value='';
  document.getElementById('modalDocTitle').textContent='Nouveau document';
  document.getElementById('docType').value='Facture';
  document.getElementById('docDevise').value='FCFA';
  document.getElementById('docObjet').value='';
  document.getElementById('docNotes').value='';
  document.getElementById('docConditions').value='Paiement à réception. Tout retard de paiement entraînera des pénalités.';
  document.getElementById('docRemise').value='0';
  document.getElementById('docTva').value='0';
  document.getElementById('docDateEmis').value=new Date().toISOString().split('T')[0];
  document.getElementById('docDateEch').value='';
  currentDevise='FCFA'; lignes=[];
  renderLignes(); updateTotaux();

  const clients=await apiGet({action:'clients'});
  document.getElementById('docClient').innerHTML='<option value="">— Aucun —</option>'+clients.map(c=>`<option value="${c.id}">${c.raison_sociale}</option>`).join('');
  const projets=await apiGet({action:'projets'});
  document.getElementById('docProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
  openModal('modalDoc');
}

async function editDoc(id){
  const d=await apiGet({action:'detail',id});
  document.getElementById('docId').value=d.id;
  document.getElementById('modalDocTitle').textContent='Modifier — '+d.numero;
  document.getElementById('docType').value=d.type;
  document.getElementById('docDevise').value=d.devise;
  document.getElementById('docObjet').value=d.objet||'';
  document.getElementById('docNotes').value=d.notes||'';
  document.getElementById('docConditions').value=d.conditions||'';
  document.getElementById('docRemise').value=d.remise_pct||'0';
  document.getElementById('docTva').value=d.tva_pct||'0';
  document.getElementById('docDateEmis').value=d.date_emission;
  document.getElementById('docDateEch').value=d.date_echeance||'';
  currentDevise=d.devise||'FCFA';
  lignes=d.lignes||[];

  const clients=await apiGet({action:'clients'});
  document.getElementById('docClient').innerHTML='<option value="">— Aucun —</option>'+clients.map(c=>`<option value="${c.id}"${c.id==d.client_id?' selected':''}>${c.raison_sociale}</option>`).join('');
  const projets=await apiGet({action:'projets'});
  document.getElementById('docProjet').innerHTML='<option value="">— Aucun —</option>'+projets.map(p=>`<option value="${p.id}"${p.id==d.projet_id?' selected':''}>${p.nom}</option>`).join('');

  renderLignes(); updateTotaux();
  openModal('modalDoc');
}

async function saveDoc(){
  const id=document.getElementById('docId').value;
  currentDevise=document.getElementById('docDevise').value;

  if(id){
    const r=await api({action:'modifier',id,
      client_id:document.getElementById('docClient').value,
      projet_id:document.getElementById('docProjet').value,
      devise:currentDevise,
      date_emission:document.getElementById('docDateEmis').value,
      date_echeance:document.getElementById('docDateEch').value,
      objet:document.getElementById('docObjet').value,
      notes:document.getElementById('docNotes').value,
      conditions:document.getElementById('docConditions').value,
      remise_pct:document.getElementById('docRemise').value,
      tva_pct:document.getElementById('docTva').value,
      statut:'Brouillon',
    });
    if(r.success){
      await api({action:'save_lignes',facture_id:id,lignes:JSON.stringify(lignes)});
      closeModal('modalDoc'); toast('Document mis à jour'); loadListe(); loadStats();
    } else toast(r.error||'Erreur','error');
  } else {
    const r=await api({action:'creer',
      type:document.getElementById('docType').value,
      client_id:document.getElementById('docClient').value,
      projet_id:document.getElementById('docProjet').value,
      devise:currentDevise,
      date_emission:document.getElementById('docDateEmis').value,
      date_echeance:document.getElementById('docDateEch').value,
      objet:document.getElementById('docObjet').value,
      notes:document.getElementById('docNotes').value,
      conditions:document.getElementById('docConditions').value,
      remise_pct:document.getElementById('docRemise').value,
      tva_pct:document.getElementById('docTva').value,
    });
    if(r.success){
      await api({action:'save_lignes',facture_id:r.id,lignes:JSON.stringify(lignes)});
      closeModal('modalDoc'); toast('Document créé : '+r.numero); loadListe(); loadStats();
    } else toast(r.error||'Erreur','error');
  }
}

// ===== LIGNES =====
function addLigne(){
  lignes.push({description:'',quantite:1,unite:'',prix_unit:0,remise_pct:0});
  renderLignes();
}

function renderLignes(){
  const c=document.getElementById('lignesContainer');
  c.innerHTML=lignes.map((l,i)=>`
    <div class="ligne-row">
      <input type="text" value="${l.description||''}" placeholder="Description de la prestation" onchange="lignes[${i}].description=this.value;updateTotaux()">
      <input type="number" value="${l.quantite||1}" min="0" step="0.5" onchange="lignes[${i}].quantite=parseFloat(this.value)||1;updateTotaux()">
      <input type="text" value="${l.unite||''}" placeholder="h/j/u" onchange="lignes[${i}].unite=this.value">
      <input type="number" value="${l.prix_unit||0}" min="0" step="100" onchange="lignes[${i}].prix_unit=parseFloat(this.value)||0;updateTotaux()">
      <input type="number" value="${l.remise_pct||0}" min="0" max="100" step="5" onchange="lignes[${i}].remise_pct=parseFloat(this.value)||0;updateTotaux()">
      <button class="ligne-del" onclick="lignes.splice(${i},1);renderLignes();updateTotaux()"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
    </div>`).join('');
  if(!lignes.length) addLigne();
}

function updateTotaux(){
  currentDevise=document.getElementById('docDevise')?.value||'FCFA';
  const remisePct=parseFloat(document.getElementById('docRemise')?.value)||0;
  const tvaPct   =parseFloat(document.getElementById('docTva')?.value)||0;
  let ht=0;
  lignes.forEach(l=>{ht+=((l.quantite||1)*(l.prix_unit||0))*(1-(l.remise_pct||0)/100);});
  const remise=ht*(remisePct/100);
  const htApres=ht-remise;
  const tva=htApres*(tvaPct/100);
  const ttc=htApres+tva;
  document.getElementById('prevHT').textContent=fmt(ht);
  document.getElementById('prevRemiseRow').style.display=remisePct>0?'flex':'none';
  document.getElementById('prevRemise').textContent='- '+fmt(remise);
  document.getElementById('prevTvaRow').style.display=tvaPct>0?'flex':'none';
  document.getElementById('prevTva').textContent=fmt(tva);
  document.getElementById('prevTTC').textContent=fmt(ttc);
}

// ===== STATUT =====
async function changerStatut(id, current){
  const statuts=['Brouillon','Envoyé','Accepté','Payé','Annulé','Refusé'];
  const nouveau=prompt('Nouveau statut :\n'+statuts.join(', '),current);
  if(!nouveau||!statuts.includes(nouveau))return;
  const r=await api({action:'statut',id,statut:nouveau});
  if(r.success){toast('Statut mis à jour → '+nouveau);loadListe();loadStats();}
  else toast(r.error||'Erreur','error');
}

async function creerAvoir(id){
  if(!confirm('Créer un avoir pour cette facture ?'))return;
  const r=await api({action:'dupliquer',id,type:'Avoir'});
  if(r.success){toast('Avoir créé : '+r.numero);loadListe();loadStats();editDoc(r.id);}
  else toast(r.error||'Erreur','error');
}

async function supprimerDoc(id){
  if(!confirm('Supprimer ce document ? Cette action est irréversible.'))return;
  const r=await api({action:'supprimer',id});
  if(r.success){toast('Document supprimé');loadListe();loadStats();}
  else toast(r.error||'Erreur','error');
}

// ===== INIT =====
loadStats();
loadListe();
</script>
</body>
</html>
