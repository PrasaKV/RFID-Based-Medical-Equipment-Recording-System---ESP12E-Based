<?php
require_once __DIR__ . '/auth.php';
$user = currentUser();
$page = basename($_SERVER['PHP_SELF'], '.php');

// Detect active nav item
function isActive(string $p): string {
    global $page;
    return $page === $p ? 'bg-teal-600/20 text-teal-300 border-r-2 border-teal-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'RFID System') ?> — MedTrack</title>
  <meta name="description" content="RFID-Based Equipment Tracking and Preventive Maintenance Management System"/>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet"/>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter','sans-serif'],
            mono: ['JetBrains Mono','monospace'],
          },
          colors: {
            brand: {
              50:'#f0fdf9',100:'#ccfbef',200:'#99f6e0',300:'#5eead4',
              400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',
              800:'#115e59',900:'#134e4a',
            },
          },
        },
      },
    };
  </script>

  <style>
    body { font-family:'Inter',sans-serif; background:#060B18; color:#e2e8f0; }
    ::-webkit-scrollbar { width:5px; height:5px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#0d9488; border-radius:99px; }
    .glass {
      background: rgba(255,255,255,0.04);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.07);
    }
    @keyframes slideUp {
      from { opacity:0; transform:translateY(16px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .slide-up { animation: slideUp 0.35s cubic-bezier(0.16,1,0.3,1) forwards; }
    @keyframes fadeIn {
      from { opacity:0; } to { opacity:1; }
    }
    .fade-in { animation: fadeIn 0.25s ease-out forwards; }
    @keyframes livePulse {
      0%,100% { transform:scale(1); opacity:1; }
      50%      { transform:scale(1.7); opacity:0.3; }
    }
    .live-dot { animation: livePulse 1.4s ease-in-out infinite; }
    .modal-overlay {
      position:fixed; inset:0; background:rgba(0,0,0,0.7);
      backdrop-filter:blur(4px); z-index:50;
      display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .modal-box {
      background:#0d1325; border:1px solid rgba(255,255,255,0.1);
      border-radius:1.25rem; padding:2rem; width:100%; max-width:540px;
      max-height:90vh; overflow-y:auto;
    }
    .btn-primary {
      background:#0d9488; color:#fff; font-weight:600;
      padding:0.6rem 1.4rem; border-radius:0.75rem;
      border:none; cursor:pointer; transition:all 0.2s;
    }
    .btn-primary:hover { background:#0f766e; transform:translateY(-1px); }
    .btn-secondary {
      background:rgba(255,255,255,0.06); color:#94a3b8; font-weight:500;
      padding:0.6rem 1.4rem; border-radius:0.75rem;
      border:1px solid rgba(255,255,255,0.1); cursor:pointer; transition:all 0.2s;
    }
    .btn-secondary:hover { background:rgba(255,255,255,0.1); color:#e2e8f0; }
    /* ── Input & form elements — !important overrides Tailwind CDN reset ──── */
    input[type=text],input[type=email],input[type=password],input[type=date],
    input[type=number],select,textarea {
      background:#111827 !important; border:1px solid rgba(255,255,255,0.1) !important;
      color:#e2e8f0 !important; border-radius:0.625rem !important; padding:0.55rem 0.85rem !important;
      width:100% !important; font-size:0.875rem !important; font-family:inherit !important; outline:none !important;
      transition:border-color 0.2s;
    }
    select.w-auto, input.w-auto, button.w-auto { width:auto !important; }
    input::placeholder, textarea::placeholder { color:#4b5563 !important; }
    input:focus,select:focus,textarea:focus {
      border-color:#0d9488 !important; box-shadow:0 0 0 3px rgba(13,148,136,0.15) !important;
    }
    label { display:block; font-size:0.7rem; color:#64748b;
            text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .form-group { margin-bottom:1rem; }
    .badge {
      display:inline-flex; align-items:center; gap:0.35rem;
      padding:0.2rem 0.65rem; border-radius:9999px;
      font-size:0.7rem; font-weight:600; letter-spacing:0.05em;
      text-transform:uppercase; white-space:nowrap;
    }
    .badge-operational  { background:rgba(34,197,94,0.15);  color:#86efac; }
    .badge-pm-due       { background:rgba(251,191,36,0.15); color:#fde68a; }
    .badge-in-repair    { background:rgba(59,130,246,0.15); color:#93c5fd; }
    .badge-needs-attention { background:rgba(249,115,22,0.15); color:#fdba74; }
    .badge-decommissioned  { background:rgba(100,116,139,0.15); color:#94a3b8; }
    .badge-returned     { background:rgba(34,197,94,0.15);  color:#86efac; }

    /* ── Theme toggle button ──────────────────────────────────────────────── */
    #themeToggle {
      width:2.1rem; height:2.1rem; border-radius:0.6rem;
      background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:all 0.2s; color:#64748b; flex-shrink:0;
    }
    #themeToggle:hover { background:rgba(255,255,255,0.12); color:#e2e8f0; }

    /* ══════════════════════════════════════════
       LIGHT MODE  (html.light)
       ══════════════════════════════════════════ */
    html.light body { background:#f0f4f8; color:#0f172a; }

    /* Sidebar */
    html.light aside { background:#fff !important; border-color:#e2e8f0 !important; }
    html.light aside p, html.light aside a { color:#374151; }
    html.light aside a:hover { background:rgba(13,148,136,0.08) !important; color:#0f172a; }
    html.light aside .text-slate-500 { color:#6b7280; }
    html.light aside .border-b, html.light aside .border-t { border-color:#e5e7eb !important; }
    html.light aside p.font-bold { color:#111827; }
    html.light aside .text-white { color:#111827; }

    /* Top bar */
    html.light header { background:rgba(240,244,248,0.92) !important; border-color:#e2e8f0 !important; }
    html.light header h2 { color:#111827; }
    html.light header .text-slate-500, html.light header .text-xs { color:#6b7280; }

    /* Ambient glows hidden in light mode */
    html.light .blur-\[120px\], html.light .blur-\[100px\] { opacity:0.3; }

    /* Cards / glass */
    html.light .glass { background:rgba(255,255,255,0.85) !important; border-color:#e2e8f0 !important; }
    html.light .glass p, html.light .glass span, html.light .glass a { color:#374151; }
    html.light .glass .text-white { color:#111827 !important; }
    html.light .glass .text-slate-400 { color:#6b7280 !important; }
    html.light .glass .text-slate-500 { color:#9ca3af !important; }
    html.light .glass .text-slate-600 { color:#b0b8c4 !important; }
    html.light .glass .text-gray-400, html.light .glass .text-gray-500 { color:#6b7280 !important; }

    /* Tables */
    html.light table { color:#111827; }
    html.light thead tr { background:rgba(0,0,0,0.03) !important; border-color:#e5e7eb !important; }
    html.light tbody tr { border-color:#f3f4f6 !important; }
    html.light tbody tr:hover { background:rgba(0,0,0,0.02) !important; }
    html.light th { color:#6b7280 !important; }
    html.light td { color:#374151; }
    html.light td .text-white { color:#111827 !important; }
    html.light td .text-slate-400, html.light td .text-slate-500 { color:#6b7280 !important; }

    /* Modals */
    html.light .modal-box { background:#fff !important; border-color:#e5e7eb !important; }
    html.light .modal-box h3 { color:#111827 !important; }
    html.light .modal-box label { color:#6b7280; }
    html.light .modal-box p { color:#374151; }
    html.light input[type=text], html.light input[type=email],
    html.light input[type=password], html.light input[type=date],
    html.light input[type=number], html.light select, html.light textarea {
      background:#f9fafb !important; border-color:#d1d5db !important; color:#111827 !important;
    }
    html.light input::placeholder, html.light textarea::placeholder { color:#9ca3af !important; }

    /* Scan popup */
    html.light #scanPopup { background:rgba(255,255,255,0.95) !important; border-color:#e2e8f0 !important; }
    html.light #scanPopup p { color:#111827; }

    /* Buttons */
    html.light .btn-secondary {
      background:rgba(0,0,0,0.05) !important; color:#374151 !important;
      border-color:#d1d5db !important;
    }
    html.light .btn-secondary:hover { background:rgba(0,0,0,0.08) !important; color:#111827 !important; }

    /* Misc text */
    html.light .text-white { color:#111827 !important; }
    html.light .text-slate-200 { color:#374151 !important; }
    html.light .text-slate-300 { color:#4b5563 !important; }
    html.light .text-slate-400 { color:#6b7280 !important; }
    html.light .text-slate-500 { color:#9ca3af !important; }
    html.light .text-slate-600 { color:#b0b8c4 !important; }
    html.light .bg-slate-800 { background:#e5e7eb !important; }
    html.light .bg-slate-900 { background:#f3f4f6 !important; }
    html.light .bg-gray-950, html.light .bg-gray-900 { background:#f0f4f8 !important; }
    html.light .border-white\/5 { border-color:#e5e7eb !important; }
    html.light .border-white\/7 { border-color:#e5e7eb !important; }
    html.light #themeToggle { background:rgba(0,0,0,0.06) !important; border-color:#d1d5db !important; color:#374151; }
    html.light #themeToggle:hover { background:rgba(0,0,0,0.1) !important; color:#111827; }

    /* ── Collapsible Sidebar ──────────────────────────────────────────────── */
    #appSidebar {
      transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    #mainWrapper {
      transition: margin-left 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    #appSidebar.collapsed {
      width: 4.5rem !important;
    }
    #mainWrapper.collapsed {
      margin-left: 4.5rem !important;
    }
    #appSidebar.collapsed .sidebar-label {
      display: none !important;
    }
    #appSidebar.collapsed nav a {
      justify-content: center;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }
    #appSidebar.collapsed .sidebar-user-box {
      justify-content: center;
      padding-left: 0;
      padding-right: 0;
    }
    #appSidebar.collapsed .sidebar-logo-box {
      justify-content: center;
      padding-left: 0.75rem;
      padding-right: 0.75rem;
    }
  </style>
</head>
<body class="min-h-screen flex">

<!-- ── Ambient glow ─────────────────────────────────────── -->
<div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
  <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-teal-600/10 blur-[120px]"></div>
  <div class="absolute bottom-0 right-0 w-[400px] h-[400px] rounded-full bg-teal-900/10 blur-[100px]"></div>
</div>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<aside id="appSidebar" class="fixed top-0 left-0 h-full w-60 flex flex-col z-20"
       style="background:#0a0f1e; border-right:1px solid rgba(255,255,255,0.06);">

  <!-- Logo -->
  <div class="px-5 py-5 border-b sidebar-logo-box" style="border-color:rgba(255,255,255,0.06)">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-teal-600/25 border border-teal-500/40 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M8.25 3.75H5.625a1.875 1.875 0 0 0-1.875 1.875v3.375M8.25 20.25H5.625a1.875 1.875 0 0 1-1.875-1.875v-3.375M15.75 3.75h2.625a1.875 1.875 0 0 1 1.875 1.875v3.375M15.75 20.25h2.625a1.875 1.875 0 0 0 1.875-1.875v-3.375M9 9h6M9 12h6M9 15h6"/>
        </svg>
      </div>
      <div class="sidebar-label">
        <p class="font-bold text-white text-sm leading-none">MedTrack</p>
        <p class="text-xs text-slate-500 mt-0.5">RFID Asset System</p>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 py-4 px-2 space-y-0.5 overflow-y-auto">
    <a href="/dashboard.php" title="Dashboard"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 <?= isActive('dashboard') ?>">
      <svg class="w-4.5 h-4.5 flex-shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
      </svg>
      <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="/inventory.php" title="Inventory"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 <?= isActive('inventory') ?>">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
      </svg>
      <span class="sidebar-label">Inventory</span>
    </a>
    <a href="/repairs.php" title="Repairs & Parts"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 <?= isActive('repairs') ?>">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
      </svg>
      <span class="sidebar-label">Repairs & Parts</span>
    </a>
    <?php if (isAdmin()): ?>
    <a href="/admin/users.php" title="Users"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 <?= isActive('users') ?>">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
      </svg>
      <span class="sidebar-label flex items-center justify-between w-full">
        Users
        <span class="ml-auto text-xs bg-teal-900/60 text-teal-400 px-1.5 py-0.5 rounded-md">Admin</span>
      </span>
    </a>
    <a href="/admin/departments.php" title="Departments & Categories"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 <?= isActive('departments') ?>">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5s1.5 0 1.5 1.5v1.5c0 1.5-1.5 1.5-1.5 1.5H9m0-4.5H7.5m1.5 0v4.5m0-4.5h4.5m-4.5 4.5h4.5"/>
      </svg>
      <span class="sidebar-label flex items-center justify-between w-full">
        Depts & Categories
        <span class="ml-auto text-xs bg-teal-900/60 text-teal-400 px-1.5 py-0.5 rounded-md">Admin</span>
      </span>
    </a>
    <?php endif; ?>

    <div class="pt-3 pb-1 px-3 sidebar-label">
      <p class="text-xs text-slate-700 uppercase tracking-widest font-medium">Live Monitor</p>
    </div>
    <div class="flex items-center gap-2 px-3 py-2" title="Live status polling">
      <span class="live-dot w-2 h-2 rounded-full bg-teal-400 flex-shrink-0"></span>
      <span class="text-xs text-slate-500 sidebar-label" id="sidebarPollStatus">Polling…</span>
    </div>
  </nav>

  <!-- User section -->
  <div class="px-3 py-4 border-t" style="border-color:rgba(255,255,255,0.06)">
    <div class="flex items-center gap-3 mb-3 px-2 sidebar-user-box">
      <div class="w-8 h-8 rounded-full bg-teal-700/40 border border-teal-600/30 flex items-center justify-center flex-shrink-0 text-teal-300 font-bold text-sm" title="<?= htmlspecialchars($user['username']) ?>">
        <?= strtoupper(substr($user['username'], 0, 1)) ?>
      </div>
      <div class="min-w-0 sidebar-label">
        <p class="text-sm font-medium text-slate-200 truncate"><?= htmlspecialchars($user['username']) ?></p>
        <p class="text-xs text-slate-500 capitalize"><?= htmlspecialchars($user['role']) ?></p>
      </div>
    </div>
    <a href="/logout.php" title="Logout"
       class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-all duration-150">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
      </svg>
      <span class="sidebar-label">Logout</span>
    </a>
  </div>
</aside>

<!-- ── Main content wrapper ──────────────────────────────── -->
<div id="mainWrapper" class="flex-1 ml-60 min-h-screen relative z-10">
  <!-- Top bar -->
  <header class="sticky top-0 z-10 flex items-center justify-between px-6 py-4"
          style="background:rgba(6,11,24,0.85); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.05);">
    <div class="flex items-center gap-3">
      <button onclick="toggleSidebar()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-colors" title="Toggle sidebar">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
      </button>
      <h2 class="text-lg font-semibold text-white"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-xs text-slate-500" id="topbarTime"></span>
      <!-- Manual Scan Button -->
      <button onclick="openManualScanModal()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-600/15 hover:bg-teal-600/25 text-teal-300 border border-teal-500/30 transition-colors" title="Manually look up a machine by tag ID or serial number">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
        </svg>
        Manual Scan
      </button>
      <?php if (!empty($pageActions)) echo $pageActions; ?>
      <!-- Theme toggle -->
      <button id="themeToggle" onclick="toggleTheme()" title="Toggle light/dark theme">
        <svg id="themeIconMoon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>
        <svg id="themeIconSun" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
        </svg>
      </button>
    </div>
  </header>

  <!-- Page content starts here -->
  <main class="p-6">

<!-- ════════════════════════════════════════════════════════
     GLOBAL REAL-TIME POPUP SYSTEM
     ════════════════════════════════════════════════════════ -->

<!-- Scan popup (bottom-right toast) — context-aware buttons based on status -->
<div id="scanPopup" class="fixed bottom-6 right-6 z-50 w-96 rounded-2xl glass border border-teal-500/20 p-5 shadow-2xl
     transform translate-y-20 opacity-0 pointer-events-none transition-all duration-400" style="transition:all 0.4s cubic-bezier(0.16,1,0.3,1)">
  <div class="flex items-start gap-3">
    <div id="popupIconBox" class="w-9 h-9 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center flex-shrink-0 mt-0.5">
      <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
      </svg>
    </div>
    <div class="flex-1 min-w-0">
      <p id="popupLabel" class="text-xs text-teal-400 font-semibold uppercase tracking-wider mb-0.5">RFID Scan Detected</p>
      <p id="popupDeviceName" class="text-sm font-bold text-white truncate">—</p>
      <p id="popupDeviceSerial" class="text-xs font-mono text-teal-300 mt-0.5">—</p>
      <p id="popupDeviceLocation" class="text-xs text-teal-200 mt-0.5 flex items-center gap-1 font-medium"></p>
      <p id="popupDeviceStatus" class="text-xs text-slate-400 mt-0.5">—</p>
    </div>
    <button onclick="closeScanPopup()" class="text-slate-600 hover:text-slate-300 flex-shrink-0 transition-colors">✕</button>
  </div>
  <div id="popupButtons" class="mt-3 flex gap-2">
    <!-- Dynamic buttons injected by JS -->
  </div>
</div>

<!-- Register Equipment Modal (IoT triggered) -->
<div id="registerModal" class="modal-overlay hidden" style="z-index:60">
  <div class="modal-box slide-up">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Register New Equipment</h3>
        <p class="text-xs text-slate-400">New RFID tag detected — fill in device details</p>
      </div>
    </div>
    <form id="registerForm" onsubmit="submitRegisterForm(event)">
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>RFID Tag ID</label>
          <input type="text" id="reg_rfid_tag_id" name="rfid_tag_id" readonly
                 class="font-mono bg-slate-900/80 text-teal-300 cursor-not-allowed"/>
        </div>
        <div class="form-group col-span-2">
          <label>Device Name *</label>
          <input type="text" id="reg_name" name="name" required placeholder="e.g. Ventilator Pro X200"/>
        </div>
        <div class="form-group">
          <label>Serial Number</label>
          <input type="text" id="reg_serial" name="serial_number" placeholder="SN-000-000"/>
        </div>
        <div class="form-group">
          <label>Model</label>
          <input type="text" id="reg_model" name="model" placeholder="Model number"/>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select id="reg_category" name="category">
            <option value="Imaging">Imaging</option>
            <option value="Life Support">Life Support</option>
            <option value="Monitoring">Monitoring</option>
            <option value="Lab">Lab</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Department / Location</label>
          <select id="reg_location" name="current_location">
            <option value="">Select Department…</option>
          </select>
        </div>
        <div class="form-group">
          <label>PM Interval (days)</label>
          <input type="number" id="reg_pm" name="pm_interval_days" value="90" min="1"/>
        </div>
        <div class="form-group">
          <label>Procurement Date</label>
          <input type="date" id="reg_procurement" name="procurement_date"/>
        </div>
        <div class="form-group col-span-2">
          <label>Description</label>
          <textarea id="reg_description" name="description" rows="2" placeholder="Brief description of the machine…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="registerError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Save Equipment</button>
        <button type="button" onclick="closeRegisterModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Manual Scan Modal -->
<div id="manualScanModal" class="modal-overlay hidden" style="z-index:60">
  <div class="modal-box slide-up" style="max-width:440px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Manual Machine Lookup</h3>
        <p class="text-xs text-slate-400">Enter RFID Tag ID or Serial Number to look up a machine</p>
      </div>
    </div>
    <div class="form-group">
      <label>RFID Tag ID or Serial Number</label>
      <input type="text" id="manualScanInput" placeholder="e.g. TAG-001 or VNT-2023-001" autofocus/>
    </div>
    <div id="manualScanError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
    <div id="manualScanResult" class="hidden mb-3"></div>
    <div class="flex gap-3">
      <button onclick="performManualScan()" class="btn-primary flex-1">Look Up</button>
      <button onclick="closeManualScanModal()" class="btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Put to Repair Modal (triggered from scan popup) -->
<div id="putToRepairModal" class="modal-overlay hidden" style="z-index:65">
  <div class="modal-box slide-up" style="max-width:540px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Put Machine to Repair</h3>
        <p class="text-xs text-slate-400">Record intake details for this machine</p>
      </div>
    </div>
    <div class="glass p-3 rounded-xl border border-white/5 mb-4 space-y-1 text-xs">
      <p class="text-slate-400">Machine: <span id="ptr_machine_name" class="font-bold text-white">—</span></p>
      <p class="text-slate-400">Serial NO: <span id="ptr_serial_number" class="font-mono text-teal-300">—</span></p>
    </div>
    <form id="putToRepairForm" onsubmit="submitPutToRepair(event)">
      <input type="hidden" id="ptr_equipment_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Reported Issue / Problem *</label>
          <textarea id="ptr_problem" rows="3" required placeholder="Describe the problem or fault in detail…" style="resize:vertical"></textarea>
        </div>
        <div class="form-group">
          <label title="Person Who Brought Machine">Brought By *</label>
          <input type="text" id="ptr_brought_by" required placeholder="Full Name of person bringing machine"/>
        </div>
        <div class="form-group">
          <label>Department *</label>
          <select id="ptr_department" required>
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Date & Time</label>
          <input type="text" id="ptr_datetime" readonly class="cursor-not-allowed"/>
        </div>
      </div>
      <div id="ptrError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Confirm — Put to Repair</button>
        <button type="button" onclick="closePutToRepairModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Record Delivery Modal (triggered from scan popup for IN_REPAIR machines) -->
<div id="recordDeliveryModal" class="modal-overlay hidden" style="z-index:65">
  <div class="modal-box slide-up" style="max-width:540px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-green-500/20 border border-green-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Record Machine Delivery</h3>
        <p class="text-xs text-slate-400">Record when a repaired machine is picked up</p>
      </div>
    </div>
    <div class="glass p-3 rounded-xl border border-white/5 mb-4 space-y-1 text-xs">
      <p class="text-slate-400">Machine: <span id="rd_machine_name" class="font-bold text-white">—</span></p>
      <p class="text-slate-400">Serial NO: <span id="rd_serial_number" class="font-mono text-teal-300">—</span></p>
    </div>
    <form id="recordDeliveryForm" onsubmit="submitRecordDelivery(event)">
      <input type="hidden" id="rd_repair_id"/>
      <input type="hidden" id="rd_equipment_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label title="Person Who Collected Machine">Collected By *</label>
          <input type="text" id="rd_received_by" required placeholder="Full Name of person collecting"/>
        </div>
        <div class="form-group">
          <label>Delivery Department</label>
          <select id="rd_department">
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group">
          <label>Date & Time</label>
          <input type="text" id="rd_datetime" readonly class="cursor-not-allowed"/>
        </div>
        <div class="form-group col-span-2">
          <label>Remarks</label>
          <textarea id="rd_remarks" rows="2" placeholder="Any additional notes about the delivery…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="rdError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Confirm Delivery</button>
        <button type="button" onclick="closeRecordDeliveryModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Custom Confirm Modal -->
<div id="customConfirmModal" class="modal-overlay hidden" style="z-index:100">
  <div class="modal-box slide-up" style="max-width:440px">
    <div class="flex items-center gap-3 mb-4">
      <div id="confirmIconBox" class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 id="confirmTitle" class="text-lg font-bold text-white">Confirm Action</h3>
        <p id="confirmSubtitle" class="text-xs text-slate-400">Please confirm to proceed</p>
      </div>
    </div>
    <p id="confirmMessage" class="text-sm text-slate-300 mb-6 leading-relaxed">—</p>
    <div class="flex justify-end gap-3">
      <button id="confirmCancelBtn" class="btn-secondary text-sm">Cancel</button>
      <button id="confirmActionBtn" class="px-4 py-2 rounded-xl text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors">
        Confirm
      </button>
    </div>
  </div>
</div>

<!-- Custom Alert Modal -->
<div id="customAlertModal" class="modal-overlay hidden" style="z-index:100">
  <div class="modal-box slide-up" style="max-width:420px">
    <div class="flex items-center gap-3 mb-4">
      <div id="alertIconBox" class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 id="alertTitle" class="text-lg font-bold text-white">Notice</h3>
        <p id="alertSubtitle" class="text-xs text-slate-400">System Notification</p>
      </div>
    </div>
    <p id="alertMessage" class="text-sm text-slate-300 mb-6 leading-relaxed">—</p>
    <div class="flex justify-end">
      <button id="alertOkBtn" class="btn-primary text-sm min-w-[100px]">OK</button>
    </div>
  </div>
</div>

<script>
// ── Global User Role (for permission checks in JS) ──────
const USER_ROLE = '<?= htmlspecialchars($user['role']) ?>';
const CAN_EDIT   = <?= canEdit() ? 'true' : 'false' ?>;
const CAN_DELETE = <?= canDelete() ? 'true' : 'false' ?>;

// ── Global Custom Dialog System ────────────────────────────
function showCustomConfirm(title, message, confirmLabel = 'Confirm', isDanger = true) {
  return new Promise((resolve) => {
    const modal = document.getElementById('customConfirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const msgEl = document.getElementById('confirmMessage');
    const actionBtn = document.getElementById('confirmActionBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    const iconBox = document.getElementById('confirmIconBox');

    titleEl.textContent = title || 'Confirm Action';
    msgEl.textContent   = message || 'Are you sure?';
    actionBtn.textContent = confirmLabel;

    if (isDanger) {
      iconBox.className = 'w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center flex-shrink-0 text-red-400';
      actionBtn.className = 'px-4 py-2 rounded-xl text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors';
    } else {
      iconBox.className = 'w-10 h-10 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center flex-shrink-0 text-teal-400';
      actionBtn.className = 'btn-primary text-sm';
    }

    modal.classList.remove('hidden');

    function cleanup() {
      modal.classList.add('hidden');
      actionBtn.onclick = null;
      cancelBtn.onclick = null;
    }

    actionBtn.onclick = () => { cleanup(); resolve(true); };
    cancelBtn.onclick = () => { cleanup(); resolve(false); };
  });
}

function showCustomAlert(title, message, type = 'error') {
  return new Promise((resolve) => {
    const modal = document.getElementById('customAlertModal');
    const titleEl = document.getElementById('alertTitle');
    const msgEl = document.getElementById('alertMessage');
    const okBtn = document.getElementById('alertOkBtn');
    const iconBox = document.getElementById('alertIconBox');

    titleEl.textContent = title || (type === 'success' ? 'Success' : 'Notice');
    msgEl.textContent   = message || '';

    if (type === 'success') {
      iconBox.className = 'w-10 h-10 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center flex-shrink-0 text-teal-400';
    } else if (type === 'warning') {
      iconBox.className = 'w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center flex-shrink-0 text-amber-400';
    } else {
      iconBox.className = 'w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center flex-shrink-0 text-red-400';
    }

    modal.classList.remove('hidden');

    okBtn.onclick = () => {
      modal.classList.add('hidden');
      okBtn.onclick = null;
      resolve();
    };
  });
}

// ── Real-time poll system ─────────────────────────────────
const seenIds = new Set(JSON.parse(sessionStorage.getItem('seenEventIds') || '[]'));

function persistSeen() {
  sessionStorage.setItem('seenEventIds', JSON.stringify([...seenIds]));
}

// Store current scanned equipment data for use by modals
let currentScanEquipment = null;

async function pollEvents() {
  try {
    const res = await fetch('/api/rfid/poll.php', { cache: 'no-store' });
    if (!res.ok) return;
    const data = await res.json();
    const events = data.events || [];

    document.getElementById('sidebarPollStatus').textContent = 'Last sync: ' + new Date().toLocaleTimeString();

    const newEvents = events.filter(e => !seenIds.has(e.id));
    newEvents.forEach(e => seenIds.add(e.id));
    persistSeen();

    if (newEvents.length > 0) {
      const latest = newEvents[0];
      const tagId = latest.tag_id || latest.data?.tag_id || latest.data?.cardId || latest.data?.uid || '';
      const exists = !!latest.equipment_id;

      if (latest.mode === 'register') {
        if (exists) {
          // Tag exists — show popup with context-aware buttons
          showContextScanPopup(latest);
        } else {
          openRegisterModal(tagId, latest.equipment_location || latest.data?.location || '');
        }
      } else {
        // Mode = scan
        if (exists) {
          showContextScanPopup(latest);
        } else {
          showNoDeviceFoundPopup(latest, tagId);
        }
      }
    }
  } catch(e) {
    document.getElementById('sidebarPollStatus').textContent = 'Connection error…';
  }
}

// ── Context-aware scan popup ─────────────────────────────
let scanPopupTimer = null;

function showContextScanPopup(ev) {
  const pop = document.getElementById('scanPopup');
  const isInRepair = ev.equipment_status === 'IN_REPAIR';

  document.getElementById('popupDeviceName').textContent = ev.equipment_name || 'Device Found';
  document.getElementById('popupDeviceSerial').textContent = 'S/N: ' + (ev.equipment_serial || '—');
  document.getElementById('popupDeviceStatus').textContent = 'Status: ' + (ev.equipment_status || 'OPERATIONAL');
  
  const locEl = document.getElementById('popupDeviceLocation');
  if (locEl) {
    const locText = ev.equipment_location ? `📍 Location: ${ev.equipment_location}` : '';
    locEl.textContent = locText;
    locEl.classList.toggle('hidden', !locText);
  }

  document.getElementById('popupLabel').textContent = 'RFID Scan Detected';

  // Store for modals
  currentScanEquipment = {
    equipment_id: ev.equipment_id,
    name: ev.equipment_name,
    serial_number: ev.equipment_serial || '',
    status: ev.equipment_status,
    location: ev.equipment_location,
    tag_id: ev.tag_id
  };

  // Build context-aware buttons
  const btnBox = document.getElementById('popupButtons');

  if (isInRepair) {
    // IN_REPAIR: See More + Record Delivery
    btnBox.innerHTML = `
      <a href="/device.php?id=${ev.equipment_id}" class="flex-1 text-center text-xs font-semibold py-1.5 rounded-lg bg-teal-600/20 hover:bg-teal-600/30 text-teal-300 border border-teal-600/30 transition-colors">
        See More Info
      </a>
      <button onclick="openRecordDeliveryFromScan()" class="flex-1 text-center text-xs font-semibold py-1.5 rounded-lg bg-green-600/20 hover:bg-green-600/30 text-green-300 border border-green-600/30 transition-colors">
        Record Delivery
      </button>
    `;
  } else {
    // NOT IN_REPAIR: See More + Put to Repair
    btnBox.innerHTML = `
      <a href="/device.php?id=${ev.equipment_id}" class="flex-1 text-center text-xs font-semibold py-1.5 rounded-lg bg-teal-600/20 hover:bg-teal-600/30 text-teal-300 border border-teal-600/30 transition-colors">
        See More Info
      </a>
      <button onclick="openPutToRepairFromScan()" class="flex-1 text-center text-xs font-semibold py-1.5 rounded-lg bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 border border-blue-600/30 transition-colors">
        Put to Repair
      </button>
    `;
  }

  pop.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
  pop.classList.add('translate-y-0', 'opacity-100');
  clearTimeout(scanPopupTimer);
  scanPopupTimer = setTimeout(closeScanPopup, 10000);
}

function showNoDeviceFoundPopup(ev, tagId) {
  const pop = document.getElementById('scanPopup');
  const locVal = ev.equipment_location || ev.data?.location || '';
  
  document.getElementById('popupDeviceName').textContent = '⚠️ No Device Found';
  document.getElementById('popupDeviceSerial').textContent = 'Tag ID: ' + (tagId || 'Unknown');
  
  const locEl = document.getElementById('popupDeviceLocation');
  if (locEl) {
    locEl.textContent = locVal ? `📍 Scanned at: ${locVal}` : '';
    locEl.classList.toggle('hidden', !locVal);
  }

  document.getElementById('popupDeviceStatus').textContent = 'No matching equipment registered in DB';
  document.getElementById('popupLabel').textContent = 'Unknown Tag Scanned';

  const safeLoc = locVal.replace(/'/g, "\\'");
  const btnBox = document.getElementById('popupButtons');
  btnBox.innerHTML = `
    <button onclick="closeScanPopup(); openRegisterModal('${tagId}', '${safeLoc}')" class="flex-1 text-center text-xs font-semibold py-1.5 rounded-lg bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-600/30 transition-colors">
      Register This Device
    </button>
    <button onclick="closeScanPopup()" class="text-xs font-medium py-1.5 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 border border-slate-700 transition-colors">
      Dismiss
    </button>
  `;

  pop.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
  pop.classList.add('translate-y-0', 'opacity-100');
  clearTimeout(scanPopupTimer);
  scanPopupTimer = setTimeout(closeScanPopup, 9000);
}

function closeScanPopup() {
  const pop = document.getElementById('scanPopup');
  pop.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
  pop.classList.remove('translate-y-0', 'opacity-100');
}

// ── Put to Repair Modal ─────────────────────────────────
function openPutToRepairFromScan() {
  closeScanPopup();
  if (!currentScanEquipment) return;
  openPutToRepairModal(currentScanEquipment.equipment_id, currentScanEquipment.name, currentScanEquipment.serial_number, currentScanEquipment.location);
}

function openPutToRepairModal(equipId, machineName, serialNum, department) {
  document.getElementById('ptr_equipment_id').value = equipId;
  document.getElementById('ptr_machine_name').textContent = machineName || '—';
  document.getElementById('ptr_serial_number').textContent = serialNum || '—';
  document.getElementById('ptr_datetime').value = new Date().toLocaleString();
  populateDepartmentSelect('ptr_department', 'Select Department…', department);
  document.getElementById('ptrError').classList.add('hidden');
  document.getElementById('putToRepairModal').classList.remove('hidden');
}

function closePutToRepairModal() {
  document.getElementById('putToRepairModal').classList.add('hidden');
}

async function submitPutToRepair(e) {
  e.preventDefault();
  const err = document.getElementById('ptrError');
  err.classList.add('hidden');

  const payload = {
    equipment_id:    document.getElementById('ptr_equipment_id').value,
    machine_problem: document.getElementById('ptr_problem').value,
    brought_by:      document.getElementById('ptr_brought_by').value,
    department:      document.getElementById('ptr_department').value,
    intake_date:     new Date().toISOString().split('T')[0],
  };

  const res = await fetch('/api/repairs/intake.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closePutToRepairModal();
    showCustomAlert('Machine Sent to Repair', 'Repair intake recorded. Machine status updated to IN_REPAIR.', 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    err.textContent = json.error || 'Failed to log repair intake.';
    err.classList.remove('hidden');
  }
}

// ── Record Delivery Modal ────────────────────────────────
function openRecordDeliveryFromScan() {
  closeScanPopup();
  if (!currentScanEquipment) return;
  openRecordDeliveryModal(currentScanEquipment.equipment_id, currentScanEquipment.name, currentScanEquipment.serial_number, currentScanEquipment.location);
}

async function openRecordDeliveryModal(equipId, machineName, serialNum, defaultDept = '') {
  document.getElementById('rd_equipment_id').value = equipId;
  document.getElementById('rd_machine_name').textContent = machineName || '—';
  document.getElementById('rd_serial_number').textContent = serialNum || '—';
  document.getElementById('rd_datetime').value = new Date().toLocaleString();
  populateDepartmentSelect('rd_department', 'Select Department…', defaultDept);
  document.getElementById('rdError').classList.add('hidden');

  // Find the active repair_id for this equipment
  try {
    const res = await fetch('/api/repairs/list.php?equipment_id=' + equipId + '&status=IN_REPAIR');
    const repairs = await res.json();
    if (Array.isArray(repairs) && repairs.length > 0) {
      document.getElementById('rd_repair_id').value = repairs[0].repair_id;
    }
  } catch(e) {}

  document.getElementById('recordDeliveryModal').classList.remove('hidden');
}

function closeRecordDeliveryModal() {
  document.getElementById('recordDeliveryModal').classList.add('hidden');
}

async function submitRecordDelivery(e) {
  e.preventDefault();
  const err = document.getElementById('rdError');
  err.classList.add('hidden');

  const repairId = document.getElementById('rd_repair_id').value;
  if (!repairId) {
    err.textContent = 'No active repair found for this machine.';
    err.classList.remove('hidden');
    return;
  }

  const payload = {
    repair_id:            repairId,
    return_date:          new Date().toISOString().split('T')[0],
    received_by:          document.getElementById('rd_received_by').value,
    delivery_department:  document.getElementById('rd_department').value,
    delivery_remarks:     document.getElementById('rd_remarks').value,
  };

  const res = await fetch('/api/repairs/return.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closeRecordDeliveryModal();
    showCustomAlert('Delivery Recorded', 'Machine delivery recorded. Status updated to OPERATIONAL.', 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    err.textContent = json.error || 'Failed to record delivery.';
    err.classList.remove('hidden');
  }
}

// ── Manual Scan Modal ────────────────────────────────────
function openManualScanModal() {
  document.getElementById('manualScanInput').value = '';
  document.getElementById('manualScanError').classList.add('hidden');
  document.getElementById('manualScanResult').classList.add('hidden');
  document.getElementById('manualScanResult').innerHTML = '';
  document.getElementById('manualScanModal').classList.remove('hidden');
  setTimeout(() => document.getElementById('manualScanInput').focus(), 100);
}

function closeManualScanModal() {
  document.getElementById('manualScanModal').classList.add('hidden');
}

// Allow Enter key to trigger lookup
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('manualScanInput');
  if (input) {
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); performManualScan(); }
    });
  }
});

async function performManualScan() {
  const query = document.getElementById('manualScanInput').value.trim();
  const errEl = document.getElementById('manualScanError');
  const resultEl = document.getElementById('manualScanResult');
  errEl.classList.add('hidden');
  resultEl.classList.add('hidden');

  if (!query) {
    errEl.textContent = 'Please enter an RFID Tag ID or Serial Number.';
    errEl.classList.remove('hidden');
    return;
  }

  // Search by tag ID or serial number
  const res = await fetch('/api/equipment/list.php?search=' + encodeURIComponent(query));
  const data = await res.json();
  const equipment = data.equipment || [];

  if (equipment.length === 0) {
    resultEl.innerHTML = `
      <div class="glass p-4 rounded-xl border border-amber-500/20">
        <p class="text-sm text-amber-300 font-semibold mb-2">⚠ No machine found</p>
        <p class="text-xs text-slate-400 mb-3">No equipment matches "${query}" in the database.</p>
        <button onclick="closeManualScanModal(); openRegisterModal('${query}')" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-600/20 text-amber-300 border border-amber-600/30 hover:bg-amber-600/30 transition-colors">
          Register New Equipment
        </button>
      </div>`;
    resultEl.classList.remove('hidden');
    return;
  }

  // Show found equipment with context-aware buttons
  const eq = equipment[0];
  const isInRepair = eq.status === 'IN_REPAIR';

  currentScanEquipment = {
    equipment_id: eq.equipment_id,
    name: eq.name,
    serial_number: eq.serial_number || '',
    status: eq.status,
    location: eq.current_location,
    tag_id: eq.rfid_tag_id
  };

  const statusBadges = {
    'OPERATIONAL': '<span class="badge badge-operational">OPERATIONAL</span>',
    'PM_DUE': '<span class="badge badge-pm-due">PM DUE</span>',
    'IN_REPAIR': '<span class="badge badge-in-repair">IN REPAIR</span>',
    'NEEDS_ATTENTION': '<span class="badge badge-needs-attention">NEEDS ATTN</span>',
    'DECOMMISSIONED': '<span class="badge badge-decommissioned">DECOMM.</span>',
  };

  let actionBtns = '';
  if (isInRepair) {
    actionBtns = `
      <a href="/device.php?id=${eq.equipment_id}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-600/20 text-teal-300 border border-teal-600/30 hover:bg-teal-600/30 transition-colors">See More Info</a>
      <button onclick="closeManualScanModal(); openRecordDeliveryModal(${eq.equipment_id}, '${(eq.name||'').replace(/'/g,"\\'")}', '${(eq.serial_number||'').replace(/'/g,"\\'")}')" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-green-600/20 text-green-300 border border-green-600/30 hover:bg-green-600/30 transition-colors">Record Delivery</button>
    `;
  } else {
    actionBtns = `
      <a href="/device.php?id=${eq.equipment_id}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-teal-600/20 text-teal-300 border border-teal-600/30 hover:bg-teal-600/30 transition-colors">See More Info</a>
      <button onclick="closeManualScanModal(); openPutToRepairModal(${eq.equipment_id}, '${(eq.name||'').replace(/'/g,"\\'")}', '${(eq.serial_number||'').replace(/'/g,"\\'")}', '${(eq.current_location||'').replace(/'/g,"\\'")}')" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-600/20 text-blue-300 border border-blue-600/30 hover:bg-blue-600/30 transition-colors">Put to Repair</button>
    `;
  }

  resultEl.innerHTML = `
    <div class="glass p-4 rounded-xl border border-teal-500/20">
      <div class="flex items-start justify-between gap-3 mb-2">
        <div>
          <p class="text-sm font-bold text-white">${eq.name}</p>
          <p class="text-xs font-mono text-teal-300">${eq.serial_number || '—'}</p>
        </div>
        ${statusBadges[eq.status] || ''}
      </div>
      <p class="text-xs text-slate-400 mb-3">📍 ${eq.current_location || '—'} · ${eq.category || ''}</p>
      <div class="flex gap-2">${actionBtns}</div>
    </div>`;
  resultEl.classList.remove('hidden');
}

// ── Register modal ────────────────────────────────────────
function openRegisterModal(tagId, defaultLoc = '') {
  document.getElementById('reg_rfid_tag_id').value = tagId;
  populateCategorySelect('reg_category', 'Select Category…');
  populateDepartmentSelect('reg_location', 'Select Department…', defaultLoc);
  document.getElementById('registerModal').classList.remove('hidden');
}
function closeRegisterModal() {
  document.getElementById('registerModal').classList.add('hidden');
}

async function submitRegisterForm(e) {
  e.preventDefault();
  const form = document.getElementById('registerForm');
  const errEl = document.getElementById('registerError');
  errEl.classList.add('hidden');
  const data = Object.fromEntries(new FormData(form));
  try {
    const res = await fetch('/api/equipment/create.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.success) {
      closeRegisterModal();
      window.location.href = '/device.php?id=' + json.equipment_id;
    } else {
      errEl.textContent = json.error || 'Failed to save.';
      errEl.classList.remove('hidden');
    }
  } catch(err) {
    errEl.textContent = 'Network error.';
    errEl.classList.remove('hidden');
  }
}

// ── Clock ─────────────────────────────────────────────────
function updateClock() {
  const el = document.getElementById('topbarTime');
  if (el) el.textContent = new Date().toLocaleString();
}
updateClock();
setInterval(updateClock, 1000);

// ── Theme toggle ──────────────────────────────────────────
function toggleTheme() {
  const isLight = document.documentElement.classList.toggle('light');
  document.getElementById('themeIconMoon').classList.toggle('hidden', isLight);
  document.getElementById('themeIconSun').classList.toggle('hidden', !isLight);
  localStorage.setItem('medtrack_theme', isLight ? 'light' : 'dark');
}

// ── Sidebar toggle ────────────────────────────────────────
function toggleSidebar() {
  const sidebar = document.getElementById('appSidebar');
  const wrapper = document.getElementById('mainWrapper');
  if (!sidebar || !wrapper) return;
  const isCollapsed = sidebar.classList.toggle('collapsed');
  wrapper.classList.toggle('collapsed', isCollapsed);
  localStorage.setItem('medtrack_sidebar_collapsed', isCollapsed ? 'true' : 'false');
}

// Restore saved theme & sidebar state on page load
(function() {
  if (localStorage.getItem('medtrack_theme') === 'light') {
    document.documentElement.classList.add('light');
    const moon = document.getElementById('themeIconMoon');
    const sun  = document.getElementById('themeIconSun');
    if (moon) moon.classList.add('hidden');
    if (sun)  sun.classList.remove('hidden');
  }

  if (localStorage.getItem('medtrack_sidebar_collapsed') === 'true') {
    const sidebar = document.getElementById('appSidebar');
    const wrapper = document.getElementById('mainWrapper');
    if (sidebar) sidebar.classList.add('collapsed');
    if (wrapper) wrapper.classList.add('collapsed');
  }
})();

// ── Boot polling & Master Data ─────────────────────────────
window.GLOBAL_DEPTS = [];
window.GLOBAL_CATS  = [];

async function loadGlobalMasterData() {
  try {
    const [deptRes, catRes] = await Promise.all([
      fetch('/api/departments/list.php'),
      fetch('/api/categories/list.php')
    ]);
    if (deptRes.ok) window.GLOBAL_DEPTS = await deptRes.json();
    if (catRes.ok)  window.GLOBAL_CATS  = await catRes.json();

    // Trigger any page-specific master data handlers
    if (typeof onMasterDataLoaded === 'function') {
      onMasterDataLoaded();
    }
  } catch(e) {}
}

function populateDepartmentSelect(elementOrId, defaultLabel = 'Select Department…', selectedVal = '') {
  const el = typeof elementOrId === 'string' ? document.getElementById(elementOrId) : elementOrId;
  if (!el) return;
  const currentVal = selectedVal || el.value;
  el.innerHTML = `<option value="">${defaultLabel}</option>` + 
    window.GLOBAL_DEPTS.map(d => `<option value="${escHTML(d.name)}" ${d.name === currentVal ? 'selected' : ''}>${escHTML(d.name)}</option>`).join('');
}

function populateCategorySelect(elementOrId, defaultLabel = 'Select Category…', selectedVal = '') {
  const el = typeof elementOrId === 'string' ? document.getElementById(elementOrId) : elementOrId;
  if (!el) return;
  const currentVal = selectedVal || el.value;
  el.innerHTML = `<option value="">${defaultLabel}</option>` + 
    window.GLOBAL_CATS.map(c => `<option value="${escHTML(c.name)}" ${c.name === currentVal ? 'selected' : ''}>${escHTML(c.name)}</option>`).join('');
}

// Escaping helper
function escHTML(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

loadGlobalMasterData();
pollEvents();
setInterval(pollEvents, 2000);
</script>
