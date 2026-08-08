<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';

$db        = getDB();
$pageTitle = 'Department & Category Management';
$user      = currentUser();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-white mb-1">Departments & Categories</h1>
    <p class="text-xs text-slate-400">Add, view, and manage hospital departments and equipment categories.</p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  
  <!-- Departments Management Card -->
  <div class="glass rounded-2xl p-5 border border-white/5 space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center text-teal-400">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5s1.5 0 1.5 1.5v1.5c0 1.5-1.5 1.5-1.5 1.5H9m0-4.5H7.5m1.5 0v4.5m0-4.5h4.5m-4.5 4.5h4.5"/>
          </svg>
        </div>
        <h3 class="font-bold text-white text-base">Departments</h3>
      </div>
      <button onclick="openAddDepartmentModal()" class="btn-primary text-xs flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Department
      </button>
    </div>

    <div class="rounded-xl border border-white/5 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.07)">
            <th class="text-left px-4 py-2.5 text-xs text-slate-500 uppercase tracking-widest">Department Name</th>
            <th class="px-4 py-2.5 text-right text-xs text-slate-500 uppercase tracking-widest">Action</th>
          </tr>
        </thead>
        <tbody id="departmentsBody">
          <tr><td colspan="2" class="text-center py-6 text-slate-500">Loading departments…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Categories Management Card -->
  <div class="glass rounded-2xl p-5 border border-white/5 space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
          </svg>
        </div>
        <h3 class="font-bold text-white text-base">Machine Categories</h3>
      </div>
      <button onclick="openAddCategoryModal()" class="btn-primary text-xs flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Add Category
      </button>
    </div>

    <div class="rounded-xl border border-white/5 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr style="background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.07)">
            <th class="text-left px-4 py-2.5 text-xs text-slate-500 uppercase tracking-widest">Category Name</th>
            <th class="px-4 py-2.5 text-right text-xs text-slate-500 uppercase tracking-widest">Action</th>
          </tr>
        </thead>
        <tbody id="categoriesBody">
          <tr><td colspan="2" class="text-center py-6 text-slate-500">Loading categories…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ── Modals ──────────────────────────────────────────────── -->

<!-- Add Department Modal -->
<div id="addDepartmentModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:400px">
    <h3 class="text-lg font-bold text-white mb-4">Add Department</h3>
    <form onsubmit="submitAddDepartment(event)">
      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" id="new_dept_name" required placeholder="e.g. Pediatric Ward"/>
      </div>
      <div id="deptError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn-primary flex-1">Save Department</button>
        <button type="button" onclick="closeAddDepartmentModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:400px">
    <h3 class="text-lg font-bold text-white mb-4">Add Machine Category</h3>
    <form onsubmit="submitAddCategory(event)">
      <div class="form-group">
        <label>Category Name *</label>
        <input type="text" id="new_cat_name" required placeholder="e.g. Surgical Equipment"/>
      </div>
      <div id="catError" class="hidden mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-sm"></div>
      <div class="flex gap-3 mt-4">
        <button type="submit" class="btn-primary flex-1">Save Category</button>
        <button type="button" onclick="closeAddCategoryModal()" class="btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
// Load Departments
async function loadDepartments() {
  const res  = await fetch('/api/departments/list.php');
  const rows = await res.json();
  const body = document.getElementById('departmentsBody');

  if (!Array.isArray(rows) || !rows.length) {
    body.innerHTML = '<tr><td colspan="2" class="text-center py-6 text-slate-500">No departments added yet.</td></tr>';
    return;
  }

  body.innerHTML = rows.map(d => `
    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
      <td class="px-4 py-2.5 font-medium text-white">${escHTML(d.name)}</td>
      <td class="px-4 py-2.5 text-right">
        <button onclick="deleteDepartment(${d.department_id}, '${escapeQuotes(d.name)}')" class="text-xs px-2 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition-colors">
          Delete
        </button>
      </td>
    </tr>
  `).join('');
}

// Load Categories
async function loadCategories() {
  const res  = await fetch('/api/categories/list.php');
  const rows = await res.json();
  const body = document.getElementById('categoriesBody');

  if (!Array.isArray(rows) || !rows.length) {
    body.innerHTML = '<tr><td colspan="2" class="text-center py-6 text-slate-500">No categories added yet.</td></tr>';
    return;
  }

  body.innerHTML = rows.map(c => `
    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
      <td class="px-4 py-2.5 font-medium text-white">${escHTML(c.name)}</td>
      <td class="px-4 py-2.5 text-right">
        <button onclick="deleteCategory(${c.category_id}, '${escapeQuotes(c.name)}')" class="text-xs px-2 py-1 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition-colors">
          Delete
        </button>
      </td>
    </tr>
  `).join('');
}

// Department Modals & Actions
function openAddDepartmentModal() {
  document.getElementById('new_dept_name').value = '';
  document.getElementById('deptError').classList.add('hidden');
  document.getElementById('addDepartmentModal').classList.remove('hidden');
}
function closeAddDepartmentModal() {
  document.getElementById('addDepartmentModal').classList.add('hidden');
}

async function submitAddDepartment(e) {
  e.preventDefault();
  const name = document.getElementById('new_dept_name').value.trim();
  const err = document.getElementById('deptError');
  err.classList.add('hidden');

  const res = await fetch('/api/departments/create.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name })
  });
  const json = await res.json();

  if (json.success) {
    closeAddDepartmentModal();
    loadDepartments();
    showCustomAlert('Department Added', `"${name}" added successfully.`, 'success');
  } else {
    err.textContent = json.error || 'Failed to add department.';
    err.classList.remove('hidden');
  }
}

async function deleteDepartment(id, name) {
  const confirmed = await showCustomConfirm('Delete Department', `Are you sure you want to delete department "${name}"?`, 'Delete', true);
  if (!confirmed) return;

  const res = await fetch('/api/departments/delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ department_id: id })
  });
  const json = await res.json();

  if (json.success) {
    loadDepartments();
    showCustomAlert('Deleted', 'Department deleted.', 'success');
  } else {
    showCustomAlert('Delete Failed', json.error || 'Failed to delete department.', 'error');
  }
}

// Category Modals & Actions
function openAddCategoryModal() {
  document.getElementById('new_cat_name').value = '';
  document.getElementById('catError').classList.add('hidden');
  document.getElementById('addCategoryModal').classList.remove('hidden');
}
function closeAddCategoryModal() {
  document.getElementById('addCategoryModal').classList.add('hidden');
}

async function submitAddCategory(e) {
  e.preventDefault();
  const name = document.getElementById('new_cat_name').value.trim();
  const err = document.getElementById('catError');
  err.classList.add('hidden');

  const res = await fetch('/api/categories/create.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name })
  });
  const json = await res.json();

  if (json.success) {
    closeAddCategoryModal();
    loadCategories();
    showCustomAlert('Category Added', `"${name}" added successfully.`, 'success');
  } else {
    err.textContent = json.error || 'Failed to add category.';
    err.classList.remove('hidden');
  }
}

async function deleteCategory(id, name) {
  const confirmed = await showCustomConfirm('Delete Category', `Are you sure you want to delete category "${name}"?`, 'Delete', true);
  if (!confirmed) return;

  const res = await fetch('/api/categories/delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ category_id: id })
  });
  const json = await res.json();

  if (json.success) {
    loadCategories();
    showCustomAlert('Deleted', 'Category deleted.', 'success');
  } else {
    showCustomAlert('Delete Failed', json.error || 'Failed to delete category.', 'error');
  }
}

function escHTML(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function escapeQuotes(str) {
  return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// Boot
loadDepartments();
loadCategories();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
