<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Helios OS – Inteligência em Engenharia</title>
<style>
  :root {
    --bg: #06070a; 
    --surface: #0f111a; 
    --surface-hover: #171a26;
    --border: #1e2235;
    --accent: #2e210d; 
    --accent-rgb: 245, 158, 11;
    --accent2: #ff6b35; 
    --text: #e2e8f0;
    --muted: #475569; 
    --success: #10b981; 
    --error: #ef4444;
    --radius: 8px; 
    --font: 'Segoe UI', system-ui, sans-serif;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--font); min-height: 100vh; overflow-x: hidden; }

  /* GESTÃO DE TELAS */
  .view-section { display: none; }
  .view-section.active { display: flex; }

  /* TELA DE AUTENTICAÇÃO (LOGIN / CADASTRO) */
  .auth-container {
    min-height: 100vh;
    width: 100vw;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.03), transparent 40%), var(--bg);
  }
  .auth-card {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 2.5rem;
    border-radius: calc(var(--radius) * 1.5);
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
  }
  .auth-header { text-align: center; margin-bottom: 2rem; }
  .auth-header .logo { justify-content: center; font-size: 1.8rem; margin-bottom: 0.5rem; }
  .auth-header p { color: #64748b; font-size: 0.9rem; }
  .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #64748b; }
  .auth-footer span { color: var(--accent); text-decoration: none; font-weight: 600; cursor: pointer; }
  .auth-footer span:hover { text-decoration: underline; }

  /* HEADER PRINCIPAL */
  header {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
  }
  .logo { font-size: 1.3rem; font-weight: 800; color: #fff; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; }
  .logo svg { color: var(--accent); filter: drop-shadow(0 0 8px rgba(var(--accent-rgb), 0.4)); }
  .logo span { color: #64748b; font-weight: 400; font-size: 0.85rem; text-transform: none; }
  
  .header-right { display: flex; align-items: center; gap: 1rem; }
  .badge { background: rgba(16, 185, 129, 0.05); color: var(--success); font-size: .75rem; font-weight: 600; padding: 4px 12px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.15); display: flex; align-items: center; gap: 0.35rem; }
  .btn-logout { background: transparent; border: 1px solid var(--border); color: #ef4444; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; cursor: pointer; }
  .btn-logout:hover { background: rgba(239, 68, 68, 0.1); }

  /* DASHBOARD WORKSPACE */
  #mainView { flex-direction: column; }
  main { max-width: 1400px; width: 100%; margin: 1.5rem auto; padding: 0 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; }
  .grid { display: grid; grid-template-columns: 260px 1fr; gap: 1.5rem; }

  /* SIDEBAR */
  .sidebar { display: flex; flex-direction: column; gap: .3rem; background: var(--surface); border: 1px solid var(--border); padding: 1.25rem; border-radius: var(--radius); height: fit-content; }
  .sidebar h3 { font-size: .65rem; text-transform: uppercase; letter-spacing: 1.5px; color: #64748b; margin: 1rem 0 .4rem; padding-left: 0.5rem; font-weight: 700; }
  .sidebar h3:first-child { margin-top: 0; }
  
  .route-btn { background: transparent; border: 1px solid transparent; color: #94a3b8; padding: .55rem .75rem; border-radius: var(--radius); cursor: pointer; text-align: left; font-size: .82rem; display: flex; align-items: center; gap: .6rem; transition: all .15s; font-weight: 500; }
  .route-btn:hover { background: var(--surface-hover); color: #fff; }
  .route-btn.active { background: rgba(245, 158, 11, 0.06); border-color: rgba(245, 158, 11, 0.2); color: var(--accent); }
  
  .method { font-size: .6rem; font-weight: 700; padding: 2px 5px; border-radius: 4px; min-width: 44px; text-align: center; font-family: monospace; }
  .GET    { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
  .POST   { background: rgba(16, 185, 129, 0.15); color: #34d399; }

  /* SEÇÃO SUPERIOR: CHAT IA HELIOS */
  .chat-section {
    display: grid;
    grid-template-columns: 1fr;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .chat-container { display: flex; flex-direction: column; height: 350px; }
  .chat-messages { flex: 1; padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; background: #0b0d14; }
  
  .msg { max-width: 80%; padding: 0.75rem 1rem; border-radius: var(--radius); font-size: 0.9rem; line-height: 1.5; }
  .msg.user { background: var(--border); color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; }
  .msg.helios { background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.15); color: #e2e8f0; align-self: flex-start; border-bottom-left-radius: 2px; }
  .msg-meta { font-size: 0.7rem; color: #64748b; margin-bottom: 0.25rem; font-weight: 600; text-transform: uppercase; }

  .chat-input-area { display: flex; gap: 0.5rem; padding: 0.75rem 1.25rem; background: var(--surface); border-top: 1px solid var(--border); }
  .chat-input-area input { flex: 1; background: #06070a; }

  /* PAINEL INFERIOR (FORMULÁRIOS E RESPOSTAS API) */
  .panel { display: flex; flex-direction: column; gap: 1.5rem; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .card-header { padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.01); }
  .card-title { font-weight: 600; font-size: .88rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; }
  .endpoint-badge { background: var(--bg); border: 1px solid var(--border); border-radius: 4px; padding: 2px 6px; font-family: monospace; font-size: .78rem; color: #94a3b8; }
  .card-body { padding: 1.25rem; }

  /* FORMULÁRIOS */
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0.75rem; }
  .form-group { display: flex; flex-direction: column; gap: .4rem; }
  .form-group.full { grid-column: 1 / -1; margin-bottom: 0.75rem; }
  label { font-size: .72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
  
  input, select, textarea { background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text); padding: .6rem .8rem; font-size: .85rem; outline: none; transition: all .15s; }
  input:focus, select:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.15); }
  textarea { resize: vertical; min-height: 70px; }

  /* BOTÕES */
  .btn { padding: .65rem 1.25rem; border-radius: var(--radius); border: none; cursor: pointer; font-weight: 600; font-size: .85rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: all .15s; }
  .btn:hover { filter: brightness(1.1); }
  .btn-block { width: 100%; justify-content: center; margin-top: 1rem; }
  .btn-primary { background: var(--accent); color: #000; }
  .btn-blue    { background: #1d4ed8; color: #fff; }
  .btn-red     { background: #b91c1c; color: #fff; }

  /* TERMINAL DE CONSOLE */
  .status-badge { padding: 3px 8px; border-radius: 4px; font-size: .72rem; font-weight: 700; font-family: monospace; }
  .status-ok  { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
  .status-err { background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); }
  
  pre { background: #050609; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; overflow-x: auto; font-size: .82rem; line-height: 1.5; max-height: 350px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; font-family: 'Courier New', Courier, monospace; color: #34d399; }
  .placeholder { color: #475569; font-style: italic; font-size: .82rem; text-align: center; padding: 2rem; }
  
  /* LOADING ANIMATIONS */
  .loading { display: flex; align-items: center; justify-content: center; gap: .5rem; color: #64748b; font-size: .82rem; padding: 2rem; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .spinner { width: 16px; height: 16 }