<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT ID, NOME, SENHA FROM USUARIOS WHERE EMAIL = ? AND SIT = 'A' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        

        if ( password_verify($senha, $user['SENHA'])) {
            echo 'Verify';
        } else {
            echo 'Não';
        }

        if ($user && password_verify($senha, $user['SENHA'])) {
            $_SESSION['user_id']   = $user['ID'];
            $_SESSION['user_nome'] = $user['NOME'];
            header('Location: index.php');
            exit;
        }
    }
    $erro = 'E-mail ou senha inválidos.';
}

if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LEF · Entrar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0f14;
  --surface:#16191f;
  --border:#2a2d35;
  --accent:#c8f060;
  --accent2:#60d4f0;
  --text:#f0f2f5;
  --muted:#7a7f8e;
  --danger:#f05060;
}
body{min-height:100vh;background:var(--bg);font-family:'DM Sans',sans-serif;color:var(--text);display:flex;align-items:center;justify-content:center;overflow:hidden}
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:48px 48px;opacity:.35;pointer-events:none}
.glow{position:fixed;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(200,240,96,.07) 0%,transparent 70%);top:-200px;left:50%;transform:translateX(-50%);pointer-events:none}
.card{position:relative;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:3rem 2.5rem;width:100%;max-width:420px;animation:fadeUp .5s ease both}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo{display:flex;align-items:baseline;gap:8px;margin-bottom:2.5rem}
.logo-mark{font-family:'Syne',sans-serif;font-weight:800;font-size:2.2rem;color:var(--accent);letter-spacing:-1px;line-height:1}
.logo-sub{font-size:.75rem;color:var(--muted);letter-spacing:.12em;text-transform:uppercase}
h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:.4rem}
.sub{font-size:.875rem;color:var(--muted);margin-bottom:2rem}
.field{margin-bottom:1.25rem}
label{display:block;font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem}
input{width:100%;background:#1e2128;border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;color:var(--text);font-family:inherit;font-size:.95rem;transition:border-color .2s}
input:focus{outline:none;border-color:var(--accent)}
.btn{width:100%;background:var(--accent);color:#0d0f14;border:none;border-radius:10px;padding:.85rem;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;letter-spacing:.02em;transition:opacity .2s,transform .1s;margin-top:.5rem}
.btn:hover{opacity:.9}
.btn:active{transform:scale(.98)}
.erro{background:rgba(240,80,96,.12);border:1px solid rgba(240,80,96,.3);border-radius:10px;padding:.75rem 1rem;font-size:.85rem;color:var(--danger);margin-bottom:1.25rem}
.demo-hint{margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);font-size:.8rem;color:var(--muted);text-align:center}
.demo-hint code{color:var(--accent2);font-size:.8rem}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="glow"></div>

<div class="card">
  <div class="logo">
    <span class="logo-mark">LEF</span>
    <span class="logo-sub">Gestão Financeira</span>
  </div>
  <h1>Bem-vindo de volta</h1>
  <p class="sub">Acesse sua conta para continuar</p>

  <?php if ($erro): ?>
    <div class="erro"><?= sanitize($erro) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" placeholder="seu@email.com" required value="<?= sanitize($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" placeholder="••••••••" required>
    </div>
    <button class="btn" type="submit">Entrar →</button>
  </form>

  <div class="demo-hint">
    Demo: <code>demo@lef.com</code> · senha <code>password</code>
  </div>
</div>
</body>
</html>
