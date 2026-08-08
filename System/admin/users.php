<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';

$db        = getDB();
$pageTitle = 'User & Role Management';
$user      = currentUser();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h3 class="text-base font-semibold text-white">System Users & Roles</h3>
    <p class="text-xs text-slate-400">Manage user accounts, passwords, access roles, and permissions.</p>
  </div>
  <div class="flex items-center gap-2">
    <button onclick="openManageRolesModal()" class="btn-secondary text-sm flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
      </svg>
      Manage Roles
    </button>
    <button onclick="openAddUser()" class="btn-primary flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      Add User
    </button>
  </div>
</div>

<!-- Users table -->
<div class="glass rounded-2xl border border-white/5 overflow-hidden">
  <table class="w-full text-sm">
    <thead>
      <tr style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.07)">
        <th class="text-left px-5 py-3 text-xs text-slate-500 uppercase tracking-widest">User</th>
        <th class="text-left px-5 py-3 text-xs text-slate-500 uppercase tracking-widest">Email</th>
        <th class="text-left px-5 py-3 text-xs text-slate-500 uppercase tracking-widest">Password</th>
        <th class="text-left px-5 py-3 text-xs text-slate-500 uppercase tracking-widest">Role</th>
        <th class="text-left px-5 py-3 text-xs text-slate-500 uppercase tracking-widest">Created</th>
        <th class="px-5 py-3 text-right">Actions</th>
      </tr>
    </thead>
    <tbody id="usersBody">
      <tr><td colspan="6" class="text-center py-10 text-slate-500">Loading users…</td></tr>
    </tbody>
  </table>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:440px">
    <h3 class="text-lg font-bold text-white mb-4">Add New User</h3>
    <div class="form-group">
      <label>Username *</label>
      <input type="text" id="new_username" placeholder="john_doe"/>
    </div>
    <div class="form-group">
      <label>Email *</label>
      <input type="email" id="new_email" placeholder="john@hospital.local"/>
    </div>
    <div class="form-group">
      <label>Password *</label>
      <input type="text" id="new_password" placeholder="Plain text password"/>
    </div>
    <div class="form-group">
      <label>Role *</label>
      <select id="new_role" class="role-select-options">
        <option value="engineer">engineer</option>
        <option value="admin">admin</option>
      </select>
    </div>
    <div id="addUserError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
    <div class="flex gap-3 mt-2">
      <button onclick="submitAddUser()" class="btn-primary flex-1">Create User</button>
      <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
    </div>
  </div>
</div>

<!-- Detailed User View / Edit Modal -->
<div id="userDetailModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:540px">
    <div class="flex items-start justify-between mb-4 border-b pb-3" style="border-color:rgba(255,255,255,0.08)">
      <div class="flex items-center gap-3">
        <div id="detailAvatar" class="w-12 h-12 rounded-2xl bg-teal-700/30 border border-teal-500/40 flex items-center justify-center text-teal-300 font-bold text-lg">
          U
        </div>
        <div>
          <h3 id="detailHeaderTitle" class="text-lg font-bold text-white">User Details</h3>
          <p id="detailHeaderSubtitle" class="text-xs text-slate-400">ID: —</p>
        </div>
      </div>
      <button onclick="closeUserDetailModal()" class="text-slate-400 hover:text-white p-1">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Edit User Form -->
    <form id="editUserForm" onsubmit="submitEditUser(event)">
      <input type="hidden" id="detail_user_id"/>
      <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="form-group">
          <label>Username</label>
          <input type="text" id="detail_username" required/>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select id="detail_role" class="role-select-options" required></select>
        </div>
        <div class="form-group col-span-2">
          <label>Email Address</label>
          <input type="email" id="detail_email" required/>
        </div>
        <div class="form-group col-span-2">
          <label>Password (Plain Text)</label>
          <div class="relative">
            <input type="password" id="detail_password" class="pr-10 font-mono" required/>
            <button type="button" onclick="toggleDetailPasswordVisibility()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200">
              <svg id="eyeIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- User Stats / Activity -->
      <div class="glass p-3 rounded-xl border border-white/5 mb-4 space-y-2 text-xs">
        <div class="flex justify-between">
          <span class="text-slate-400">Account Created:</span>
          <span id="detailCreated" class="font-medium text-slate-200">—</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Tickets Reported:</span>
          <span id="detailTicketCount" class="font-medium text-teal-400">0</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Maintenance Records:</span>
          <span id="detailMaintCount" class="font-medium text-teal-400">0</span>
        </div>
      </div>

      <div id="editUserError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>

      <div class="flex items-center justify-between gap-3 pt-2">
        <button type="button" id="deleteUserBtn" onclick="deleteCurrentUser()"
                class="px-3 py-2 rounded-xl text-xs font-semibold bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 transition-colors">
          Delete User
        </button>
        <div class="flex gap-2">
          <button type="button" onclick="closeUserDetailModal()" class="btn-secondary text-sm">Cancel</button>
          <button type="submit" class="btn-primary text-sm">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Manage Roles Modal -->
<div id="manageRolesModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:500px">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-white">Role Management</h3>
      <button onclick="document.getElementById('manageRolesModal').classList.add('hidden')" class="text-slate-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Create Role Form -->
    <div class="glass p-3 rounded-xl border border-white/5 mb-4">
      <p class="text-xs font-semibold text-slate-300 uppercase tracking-widest mb-2">Create Custom Role</p>
      <div class="flex gap-2 mb-2">
        <input type="text" id="new_role_name" placeholder="Role name (e.g. supervisor)" class="flex-1 text-sm"/>
        <button onclick="submitCreateRole()" class="btn-primary whitespace-nowrap text-xs">Add Role</button>
      </div>
      <input type="text" id="new_role_desc" placeholder="Role description (optional)" class="text-xs"/>
      <div id="createRoleError" class="hidden mt-2 p-2 rounded bg-red-500/10 text-red-300 text-xs"></div>
    </div>

    <!-- Role List -->
    <p class="text-xs text-slate-400 mb-2 font-medium">Existing Roles:</p>
    <div id="rolesListContainer" class="space-y-2 max-h-56 overflow-y-auto pr-1"></div>
  </div>
</div>

<script>
const CURRENT_LOGGED_IN_ID = <?= $user['user_id'] ?>;
let availableRoles = [];
let allUsers = [];

async function loadRoles() {
  const res = await fetch('/api/roles/list.php');
  availableRoles = await res.json();
  populateRoleSelects();
  renderRolesList();
}

function populateRoleSelects() {
  const optionsHTML = availableRoles.map(r =>
    `<option value="${r.role_name}">${r.role_name} ${r.description ? ' - ' + r.description : ''}</option>`
  ).join('');

  document.querySelectorAll('.role-select-options').forEach(select => {
    select.innerHTML = optionsHTML;
  });
}

function renderRolesList() {
  const container = document.getElementById('rolesListContainer');
  if (!availableRoles.length) {
    container.innerHTML = '<p class="text-xs text-slate-500">No roles defined.</p>';
    return;
  }
  container.innerHTML = availableRoles.map(r => `
    <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/3 border border-white/5 text-xs">
      <div>
        <p class="font-semibold text-white uppercase tracking-wider">${r.role_name}</p>
        <p class="text-slate-400">${r.description || 'System role'}</p>
      </div>
      ${['admin','engineer'].includes(r.role_name) ?
        '<span class="text-slate-500 text-[10px]">Built-in</span>' :
        `<button onclick="deleteRole(${r.role_id}, '${r.role_name}')" class="text-red-400 hover:text-red-300 px-2 py-1 rounded bg-red-500/10 border border-red-500/20">Delete</button>`
      }
    </div>
  `).join('');
}

async function submitCreateRole() {
  const name = document.getElementById('new_role_name').value.trim();
  const desc = document.getElementById('new_role_desc').value.trim();
  const err  = document.getElementById('createRoleError');
  err.classList.add('hidden');

  if (!name) { err.textContent = 'Role name required.'; err.classList.remove('hidden'); return; }

  const res = await fetch('/api/roles/create.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ role_name: name, description: desc })
  });
  const json = await res.json();

  if (json.success) {
    document.getElementById('new_role_name').value = '';
    document.getElementById('new_role_desc').value = '';
    loadRoles();
  } else {
    err.textContent = json.error || 'Failed to create role.';
    err.classList.remove('hidden');
  }
}

async function deleteRole(id, name) {
  const confirmed = await showCustomConfirm(
    'Delete Role',
    `Are you sure you want to delete the custom role "${name}"?`,
    'Delete Role',
    true
  );
  if (!confirmed) return;

  const res = await fetch('/api/roles/delete.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ role_id: id, role_name: name })
  });
  const json = await res.json();

  if (json.success) {
    loadRoles();
  } else {
    await showCustomAlert('Delete Role Failed', json.error || 'Failed to delete role.', 'error');
  }
}

async function loadUsers() {
  await loadRoles();
  const res = await fetch('/api/users/list.php');
  allUsers  = await res.json();
  const body = document.getElementById('usersBody');

  if (!allUsers.length) {
    body.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500">No users found.</td></tr>';
    return;
  }

  const roleColors = { admin: 'badge-pm-due', engineer: 'badge-operational', technician: 'badge-in-repair' };

  body.innerHTML = allUsers.map(u => `
    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors cursor-pointer" onclick="openUserDetailModal(${u.user_id})">
      <td class="px-5 py-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-teal-700/30 border border-teal-600/20 flex items-center justify-center text-teal-300 font-bold text-sm flex-shrink-0">
            ${u.username[0].toUpperCase()}
          </div>
          <div>
            <span class="font-medium text-white block">${u.username}</span>
            ${u.user_id == CURRENT_LOGGED_IN_ID ? '<span class="text-[10px] text-teal-400 font-semibold">(You)</span>' : ''}
          </div>
        </div>
      </td>
      <td class="px-5 py-3 text-slate-400 text-sm">${u.email}</td>
      <td class="px-5 py-3 text-sm font-mono text-slate-300">
        <span id="pwd-preview-${u.user_id}">••••••••</span>
        <button onclick="event.stopPropagation(); toggleTablePassword(${u.user_id})" class="ml-2 text-slate-500 hover:text-white text-xs" title="Toggle password visibility">
          👁
        </button>
      </td>
      <td class="px-5 py-3">
        <span class="badge ${roleColors[u.role] || 'badge-decommissioned'}">${u.role}</span>
      </td>
      <td class="px-5 py-3 text-xs text-slate-500">${new Date(u.created_at).toLocaleDateString()}</td>
      <td class="px-5 py-3 text-right">
        <button onclick="event.stopPropagation(); openUserDetailModal(${u.user_id})"
          class="text-xs px-2.5 py-1.5 rounded-lg text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
          View / Edit
        </button>
      </td>
    </tr>`).join('');
}

function toggleTablePassword(id) {
  const el = document.getElementById('pwd-preview-' + id);
  const u  = allUsers.find(x => x.user_id == id);
  if (!el || !u) return;
  if (el.textContent === '••••••••') {
    el.textContent = u.password;
  } else {
    el.textContent = '••••••••';
  }
}

async function openUserDetailModal(userId) {
  document.getElementById('editUserError').classList.add('hidden');
  const res  = await fetch('/api/users/get.php?id=' + userId);
  const data = await res.json();
  const u    = data.user;

  if (!u) return;

  document.getElementById('detail_user_id').value  = u.user_id;
  document.getElementById('detail_username').value = u.username;
  document.getElementById('detail_email').value    = u.email;
  document.getElementById('detail_password').value = u.password;
  document.getElementById('detail_role').value     = u.role;

  document.getElementById('detailHeaderTitle').textContent    = u.username;
  document.getElementById('detailHeaderSubtitle').textContent = `User ID: #${u.user_id} · ${u.role}`;
  document.getElementById('detailAvatar').textContent         = u.username[0].toUpperCase();
  document.getElementById('detailCreated').textContent        = new Date(u.created_at).toLocaleString();
  document.getElementById('detailTicketCount').textContent   = data.ticket_count || 0;
  document.getElementById('detailMaintCount').textContent    = data.maintenance_count || 0;

  const deleteBtn = document.getElementById('deleteUserBtn');
  deleteBtn.style.display = (u.user_id == CURRENT_LOGGED_IN_ID) ? 'none' : 'block';

  document.getElementById('userDetailModal').classList.remove('hidden');
}

function closeUserDetailModal() {
  document.getElementById('userDetailModal').classList.add('hidden');
}

function toggleDetailPasswordVisibility() {
  const input = document.getElementById('detail_password');
  input.type = input.type === 'password' ? 'text' : 'password';
}

async function submitEditUser(e) {
  e.preventDefault();
  const errEl = document.getElementById('editUserError');
  errEl.classList.add('hidden');

  const data = {
    user_id:  document.getElementById('detail_user_id').value,
    username: document.getElementById('detail_username').value.trim(),
    email:    document.getElementById('detail_email').value.trim(),
    password: document.getElementById('detail_password').value,
    role:     document.getElementById('detail_role').value,
  };

  const res  = await fetch('/api/users/update.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  });
  const json = await res.json();

  if (json.success) {
    closeUserDetailModal();
    loadUsers();
  } else {
    errEl.textContent = json.error || 'Failed to update user.';
    errEl.classList.remove('hidden');
  }
}

async function deleteCurrentUser() {
  const userId   = document.getElementById('detail_user_id').value;
  const username = document.getElementById('detail_username').value;
  const confirmed = await showCustomConfirm(
    'Delete User Account',
    `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
    'Delete User',
    true
  );
  if (!confirmed) return;

  const res  = await fetch('/api/users/delete.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ user_id: userId })
  });
  const json = await res.json();

  if (json.success) {
    closeUserDetailModal();
    loadUsers();
  } else {
    await showCustomAlert('Delete Failed', json.error || 'Failed to delete user.', 'error');
  }
}

function openAddUser() {
  document.getElementById('addUserModal').classList.remove('hidden');
  document.getElementById('addUserError').classList.add('hidden');
  ['new_username','new_email','new_password'].forEach(id => document.getElementById(id).value = '');
}

function openManageRolesModal() {
  document.getElementById('manageRolesModal').classList.remove('hidden');
  document.getElementById('createRoleError').classList.add('hidden');
  renderRolesList();
}

async function submitAddUser() {
  const errEl = document.getElementById('addUserError');
  errEl.classList.add('hidden');
  const data = {
    username: document.getElementById('new_username').value.trim(),
    email:    document.getElementById('new_email').value.trim(),
    password: document.getElementById('new_password').value,
    role:     document.getElementById('new_role').value,
  };
  const res  = await fetch('/api/users/create.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
  const json = await res.json();
  if (json.success) {
    document.getElementById('addUserModal').classList.add('hidden');
    loadUsers();
  } else {
    errEl.textContent = json.error || 'Failed to create user.';
    errEl.classList.remove('hidden');
  }
}

loadUsers();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
