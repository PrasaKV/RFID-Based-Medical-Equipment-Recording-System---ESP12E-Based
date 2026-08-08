<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Inventory';
$user      = currentUser();
$db        = getDB();

include __DIR__ . '/includes/header.php';
?>

<!-- ── Toolbar ─────────────────────────────────────────────── -->
<div class="flex flex-wrap items-center gap-3 mb-5">
  <!-- Search -->
  <div class="relative flex-1 min-w-[200px]">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
    </svg>
    <input id="searchBox" type="text" placeholder="Search by name, serial, tag ID…"
           class="pl-9 pr-4" oninput="debounceLoad()" style="background:#111827;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;padding:0.6rem 1rem 0.6rem 2.25rem;color:#e2e8f0;width:100%;outline:none;transition:border-color 0.2s;"
           onfocus="this.style.borderColor='#0d9488'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"/>
  </div>

  <!-- Filters -->
  <select id="filterCategory" class="w-auto" onchange="loadInventory()" style="background:#111827;border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:0.75rem;padding:0.6rem 0.9rem;outline:none;font-size:0.875rem;">
    <option value="">All Categories</option>
    <option>Imaging</option><option>Life Support</option>
    <option>Monitoring</option><option>Lab</option><option>Other</option>
  </select>
  <select id="filterStatus" class="w-auto" onchange="loadInventory()" style="background:#111827;border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:0.75rem;padding:0.6rem 0.9rem;outline:none;font-size:0.875rem;">
    <option value="">All Statuses</option>
    <option>OPERATIONAL</option><option>PM_DUE</option>
    <option>IN_REPAIR</option><option>NEEDS_ATTENTION</option><option>DECOMMISSIONED</option>
  </select>
  <select id="filterLocation" class="w-auto" onchange="loadInventory()" style="background:#111827;border:1px solid rgba(255,255,255,0.1);color:#e2e8f0;border-radius:0.75rem;padding:0.6rem 0.9rem;outline:none;font-size:0.875rem;">
    <option value="">All Locations</option>
    <option>ICU</option><option>Emergency</option><option>Ward A</option>
    <option>Ward B</option><option>OPD</option><option>Radiology</option>
    <option>Lab</option><option>Storage</option>
  </select>

  <!-- Add button -->
  <button onclick="openAddModal()" class="btn-primary flex items-center gap-2 whitespace-nowrap">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
    </svg>
    Add Device
  </button>
</div>

<!-- ── Results count ──────────────────────────────────────── -->
<div class="mb-3 flex items-center justify-between">
  <p class="text-sm text-slate-500"><span id="resultCount">—</span> devices found</p>
</div>

<!-- ── Table ──────────────────────────────────────────────── -->
<div class="glass rounded-2xl border border-white/5 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.07)">
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Device</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest hidden sm:table-cell">Serial / Tag</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest hidden md:table-cell">Category</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Status</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest hidden lg:table-cell">Location</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-widest hidden xl:table-cell">Next PM</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody id="inventoryBody">
        <tr><td colspan="7" class="text-center py-10 text-slate-500">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Add / Edit Equipment Modal ────────────────────────── -->
<div id="equipModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:600px">
    <div class="flex items-center gap-3 mb-5">
      <div class="w-9 h-9 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center">
        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
      </div>
      <div>
        <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Device</h3>
        <p class="text-xs text-slate-400">Fill in the equipment details below</p>
      </div>
    </div>
    <form id="equipForm" onsubmit="submitEquipForm(event)">
      <input type="hidden" id="eq_id" name="equipment_id"/>
      <div class="grid grid-cols-2 gap-3">
        <div class="form-group col-span-2">
          <label>Device Name *</label>
          <input type="text" id="eq_name" name="name" required placeholder="e.g. Ventilator Pro X200"/>
        </div>
        <div class="form-group">
          <label>RFID Tag ID</label>
          <input type="text" id="eq_rfid" name="rfid_tag_id" class="font-mono" placeholder="TAG-001"/>
        </div>
        <div class="form-group">
          <label>Serial Number</label>
          <input type="text" id="eq_serial" name="serial_number" placeholder="SN-000"/>
        </div>
        <div class="form-group">
          <label>Model</label>
          <input type="text" id="eq_model" name="model" placeholder="Model number"/>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select id="eq_category" name="category">
            <option>Imaging</option><option>Life Support</option>
            <option>Monitoring</option><option>Lab</option><option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="eq_status" name="status">
            <option>OPERATIONAL</option><option>PM_DUE</option>
            <option>IN_REPAIR</option><option>NEEDS_ATTENTION</option><option>DECOMMISSIONED</option>
          </select>
        </div>
        <div class="form-group">
          <label>Department</label>
          <select id="eq_location" name="current_location">
            <option value="">Select…</option>
            <option>ICU</option><option>Emergency</option><option>Ward A</option>
            <option>Ward B</option><option>OPD</option><option>Radiology</option>
            <option>Lab</option><option>Storage</option>
          </select>
        </div>
        <div class="form-group">
          <label>PM Interval (days)</label>
          <input type="number" id="eq_pm" name="pm_interval_days" value="90" min="1"/>
        </div>
        <div class="form-group">
          <label>Last Maintenance Date</label>
          <input type="date" id="eq_lastpm" name="last_maintenance_date"/>
        </div>
        <div class="form-group">
          <label>Procurement Date</label>
          <input type="date" id="eq_procdate" name="procurement_date"/>
        </div>
        <div class="form-group col-span-2">
          <label>Description</label>
          <textarea id="eq_description" name="description" rows="2" placeholder="Brief description of the machine…" style="resize:vertical"></textarea>
        </div>
        <div class="form-group col-span-2">
          <label>Notes</label>
          <textarea id="eq_notes" name="notes" rows="2" placeholder="Optional notes…" style="resize:vertical"></textarea>
        </div>
      </div>
      <div id="equipError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex items-center justify-between gap-3 mt-1">
        <button type="button" id="deleteDeviceBtn" onclick="deleteDevice()" class="hidden text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 transition-colors">
          Delete Device
        </button>
        <div class="flex gap-2 ml-auto">
          <button type="button" onclick="closeEquipModal()" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary">Save Device</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// ── Inventory load ────────────────────────────────────────
let debounceTimer;
function debounceLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(loadInventory, 300);
}

const statusBadgeHTML = {
  'OPERATIONAL':     '<span class="badge badge-operational">OPERATIONAL</span>',
  'PM_DUE':          '<span class="badge badge-pm-due">PM DUE</span>',
  'IN_REPAIR':       '<span class="badge badge-in-repair">IN REPAIR</span>',
  'NEEDS_ATTENTION': '<span class="badge badge-needs-attention">NEEDS ATTN</span>',
  'DECOMMISSIONED':  '<span class="badge badge-decommissioned">DECOMM.</span>',
};

let inventoryEquip = [];

async function loadInventory() {
  const search   = document.getElementById('searchBox').value;
  const category = document.getElementById('filterCategory').value;
  const status   = document.getElementById('filterStatus').value;
  const location = document.getElementById('filterLocation').value;

  const params = new URLSearchParams({ search, category, status, location });
  const res    = await fetch('/api/equipment/list.php?' + params);
  const data   = await res.json();
  inventoryEquip = data.equipment || [];

  document.getElementById('resultCount').textContent = inventoryEquip.length;
  const body = document.getElementById('inventoryBody');

  if (inventoryEquip.length === 0) {
    body.innerHTML = '<tr><td colspan="7" class="text-center py-12 text-slate-500">No devices match your filters.</td></tr>';
    return;
  }

  body.innerHTML = inventoryEquip.map(e => {
    const nextPm = e.next_pm_date ? new Date(e.next_pm_date) : null;
    const today  = new Date();
    const daysLeft = nextPm ? Math.ceil((nextPm - today) / 86400000) : null;
    const pmClass  = daysLeft === null ? '' : daysLeft < 0 ? 'text-red-400' : daysLeft <= 7 ? 'text-amber-400' : 'text-slate-400';
    const pmLabel  = daysLeft === null ? '—' : daysLeft < 0 ? Math.abs(daysLeft)+'d overdue' : daysLeft+'d left';
    return `<tr class="border-t border-white/5 hover:bg-white/3 cursor-pointer transition-colors" onclick="window.location='/device.php?id=${e.equipment_id}'">
      <td class="px-4 py-3">
        <p class="font-medium text-white">${e.name}</p>
        <p class="text-xs text-slate-500 font-mono">${e.model || ''}</p>
      </td>
      <td class="px-4 py-3 hidden sm:table-cell">
        <p class="text-xs text-slate-300 font-mono">${e.serial_number || '—'}</p>
        <p class="text-xs text-teal-400 font-mono">${e.rfid_tag_id || '—'}</p>
      </td>
      <td class="px-4 py-3 hidden md:table-cell text-xs text-slate-400">${e.category}</td>
      <td class="px-4 py-3">${statusBadgeHTML[e.status] || e.status}</td>
      <td class="px-4 py-3 hidden lg:table-cell text-xs text-slate-400">${e.current_location || '—'}</td>
      <td class="px-4 py-3 hidden xl:table-cell text-xs ${pmClass} font-medium">${pmLabel}</td>
      <td class="px-4 py-3 text-right whitespace-nowrap">
        ${CAN_EDIT ? `<button onclick="event.stopPropagation(); openEditModal(${e.equipment_id})"
          class="text-xs px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors mr-1">
          Edit
        </button>` : ''}
        ${CAN_DELETE ? `<button onclick="event.stopPropagation(); deleteDevice(${e.equipment_id})"
          class="text-xs px-2.5 py-1.5 rounded-lg text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 transition-colors">
          Delete
        </button>` : ''}
      </td>
    </tr>`;
  }).join('');
}

function onMasterDataLoaded() {
  populateCategorySelect('filterCategory', 'All Categories');
  populateDepartmentSelect('filterLocation', 'All Locations');
}

function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add New Device';
  document.getElementById('equipForm').reset();
  document.getElementById('eq_id').value = '';
  populateCategorySelect('eq_category', 'Select Category…');
  populateDepartmentSelect('eq_location', 'Select Department…');
  document.getElementById('deleteDeviceBtn').classList.add('hidden');
  document.getElementById('equipError').classList.add('hidden');
  document.getElementById('equipModal').classList.remove('hidden');
}

function openEditModal(eqParam) {
  const eq = (typeof eqParam === 'object') ? eqParam : inventoryEquip.find(x => x.equipment_id == eqParam);
  if (!eq) return;
  document.getElementById('modalTitle').textContent = 'Edit Device';
  document.getElementById('eq_id').value        = eq.equipment_id;
  document.getElementById('eq_name').value      = eq.name || '';
  document.getElementById('eq_rfid').value      = eq.rfid_tag_id || '';
  document.getElementById('eq_serial').value    = eq.serial_number || '';
  document.getElementById('eq_model').value       = eq.model || '';
  document.getElementById('eq_description').value = eq.description || '';
  populateCategorySelect('eq_category', 'Select Category…', eq.category || 'Other');
  document.getElementById('eq_status').value    = eq.status || 'OPERATIONAL';
  populateDepartmentSelect('eq_location', 'Select Department…', eq.current_location || '');
  document.getElementById('eq_pm').value        = eq.pm_interval_days || 90;
  document.getElementById('eq_lastpm').value    = eq.last_maintenance_date || '';
  document.getElementById('eq_procdate').value  = eq.procurement_date || '';
  document.getElementById('eq_notes').value     = eq.notes || '';
  if (CAN_DELETE) {
    document.getElementById('deleteDeviceBtn').classList.remove('hidden');
  } else {
    document.getElementById('deleteDeviceBtn').classList.add('hidden');
  }
  document.getElementById('equipError').classList.add('hidden');
  document.getElementById('equipModal').classList.remove('hidden');
}

function closeEquipModal() {
  document.getElementById('equipModal').classList.add('hidden');
}

async function submitEquipForm(e) {
  e.preventDefault();
  const errEl = document.getElementById('equipError');
  errEl.classList.add('hidden');
  const data = Object.fromEntries(new FormData(document.getElementById('equipForm')));
  const isEdit = !!data.equipment_id;
  const url = isEdit ? '/api/equipment/update.php' : '/api/equipment/create.php';
  const res  = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
  const json = await res.json();
  if (json.success || json.equipment_id) {
    closeEquipModal();
    loadInventory();
  } else {
    errEl.textContent = json.error || 'Failed to save.';
    errEl.classList.remove('hidden');
  }
}

async function deleteDevice(id, name) {
  const devId   = id || document.getElementById('eq_id').value;
  const devObj  = inventoryEquip.find(x => x.equipment_id == devId);
  const devName = name || (devObj ? devObj.name : (document.getElementById('eq_name').value || 'this device'));
  
  const confirmed = await showCustomConfirm(
    'Remove Device',
    `Are you sure you want to remove "${devName}"? This will permanently delete its location history and maintenance logs.`,
    'Remove Device',
    true
  );
  if (!confirmed) return;

  const res = await fetch('/api/equipment/delete.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ equipment_id: devId })
  });
  const json = await res.json();

  if (json.success) {
    closeEquipModal();
    loadInventory();
  } else {
    await showCustomAlert('Delete Failed', json.error || 'Failed to delete device.', 'error');
  }
}

// Boot
loadInventory();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
