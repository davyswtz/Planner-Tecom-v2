<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Planner Telecom — Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Barlow+Condensed:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    height: 100vh;
    display: flex;
    overflow: hidden;
  }

  /* ── COLUNA ESQUERDA ── */
  .left {
    width: 52%;
    background: #0d5aaa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px;
    position: relative;
    overflow: hidden;
  }

  .left-pattern {
    position: absolute;
    inset: 0;
    background-image:
      radial-gradient(circle at 20% 80%, rgba(255,255,255,0.04) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 50%);
  }

  .left-grid {
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
  }

  .left-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 420px;
  }

  .brand-block {
    margin-bottom: 48px;
  }

  .planner-text {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 64px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.06em;
    line-height: 1;
    margin-bottom: 12px;
  }

  .logo-wrap {
    width: 200px;
    margin: 0 auto;
    opacity: 0.95;
  }

  .logo-wrap svg .fil0, .logo-wrap svg .fil1 { fill: #ffffff !important; }
  .logo-wrap svg .fil2 { fill: rgba(255,255,255,0.7) !important; }
  .logo-wrap svg .fil3 { fill: rgba(255,255,255,0.85) !important; }

  .left-tagline {
    color: rgba(255,255,255,0.55);
    font-size: 14px;
    font-weight: 400;
    margin-top: 40px;
    line-height: 1.6;
  }

  .left-features {
    margin-top: 36px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    text-align: left;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255,255,255,0.75);
    font-size: 13px;
  }

  .feature-icon {
    width: 32px;
    height: 32px;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: rgba(255,255,255,0.8);
    flex-shrink: 0;
  }

  /* ── COLUNA DIREITA ── */
  .right {
    flex: 1;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px;
  }

  .form-box {
    width: 100%;
    max-width: 380px;
  }

  .form-header {
    margin-bottom: 32px;
  }

  .form-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
  }

  .form-sub {
    font-size: 14px;
    color: #64748b;
  }

  .form-group {
    margin-bottom: 16px;
  }

  label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
  }

  .input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    pointer-events: none;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    height: 42px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0 40px 0 38px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  input[type="text"]:focus,
  input[type="password"]:focus {
    border-color: #166ac4;
    box-shadow: 0 0 0 3px rgba(22,106,196,0.12);
  }

  input::placeholder { color: #cbd5e1; }

  .input-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.15s;
  }
  .input-toggle:hover { color: #64748b; }

  .form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    margin-top: 4px;
  }

  .checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
  }

  .checkbox-wrap input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #166ac4;
    cursor: pointer;
  }

  .checkbox-label {
    font-size: 13px;
    color: #64748b;
    cursor: pointer;
  }

  .forgot-link {
    font-size: 13px;
    color: #166ac4;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.15s;
  }
  .forgot-link:hover { color: #0d5aaa; }

  .btn-login {
    width: 100%;
    height: 44px;
    background: #166ac4;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.15s, transform 0.1s;
    letter-spacing: 0.01em;
  }
  .btn-login:hover { background: #0d5aaa; }
  .btn-login:active { transform: scale(0.99); }
  .btn-login i { font-size: 16px; }

  .btn-login.loading { opacity: 0.8; pointer-events: none; }

  .error-msg {
    display: none;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 13px;
    color: #991b1b;
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .error-msg.hidden { display: none; }

  .form-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
    font-size: 12px;
    color: #94a3b8;
  }

  /* responsive */
  @media (max-width: 768px) {
    .left { display: none; }
    .right { padding: 32px 24px; }
  }
</style>
</head>
<body>

<div class="left">
  <div class="left-pattern"></div>
  <div class="left-grid"></div>
  <div class="left-content">
    <div class="brand-block">
      <div class="planner-text">PLANNER</div>
      <div class="logo-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="100%" height="100%" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 2728.77 550.48">
          <defs>
            <style type="text/css">
              .fil3 {fill:#ffffff;opacity:0.85}
              .fil2 {fill:#ffffff;opacity:0.7}
              .fil0 {fill:#ffffff}
              .fil1 {fill:#ffffff;fill-rule:nonzero}
            </style>
          </defs>
          <g id="Camada_x0020_1">
            <g id="_2048750745184">
              <g>
                <g>
                  <path class="fil0" d="M2609.16 537.98l112.98 0.07c1.83,0 3.4,-0.65 4.69,-1.93 1.29,-1.29 1.94,-2.87 1.94,-4.69 0,-152.65 0,-306.74 0,-459.35 0,-18.08 -14.75,-32.84 -32.83,-32.84 -30.36,-0 -60.7,0.02 -91.05,0.04 -3.64,0.01 -6.62,2.98 -6.62,6.62l0 241.79c0,2.96 -1.87,5.48 -4.69,6.34 -2.82,0.86 -5.77,-0.18 -7.42,-2.63l-162.1 -240.51c-5.03,-7.47 -12.77,-11.59 -21.79,-11.59l-136.09 0c-3.64,0 -6.62,2.98 -6.62,6.62l0 485.48c0,3.65 2.98,6.62 6.62,6.62l91.2 0c9.15,0 16.84,-3.21 23.29,-9.7 6.44,-6.48 9.6,-14.21 9.54,-23.35l-1.58 -252.34c-0.02,-2.96 1.83,-5.48 4.67,-6.37 2.84,-0.87 5.79,0.17 7.45,2.64l186.63 277.47c5.03,7.48 12.77,11.61 21.79,11.61l0.01 0 0 -0z"/>
                  <g>
                    <path class="fil1" d="M1880.36 49.76c-98.02,35.43 -161.88,127.21 -162.71,241.81 0,143.09 112.29,258.91 255.3,258.91 143.38,0 256.08,-115.17 256.08,-258.91 -0.82,-112.89 -62.8,-203.65 -158.34,-240.21 2.73,9.07 4.21,18.66 4.21,28.58l0 125.15c15.44,22.42 24.96,51.67 25.33,87.43 2.2,183.41 -255.98,183.41 -253.78,0 0.4,-38.67 11.48,-69.72 29.2,-92.75l0 -119.82c0,-10.51 1.65,-20.66 4.71,-30.2l0 0.01 0 0z"/>
                    <path class="fil2" d="M1975.27 0l0 0c44.16,0 79.97,24.92 79.97,55.65l0.01 194.57c-0.01,30.72 -35.81,55.64 -79.97,55.64l0 0.01c-44.16,-0.01 -79.97,-24.94 -79.97,-55.65l0 -194.57c0,-30.73 35.81,-55.65 79.97,-55.65z"/>
                  </g>
                </g>
                <path class="fil0" d="M954.11 523.1l112.98 0.07c1.83,0 3.4,-0.65 4.69,-1.93 1.29,-1.29 1.94,-2.87 1.94,-4.69 0,-152.65 0,-306.74 0,-459.35 0,-18.08 -14.75,-32.84 -32.83,-32.84 -30.36,-0.01 -60.7,0.02 -91.05,0.04 -3.64,0.01 -6.62,2.98 -6.62,6.62l0 241.79c0,2.96 -1.87,5.47 -4.69,6.33 -2.82,0.86 -5.77,-0.18 -7.42,-2.63l-162.11 -240.51c-5.03,-7.47 -12.77,-11.59 -21.79,-11.59l-136.09 0c-3.64,0 -6.62,2.97 -6.62,6.62l0 485.48c0,3.65 2.98,6.62 6.62,6.62l91.19 0c9.15,0 16.84,-3.2 23.3,-9.7 6.44,-6.48 9.6,-14.21 9.55,-23.35l-1.58 -252.33c-0.02,-2.96 1.83,-5.49 4.67,-6.37 2.84,-0.87 5.79,0.17 7.45,2.64l186.62 277.47c5.03,7.48 12.78,11.61 21.8,11.61l0.01 0 0 -0z"/>
                <path class="fil0" d="M1245.64 516.61c0,-153.05 0,-306.12 0,-459.17 0,-18.16 -14.75,-33 -32.84,-33l-91.05 0c-3.64,0 -6.62,2.98 -6.62,6.62l0 429.04c-0.75,30.13 15.85,62.77 49.35,62.88l74.51 0.26c1.83,0.01 3.4,-0.63 4.69,-1.94 1.3,-1.29 1.95,-2.87 1.95,-4.69l0 0.01z"/>
                <path class="fil1" d="M1712.52 92.48c-36.3,-49.88 -113.25,-73.41 -179.56,-73.41 -162.46,0.96 -254.73,125.94 -255.7,257.62 0,131.69 90.36,254.72 255.7,254.72 72.32,0 129.97,-18.35 183.67,-82.85 2.18,-2.61 2.02,-6.42 -0.35,-8.87l-44.94 -46.09c-13.67,-14.02 -35.13,-15.95 -51.08,-4.57 -28.55,20.35 -59.73,28.5 -89.23,27.02 -67.29,-3.84 -125.93,-58.64 -124.98,-138.42 0.96,-94.22 65.36,-143.24 134.59,-141.3 25.37,0.7 51.79,8.13 75.82,23.03 15.93,9.89 36.2,7.03 48.79,-6.87l46.82 -51.7c2.12,-2.34 2.31,-5.77 0.45,-8.34l-0.01 0.03 0 0z"/>
              </g>
              <g>
                <polygon class="fil0" points="327.73,355.2 491.58,355.2 491.58,520.2 327.73,520.2 "/>
                <polygon class="fil3" points="327.73,190.19 491.58,190.19 491.58,355.2 327.73,355.2 "/>
                <polygon class="fil2" points="327.73,25.16 491.58,25.16 491.58,190.19 327.73,190.19 "/>
                <polygon class="fil3" points="163.85,355.2 327.73,355.2 327.73,520.2 163.85,520.2 "/>
                <polygon class="fil2" points="-0,355.2 163.85,355.2 163.85,520.2 -0,520.2 "/>
                <polygon class="fil2" points="163.85,190.19 327.73,190.19 327.73,355.2 163.85,355.2 "/>
              </g>
            </g>
          </g>
        </svg>
      </div>
    </div>

    <div class="left-tagline">
      Gestão operacional de campo para equipes de telecomunicações
    </div>

    <div class="left-features">
      <div class="feature-item">
        <div class="feature-icon"><i class="ti ti-layout-kanban"></i></div>
        Kanban em tempo real para toda a equipe
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="ti ti-map-pin"></i></div>
        Mapa de calor com localização dos rompimentos
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="ti ti-bell"></i></div>
        Notificações automáticas no Google Chat
      </div>
    </div>
  </div>
</div>

<div class="right">
  <div class="form-box">
    <div class="form-header">
      <div class="form-title">Bem-vindo de volta</div>
      <div class="form-sub">Entre com suas credenciais para acessar o painel</div>
    </div>

    <div class="form-group">
      <label for="username">Usuário</label>
      <div class="input-wrap">
        <i class="ti ti-user input-icon"></i>
        <input type="text" id="username" placeholder="seu.usuario" autocomplete="username">
      </div>
    </div>

    <div class="form-group">
      <label for="password">Senha</label>
      <div class="input-wrap">
        <i class="ti ti-lock input-icon"></i>
        <input type="password" id="password" placeholder="••••••••" autocomplete="current-password">
        <button class="input-toggle" onclick="togglePassword()" type="button" id="toggle-btn">
          <i class="ti ti-eye" id="toggle-icon"></i>
        </button>
      </div>
    </div>

    <div class="form-options">
      <label class="checkbox-wrap">
        <input type="checkbox" id="remember">
        <span class="checkbox-label">Lembrar de mim</span>
      </label>
      <a href="#" class="forgot-link">Esqueci a senha</a>
    </div>

    <button class="btn-login" onclick="handleLogin()" id="login-btn">
      <i class="ti ti-login"></i>
      Entrar
    </button>

    <div class="error-msg hidden" id="error-msg">
      <i class="ti ti-alert-circle"></i>
      <span id="error-text">Usuário ou senha incorretos.</span>
    </div>

    <div class="form-footer">
      Planner Telecom &copy; 2025 &middot; NICON Telecomunicações
    </div>
  </div>
</div>

<script>
  function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggle-icon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'ti ti-eye-off';
    } else {
      input.type = 'password';
      icon.className = 'ti ti-eye';
    }
  }

  async function handleLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const btn = document.getElementById('login-btn');
    const errorMsg = document.getElementById('error-msg');
    const errorText = document.getElementById('error-text');

    errorMsg.classList.add('hidden');

    if (!username || !password) {
      errorText.textContent = 'Preencha usuário e senha.';
      errorMsg.classList.remove('hidden');
      return;
    }

    btn.classList.add('loading');
    btn.innerHTML = '<i class="ti ti-loader"></i> Entrando...';

    try {
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ username, password })
      });

      const data = await response.json();

      if (response.ok && data.token) {
        localStorage.setItem('planner_token', data.token);
        localStorage.setItem('planner_user', JSON.stringify(data.user));
        if (document.getElementById('remember').checked) {
          localStorage.setItem('planner_remember', '1');
        }
        window.location.href = '/dashboard';
      } else {
        errorText.textContent = data.message || 'Usuário ou senha incorretos.';
        errorMsg.classList.remove('hidden');
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="ti ti-login"></i> Entrar';
      }
    } catch (err) {
      errorText.textContent = 'Erro de conexão. Tente novamente.';
      errorMsg.classList.remove('hidden');
      btn.classList.remove('loading');
      btn.innerHTML = '<i class="ti ti-login"></i> Entrar';
    }
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') handleLogin();
  });
</script>
</body>
</html>
