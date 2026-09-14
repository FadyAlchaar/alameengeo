<?php
require_once __DIR__ . '/../app/Auth.php';

// Already logged in? Go home.
if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error    = '';
$lockLeft = Auth::remainingLockSeconds();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockLeft === 0) {
    $pass = (string) ($_POST['password'] ?? '');
    if ($pass === '') {
        $error = 'Please enter the password.';
    } elseif (Auth::attempt($pass)) {
        $r = $_POST['r'] ?? 'index.php';
        // Prevent open redirect
        if (!preg_match('/^[A-Za-z0-9_\-\.\/\?=&%]+$/', $r) || strpos($r, '//') !== false) {
            $r = 'index.php';
        }
        header('Location: ' . $r);
        exit;
    } else {
        if (Auth::remainingLockSeconds() > 0) {
            $lockLeft = Auth::remainingLockSeconds();
        } else {
            $left = 5 - Auth::failedAttempts();
            $error = "Incorrect password. {$left} attempt" . ($left === 1 ? '' : 's') . " remaining.";
        }
    }
}

$lang = $_COOKIE['alameen_lang'] ?? 'ar';
$isAr = $lang === 'ar';
$t = function($en, $ar) use ($isAr) { return $isAr ? $ar : $en; };
$r = htmlspecialchars($_GET['r'] ?? ($_POST['r'] ?? 'index.php'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'en' ?>" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= $t('Sign in', 'تسجيل الدخول') ?> — Alameen Tracker</title>

<script>
(function(){
  try {
    var t = localStorage.getItem('alameen_theme');
    document.documentElement.setAttribute('data-theme', (t === 'light' || t === 'dark') ? t : 'dark');
  } catch(e) { document.documentElement.setAttribute('data-theme', 'dark'); }
})();
</script>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0f172a; --panel:#1e293b; --input:#0b1220;
    --border:#334155; --text:#e2e8f0; --text-dim:#94a3b8;
    --accent:#3b82f6; --accent-hover:#2563eb;
    --danger:#ef4444; --warning:#f59e0b;
  }
  html[data-theme="light"] {
    --bg:#f1f5f9; --panel:#ffffff; --input:#f8fafc;
    --border:#cbd5e1; --text:#0f172a; --text-dim:#64748b;
    --accent:#2563eb; --accent-hover:#1d4ed8;
    --danger:#dc2626; --warning:#d97706;
  }
  * { box-sizing: border-box; }
  html, body {
    margin:0; min-height:100%;
    font-family:'Cairo', system-ui, -apple-system, sans-serif;
    background: var(--bg); color: var(--text);
  }
  body {
    display:flex; align-items:center; justify-content:center;
    padding: 20px;
    min-height: 100vh;
    background:
      radial-gradient(circle at 20% 30%, rgba(59,130,246,.15), transparent 45%),
      radial-gradient(circle at 80% 70%, rgba(139,92,246,.12), transparent 45%),
      var(--bg);
  }
  .card {
    width: 100%;
    max-width: 420px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 36px 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
  }
  .logo {
    width: 60px; height: 60px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--accent), #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: 30px;
    margin: 0 auto 20px;
    box-shadow: 0 10px 30px rgba(59,130,246,.35);
  }
  h1 {
    font-size: 22px;
    font-weight: 800;
    text-align: center;
    margin: 0 0 6px;
    color: var(--text);
  }
  .sub {
    text-align: center;
    color: var(--text-dim);
    font-size: 13px;
    margin-bottom: 26px;
  }
  label {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--text-dim);
    font-weight: 700;
    margin-bottom: 8px;
  }
  input[type="password"] {
    width: 100%;
    padding: 13px 16px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--input);
    color: var(--text);
    font-family: inherit;
    font-size: 15px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
  }
  input[type="password"]:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(59,130,246,.15);
  }
  button {
    width: 100%;
    margin-top: 18px;
    padding: 14px;
    border-radius: 10px;
    border: none;
    background: var(--accent);
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
  }
  button:hover:not(:disabled) { background: var(--accent-hover); }
  button:disabled { opacity: .55; cursor: not-allowed; }

  .msg {
    padding: 11px 14px;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 18px;
    line-height: 1.55;
    display: flex; align-items: flex-start; gap: 9px;
  }
  .msg.error { background: rgba(239,68,68,.12); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
  .msg.lock  { background: rgba(245,158,11,.12); color: var(--warning); border: 1px solid rgba(245,158,11,.3); }
  .msg .icon { font-size: 16px; line-height: 1.2; flex-shrink: 0; }

  .lang-btn {
    position: fixed;
    top: calc(16px + env(safe-area-inset-top, 0px));
    inset-inline-end: 16px;
    min-width: 52px;
    height: 38px;
    padding: 0 12px;
    background: var(--panel);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 10px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .15s, border-color .15s;
  }
  .lang-btn:hover { border-color: var(--accent); }

  .footer {
    text-align: center;
    margin-top: 22px;
    font-size: 11.5px;
    color: var(--text-dim);
  }
</style>
</head>
<body>

<button class="lang-btn" id="langBtn"><?= $isAr ? 'EN' : 'ع' ?></button>

<form class="card" method="post" autocomplete="off">
  <div class="logo">🔐</div>
  <h1><?= $t('Sign in', 'تسجيل الدخول') ?></h1>
  <div class="sub"><?= $t('Alameen Distributor Tracker', ' مسارات الموزعين') ?></div>

  <?php if ($lockLeft > 0): ?>
    <div class="msg lock">
      <span class="icon">🔒</span>
      <div>
        <?= $t('Too many failed attempts. Try again in', 'محاولات فاشلة كثيرة. أعد المحاولة بعد') ?>
        <b id="lockTimer"><?= (int) ceil($lockLeft / 60) ?></b>
        <?= $t('minute(s).', 'دقيقة.') ?>
      </div>
    </div>
  <?php elseif ($error): ?>
    <div class="msg error">
      <span class="icon">⚠️</span>
      <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  <?php endif; ?>

  <input type="hidden" name="r" value="<?= $r ?>">

  <label for="password"><?= $t('Password', 'كلمة المرور') ?></label>
  <input type="password" id="password" name="password" autocomplete="current-password" autofocus
         <?= $lockLeft > 0 ? 'disabled' : '' ?> required>

  <button type="submit" <?= $lockLeft > 0 ? 'disabled' : '' ?>>
    <?= $t('Unlock', 'دخول') ?>
  </button>

  <div class="footer">
    <?= $t('Protected area — authorized users only.', 'منطقة محمية — للمصرّح لهم فقط.') ?>
  </div>
</form>

<script>
document.getElementById('langBtn').addEventListener('click', () => {
  const cur = document.documentElement.lang || 'ar';
  const next = cur === 'ar' ? 'en' : 'ar';
  try { document.cookie = 'alameen_lang=' + next + ';path=/;max-age=31536000;SameSite=Lax'; } catch(e) {}
  location.reload();
});

// Lock countdown
const lockEl = document.getElementById('lockTimer');
if (lockEl) {
  let m = parseInt(lockEl.textContent, 10) || 15;
  let sec = m * 60;
  setInterval(() => {
    sec--;
    if (sec <= 0) { location.reload(); return; }
    lockEl.textContent = Math.ceil(sec / 60);
  }, 1000);
}
</script>
</body>
</html>