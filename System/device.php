<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

$db  = getDB();
$id  = intval($_GET['id'] ?? 0);
$user = currentUser();

if (!$id || !$db) { header('Location: /inventory.php'); exit(); }

$stmt = $db->prepare('SELECT * FROM equipment WHERE equipment_id = ?');
$stmt->execute([$id]);
$eq = $stmt->fetch();
if (!$eq) { header('Location: /inventory.php'); exit(); }

// ── Computed PM info ─────────────────────────────────────
$today    = new DateTime();
$nextPmDt = $eq['next_pm_date'] ? new DateTime($eq['next_pm_date']) : null;
$daysTopm = $nextPmDt ? $today->diff($nextPmDt)->days * ($nextPmDt > $today ? 1 : -1) : null;

$pageTitle = htmlspecialchars($eq['name']);

include __DIR__ . '/includes/header.php';

function statusBadge(string $s): string {
    $map = ['OPERATIONAL'=>'badge-operational','PM_DUE'=>'badge-pm-due',
            'IN_REPAIR'=>'badge-in-repair','NEEDS_ATTENTION'=>'badge-needs-attention','DECOMMISSIONED'=>'badge-decommissioned'];
    return '<span class="badge '.($map[$s]??'badge-decommissioned').'">'.$s.'</span>';
}
?>

<!-- ── Breadcrumb ─────────────────────────────────────────── -->
<div class="flex items-center gap-2 text-sm text-slate-500 mb-5">
  <a href="/inventory.php" class="hover:text-teal-400 transition-colors">Inventory</a>
  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
  <span class="text-slate-300"><?= htmlspecialchars($eq['name']) ?></span>
</div>

<!-- ── Core Details ───────────────────────────────────────── -->
<div class="glass rounded-2xl p-6 border border-white/5 mb-5">
  <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
      <h2 class="text-2xl font-bold text-white mb-1"><?= htmlspecialchars($eq['name']) ?></h2>
      <div class="flex items-center gap-2 flex-wrap">
        <?= statusBadge($eq['status']) ?>
        <span class="text-xs text-slate-500">· <?= htmlspecialchars($eq['category']) ?></span>
        <?php if ($eq['current_location']): ?>
        <span class="text-xs text-slate-500">· 📍 <?= htmlspecialchars($eq['current_location']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex gap-2 flex-wrap items-center">
      <button onclick="openCheckinModal()" class="px-3.5 py-2 rounded-xl text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
        Check-In
      </button>
      <button onclick="openMaintenanceModal()" class="px-3.5 py-2 rounded-xl text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/></svg>
        Log Maintenance
      </button>
      <button onclick="openReportIssueModal()" class="px-3.5 py-2 rounded-xl text-sm font-medium border border-orange-500/30 text-orange-400 bg-orange-500/10 hover:bg-orange-500/20 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        Report Issue
      </button>
      <?php if ($eq['status'] !== 'IN_REPAIR'): ?>
      <button onclick="openPutToRepairModal(EQ_ID, '<?= addslashes($eq['name']) ?>', '<?= addslashes($eq['serial_number'] ?? '') ?>', '<?= addslashes($eq['current_location'] ?? '') ?>')" class="px-3.5 py-2 rounded-xl text-sm font-medium border border-blue-500/30 text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/></svg>
        Put to Repair
      </button>
      <?php else: ?>
      <button onclick="openRecordDeliveryModal(EQ_ID, '<?= addslashes($eq['name']) ?>', '<?= addslashes($eq['serial_number'] ?? '') ?>')" class="px-3.5 py-2 rounded-xl text-sm font-medium border border-green-500/30 text-green-400 bg-green-500/10 hover:bg-green-500/20 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Record Delivery
      </button>
      <?php endif; ?>
      <?php if (canDelete()): ?>
      <button onclick="removeCurrentDevice()" class="px-3.5 py-2 rounded-xl text-sm font-medium border border-red-500/30 text-red-400 bg-red-500/10 hover:bg-red-500/20 transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
        Remove Device
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Detail grid -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php
    $fields = [
      ['Serial #',       $eq['serial_number']   ?? '—', 'font-mono'],
      ['RFID Tag',       $eq['rfid_tag_id']      ?? '—', 'font-mono text-teal-300'],
      ['Model',          $eq['model']            ?? '—', ''],
      ['Location',       $eq['current_location'] ?? '—', ''],
      ['PM Interval',    ($eq['pm_interval_days'] ?? '—') . ' days', ''],
      ['Procurement',    $eq['procurement_date']  ? date('M d, Y', strtotime($eq['procurement_date'])) : '—', ''],
      ['Last PM',        $eq['last_maintenance_date'] ? date('M d, Y', strtotime($eq['last_maintenance_date'])) : '—', ''],
    ];
    foreach ($fields as [$lbl, $val, $cls]):
    ?>
    <div>
      <p class="text-xs text-slate-600 uppercase tracking-widest mb-1"><?= $lbl ?></p>
      <p class="text-sm font-medium text-slate-200 <?= $cls ?>"><?= htmlspecialchars($val) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($eq['description'])): ?>
  <div class="mt-4 pt-4 border-t border-white/5">
    <p class="text-xs text-slate-500 uppercase tracking-widest mb-1">Description</p>
    <p class="text-sm text-slate-300"><?= nl2br(htmlspecialchars($eq['description'])) ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- ── PM Engine ──────────────────────────────────────────── -->
<?php if ($nextPmDt): ?>
<div class="glass rounded-2xl p-5 border mb-5 <?= $daysTopm !== null && $daysTopm <= 7 ? 'border-amber-500/30 bg-amber-500/5' : 'border-white/5' ?>">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <p class="text-xs text-slate-500 uppercase tracking-widest mb-1">Next Preventive Maintenance</p>
      <p class="text-xl font-bold text-white"><?= $nextPmDt->format('M d, Y') ?></p>
    </div>
    <?php if ($daysTopm !== null):
      $dLabel = $daysTopm < 0 ? abs($daysTopm) . ' days overdue' : ($daysTopm === 0 ? 'Due Today' : $daysTopm . ' days remaining');
      $dClass = $daysTopm < 0 ? 'text-red-400 bg-red-500/10 border-red-500/20' : ($daysTopm <= 7 ? 'text-amber-400 bg-amber-500/10 border-amber-500/20' : 'text-teal-400 bg-teal-500/10 border-teal-500/20');
    ?>
    <div class="px-4 py-2 rounded-xl border <?= $dClass ?> font-semibold text-sm"><?= $dLabel ?></div>
    <?php endif; ?>
  </div>
  <?php if ($daysTopm !== null): ?>
  <div class="mt-4 h-2 bg-slate-800 rounded-full overflow-hidden">
    <?php
    $total = $eq['pm_interval_days'] ?: 90;
    $done  = max(0, $total - max(0, $daysTopm));
    $pct   = min(100, round($done / $total * 100));
    $bar   = $daysTopm < 0 ? 'bg-red-500' : ($daysTopm <= 7 ? 'bg-amber-500' : 'bg-teal-500');
    ?>
    <div class="h-full <?= $bar ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Tabs ───────────────────────────────────────────────── -->
<div class="flex gap-1 mb-4 p-1 rounded-xl" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); width:fit-content">
  <?php foreach (['location'=>'Location History','maintenance'=>'Maintenance Log','issues'=>'Issue Log','parts'=>'Replacement Parts','repairs'=>'Repair History'] as $tab=>$label): ?>
  <button id="tab-<?= $tab ?>" onclick="switchTab('<?= $tab ?>')"
    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all duration-150 tab-btn">
    <?= $label ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Location History -->
<div id="panel-location" class="glass rounded-2xl border border-white/5 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead><tr style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.07)">
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">#</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Location</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Reader / Device</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Timestamp</th>
      </tr></thead>
      <tbody id="locationBody"><tr><td colspan="4" class="text-center py-8 text-slate-500">Loading…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Maintenance Log -->
<div id="panel-maintenance" class="glass rounded-2xl border border-white/5 overflow-hidden hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead><tr style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.07)">
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Type</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Date</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Technician</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Remarks</th>
        <th class="text-left px-4 py-3 text-xs text-slate-500 uppercase tracking-widest">Next PM</th>
      </tr></thead>
      <tbody id="maintenanceBody"><tr><td colspan="5" class="text-center py-8 text-slate-500">Loading…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Issue Log -->
<div id="panel-issues" class="hidden">
  <div id="issuesList" class="space-y-3"></div>
</div>

<!-- Replacement Parts -->
<div id="panel-parts" class="hidden">
  <div class="flex items-center justify-between mb-3">
    <p class="text-sm text-slate-400">Replacement parts for this machine</p>
    <button onclick="openAddPartModal()" class="btn-secondary text-xs flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Add Part
    </button>
  </div>
  <div id="partsList" class="space-y-3"></div>
</div>

<!-- Repair History -->
<div id="panel-repairs" class="hidden">
  <div id="repairsList" class="space-y-3"></div>
</div>

<!-- ── Modals ──────────────────────────────────────────────── -->

<!-- Manual Check-In -->
<div id="checkinModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:400px">
    <h3 class="text-lg font-bold text-white mb-4">Manual Check-In</h3>
    <div class="form-group">
      <label>New Location</label>
      <select id="checkin_location">
        <option value="">Select location…</option>
        <option>ICU</option><option>Emergency</option><option>Ward A</option>
        <option>Ward B</option><option>OPD</option><option>Radiology</option>
        <option>Lab</option><option>Storage</option>
      </select>
    </div>
    <div id="checkinError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
    <div class="flex gap-3 mt-4">
      <button onclick="submitCheckin()" class="btn-primary flex-1">Update Location</button>
      <button onclick="document.getElementById('checkinModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Log Maintenance -->
<div id="maintenanceModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:480px">
    <h3 class="text-lg font-bold text-white mb-4">Log Maintenance</h3>
    <div class="grid grid-cols-2 gap-3">
      <div class="form-group">
        <label>Type</label>
        <select id="maint_type"><option value="PM">PM (Preventive)</option><option value="REPAIR">REPAIR</option></select>
      </div>
      <div class="form-group">
        <label>Service Date</label>
        <input type="date" id="maint_date" value="<?= date('Y-m-d') ?>"/>
      </div>
      <div class="form-group col-span-2">
        <label>Technician</label>
        <input type="text" id="maint_tech" placeholder="Technician name"/>
      </div>
      <div class="form-group col-span-2">
        <label>Remarks</label>
        <textarea id="maint_remarks" rows="3" placeholder="Service details, parts replaced, etc." style="resize:vertical"></textarea>
      </div>
      <div class="form-group col-span-2">
        <label>Next PM Date</label>
        <input type="date" id="maint_nextpm"/>
      </div>
    </div>
    <div id="maintError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
    <div class="flex gap-3 mt-2">
      <button onclick="submitMaintenance()" class="btn-primary flex-1">Save Log</button>
      <button onclick="document.getElementById('maintenanceModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
    </div>
  </div>
</div>



<!-- Report Issue Modal -->
<div id="reportIssueModal" class="modal-overlay hidden" style="z-index:55">
  <div class="modal-box" style="max-width:480px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-orange-500/20 border border-orange-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Report Issue on Machine</h3>
        <p class="text-xs text-slate-400">Record a fault or problem for this machine</p>
      </div>
    </div>
    <form id="reportIssueForm" onsubmit="submitReportIssue(event)">
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Issue Name / Title *</label>
          <input type="text" id="ri_title" required placeholder="e.g. Screen Flickering, Motor Overheating"/>
        </div>
        <div class="form-group">
          <label>Person Reporting *</label>
          <input type="text" id="ri_reporter" required value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Full Name"/>
        </div>
        <div class="form-group">
          <label>Date & Time</label>
          <input type="text" id="ri_datetime" readonly value="<?= date('Y-m-d H:i') ?>" class="cursor-not-allowed"/>
        </div>
        <div class="form-group col-span-2">
          <label>Issue Description *</label>
          <textarea id="ri_desc" rows="3" required placeholder="Describe the problem, symptoms, or error details…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="reportIssueError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Submit Issue</button>
        <button type="button" onclick="closeReportIssueModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>
<div id="editIssueModal" class="modal-overlay hidden" style="z-index:55">
  <div class="modal-box" style="max-width:500px">
    <h3 class="text-lg font-bold text-white mb-4">Edit Issue Details</h3>
    <input type="hidden" id="edit_issue_id"/>
    <div class="form-group">
      <label>Issue Description</label>
      <textarea id="edit_issue_desc" rows="3" style="resize:vertical"></textarea>
    </div>
    <div class="form-group">
      <label>Status</label>
      <select id="edit_issue_status">
        <option value="OPEN">OPEN</option>
        <option value="IN_PROGRESS">IN PROGRESS</option>
        <option value="RESOLVED">RESOLVED</option>
      </select>
    </div>
    <div class="form-group">
      <label>How Was It Repaired? (Repair Method)</label>
      <textarea id="edit_issue_repair_method" rows="2" placeholder="Describe the repair method used…" style="resize:vertical"></textarea>
    </div>
    <div class="form-group">
      <label>Resolution Notes</label>
      <textarea id="edit_issue_resolution" rows="2" placeholder="Additional resolution details…" style="resize:vertical"></textarea>
    </div>
    <div id="editIssueError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
    <div class="flex gap-3 mt-2">
      <button onclick="submitEditIssue()" class="btn-primary flex-1">Save Changes</button>
      <button onclick="document.getElementById('editIssueModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Add Part Modal (device-level) -->
<div id="addPartModal" class="modal-overlay hidden" style="z-index:55">
  <div class="modal-box" style="max-width:520px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Add Replacement Part</h3>
        <p class="text-xs text-slate-400">Record a part replacement for this machine</p>
      </div>
    </div>
    <form id="addPartForm" onsubmit="submitAddPart(event)">
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Part Name *</label>
          <input type="text" id="ap_part_name" required placeholder="e.g. Pump Head Assembly"/>
        </div>
        <div class="form-group">
          <label>Part Serial NO *</label>
          <input type="text" id="ap_part_serial" required placeholder="PRT-SN-001" class="font-mono"/>
        </div>
        <div class="form-group">
          <label>Department (Optional)</label>
          <select id="ap_department">
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Part Description</label>
          <textarea id="ap_description" rows="2" placeholder="Describe this replacement part…" style="resize:vertical"></textarea>
        </div>
        <div class="form-group">
          <label>Date Replaced *</label>
          <input type="date" id="ap_replacement_date" required value="<?= date('Y-m-d') ?>"/>
        </div>
        <div class="form-group">
          <label>Link to Issue</label>
          <select id="ap_ticket_id">
            <option value="">None</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Notes</label>
          <textarea id="ap_notes" rows="2" placeholder="Additional notes…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="addPartError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Save Part</button>
        <button type="button" onclick="document.getElementById('addPartModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const EQ_ID = <?= $id ?>;
const USER_ID = <?= $user['user_id'] ?>;

// ── Tabs ──────────────────────────────────────────────────
const TABS = ['location','maintenance','issues','parts','repairs'];
function switchTab(active) {
  TABS.forEach(t => {
    const panel = document.getElementById('panel-' + t);
    const btn   = document.getElementById('tab-' + t);
    if (panel) panel.classList.toggle('hidden', t !== active);
    if (btn) {
      btn.style.background = t === active ? 'rgba(13,148,136,0.2)' : 'transparent';
      btn.style.color      = t === active ? '#2dd4bf' : '#64748b';
    }
  });
  if (active === 'location')    loadLocation();
  if (active === 'maintenance') loadMaintenance();
  if (active === 'issues')      loadIssues();
  if (active === 'parts')       loadParts();
  if (active === 'repairs')     loadRepairHistory();
}

// ── Load data ─────────────────────────────────────────────
async function loadLocation() {
  const res  = await fetch('/api/location/list.php?equipment_id=' + EQ_ID);
  const rows = await res.json();
  const body = document.getElementById('locationBody');
  if (!rows.length) { body.innerHTML='<tr><td colspan="4" class="text-center py-8 text-slate-500">No location history.</td></tr>'; return; }
  body.innerHTML = rows.map((r,i) => `
    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
      <td class="px-4 py-3 text-xs text-slate-600 font-mono">${i+1}</td>
      <td class="px-4 py-3 font-medium text-white">${r.location || '—'}</td>
      <td class="px-4 py-3 text-xs text-slate-400 font-mono">${r.reader_id || '—'}</td>
      <td class="px-4 py-3 text-xs text-slate-400">${new Date(r.scanned_at).toLocaleString()}</td>
    </tr>`).join('');
}

async function loadMaintenance() {
  const res  = await fetch('/api/maintenance/list.php?equipment_id=' + EQ_ID);
  const rows = await res.json();
  const body = document.getElementById('maintenanceBody');
  if (!rows.length) { body.innerHTML='<tr><td colspan="5" class="text-center py-8 text-slate-500">No maintenance records.</td></tr>'; return; }
  body.innerHTML = rows.map(r => {
    const tc = r.type === 'PM' ? 'badge-operational' : 'badge-in-repair';
    return `<tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
      <td class="px-4 py-3"><span class="badge ${tc}">${r.type}</span></td>
      <td class="px-4 py-3 text-sm text-slate-200">${r.service_date ? new Date(r.service_date).toLocaleDateString() : '—'}</td>
      <td class="px-4 py-3 text-sm text-slate-400">${r.technician || '—'}</td>
      <td class="px-4 py-3 text-sm text-slate-400">${r.remarks || '—'}</td>
      <td class="px-4 py-3 text-xs text-teal-400 font-mono">${r.next_pm_date || '—'}</td>
    </tr>`;
  }).join('');
}

let cachedIssues = [];

async function loadIssues() {
  const res   = await fetch('/api/issues/list.php?equipment_id=' + EQ_ID);
  const rows  = await res.json();
  cachedIssues = Array.isArray(rows) ? rows : [];
  const panel = document.getElementById('issuesList');

  // Also populate the issue dropdown for adding parts
  populateIssueDropdown();

  if (!cachedIssues.length) {
    panel.innerHTML = '<p class="text-slate-500 text-sm text-center py-8">No reported issues.</p>';
    return;
  }

  const sc = {'OPEN':'badge-needs-attention','IN_PROGRESS':'badge-in-repair','RESOLVED':'badge-operational'};
  panel.innerHTML = cachedIssues.map(r => `
    <div class="glass rounded-xl p-4 border border-white/5">
      <div class="flex items-start justify-between gap-3 mb-2">
        <div class="flex-1">
          <p class="text-sm font-bold text-white mb-0.5">${r.title ? escHTML(r.title) : 'Issue #' + r.ticket_id}</p>
          <p class="text-xs text-slate-300">${escHTML(r.issue_description)}</p>
          <p class="text-xs text-slate-500 mt-2">Reported by <span class="font-medium text-slate-300">${escHTML(r.reporter_name||'Unknown')}</span> · ${new Date(r.created_at).toLocaleString()}</p>
          <p class="text-xs text-slate-600 font-mono mt-0.5">Ticket #${r.ticket_id}</p>
        </div>
        <span class="badge ${sc[r.status]||''} flex-shrink-0">${r.status}</span>
      </div>
      ${r.repair_method ? `<div class="mt-2 p-2 rounded-lg bg-teal-500/5 border border-teal-500/10">
        <p class="text-xs text-teal-400 font-semibold uppercase tracking-wider mb-0.5">Repair Method</p>
        <p class="text-xs text-slate-300">${escHTML(r.repair_method)}</p>
      </div>` : ''}
      ${r.resolution_notes ? `<div class="mt-2 p-2 rounded-lg bg-blue-500/5 border border-blue-500/10">
        <p class="text-xs text-blue-400 font-semibold uppercase tracking-wider mb-0.5">Resolution Notes</p>
        <p class="text-xs text-slate-300">${escHTML(r.resolution_notes)}</p>
      </div>` : ''}
      ${r.linked_parts_count > 0 ? `<div class="mt-2">
        <p class="text-xs text-purple-400 font-semibold uppercase tracking-wider mb-1">Linked Parts (${r.linked_parts_count})</p>
        <div id="issue-parts-${r.ticket_id}" class="space-y-1"></div>
      </div>` : ''}
      <div class="flex gap-2 mt-3 flex-wrap">
        ${CAN_EDIT ? `<button onclick="openEditIssueModal(${r.ticket_id})" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700/50 border border-slate-600/30 text-slate-300 hover:bg-slate-700 transition-colors">Edit Details</button>` : ''}
        ${r.status !== 'RESOLVED' && CAN_EDIT ? `
          ${r.status === 'OPEN' ? `<button onclick="updateIssueStatus(${r.ticket_id},'IN_PROGRESS')" class="text-xs px-3 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-300 hover:bg-blue-500/20 transition-colors">Mark In Progress</button>` : ''}
          <button onclick="updateIssueStatus(${r.ticket_id},'RESOLVED')" class="text-xs px-3 py-1.5 rounded-lg bg-teal-500/10 border border-teal-500/20 text-teal-300 hover:bg-teal-500/20 transition-colors">Mark Resolved</button>
        ` : ''}
        <button onclick="openAddPartForIssue(${r.ticket_id})" class="text-xs px-3 py-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-300 hover:bg-purple-500/20 transition-colors">+ Add Part</button>
        ${CAN_DELETE ? `<button onclick="deleteIssue(${r.ticket_id})" class="text-xs px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 transition-colors">Delete</button>` : ''}
      </div>
    </div>`).join('');

  // Load linked parts for each issue
  cachedIssues.forEach(r => {
    if (r.linked_parts_count > 0) loadPartsForIssue(r.ticket_id);
  });
}

async function loadPartsForIssue(ticketId) {
  const res = await fetch('/api/parts/list.php?ticket_id=' + ticketId);
  const parts = await res.json();
  const container = document.getElementById('issue-parts-' + ticketId);
  if (!container || !Array.isArray(parts) || !parts.length) return;

  container.innerHTML = parts.map(p => `
    <div class="flex items-center gap-2 p-2 rounded-lg bg-purple-500/5 border border-purple-500/10 text-xs">
      <span class="text-purple-300 font-semibold">${escHTML(p.part_name)}</span>
      <span class="text-slate-500 font-mono">${escHTML(p.part_serial_number)}</span>
      <span class="text-slate-600">· ${p.replacement_date}</span>
    </div>
  `).join('');
}

function populateIssueDropdown() {
  const sel = document.getElementById('ap_ticket_id');
  if (!sel) return;
  sel.innerHTML = '<option value="">None — Standalone Part</option>' + cachedIssues.map(i =>
    `<option value="${i.ticket_id}">#${i.ticket_id} — ${escHTML(i.issue_description?.substring(0,60) || 'Issue')}</option>`
  ).join('');
}

function openAddPartForIssue(ticketId) {
  openAddPartModal();
  setTimeout(() => {
    const sel = document.getElementById('ap_ticket_id');
    if (sel) sel.value = ticketId;
  }, 50);
}

async function loadParts() {
  const res = await fetch('/api/parts/list.php?equipment_id=' + EQ_ID);
  const parts = await res.json();
  const panel = document.getElementById('partsList');

  if (!Array.isArray(parts) || !parts.length) {
    panel.innerHTML = '<p class="text-slate-500 text-sm text-center py-8">No replacement parts recorded.</p>';
    return;
  }

  panel.innerHTML = parts.map(p => `
    <div class="glass rounded-xl p-4 border border-white/5">
      <div class="flex items-start justify-between gap-3 mb-2">
        <div>
          <p class="text-sm font-bold text-white">${escHTML(p.part_name)}</p>
          <p class="text-xs font-mono text-purple-300">${escHTML(p.part_serial_number)}</p>
        </div>
        <span class="text-xs text-slate-500">${p.replacement_date}</span>
      </div>
      ${p.description ? `<p class="text-xs text-slate-400 mb-2">${escHTML(p.description)}</p>` : ''}
      <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mb-2">
        <span>📍 ${escHTML(p.department)}</span>
        ${p.issue_description ? `<span class="text-purple-400">🔗 Issue: ${escHTML(p.issue_description?.substring(0,40))}…</span>` : ''}
        ${p.repair_machine ? `<span class="text-teal-400">🛠 Repair: ${escHTML(p.repair_machine)}</span>` : ''}
      </div>
      ${p.notes ? `<p class="text-xs text-slate-500 italic">${escHTML(p.notes)}</p>` : ''}
      <div class="flex gap-2 mt-2">
        ${CAN_EDIT ? `<button onclick="editPart(${p.part_replacement_id})" class="text-xs px-2.5 py-1 rounded-lg bg-slate-700/50 border border-slate-600/30 text-slate-300 hover:bg-slate-700 transition-colors">Edit</button>` : ''}
        ${CAN_DELETE ? `<button onclick="deletePart(${p.part_replacement_id})" class="text-xs px-2.5 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 transition-colors">Delete</button>` : ''}
      </div>
    </div>
  `).join('');
}

async function loadRepairHistory() {
  const res = await fetch('/api/repairs/list.php?equipment_id=' + EQ_ID);
  const repairs = await res.json();
  const panel = document.getElementById('repairsList');

  if (!Array.isArray(repairs) || !repairs.length) {
    panel.innerHTML = '<p class="text-slate-500 text-sm text-center py-8">No repair history.</p>';
    return;
  }

  panel.innerHTML = repairs.map(r => {
    const isInRepair = r.status === 'IN_REPAIR';
    return `
    <div class="glass rounded-xl p-4 border ${isInRepair ? 'border-blue-500/20' : 'border-white/5'}">
      <div class="flex items-start justify-between gap-3 mb-2">
        <div>
          <p class="text-sm font-bold text-white">${escHTML(r.machine_name)}</p>
          <p class="text-xs font-mono text-teal-300">${escHTML(r.serial_number)}</p>
        </div>
        <span class="badge ${isInRepair ? 'badge-in-repair' : 'badge-operational'}">${r.status}</span>
      </div>
      <p class="text-xs text-slate-400 mb-1"><strong>Problem:</strong> ${escHTML(r.machine_problem)}</p>
      <div class="flex flex-wrap gap-3 text-xs text-slate-500">
        <span>Brought by: ${escHTML(r.brought_by)} · ${r.intake_date}</span>
        ${r.received_by ? `<span>Collected by: ${escHTML(r.received_by)} · ${r.return_date || ''}</span>` : ''}
        ${r.delivery_department ? `<span>Delivered to: ${escHTML(r.delivery_department)}</span>` : ''}
      </div>
      ${r.delivery_remarks ? `<p class="text-xs text-slate-500 mt-1 italic">Remarks: ${escHTML(r.delivery_remarks)}</p>` : ''}
    </div>`;
  }).join('');
}

// ── Issue Actions ─────────────────────────────────────────
async function updateIssueStatus(ticketId, status) {
  await fetch('/api/issues/update.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ticket_id: ticketId, status})
  });
  loadIssues();
  setTimeout(() => location.reload(), 800);
}

function openEditIssueModal(ticketId) {
  const issue = cachedIssues.find(i => i.ticket_id == ticketId);
  if (!issue) return;
  document.getElementById('edit_issue_id').value = ticketId;
  document.getElementById('edit_issue_desc').value = issue.issue_description || '';
  document.getElementById('edit_issue_status').value = issue.status || 'OPEN';
  document.getElementById('edit_issue_repair_method').value = issue.repair_method || '';
  document.getElementById('edit_issue_resolution').value = issue.resolution_notes || '';
  document.getElementById('editIssueError').classList.add('hidden');
  document.getElementById('editIssueModal').classList.remove('hidden');
}

async function submitEditIssue() {
  const err = document.getElementById('editIssueError');
  err.classList.add('hidden');
  const payload = {
    ticket_id:        document.getElementById('edit_issue_id').value,
    issue_description: document.getElementById('edit_issue_desc').value,
    status:           document.getElementById('edit_issue_status').value,
    repair_method:    document.getElementById('edit_issue_repair_method').value,
    resolution_notes: document.getElementById('edit_issue_resolution').value,
  };
  const res = await fetch('/api/issues/update.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const json = await res.json();
  if (json.success) {
    document.getElementById('editIssueModal').classList.add('hidden');
    loadIssues();
  } else {
    err.textContent = json.error || 'Failed to update.';
    err.classList.remove('hidden');
  }
}

async function deleteIssue(ticketId) {
  const confirmed = await showCustomConfirm('Delete Issue', 'Are you sure you want to delete this issue? This action cannot be undone.', 'Delete', true);
  if (!confirmed) return;
  await fetch('/api/issues/delete.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ticket_id: ticketId})
  });
  loadIssues();
}

// ── Part Actions ──────────────────────────────────────────
function openAddPartModal() {
  document.getElementById('addPartForm').reset();
  document.getElementById('ap_replacement_date').value = new Date().toISOString().split('T')[0];
  const loc = '<?= addslashes($eq['current_location'] ?? '') ?>';
  populateDepartmentSelect('ap_department', 'Select Department…', loc);
  populateIssueDropdown();
  document.getElementById('addPartError').classList.add('hidden');
  document.getElementById('addPartModal').classList.remove('hidden');
}

async function submitAddPart(e) {
  e.preventDefault();
  const err = document.getElementById('addPartError');
  err.classList.add('hidden');
  const payload = {
    equipment_id:       EQ_ID,
    part_name:          document.getElementById('ap_part_name').value,
    part_serial_number: document.getElementById('ap_part_serial').value,
    department:         document.getElementById('ap_department').value,
    description:        document.getElementById('ap_description').value,
    replacement_date:   document.getElementById('ap_replacement_date').value,
    ticket_id:          document.getElementById('ap_ticket_id').value,
    notes:              document.getElementById('ap_notes').value,
  };
  const res = await fetch('/api/parts/create.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const json = await res.json();
  if (json.success) {
    document.getElementById('addPartModal').classList.add('hidden');
    loadParts();
    loadIssues(); // Refresh issues to show linked parts
    showCustomAlert('Part Recorded', 'Replacement part logged successfully.', 'success');
  } else {
    err.textContent = json.error || 'Failed to save.';
    err.classList.remove('hidden');
  }
}

async function editPart(partId) {
  showCustomAlert('Edit Part', 'To edit this part, please use the Repairs & Parts page.', 'warning');
}

async function deletePart(partId) {
  const confirmed = await showCustomConfirm('Delete Part', 'Are you sure you want to delete this replacement part?', 'Delete', true);
  if (!confirmed) return;
  await fetch('/api/parts/delete.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({part_replacement_id: partId})
  });
  loadParts();
  loadIssues();
}

// ── Modal actions ─────────────────────────────────────────
function openReportIssueModal() {
  document.getElementById('reportIssueForm').reset();
  document.getElementById('ri_reporter').value = '<?= addslashes(htmlspecialchars($user['username'] ?? '')) ?>';
  document.getElementById('ri_datetime').value = new Date().toLocaleString();
  document.getElementById('reportIssueError').classList.add('hidden');
  document.getElementById('reportIssueModal').classList.remove('hidden');
}

function closeReportIssueModal() {
  document.getElementById('reportIssueModal').classList.add('hidden');
}

async function submitReportIssue(e) {
  e.preventDefault();
  const err = document.getElementById('reportIssueError');
  err.classList.add('hidden');

  const title        = document.getElementById('ri_title').value.trim();
  const reporterName = document.getElementById('ri_reporter').value.trim();
  const desc         = document.getElementById('ri_desc').value.trim();

  if (!title || !desc) {
    err.textContent = 'Issue name and description are required.';
    err.classList.remove('hidden');
    return;
  }

  const res = await fetch('/api/issues/create.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      equipment_id: EQ_ID,
      title: title,
      reporter_name: reporterName,
      issue_description: desc
    })
  });
  const json = await res.json();

  if (json.success) {
    closeReportIssueModal();
    showCustomAlert('Issue Reported', 'Issue logged successfully. Equipment status updated to NEEDS_ATTENTION.', 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    err.textContent = json.error || 'Failed to report issue.';
    err.classList.remove('hidden');
  }
}

function openCheckinModal() {
  populateDepartmentSelect('checkin_location', 'Select Location…');
  document.getElementById('checkinModal').classList.remove('hidden');
}
function openMaintenanceModal() { document.getElementById('maintenanceModal').classList.remove('hidden'); }

async function submitCheckin() {
  const loc = document.getElementById('checkin_location').value;
  const err = document.getElementById('checkinError');
  if (!loc) { err.textContent='Please select a location.'; err.classList.remove('hidden'); return; }
  await fetch('/api/rfid/scan.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ tag_id: '<?= addslashes($eq['rfid_tag_id'] ?? '') ?>', mode:'scan', location: loc, reader_id:'WEB-CHECKIN' })
  });
  await fetch('/api/equipment/update.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ equipment_id: EQ_ID, current_location: loc })
  });
  location.reload();
}

async function submitMaintenance() {
  const err = document.getElementById('maintError');
  err.classList.add('hidden');
  const data = {
    equipment_id: EQ_ID,
    type:         document.getElementById('maint_type').value,
    service_date: document.getElementById('maint_date').value,
    technician:   document.getElementById('maint_tech').value,
    remarks:      document.getElementById('maint_remarks').value,
    next_pm_date: document.getElementById('maint_nextpm').value,
  };
  const res  = await fetch('/api/maintenance/create.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
  const json = await res.json();
  if (json.success) { location.reload(); }
  else { err.textContent = json.error || 'Failed.'; err.classList.remove('hidden'); }
}



async function removeCurrentDevice() {
  const deviceName = "<?= addslashes(htmlspecialchars($eq['name'])) ?>";
  const confirmed = await showCustomConfirm(
    'Remove Device',
    `Are you sure you want to permanently remove "${deviceName}"? This will also remove all maintenance logs and location history.`,
    'Remove Device',
    true
  );
  if (!confirmed) return;

  const res = await fetch('/api/equipment/delete.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ equipment_id: EQ_ID })
  });
  const json = await res.json();
  if (json.success) {
    window.location.href = '/inventory.php';
  } else {
    await showCustomAlert('Remove Failed', json.error || 'Failed to remove device.', 'error');
  }
}

// ── Helpers ───────────────────────────────────────────────
function escHTML(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// Init
switchTab('location');
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
