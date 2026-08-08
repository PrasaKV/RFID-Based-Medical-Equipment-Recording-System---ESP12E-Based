<?php
require_once __DIR__ . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE)
  session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once __DIR__ . '/includes/db.php';
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username && $password) {
    $db = getDB();
    if ($db) {
      $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
      $stmt->execute([$username, $username]);
      $user = $stmt->fetch();

      if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: /dashboard.php');
        exit();
      } else {
        $error = 'Invalid username or password.';
      }
    } else {
      $error = 'Database connection failed. Have you run setup/init.php?';
    }
  } else {
    $error = 'Please enter both username and password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Login — MedTrack RFID System</title>
  <meta name="description" content="Login to the MedTrack RFID Equipment Tracking and Preventive Maintenance System" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
    rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #060B18;
      color: #e2e8f0;
    }

    /* Force padding — Tailwind CDN reset override */
    body input[type=text], body input[type=email],
    body input[type=password], body input[type=number] {
      background:#111827 !important;
      border:1px solid rgba(255,255,255,0.12) !important;
      color:#e2e8f0 !important;
      border-radius:0.75rem !important;
      padding:0.75rem 1rem !important;
      width:100% !important;
      font-family:inherit !important;
      font-size:0.9rem !important;
      outline:none !important;
      box-sizing:border-box !important;
      transition:border-color 0.2s, box-shadow 0.2s;
    }
    body input::placeholder { color:#4b5563 !important; }
    body input:focus {
      border-color:#0d9488 !important;
      box-shadow:0 0 0 3px rgba(13,148,136,0.2) !important;
    }
    /* Light mode */
    body.light { background:#f1f5f9; color:#0f172a; }
    body.light body input[type=text], body.light input[type=email],
    body.light input[type=password] {
      background:#fff !important; border-color:#cbd5e1 !important; color:#0f172a !important;
    }
    body.light .slide-up > div:last-child {
      background:rgba(255,255,255,0.95) !important;
      border-color:rgba(0,0,0,0.1) !important;
    }
    body.light h1, body.light h2, body.light p { color:#0f172a; }
    body.light .text-slate-400 { color:#475569; }
    body.light .text-slate-500 { color:#64748b; }
    /* Theme toggle */
    #themeToggle {
      position:fixed; top:1rem; right:1rem;
      width:2.25rem; height:2.25rem; border-radius:50%;
      background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15);
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:all 0.2s; color:#94a3b8; z-index:100;
    }
    #themeToggle:hover { background:rgba(255,255,255,0.15); color:#e2e8f0; }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .slide-up {
      animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    .float {
      animation: float 4s ease-in-out infinite;
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 overflow-hidden" id="loginBody">

<!-- Theme toggle -->
<button id="themeToggle" onclick="toggleTheme()" title="Toggle light/dark theme">
  <svg id="moonIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
  </svg>
  <svg id="sunIcon" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
  </svg>
</button>

  <!-- Ambient glow -->
  <div class="fixed inset-0 pointer-events-none overflow-hidden">
    <div class="absolute top-[-200px] left-[-200px] w-[700px] h-[700px] rounded-full bg-teal-700/15 blur-[150px]"></div>
    <div class="absolute bottom-[-100px] right-[-100px] w-[500px] h-[500px] rounded-full bg-teal-900/20 blur-[120px]">
    </div>
  </div>

  <div class="relative w-full max-w-sm slide-up">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div
        class="float inline-flex w-16 h-16 rounded-2xl bg-teal-600/20 border border-teal-500/30 items-center justify-center mb-4">
        <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M8.25 3.75H5.625a1.875 1.875 0 0 0-1.875 1.875v3.375M8.25 20.25H5.625a1.875 1.875 0 0 1-1.875-1.875v-3.375M15.75 3.75h2.625a1.875 1.875 0 0 1 1.875 1.875v3.375M15.75 20.25h2.625a1.875 1.875 0 0 0 1.875-1.875v-3.375M9 9h6M9 12h6M9 15h6" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white tracking-tight">MedTrack</h1>
      <p class="text-slate-400 text-sm mt-1">RFID Equipment Management System</p>
    </div>

    <!-- Card -->
    <div
      style="background:rgba(13,19,37,0.9); border:1px solid rgba(255,255,255,0.08); border-radius:1.5rem; padding:2rem; backdrop-filter:blur(20px);">
      <h2 class="text-base font-semibold text-white mb-6">Sign in to your account</h2>

      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-xl text-sm"
          style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#fca5a5;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-xs text-slate-500 uppercase tracking-widest mb-2 font-medium">Username or
            Email</label>
          <input type="text" name="username" id="username" autocomplete="username" placeholder="username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        </div>
        <div>
          <label class="block text-xs text-slate-500 uppercase tracking-widest mb-2 font-medium">Password</label>
          <input type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••"
            required />
        </div>
        <button type="submit" id="loginBtn" style="width:100%; padding:0.8rem; border-radius:0.875rem; background:#0d9488;
                 color:#fff; font-weight:600; font-size:0.9rem; border:none; cursor:pointer;
                 transition:all 0.2s; margin-top:0.5rem;" onmouseover="this.style.background='#0f766e'"
          onmouseout="this.style.background='#0d9488'" onmousedown="this.style.transform='scale(0.98)'"
          onmouseup="this.style.transform='scale(1)'">
          Sign In
        </button>
      </form>

      <div class="mt-5 pt-4 border-t" style="border-color:rgba(255,255,255,0.06)">
        <p class="text-xs text-slate-600 text-center">Default admin: <span class="font-mono text-slate-500">admin /
            admin123</span></p>
      </div>
    </div>
  </div>
<script>
function toggleTheme() {
  const body = document.getElementById('loginBody');
  const isDark = !body.classList.contains('light');
  body.classList.toggle('light', isDark);
  document.getElementById('moonIcon').classList.toggle('hidden', isDark);
  document.getElementById('sunIcon').classList.toggle('hidden', !isDark);
  localStorage.setItem('theme', isDark ? 'light' : 'dark');
}
// Restore saved theme
if (localStorage.getItem('theme') === 'light') {
  document.getElementById('loginBody').classList.add('light');
  document.getElementById('moonIcon').classList.add('hidden');
  document.getElementById('sunIcon').classList.remove('hidden');
}
</script>
</body>
</html>