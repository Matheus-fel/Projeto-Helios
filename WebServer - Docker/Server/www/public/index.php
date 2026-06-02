<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Helios API – Painel de Testes</title>
<style>
  :root {
    --bg: #0f1117; --surface: #1a1d27; --border: #2a2d3a;
    --accent: #f59e0b; --accent2: #3b82f6; --text: #e2e8f0;
    --muted: #64748b; --success: #22c55e; --error: #ef4444;
    --radius: 8px; --font: 'Segoe UI', system-ui, sans-serif;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--font); min-height: 100vh; }

  header {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem;
  }
  .logo { font-size: 1.4rem; font-weight: 700; color: var(--accent); letter-spacing: .5px; }
  .logo span { color: var(--text); font-weight: 300; }
  .badge { background: #22c55e22; color: var(--success); font-size:.75rem;
           padding: 2px 10px; border-radius: 20px; border: 1px solid #22c55e55; }

  main { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }

  .grid { display: grid; grid-template-columns: 240px 1fr; gap: 1.5rem; }

  /* Sidebar */
  .sidebar { display: flex; flex-direction: column; gap: .5rem; }
  .sidebar h3 { font-size: .7rem; text-transform: uppercase; letter-spacing: 1px;
                color: var(--muted); margin: 1rem 0 .25rem; }
  .sidebar h3:first-child { margin-top: 0; }
  .route-btn {
    background: transparent; border: 1px solid var(--border); color: var(--text);
    padding: .6rem .9rem; border-radius: var(--radius); cursor: pointer;
    text-align: left; font-size: .85rem; display: flex; align-items: center; gap: .5rem;
    transition: all .15s;
  }
  .route-btn:hover { background: var(--surface); border-color: var(--accent); color: var(--accent); }
  .route-btn.active { background: #f59e0b1a; border-color: var(--accent); color: var(--accent); }
  .method { font-size: .65rem; font-weight: 700; padding: 1px 6px; border-radius: 4px;
            min-width: 40px; text-align: center; }
  .GET    { background: #3b82f622; color: #3b82f6; }
  .POST   { background: #22c55e22; color: #22c55e; }
  .PUT    { background: #f59e0b22; color: #f59e0b; }
  .DELETE { background: #ef444422; color: #ef4444; }

  /* Panel */
  .panel { display: flex; flex-direction: column; gap: 1.25rem; }

  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .card-header { padding: .75rem 1rem; border-bottom: 1px solid var(--border);
                 display: flex; align-items: center; gap: .75rem; }
  .card-title { font-weight: 600; font-size: .9rem; }
  .endpoint-badge { background: var(--bg); border: 1px solid var(--border);
                    border-radius: 4px; padding: 2px 8px; font-family: monospace; font-size: .8rem;
                    color: var(--muted); }
  .card-body { padding: 1rem; }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
  .form-group { display: flex; flex-direction: column; gap: .35rem; }
  .form-group.full { grid-column: 1 / -1; }
  label { font-size: .78rem; color: var(--muted); }
  input, select, textarea {
    background: var(--bg); border: 1px solid var(--border); border-radius: 6px;
    color: var(--text); padding: .55rem .75rem; font-size: .85rem; outline: none;
    font-family: var(--font); transition: border-color .15s;
  }
  input:focus, select:focus, textarea:focus { border-color: var(--accent2); }
  textarea { resize: vertical; min-height: 80px; }

  .btn {
    padding: .6rem 1.4rem; border-radius: 6px; border: none; cursor: pointer;
    font-weight: 600; font-size: .85rem; transition: opacity .15s;
  }
  .btn:hover { opacity: .85; }
  .btn-primary { background: var(--accent); color: #000; }
  .btn-blue    { background: var(--accent2); color: #fff; }
  .btn-red     { background: var(--error); color: #fff; }

  /* Response */
  .response-header { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; }
  .status-badge { padding: 2px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
  .status-ok  { background: #22c55e22; color: var(--success); border: 1px solid #22c55e55; }
  .status-err { background: #ef444422; color: var(--error);   border: 1px solid #ef444455; }
  pre {
    background: var(--bg); border: 1px solid var(--border); border-radius: 6px;
    padding: 1rem; overflow-x: auto; font-size: .8rem; line-height: 1.6;
    max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;
  }
  .placeholder { color: var(--muted); font-style: italic; font-size: .85rem; text-align: center; padding: 2rem; }
  .loading { display: flex; align-items: center; gap: .5rem; color: var(--muted); font-size: .85rem; padding: .5rem; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .spinner { width:16px; height:16px; border: 2px solid var(--border); border-top-color: var(--accent);
             border-radius: 50%; animation: spin .6s linear infinite; }
</style>
</head>
<body>

<header>
  <div class="logo">☀ Helios <span>API Tester</span></div>
  <span class="badge" id="dbStatus">verificando banco…</span>
</header>

<main>
  <div class="grid">
    <!-- SIDEBAR -->
    <div class="sidebar">
      <h3>Empresas</h3>
      <button class="route-btn active" onclick="load('empresas-list')">
        <span class="method GET">GET</span> /empresas
      </button>
      <button class="route-btn" onclick="load('empresa-show')">
        <span class="method GET">GET</span> /empresas/{id}
      </button>
      <button class="route-btn" onclick="load('empresa-create')">
        <span class="method POST">POST</span> /empresas
      </button>
      <button class="route-btn" onclick="load('empresa-usinas')">
        <span class="method GET">GET</span> /empresas/{id}/usinas
      </button>

      <h3>Usuários</h3>
      <button class="route-btn" onclick="load('usuario-list')">
        <span class="method GET">GET</span> /usuarios
      </button>
      <button class="route-btn" onclick="load('usuario-create')">
        <span class="method POST">POST</span> /usuarios
      </button>
      <button class="route-btn" onclick="load('usuario-login')">
        <span class="method POST">POST</span> /usuarios/login
      </button>
      <button class="route-btn" onclick="load('usuario-historico')">
        <span class="method GET">GET</span> /usuarios/{id}/historico
      </button>

      <h3>Usinas</h3>
      <button class="route-btn" onclick="load('usina-list')">
        <span class="method GET">GET</span> /usinas
      </button>
      <button class="route-btn" onclick="load('usina-create')">
        <span class="method POST">POST</span> /usinas
      </button>
      <button class="route-btn" onclick="load('usina-telemetria')">
        <span class="method GET">GET</span> /usinas/{id}/telemetria
      </button>

      <h3>Histórico Chat</h3>
      <button class="route-btn" onclick="load('historico-list')">
        <span class="method GET">GET</span> /historico
      </button>
      <button class="route-btn" onclick="load('historico-create')">
        <span class="method POST">POST</span> /historico
      </button>
    </div>

    <!-- PANEL -->
    <div class="panel">
      <div class="card" id="formCard"></div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Resposta</span>
          <span id="statusBadge"></span>
        </div>
        <div class="card-body">
          <div id="response" class="placeholder">Selecione uma rota e clique em Executar</div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
const BASE = window.location.origin;

const routes = {
  'empresas-list':    { method:'GET',  path:'/empresas',           fields:[] },
  'empresa-show':     { method:'GET',  path:'/empresas/{id}',      fields:[{name:'id',label:'ID da Empresa',ph:'1'}] },
  'empresa-create':   { method:'POST', path:'/empresas',           fields:[{name:'nome_comercial',label:'Nome Comercial',ph:'Helios Energia'},{name:'cnpj',label:'CNPJ',ph:'12.345.678/0001-90'}] },
  'empresa-usinas':   { method:'GET',  path:'/empresas/{id}/usinas',fields:[{name:'id',label:'ID da Empresa',ph:'1'}] },

  'usuario-list':     { method:'GET',  path:'/usuarios',           fields:[] },
  'usuario-create':   { method:'POST', path:'/usuarios',           fields:[
    {name:'nome',label:'Nome',ph:'João Silva'},{name:'email',label:'E-mail',ph:'joao@helios.com'},
    {name:'senha',label:'Senha',ph:'••••••••',type:'password'},{name:'empresa_id',label:'Empresa ID',ph:'1'},
    {name:'nivel_acesso',label:'Nível',ph:'operador|gerente|admin'}
  ]},
  'usuario-login':    { method:'POST', path:'/usuarios/login',     fields:[{name:'email',label:'E-mail',ph:'admin@helios.com'},{name:'senha',label:'Senha',ph:'••••••••',type:'password'}] },
  'usuario-historico':{ method:'GET',  path:'/usuarios/{id}/historico',fields:[{name:'id',label:'ID do Usuário',ph:'1'},{name:'limit',label:'Limit (query)',ph:'10',query:true}] },

  'usina-list':       { method:'GET',  path:'/usinas',             fields:[] },
  'usina-create':     { method:'POST', path:'/usinas',             fields:[
    {name:'nome_usina',label:'Nome da Usina',ph:'Solar Norte'},{name:'empresa_id',label:'Empresa ID',ph:'1'},
    {name:'tipo_geracao',label:'Tipo (solar|eolica|hidro…)',ph:'solar'},
    {name:'capacidade_mw',label:'Capacidade (MW)',ph:'50.00'},{name:'localizacao_cidade',label:'Cidade',ph:'Goiânia'}
  ]},
  'usina-telemetria': { method:'GET',  path:'/usinas/{id}/telemetria',fields:[{name:'id',label:'ID da Usina',ph:'1'},{name:'limit',label:'Limit',ph:'20',query:true}] },

  'historico-list':   { method:'GET',  path:'/historico',          fields:[{name:'limit',label:'Limit',ph:'10',query:true}] },
  'historico-create': { method:'POST', path:'/historico',          fields:[
    {name:'usuario_id',label:'Usuário ID',ph:'1'},
    {name:'pergunta_tecnica',label:'Pergunta',ph:'Como melhorar eficiência?',textarea:true},
    {name:'resposta_ia',label:'Resposta IA',ph:'Recomenda-se…',textarea:true},
    {name:'normas_relacionadas',label:'Normas (opcional)',ph:'ABNT NBR 5410'}
  ]},
};

let current = 'empresas-list';

function load(key) {
  current = key;
  document.querySelectorAll('.route-btn').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
  const r = routes[key];

  let fieldsHtml = '';
  const pairs = [];
  for (let i = 0; i < r.fields.length; i += 2) {
    const f1 = r.fields[i], f2 = r.fields[i+1];
    const inp = f => f.textarea
      ? `<textarea id="f_${f.name}" placeholder="${f.ph}"></textarea>`
      : `<input id="f_${f.name}" type="${f.type||'text'}" placeholder="${f.ph}">`;
    const grp = f => `<div class="form-group${f.textarea?' full':''}"><label>${f.label}</label>${inp(f)}</div>`;
    if (f2) pairs.push(`<div class="form-row">${grp(f1)}${grp(f2)}</div>`);
    else pairs.push(grp(f1));
  }

  const btnClass = {GET:'btn-blue',POST:'btn-primary',PUT:'btn-primary',DELETE:'btn-red'}[r.method];
  document.getElementById('formCard').innerHTML = `
    <div class="card-header">
      <span class="card-title">${r.method} ${r.path}</span>
      <span class="endpoint-badge method ${r.method}">${r.method}</span>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:.75rem">
      ${pairs.join('')}
      <div><button class="btn ${btnClass}" onclick="execute()">▶ Executar</button></div>
    </div>`;

  document.getElementById('response').innerHTML = '<div class="placeholder">Clique em Executar</div>';
  document.getElementById('statusBadge').innerHTML = '';
}

function val(name) {
  const el = document.getElementById('f_'+name);
  return el ? el.value.trim() : '';
}

async function execute() {
  const r = routes[current];
  let path = r.path;
  let queryParts = [];
  const body = {};

  r.fields.forEach(f => {
    const v = val(f.name);
    if (!v) return;
    if (path.includes('{'+f.name+'}')) {
      path = path.replace('{'+f.name+'}', encodeURIComponent(v));
    } else if (f.query) {
      queryParts.push(`${f.name}=${encodeURIComponent(v)}`);
    } else {
      body[f.name] = isNaN(v) ? v : (v.includes('.') ? parseFloat(v) : parseInt(v));
    }
  });

  let url = BASE + path;
  if (queryParts.length) url += '?' + queryParts.join('&');

  const opts = { method: r.method, headers: {'Content-Type':'application/json'} };
  if (['POST','PUT'].includes(r.method) && Object.keys(body).length) {
    opts.body = JSON.stringify(body);
  }

  document.getElementById('response').innerHTML = '<div class="loading"><div class="spinner"></div> Aguardando resposta…</div>';
  document.getElementById('statusBadge').innerHTML = '';

  try {
    const res = await fetch(url, opts);
    const text = await res.text();
    let pretty;
    try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch { pretty = text; }

    const ok = res.ok;
    document.getElementById('statusBadge').innerHTML =
      `<span class="status-badge ${ok?'status-ok':'status-err'}">${res.status} ${res.statusText}</span>`;
    document.getElementById('response').innerHTML =
      `<pre>${escHtml(pretty)}</pre>`;
  } catch(e) {
    document.getElementById('statusBadge').innerHTML = '<span class="status-badge status-err">Erro de rede</span>';
    document.getElementById('response').innerHTML = `<pre>${escHtml(e.message)}</pre>`;
  }
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Verificar conexão com banco
async function checkDb() {
  try {
    const r = await fetch(BASE + '/empresas');
    const j = await r.json();
    const el = document.getElementById('dbStatus');
    if (r.ok) {
      el.textContent = '✓ Banco conectado';
      el.style.cssText = 'background:#22c55e22;color:#22c55e;border:1px solid #22c55e55;padding:2px 10px;border-radius:20px;font-size:.75rem';
    } else throw new Error(j.message);
  } catch(e) {
    const el = document.getElementById('dbStatus');
    el.textContent = '✗ Erro: ' + e.message;
    el.style.cssText = 'background:#ef444422;color:#ef4444;border:1px solid #ef444455;padding:2px 10px;border-radius:20px;font-size:.75rem';
  }
}

// Init
load('empresas-list');
checkDb();
</script>
</body>
</html>
