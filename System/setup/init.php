<?php
// One-time setup page — run this once to create the database and tables
$host = 'localhost';
$user = 'root';
$pass = '';

$sqlFile = __DIR__ . '/setup.sql';
$sqlContent = file_get_contents($sqlFile);

$success = false;
$error   = '';
$output  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            fn($s) => $s !== '' && $s !== '--'
        );

        foreach ($statements as $stmt) {
            if (trim($stmt) === '') continue;
            try {
                $pdo->exec($stmt);
                $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 60);
                $output[] = ['ok', $preview . '…'];
            } catch (PDOException $e) {
                $output[] = ['err', $e->getMessage()];
            }
        }
        $success = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Database Setup — RFID System</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet"/>
<style>body{font-family:'Inter',sans-serif;background:#060B18;color:#e2e8f0;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<div class="w-full max-w-2xl">
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-2xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625v2.625m-16.5-2.625v2.625"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-white">RFID System — Database Setup</h1>
    <p class="text-slate-400 mt-1">This will create the <code class="text-teal-300 font-mono text-sm">rfid-system</code> database and all tables.</p>
  </div>

  <?php if ($error): ?>
  <div class="mb-4 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300">
    <strong>Connection Error:</strong> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="mb-4 p-4 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-300">
    ✅ Database setup complete!
    <a href="/setup/seed.php" class="ml-3 underline font-semibold text-teal-300 hover:text-white">Seed Database with Sample Data →</a>
    <a href="/login.php" class="ml-3 underline font-semibold hover:text-teal-200">Go to Login →</a>
  </div>
  <div class="rounded-xl bg-slate-900/80 border border-slate-700 p-4 max-h-64 overflow-y-auto font-mono text-xs space-y-1">
    <?php foreach ($output as [$type, $msg]): ?>
    <div class="<?= $type === 'ok' ? 'text-teal-400' : 'text-red-400' ?>">
      <?= $type === 'ok' ? '✓' : '✗' ?> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="bg-slate-900/60 border border-slate-700 rounded-2xl p-6">
    <div class="mb-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
      ⚠️ <strong>Warning:</strong> Only run this once. Running again will attempt to re-create tables (safely ignored with IF NOT EXISTS).
    </div>
    <p class="text-slate-400 text-sm mb-4">
      Connection: <span class="font-mono text-slate-300">root@localhost (no password)</span><br/>
      Database: <span class="font-mono text-teal-300">rfid-system</span>
    </p>
    <form method="POST">
      <button type="submit"
        class="w-full py-3 rounded-xl font-semibold bg-teal-600 hover:bg-teal-500 text-white transition-all duration-200 active:scale-95">
        ▶ Run Setup SQL
      </button>
    </form>
  </div>
  <?php endif; ?>

  <p class="text-center text-slate-600 text-xs mt-6">
    After setup, delete or restrict access to <code>/setup/</code> for security.
  </p>
</div>
</body>
</html>
