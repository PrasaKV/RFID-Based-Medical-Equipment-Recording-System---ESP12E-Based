<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Machine Repairs & Part Replacements';
$user      = currentUser();
$db        = getDB();

include __DIR__ . '/includes/header.php';
?>

<!-- ── Navigation Header / Action Bar ───────────────────── -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-white mb-1">Repairs & Part Replacements</h1>
    <p class="text-xs text-slate-400">Track machine intake, repair deliveries, and replaced parts</p>
  </div>
  <div class="flex items-center gap-3">
    <button onclick="openIntakeModal()" class="btn-primary flex items-center gap-2 text-sm">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      Log Repair Intake
    </button>
    <button onclick="openPartModal()" class="btn-secondary flex items-center gap-2 text-sm">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
      </svg>
      Record Part Replacement
    </button>
  </div>
</div>

<!-- ── Main Section Tabs ──────────────────────────────────── -->
<div class="flex gap-2 mb-5 border-b border-white/10 pb-3">
  <button id="tab-repairs-btn" onclick="switchSectionTab('repairs')"
    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 bg-teal-600/20 text-teal-300 border border-teal-500/30">
    🛠 Machine Repairs
  </button>
  <button id="tab-parts-btn" onclick="switchSectionTab('parts')"
    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 text-slate-400 hover:text-white hover:bg-white/5">
    ⚙ Part Replacements
  </button>
</div>

<!-- ═════════════════════════════════════════════════════════
     SECTION 1: MACHINE REPAIRS (INTAKE & RETURN)
     ═════════════════════════════════════════════════════════ -->
<div id="section-repairs" class="space-y-4">
  <!-- Toolbar -->
  <div class="flex flex-wrap items-center gap-3">
    <div class="relative flex-1 min-w-[240px]">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
      </svg>
      <input id="repairSearch" type="text" placeholder="Search by machine name, serial, brought by, problem…"
             class="pl-9 pr-4" oninput="loadRepairs()"/>
    </div>
    <select id="repairStatusFilter" onchange="loadRepairs()" class="w-auto">
      <option value="">All Statuses</option>
      <option value="IN_REPAIR">Currently In Repair</option>
      <option value="RETURNED">Returned / Delivered</option>
    </select>
  </div>

  <!-- Table -->
  <div class="glass rounded-2xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.07)">
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Machine / Serial</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Department</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Brought By & Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Problem Description</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Status</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Delivery Info</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-widest">Actions</th>
          </tr>
        </thead>
        <tbody id="repairsBody">
          <tr><td colspan="7" class="text-center py-10 text-slate-500">Loading repair records…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ═════════════════════════════════════════════════════════
     SECTION 2: PART REPLACEMENTS
     ═════════════════════════════════════════════════════════ -->
<div id="section-parts" class="space-y-4 hidden">
  <!-- Toolbar -->
  <div class="flex flex-wrap items-center gap-3">
    <div class="relative flex-1 min-w-[240px]">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
      </svg>
      <input id="partSearch" type="text" placeholder="Search by part name, part serial, department, description…"
             class="pl-9 pr-4" oninput="loadParts()"/>
    </div>
  </div>

  <!-- Table -->
  <div class="glass rounded-2xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.07)">
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Part Name</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Serial NO</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Department</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Date Replaced</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Associated Machine / Issue</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Notes</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-widest">Actions</th>
          </tr>
        </thead>
        <tbody id="partsBody">
          <tr><td colspan="7" class="text-center py-10 text-slate-500">Loading part replacement records…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ═════════════════════════════════════════════════════════
     MODALS
     ═════════════════════════════════════════════════════════ -->

<!-- 1. Repair Intake Modal -->
<div id="intakeModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:540px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Machine Repair Intake</h3>
        <p class="text-xs text-slate-400">Record machine details when brought for repair</p>
      </div>
    </div>
    <form id="intakeForm" onsubmit="submitIntake(event)">
      <input type="hidden" id="intake_equipment_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Select Department *</label>
          <select id="intake_department" required onchange="onIntakeDepartmentChange()">
            <option value="">Select Department…</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Select Machine from Inventory *</label>
          <select id="intake_machine_select" required onchange="onIntakeMachineSelectChange()" disabled>
            <option value="">Select Department first…</option>
          </select>
        </div>
        <div id="manual_machine_fields" class="col-span-2 grid grid-cols-2 gap-3 hidden">
          <div class="form-group">
            <label>Machine Name *</label>
            <input type="text" id="intake_machine_name" placeholder="e.g. Ventilator Pro X200"/>
          </div>
          <div class="form-group">
            <label>Serial NO *</label>
            <input type="text" id="intake_serial_number" placeholder="SN-123456" class="font-mono"/>
          </div>
        </div>
        <div class="form-group">
          <label title="Person Who Brought Machine">Brought By *</label>
          <input type="text" id="intake_brought_by" required placeholder="Full Name"/>
        </div>
        <div class="form-group">
          <label>Date Brought *</label>
          <input type="date" id="intake_date" required value="<?= date('Y-m-d') ?>"/>
        </div>
        <div class="form-group col-span-2">
          <label>Machine Problem *</label>
          <textarea id="intake_machine_problem" rows="3" required placeholder="Describe the problem or fault in detail…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="intakeError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Save Repair Intake</button>
        <button type="button" onclick="closeIntakeModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Repair Delivery Modal (Return) -->
<div id="returnModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:520px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-green-500/20 border border-green-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Record Machine Delivery</h3>
        <p class="text-xs text-slate-400">Record when a repaired machine is collected</p>
      </div>
    </div>
    <form id="returnForm" onsubmit="submitReturn(event)">
      <input type="hidden" id="return_repair_id"/>
      <div class="glass p-3 rounded-xl border border-white/5 mb-4 space-y-1 text-xs">
        <p class="text-slate-400">Machine: <span id="return_machine_name" class="font-bold text-white">—</span></p>
        <p class="text-slate-400">Serial NO: <span id="return_serial_number" class="font-mono text-teal-300">—</span></p>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label title="Person Who Collected Machine">Collected By *</label>
          <input type="text" id="return_received_by" required placeholder="Full Name of person collecting"/>
        </div>
        <div class="form-group">
          <label>Delivery Department</label>
          <select id="return_delivery_department">
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group">
          <label>Delivery Date *</label>
          <input type="date" id="return_date" required value="<?= date('Y-m-d') ?>"/>
        </div>
        <div class="form-group col-span-2">
          <label>Remarks</label>
          <textarea id="return_delivery_remarks" rows="2" placeholder="Additional delivery remarks or notes…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="returnError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Confirm Delivery</button>
        <button type="button" onclick="closeReturnModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Edit Repair Modal -->
<div id="editRepairModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:540px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-bold text-white">Edit Repair Log</h3>
        <p class="text-xs text-slate-400">Modify machine repair details</p>
      </div>
    </div>
    <form id="editRepairForm" onsubmit="submitEditRepair(event)">
      <input type="hidden" id="edit_repair_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Machine Name *</label>
          <input type="text" id="edit_machine_name" required/>
        </div>
        <div class="form-group">
          <label>Serial NO *</label>
          <input type="text" id="edit_serial_number" required class="font-mono"/>
        </div>
        <div class="form-group">
          <label>Department *</label>
          <select id="edit_department" required>
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group">
          <label>Brought By</label>
          <input type="text" id="edit_brought_by"/>
        </div>
        <div class="form-group">
          <label>Intake Date</label>
          <input type="date" id="edit_intake_date"/>
        </div>
        <div class="form-group col-span-2">
          <label>Machine Problem</label>
          <textarea id="edit_machine_problem" rows="2" style="resize:vertical"></textarea>
        </div>
        <div class="form-group">
          <label>Collected By</label>
          <input type="text" id="edit_received_by"/>
        </div>
        <div class="form-group">
          <label>Delivery Department</label>
          <select id="edit_delivery_department">
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Delivery Remarks</label>
          <textarea id="edit_delivery_remarks" rows="2" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="editRepairError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Save Changes</button>
        <button type="button" onclick="closeEditRepairModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- 4. Part Replacement Modal (Add/Edit) -->
<div id="partModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:520px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>
        </svg>
      </div>
      <div>
        <h3 id="partModalTitle" class="text-lg font-bold text-white">Record Replaced Part</h3>
        <p class="text-xs text-slate-400">Log details when a part in a machine is replaced</p>
      </div>
    </div>
    <form id="partForm" onsubmit="submitPart(event)">
      <input type="hidden" id="part_replacement_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Part Name *</label>
          <input type="text" id="part_name" required placeholder="e.g. Pump Head Assembly, Display Panel"/>
        </div>
        <div class="form-group">
          <label>Part Serial NO *</label>
          <input type="text" id="part_serial_number" required placeholder="PRT-SN-001" class="font-mono"/>
        </div>
        <div class="form-group">
          <label>Department (Optional)</label>
          <select id="part_department">
            <option value="">Select Department…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option><option>Surgery</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Part Description</label>
          <textarea id="part_description" rows="2" placeholder="Describe the part or function…" style="resize:vertical"></textarea>
        </div>
        <div class="form-group">
          <label>Date Replaced *</label>
          <input type="date" id="part_replacement_date" required value="<?= date('Y-m-d') ?>"/>
        </div>
        <div class="form-group">
          <label>Linked Repair Intake</label>
          <select id="part_repair_id">
            <option value="">None / Standalone</option>
          </select>
        </div>
        <div class="form-group col-span-2">
          <label>Notes (Optional)</label>
          <textarea id="part_notes" rows="2" placeholder="Additional details about the replacement…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="partError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-2">
        <button type="submit" class="btn-primary flex-1">Save Part Replacement</button>
        <button type="button" onclick="closePartModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentSection = 'repairs';
let cachedRepairs = [];
let cachedParts = [];

function switchSectionTab(tab) {
  currentSection = tab;
  document.getElementById('section-repairs').classList.toggle('hidden', tab !== 'repairs');
  document.getElementById('section-parts').classList.toggle('hidden', tab !== 'parts');

  const repairsBtn = document.getElementById('tab-repairs-btn');
  const partsBtn   = document.getElementById('tab-parts-btn');

  if (tab === 'repairs') {
    repairsBtn.className = 'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 bg-teal-600/20 text-teal-300 border border-teal-500/30';
    partsBtn.className   = 'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 text-slate-400 hover:text-white hover:bg-white/5';
    loadRepairs();
  } else {
    partsBtn.className   = 'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 bg-purple-600/20 text-purple-300 border border-purple-500/30';
    repairsBtn.className = 'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 text-slate-400 hover:text-white hover:bg-white/5';
    loadParts();
  }
}

// ── Load Repairs ──────────────────────────────────────────
async function loadRepairs() {
  const search = document.getElementById('repairSearch').value;
  const status = document.getElementById('repairStatusFilter').value;
  const params = new URLSearchParams({ search, status });

  const res  = await fetch('/api/repairs/list.php?' + params);
  const data = await res.json();
  cachedRepairs = Array.isArray(data) ? data : [];
  populateRepairDropdown();

  const body = document.getElementById('repairsBody');
  if (cachedRepairs.length === 0) {
    body.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-slate-500">No repair records match your criteria.</td></tr>';
    return;
  }

  body.innerHTML = cachedRepairs.map(r => {
    const isRepairing = r.status === 'IN_REPAIR';
    const statusBadgeHTML = isRepairing 
      ? '<span class="badge badge-in-repair">IN REPAIR</span>'
      : '<span class="badge badge-operational">RETURNED</span>';

    const deliveryInfo = r.status === 'RETURNED'
      ? `<div class="text-xs">
          <p class="text-slate-200 font-medium">Collected by: ${escHTML(r.received_by || '—')}</p>
          <p class="text-slate-500">Dept: ${escHTML(r.delivery_department || r.department || '—')} · ${r.return_date || ''}</p>
          ${r.delivery_remarks ? `<p class="text-slate-400 italic mt-0.5">${escHTML(r.delivery_remarks)}</p>` : ''}
         </div>`
      : '<span class="text-xs text-slate-500 italic">Pending delivery</span>';

    return `
      <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
        <td class="px-4 py-3">
          <p class="font-bold text-white">${escHTML(r.machine_name)}</p>
          <p class="text-xs font-mono text-teal-300">${escHTML(r.serial_number)}</p>
        </td>
        <td class="px-4 py-3 text-slate-300">${escHTML(r.department)}</td>
        <td class="px-4 py-3 text-xs">
          <p class="text-slate-200 font-medium">${escHTML(r.brought_by)}</p>
          <p class="text-slate-500">${r.intake_date}</p>
        </td>
        <td class="px-4 py-3 text-xs text-slate-300 max-w-xs truncate" title="${escHTML(r.machine_problem)}">${escHTML(r.machine_problem)}</td>
        <td class="px-4 py-3">${statusBadgeHTML}</td>
        <td class="px-4 py-3">${deliveryInfo}</td>
        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
          ${isRepairing ? `
            <button onclick="openReturnModal(${r.repair_id}, '${escapeQuotes(r.machine_name)}', '${escapeQuotes(r.serial_number)}')" 
                    class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-green-600/20 border border-green-500/30 text-green-300 hover:bg-green-600/30 transition-colors">
              Record Delivery
            </button>
          ` : ''}
          ${CAN_EDIT ? `
            <button onclick="openEditRepairModal(${r.repair_id})" class="text-xs px-2 py-1 rounded-lg bg-slate-700/50 border border-slate-600/30 text-slate-300 hover:bg-slate-700 transition-colors">Edit</button>
          ` : ''}
          ${CAN_DELETE ? `
            <button onclick="deleteRepair(${r.repair_id})" class="text-xs px-2 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 transition-colors">Delete</button>
          ` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

function escapeQuotes(str) {
  return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function escHTML(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function populateRepairDropdown() {
  const sel = document.getElementById('part_repair_id');
  if (!sel) return;
  const inRepair = cachedRepairs.filter(r => r.status === 'IN_REPAIR');
  sel.innerHTML = '<option value="">None / Standalone</option>' + inRepair.map(r => 
    `<option value="${r.repair_id}">${escHTML(r.machine_name)} (${escHTML(r.serial_number)}) — ${escHTML(r.department)}</option>`
  ).join('');
}

// ── Load Part Replacements ─────────────────────────────────
async function loadParts() {
  const search = document.getElementById('partSearch').value;
  const res  = await fetch('/api/parts/list.php?search=' + encodeURIComponent(search));
  const data = await res.json();
  cachedParts = Array.isArray(data) ? data : [];
  const body  = document.getElementById('partsBody');

  if (cachedParts.length === 0) {
    body.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-slate-500">No part replacement records found.</td></tr>';
    return;
  }

  body.innerHTML = cachedParts.map(p => `
    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
      <td class="px-4 py-3">
        <p class="font-bold text-white">${escHTML(p.part_name)}</p>
        ${p.description ? `<p class="text-xs text-slate-400 truncate max-w-xs">${escHTML(p.description)}</p>` : ''}
      </td>
      <td class="px-4 py-3 font-mono text-purple-300 text-xs">${escHTML(p.part_serial_number)}</td>
      <td class="px-4 py-3 text-slate-300">${escHTML(p.department)}</td>
      <td class="px-4 py-3 text-xs text-slate-400">${p.replacement_date}</td>
      <td class="px-4 py-3 text-xs">
        ${p.issue_description ? `<p class="text-purple-300 font-medium">🔗 Issue #${p.linked_ticket_id || ''}: ${escHTML(p.issue_description?.substring(0,35))}…</p>` : ''}
        ${p.repair_machine ? `<p class="text-slate-200">🛠 Repair: ${escHTML(p.repair_machine)} <span class="font-mono text-slate-500">(${escHTML(p.repair_serial)})</span></p>` : (p.equip_name ? `<p class="text-slate-200">${escHTML(p.equip_name)}</p>` : '<span class="text-slate-500 italic">None</span>')}
      </td>
      <td class="px-4 py-3 text-xs text-slate-400">${escHTML(p.notes || '—')}</td>
      <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
        ${CAN_EDIT ? `<button onclick="editPartModal(${p.part_replacement_id})" class="text-xs px-2 py-1 rounded-lg bg-slate-700/50 border border-slate-600/30 text-slate-300 hover:bg-slate-700 transition-colors">Edit</button>` : ''}
        ${CAN_DELETE ? `<button onclick="deletePartRecord(${p.part_replacement_id})" class="text-xs px-2 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 hover:bg-red-500/20 transition-colors">Delete</button>` : ''}
      </td>
    </tr>
  `).join('');
}

// ── Modal Actions & Dynamic Dropdowns ─────────────────────
function openIntakeModal() {
  document.getElementById('intakeForm').reset();
  document.getElementById('intake_equipment_id').value = '';
  populateDepartmentSelect('intake_department', 'Select Department…');

  const macSelect = document.getElementById('intake_machine_select');
  macSelect.innerHTML = '<option value="">Select Department first…</option>';
  macSelect.disabled = true;

  document.getElementById('manual_machine_fields').classList.add('hidden');
  document.getElementById('intake_machine_name').required = false;
  document.getElementById('intake_serial_number').required = false;

  document.getElementById('intakeError').classList.add('hidden');
  document.getElementById('intakeModal').classList.remove('hidden');
}

function closeIntakeModal() {
  document.getElementById('intakeModal').classList.add('hidden');
}

let departmentEquipmentMap = [];

async function onIntakeDepartmentChange() {
  const dept = document.getElementById('intake_department').value;
  const macSelect = document.getElementById('intake_machine_select');
  const manualFields = document.getElementById('manual_machine_fields');
  const nameInput = document.getElementById('intake_machine_name');
  const serialInput = document.getElementById('intake_serial_number');
  const eqIdInput = document.getElementById('intake_equipment_id');

  eqIdInput.value = '';
  nameInput.value = '';
  serialInput.value = '';

  if (!dept) {
    macSelect.innerHTML = '<option value="">Select Department first…</option>';
    macSelect.disabled = true;
    manualFields.classList.add('hidden');
    nameInput.required = false;
    serialInput.required = false;
    return;
  }

  macSelect.disabled = true;
  macSelect.innerHTML = '<option value="">Loading equipment…</option>';

  try {
    const res = await fetch('/api/equipment/list.php?location=' + encodeURIComponent(dept));
    const data = await res.json();
    departmentEquipmentMap = data.equipment || [];

    let options = '<option value="">Select Machine from ' + escHTML(dept) + '…</option>';
    departmentEquipmentMap.forEach(e => {
      options += `<option value="${e.equipment_id}" data-name="${escHTML(e.name)}" data-serial="${escHTML(e.serial_number || '')}">${escHTML(e.name)} (SN: ${escHTML(e.serial_number || 'N/A')})</option>`;
    });
    options += '<option value="OTHER">+ Other Machine (Not in Inventory)</option>';

    macSelect.innerHTML = options;
    macSelect.disabled = false;
  } catch(err) {
    macSelect.innerHTML = '<option value="OTHER">+ Other Machine (Not in Inventory)</option>';
    macSelect.disabled = false;
  }
}

function onIntakeMachineSelectChange() {
  const macSelect = document.getElementById('intake_machine_select');
  const val = macSelect.value;
  const manualFields = document.getElementById('manual_machine_fields');
  const nameInput = document.getElementById('intake_machine_name');
  const serialInput = document.getElementById('intake_serial_number');
  const eqIdInput = document.getElementById('intake_equipment_id');

  if (val === 'OTHER') {
    eqIdInput.value = '';
    nameInput.value = '';
    serialInput.value = '';
    nameInput.required = true;
    serialInput.required = true;
    manualFields.classList.remove('hidden');
  } else if (val) {
    const opt = macSelect.options[macSelect.selectedIndex];
    eqIdInput.value = val;
    nameInput.value = opt.getAttribute('data-name') || '';
    serialInput.value = opt.getAttribute('data-serial') || '';
    nameInput.required = false;
    serialInput.required = false;
    manualFields.classList.add('hidden');
  } else {
    eqIdInput.value = '';
    nameInput.value = '';
    serialInput.value = '';
    nameInput.required = false;
    serialInput.required = false;
    manualFields.classList.add('hidden');
  }
}

function openReturnModal(id, name, serial) {
  document.getElementById('return_repair_id').value = id;
  document.getElementById('return_machine_name').textContent = name;
  document.getElementById('return_serial_number').textContent = serial;
  document.getElementById('returnForm').reset();
  document.getElementById('return_repair_id').value = id;
  document.getElementById('return_date').value = new Date().toISOString().split('T')[0];
  populateDepartmentSelect('return_delivery_department', 'Select Department…');
  document.getElementById('returnError').classList.add('hidden');
  document.getElementById('returnModal').classList.remove('hidden');
}
function closeReturnModal() {
  document.getElementById('returnModal').classList.add('hidden');
}

function openEditRepairModal(repairId) {
  const r = cachedRepairs.find(x => x.repair_id == repairId);
  if (!r) return;
  document.getElementById('edit_repair_id').value = repairId;
  document.getElementById('edit_machine_name').value = r.machine_name || '';
  document.getElementById('edit_serial_number').value = r.serial_number || '';
  populateDepartmentSelect('edit_department', 'Select Department…', r.department || '');
  document.getElementById('edit_brought_by').value = r.brought_by || '';
  document.getElementById('edit_intake_date').value = r.intake_date || '';
  document.getElementById('edit_machine_problem').value = r.machine_problem || '';
  document.getElementById('edit_received_by').value = r.received_by || '';
  populateDepartmentSelect('edit_delivery_department', 'Select Department…', r.delivery_department || '');
  document.getElementById('edit_delivery_remarks').value = r.delivery_remarks || '';
  document.getElementById('editRepairError').classList.add('hidden');
  document.getElementById('editRepairModal').classList.remove('hidden');
}

function closeEditRepairModal() {
  document.getElementById('editRepairModal').classList.add('hidden');
}

function openPartModal() {
  document.getElementById('partModalTitle').textContent = 'Record Replaced Part';
  document.getElementById('partForm').reset();
  document.getElementById('part_replacement_id').value = '';
  document.getElementById('part_replacement_date').value = new Date().toISOString().split('T')[0];
  populateDepartmentSelect('part_department', 'Select Department…');
  document.getElementById('partError').classList.add('hidden');
  document.getElementById('partModal').classList.remove('hidden');
}

function editPartModal(partId) {
  const p = cachedParts.find(x => x.part_replacement_id == partId);
  if (!p) return;
  document.getElementById('partModalTitle').textContent = 'Edit Replaced Part';
  document.getElementById('part_replacement_id').value = partId;
  document.getElementById('part_name').value = p.part_name || '';
  document.getElementById('part_serial_number').value = p.part_serial_number || '';
  populateDepartmentSelect('part_department', 'Select Department…', p.department || '');
  document.getElementById('part_description').value = p.description || '';
  document.getElementById('part_replacement_date').value = p.replacement_date || '';
  document.getElementById('part_repair_id').value = p.repair_id || '';
  document.getElementById('part_notes').value = p.notes || '';
  document.getElementById('partError').classList.add('hidden');
  document.getElementById('partModal').classList.remove('hidden');
}

function closePartModal() {
  document.getElementById('partModal').classList.add('hidden');
}

// ── Submit Handlers ────────────────────────────────────────
async function submitIntake(e) {
  e.preventDefault();
  const err = document.getElementById('intakeError');
  err.classList.add('hidden');

  const payload = {
    equipment_id:    document.getElementById('intake_equipment_id').value || null,
    machine_name:    document.getElementById('intake_machine_name').value,
    serial_number:   document.getElementById('intake_serial_number').value,
    department:      document.getElementById('intake_department').value,
    brought_by:      document.getElementById('intake_brought_by').value,
    intake_date:     document.getElementById('intake_date').value,
    machine_problem: document.getElementById('intake_machine_problem').value,
  };

  if (!payload.machine_name || !payload.serial_number) {
    err.textContent = 'Please select a machine or enter the machine details.';
    err.classList.remove('hidden');
    return;
  }

  const res  = await fetch('/api/repairs/intake.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closeIntakeModal();
    loadRepairs();
    showCustomAlert('Intake Logged', 'Machine intake recorded successfully.', 'success');
  } else {
    err.textContent = json.error || 'Failed to log intake.';
    err.classList.remove('hidden');
  }
}

async function submitReturn(e) {
  e.preventDefault();
  const err = document.getElementById('returnError');
  err.classList.add('hidden');

  const payload = {
    repair_id:            document.getElementById('return_repair_id').value,
    return_date:          document.getElementById('return_date').value,
    received_by:          document.getElementById('return_received_by').value,
    delivery_department:  document.getElementById('return_delivery_department').value,
    delivery_remarks:     document.getElementById('return_delivery_remarks').value,
  };

  const res  = await fetch('/api/repairs/return.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closeReturnModal();
    loadRepairs();
    showCustomAlert('Delivery Logged', 'Machine delivery recorded successfully.', 'success');
  } else {
    err.textContent = json.error || 'Failed to log delivery.';
    err.classList.remove('hidden');
  }
}

async function submitEditRepair(e) {
  e.preventDefault();
  const err = document.getElementById('editRepairError');
  err.classList.add('hidden');

  const payload = {
    repair_id:            document.getElementById('edit_repair_id').value,
    machine_name:         document.getElementById('edit_machine_name').value,
    serial_number:        document.getElementById('edit_serial_number').value,
    department:           document.getElementById('edit_department').value,
    brought_by:           document.getElementById('edit_brought_by').value,
    intake_date:          document.getElementById('edit_intake_date').value,
    machine_problem:      document.getElementById('edit_machine_problem').value,
    received_by:          document.getElementById('edit_received_by').value,
    delivery_department:  document.getElementById('edit_delivery_department').value,
    delivery_remarks:     document.getElementById('edit_delivery_remarks').value,
  };

  const res = await fetch('/api/repairs/update.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closeEditRepairModal();
    loadRepairs();
    showCustomAlert('Updated', 'Repair record updated successfully.', 'success');
  } else {
    err.textContent = json.error || 'Failed to update repair record.';
    err.classList.remove('hidden');
  }
}

async function deleteRepair(repairId) {
  const confirmed = await showCustomConfirm('Delete Repair Record', 'Are you sure you want to delete this repair record? This action cannot be undone.', 'Delete', true);
  if (!confirmed) return;

  const res = await fetch('/api/repairs/delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ repair_id: repairId })
  });
  const json = await res.json();

  if (json.success) {
    loadRepairs();
    showCustomAlert('Deleted', 'Repair record deleted.', 'success');
  } else {
    showCustomAlert('Delete Failed', json.error || 'Failed to delete record.', 'error');
  }
}

async function submitPart(e) {
  e.preventDefault();
  const err = document.getElementById('partError');
  err.classList.add('hidden');

  const isEdit = !!document.getElementById('part_replacement_id').value;
  const url = isEdit ? '/api/parts/update.php' : '/api/parts/create.php';

  const payload = {
    part_replacement_id: document.getElementById('part_replacement_id').value,
    part_name:          document.getElementById('part_name').value,
    part_serial_number: document.getElementById('part_serial_number').value,
    department:         document.getElementById('part_department').value,
    description:        document.getElementById('part_description').value,
    replacement_date:   document.getElementById('part_replacement_date').value,
    repair_id:          document.getElementById('part_repair_id').value,
    notes:              document.getElementById('part_notes').value,
  };

  const res  = await fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await res.json();

  if (json.success) {
    closePartModal();
    loadParts();
    showCustomAlert(isEdit ? 'Part Updated' : 'Part Logged', 'Part replacement saved successfully.', 'success');
  } else {
    err.textContent = json.error || 'Failed to save part replacement.';
    err.classList.remove('hidden');
  }
}

async function deletePartRecord(partId) {
  const confirmed = await showCustomConfirm('Delete Part Record', 'Are you sure you want to delete this part replacement record?', 'Delete', true);
  if (!confirmed) return;

  const res = await fetch('/api/parts/delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ part_replacement_id: partId })
  });
  const json = await res.json();

  if (json.success) {
    loadParts();
    showCustomAlert('Deleted', 'Part replacement record deleted.', 'success');
  } else {
    showCustomAlert('Delete Failed', json.error || 'Failed to delete record.', 'error');
  }
}

// Initialize
loadRepairs();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
