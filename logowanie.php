<?php
// logowanie.php - modalne okno logowania korzystające z ustawień w app_settings.php
require 'polaczenie.php';
session_start();
require_once 'app_settings.php';

function h($v){ return htmlspecialchars($v === null ? '' : $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Jeśli nie ma żadnego admina - przekieruj do ustawienia hasła admin
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM uzytkownicy WHERE rola = 'admin'");
    $row = $stmt->fetch();
    if (!$row || $row['cnt'] == 0) {
        header('Location: ustaw_haslo_admin.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('logowanie.php: błąd sprawdzania adminów: ' . $e->getMessage());
}

$blad = '';
$last_user = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa = trim((string)($_POST['nazwa'] ?? ''));
    $haslo = $_POST['haslo'] ?? '';
    $last_user = $nazwa;

    if (!csrf_verify()) {
        $blad = 'Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.';
    } elseif ($nazwa === '' || $haslo === '') {
        $blad = 'Uzupełnij nazwę użytkownika i hasło.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nazwa_uzytkownika, haslo_hash, rola, wymus_zmiany_hasla FROM uzytkownicy WHERE nazwa_uzytkownika = ? LIMIT 1");
            $stmt->execute([$nazwa]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['haslo_hash']) && password_verify($haslo, $u['haslo_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => (int)$u['id'],
                    'nazwa_uzytkownika' => $u['nazwa_uzytkownika'],
                    'rola' => $u['rola']
                ];

                $_SESSION['user_id'] = (int)$u['id'];
                $_SESSION['nazwa_uzytkownika'] = $u['nazwa_uzytkownika'];
                $_SESSION['rola'] = $u['rola'];
                $_SESSION['wymus_zmiany_hasla'] = !empty($u['wymus_zmiany_hasla']) ? 1 : 0;

                if (!empty($u['wymus_zmiany_hasla'])) {
                    $_SESSION['must_change_password'] = true;
                    header('Location: zmiana_hasla.php');
                    exit;
                }

                header('Location: index.php');
                exit;
            } else {
                $blad = 'Nieprawidłowa nazwa użytkownika lub hasło.';
            }
        } catch (Throwable $e) {
            error_log('logowanie.php: ' . $e->getMessage());
            $blad = 'Błąd serwera przy logowaniu.';
        }
    }
}

// Przygotuj ścieżkę obrazu logowania (jeśli ustawiona)
$loginImageRel = (isset($APP['login_image']) && $APP['login_image']) ? $APP['login_image'] : '';
$loginImageFull = $loginImageRel ? __DIR__ . DIRECTORY_SEPARATOR . $loginImageRel : '';
$loginImageExists = $loginImageFull ? file_exists($loginImageFull) : false;
$loginImageSrc = $loginImageExists ? ($loginImageRel . '?t=' . @filemtime($loginImageFull)) : '';

// Pobierz opis z ustawień (bezpiecznie)
$loginDescription = isset($APP['login_description']) ? (string)$APP['login_description'] : '';
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title><?= h($APP['name']) ?> — Logowanie</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#f3f4f6;
      --panel:#ffffff;
      --muted:#6b7280;
      --accent-2:#111827;
      --error:#b00020;
      --border:#e5e7eb;
    }
    html,body{height:100%;margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg);color:var(--accent-2);}
    .page-center{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;}
    .login-modal { width:100%; max-width:920px; display:flex; gap:0; align-items:stretch; background:var(--panel); border-radius:12px; box-shadow:0 10px 40px rgba(2,6,23,0.12); overflow:hidden; border:1px solid var(--border); }
    .login-left { flex:1 1 420px; padding:28px 26px; background:linear-gradient(180deg,#fafafa,#f7f7f7); display:flex;flex-direction:column; justify-content:center; }
    .login-right { width:420px; max-width:420px; padding:28px 26px; display:flex;flex-direction:column; justify-content:center; border-left:1px solid var(--border); }
    .brand { font-weight:700; font-size:1.1rem; color:var(--accent-2); margin-bottom:8px; }
    .tag { color:var(--muted); font-size:0.95rem; margin-bottom:18px; }
    .illustration { width:100%; height:auto; max-height:260px; display:block; margin:6px 0 14px 0; opacity:0.95; object-fit:contain; }
    .desc { color:var(--muted); font-size:0.9rem; line-height:1.4; }
    form label { display:block; font-size:0.9rem; color:var(--muted); margin-bottom:6px; }
    input[type=text], input[type=password], input[type=email] { width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border); background:#fff; box-sizing:border-box; font-size:0.95rem; color:var(--accent-2); }
    .row { margin-bottom:12px; }
    .btn { display:inline-block; padding:10px 14px; background:#111827; color:#fff; border-radius:8px; border:0; cursor:pointer; font-weight:600; }
    .btn.ghost { background:transparent; color:var(--accent-2); border:1px solid var(--border); }
    .muted { color:var(--muted); font-size:0.9rem; }
    .error { color:var(--error); margin-bottom:10px; font-weight:600; }
    .small { font-size:0.85rem; color:var(--muted); }
    .foot { margin-top:12px; font-size:0.85rem; color:var(--muted); }
    @media (max-width:720px){
      .login-modal{flex-direction:column;max-width:520px;}
      .login-right{width:100%;border-left:none;border-top:1px solid var(--border);}
    }
  </style>
</head>
<body>
  <div class="page-center">
    <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
      <div class="login-left" aria-hidden="false">
        <div>
          <div class="brand"><?= h($APP['name']) ?></div>
          <div class="tag">System zarządzania zasobami — autor: <?= h($APP['author']) ?></div>
        </div>

        <?php if ($loginImageSrc): ?>
          <img class="illustration" src="<?= h($loginImageSrc) ?>" alt="Grafika logowania">
        <?php else: ?>
          <!-- fallback SVG -->
          <svg class="illustration" viewBox="0 0 640 360" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
            <defs>
              <linearGradient id="g1" x1="0" x2="1" y1="0" y2="1">
                <stop offset="0" stop-color="#eef2f7"/>
                <stop offset="1" stop-color="#f8fafc"/>
              </linearGradient>
            </defs>
            <rect width="640" height="360" rx="12" fill="url(#g1)"></rect>
            <g transform="translate(80,40)" fill="none" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="0" y="0" width="480" height="220" rx="8" fill="#fff"/>
              <g transform="translate(18,18)" stroke="#e2e8f0">
                <rect x="0" y="0" width="180" height="48" rx="6" fill="#f8fafc"></rect>
                <rect x="0" y="70" width="420" height="12" rx="6" fill="#f1f5f9"></rect>
                <rect x="0" y="92" width="360" height="12" rx="6" fill="#f1f5f9"></rect>
                <rect x="0" y="114" width="200" height="12" rx="6" fill="#f1f5f9"></rect>
                <circle cx="380" cy="24" r="18" fill="#fff"></circle>
                <path d="M340 170c20-18 48-18 68 0" stroke="#cbd5e1" stroke-width="3" fill="none"></path>
              </g>
            </g>
          </svg>
        <?php endif; ?>

        <div class="desc">
          <?= nl2br(h($loginDescription)) ?>
        </div>
        <div class="foot">Masz problem z dostępem? Skontaktuj się z administratorem.</div>
      </div>

      <div class="login-right">
        <h2 id="loginTitle" style="margin:0 0 10px 0;">Logowanie</h2>

        <?php if ($blad): ?>
          <div class="error" role="alert"><?= h($blad) ?></div>
        <?php endif; ?>

        <form action="logowanie.php" method="post" novalidate>
          <?= csrf_field() ?>
          <div class="row">
            <label for="nazwa">Nazwa użytkownika</label>
            <input id="nazwa" name="nazwa" type="text" value="<?= h($last_user) ?>" autocomplete="username" required>
          </div>

          <div class="row">
            <label for="haslo">Hasło</label>
            <input id="haslo" name="haslo" type="password" autocomplete="current-password" required>
          </div>

          <div style="display:flex;gap:10px;align-items:center;margin-top:6px;">
            <button class="btn" type="submit">Zaloguj</button>
          </div>
        </form>

        <p style="margin-top:12px;" class="muted">Wersja aplikacji: <?= h($APP['version']) ?></p>
      </div>
    </div>
  </div>

<script>
(function(){
  var user = document.getElementById('nazwa');
  if (user) user.focus();
})();
</script>
</body>
</html>