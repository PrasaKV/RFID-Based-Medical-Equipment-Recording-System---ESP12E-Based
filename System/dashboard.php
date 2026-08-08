<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

$db   = getDB();
$user = currentUser();
$pageTitle = 'Dashboard';

// ── Stats ──────────────────────────────────────────────────────────────────
$stats = ['total'=>0,'pm_due'=>0,'in_repair'=>0,'needs_attention'=>0,'decommissioned'=>0,'operational'=>0];
$recentScans    = [];
$upcomingPM     = [];
$recentIssues   = [];

if ($db) {
    $s = $db->query('SELECT status, COUNT(*) as cnt FROM equipment GROUP BY status');
    foreach ($s->fetchAll() as $row) {
        $key = strtolower(str_replace(' ','',$row['status']));
        $key = str_replace('-','_', $key);
        $stats[$key] = (int)$row['cnt'];
        $stats['total'] += (int)$row['cnt'];
    }

    $recentScans = $db->query(
        'SELECT ll.*, e.name as device_name, e.status as device_status, e.equipment_id
         FROM location_logs ll
         LEFT JOIN equipment e ON ll.equipment_id = e.equipment_id
         ORDER BY ll.scanned_at DESC LIMIT 8'
    )->fetchAll();

    $upcomingPM = $db->query(
        "SELECT * FROM equipment
         WHERE next_pm_date IS NOT NULL AND status != 'DECOMMISSIONED'
         ORDER BY next_pm_date ASC LIMIT 6"
    )->fetchAll();

    $recentIssues = $db->query(
        "SELECT it.*, e.name as device_name, u.username as reporter
         FROM issues_tickets it
         LEFT JOIN equipment e ON it.equipment_id = e.equipment_id
         LEFT JOIN users u ON it.reported_by = u.user_id
         WHERE it.status != 'RESOLVED'
         ORDER BY it.created_at DESC LIMIT 5"
    )->fetchAll();
}

function statusBadge(string $s): string {
    $map = [
        'OPERATIONAL'     => 'badge-operational',
        'PM_DUE'          => 'badge-pm-due',
        'IN_REPAIR'       => 'badge-in-repair',
        'NEEDS_ATTENTION' => 'badge-needs-attention',
        'DECOMMISSIONED'  => 'badge-decommissioned',
    ];
    $cls = $map[$s] ?? 'badge-decommissioned';
    return "<span class=\"badge $cls\">$s</span>";
}

function pmDaysColor(string $date): string {
    $days = (new DateTime($date))->diff(new DateTime())->days;
    $sign = (new DateTime($date)) > new DateTime() ? 1 : -1;
    $days *= $sign;
    if ($days < 0)  return 'text-red-400';
    if ($days <= 7) return 'text-amber-400';
    if ($days <= 30) return 'text-yellow-400';
    return 'text-teal-400';
}

include __DIR__ . '/includes/header.php';
?>

<!-- ── Stats Cards ────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php
  $statCards = [
    ['label'=>'Total Devices',     'value'=>$stats['total'],         'color'=>'text-white',        'icon'=>'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776','bg'=>'bg-teal-600/15 border-teal-500/20'],
    ['label'=>'PM Due',            'value'=>$stats['pm_due'],        'color'=>'text-amber-400',    'icon'=>'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z','bg'=>'bg-amber-500/10 border-amber-500/20'],
    ['label'=>'Under Repair',      'value'=>$stats['in_repair'],     'color'=>'text-blue-400',     'icon'=>'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z','bg'=>'bg-blue-500/10 border-blue-500/20'],
    ['label'=>'Needs Attention',   'value'=>$stats['needs_attention'],'color'=>'text-orange-400', 'icon'=>'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z','bg'=>'bg-orange-500/10 border-orange-500/20'],
  ];
  foreach ($statCards as $c): ?>
  <div class="glass rounded-2xl p-5 border <?= $c['bg'] ?>">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs text-slate-500 uppercase tracking-widest mb-1"><?= $c['label'] ?></p>
        <p class="text-3xl font-bold <?= $c['color'] ?>"><?= $c['value'] ?></p>
      </div>
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:rgba(255,255,255,0.05)">
        <svg class="w-5 h-5 <?= $c['color'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="<?= $c['icon'] ?>"/>
        </svg>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Quick Action Bar ──────────────────────────────────── -->
<div class="glass rounded-2xl p-4 border border-teal-500/20 mb-6 flex flex-wrap items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
      </svg>
    </div>
    <div>
      <h3 class="text-sm font-bold text-white">Manual RFID / Serial Lookup</h3>
      <p class="text-xs text-slate-400">Scan or look up equipment manually without hardware reader</p>
    </div>
  </div>
  <div class="flex gap-2">
    <button onclick="openManualScanModal()" class="btn-primary text-sm flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
      </svg>
      Manual Scan
    </button>
    <a href="/repairs.php" class="btn-secondary text-sm flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/>
      </svg>
      Log Repair
    </a>
  </div>
</div>

<!-- ── Main 2-col grid ────────────────────────────────────── -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Recent Scans (2/3 width) -->
  <div class="xl:col-span-2">
    <div class="glass rounded-2xl p-5 border border-white/5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-white">Recent RFID Scans</h3>
        <span class="flex items-center gap-1.5 text-xs text-teal-400">
          <span class="live-dot w-1.5 h-1.5 rounded-full bg-teal-400"></span> Live
        </span>
      </div>
      <?php if (empty($recentScans)): ?>
      <p class="text-slate-500 text-sm text-center py-8">No scan events yet.</p>
      <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($recentScans as $scan): ?>
        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition-colors cursor-pointer"
             onclick="<?= $scan['equipment_id'] ? "window.location='/device.php?id={$scan['equipment_id']}'" : '' ?>">
          <div class="w-8 h-8 rounded-lg bg-teal-600/15 border border-teal-500/20 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($scan['device_name'] ?? 'Unknown Tag') ?></p>
            <p class="text-xs text-slate-500 truncate">📍 <?= htmlspecialchars($scan['location'] ?? 'Unknown') ?> · <?= htmlspecialchars($scan['reader_id'] ?? '') ?></p>
          </div>
          <div class="text-right flex-shrink-0">
            <?php if ($scan['device_status']) echo statusBadge($scan['device_status']); ?>
            <p class="text-xs text-slate-600 mt-1"><?= date('H:i', strtotime($scan['scanned_at'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Open Issues -->
    <?php if (!empty($recentIssues)): ?>
    <div class="glass rounded-2xl p-5 border border-orange-500/10 mt-5">
      <h3 class="font-semibold text-white mb-4">⚠ Open Issues</h3>
      <div class="space-y-2">
        <?php foreach ($recentIssues as $issue): ?>
        <div class="flex items-start gap-3 p-3 rounded-xl bg-orange-500/5 border border-orange-500/10">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($issue['device_name'] ?? '—') ?></p>
            <p class="text-xs text-slate-400 mt-0.5 line-clamp-1"><?= htmlspecialchars($issue['issue_description']) ?></p>
            <p class="text-xs text-slate-600 mt-0.5">Reported by <?= htmlspecialchars($issue['reporter'] ?? 'Unknown') ?></p>
          </div>
          <?php
          $sc = ['OPEN'=>'badge-needs-attention','IN_PROGRESS'=>'badge-in-repair','RESOLVED'=>'badge-operational'];
          $bcls = $sc[$issue['status']] ?? 'badge-decommissioned';
          ?>
          <span class="badge <?= $bcls ?> flex-shrink-0"><?= $issue['status'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Upcoming PM (1/3 width) -->
  <div>
    <div class="glass rounded-2xl p-5 border border-white/5">
      <h3 class="font-semibold text-white mb-4">🗓 Upcoming PM</h3>
      <?php if (empty($upcomingPM)): ?>
      <p class="text-slate-500 text-sm text-center py-6">No upcoming maintenance.</p>
      <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($upcomingPM as $eq):
          $nextDate = $eq['next_pm_date'] ?? null;
          if (!$nextDate) continue;
          $daysObj  = (new DateTime($nextDate))->diff(new DateTime());
          $days     = $daysObj->days * ($nextDate < date('Y-m-d') ? -1 : 1);
          $daysLabel = $days < 0 ? abs($days) . 'd overdue' : ($days == 0 ? 'Today' : $days . 'd left');
          $dColor    = $days < 0 ? 'text-red-400' : ($days <= 7 ? 'text-amber-400' : 'text-teal-400');
        ?>
        <a href="/device.php?id=<?= $eq['equipment_id'] ?>"
           class="block p-3 rounded-xl bg-white/3 hover:bg-white/6 border border-white/5 hover:border-teal-500/20 transition-all duration-150">
          <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($eq['name']) ?></p>
          <p class="text-xs text-slate-500 mt-0.5 truncate"><?= htmlspecialchars($eq['current_location'] ?? '—') ?></p>
          <div class="flex items-center justify-between mt-2">
            <?= statusBadge($eq['status']) ?>
            <span class="text-xs font-semibold <?= $dColor ?>"><?= $daysLabel ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="glass rounded-2xl p-5 border border-white/5 mt-5">
      <h3 class="font-semibold text-white mb-4">Fleet Status</h3>
      <?php
      $total = max($stats['total'], 1);
      $bars = [
        ['label'=>'Operational',  'count'=>$stats['operational'],     'color'=>'bg-teal-500'],
        ['label'=>'PM Due',       'count'=>$stats['pm_due'],          'color'=>'bg-amber-500'],
        ['label'=>'In Repair',    'count'=>$stats['in_repair'],       'color'=>'bg-blue-500'],
        ['label'=>'Needs Attn',   'count'=>$stats['needs_attention'], 'color'=>'bg-orange-500'],
        ['label'=>'Decommissioned','count'=>$stats['decommissioned'], 'color'=>'bg-slate-600'],
      ];
      foreach ($bars as $b):
        $pct = round($b['count'] / $total * 100);
      ?>
      <div class="mb-3">
        <div class="flex justify-between text-xs mb-1">
          <span class="text-slate-400"><?= $b['label'] ?></span>
          <span class="text-slate-300 font-medium"><?= $b['count'] ?> <span class="text-slate-600">(<?= $pct ?>%)</span></span>
        </div>
        <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
          <div class="h-full <?= $b['color'] ?> rounded-full transition-all duration-500" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
