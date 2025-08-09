<?php
/**
 * views/vision_edit.php
 * Vision create/edit form with left sidebar nav.
 *
 * EXPECTS (when editing):
 *   $vision  = [ 'id'=>?, 'title'=>?, 'description'=>?, 'start_date'=>?, 'end_date'=>?, 'approval_required'=>0/1, 'delegate_user_id'=>null, 'dream_id'=>null ];
 *   $milestones = [ [id, text, due_date, done], ... ]
 *   $budget = [ [id, label, amount, currency, paid], ... ]
 *   $roles = [ ['user_id'=>?, 'name'=>?, 'role'=>'Owner|Co-owner|Editor|Viewer|Delegate'], ... ]
 *   $contacts = [ ['id'=>?, 'name'=>?, 'email'=>?], ... ] // linked contacts
 *   $all_contacts = [ ... ] // for picker
 *   $mood_boards = [ ['id'=>?, 'title'=>?], ... ]
 *   $dreams = [ ['id'=>?, 'title'=>?], ... ]
 *
 * ROUTES assumed:
 *   POST /visions/save           (handles create/update)
 *   GET  /visions                (list)
 *   GET  /dashboard              (dashboard)
 */
?>

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<style>
  /* Minimal, framework-agnostic layout */
  .layout { display:flex; min-height: 100vh; background:#0b0f14; color:#e6edf3; }
  .sidebar { width: 260px; background:#0f1621; border-right:1px solid #1c2740; position:sticky; top:0; height:100vh; padding:16px; }
  .brand { font-weight:700; letter-spacing:.5px; margin-bottom:12px; }
  .nav a { display:block; padding:10px 12px; border-radius:10px; color:#b7c2d0; text-decoration:none; margin-bottom:6px; }
  .nav a.active, .nav a:hover { background:#122033; color:#fff; }

  .content { flex:1; padding:24px; }
  .card { background:#0f1621; border:1px solid #1c2740; border-radius:16px; padding:20px; margin-bottom:20px; }
  .card h3 { margin:0 0 12px 0; font-size:18px; }
  .grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
  .grid-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; }
  .grid-4 { display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; }
  .field { display:flex; flex-direction:column; gap:6px; }
  label { font-size:13px; color:#9bb0c5; }
  input[type=text], input[type=date], input[type=number], select, textarea { background:#0b1220; border:1px solid #1c2740; color:#e6edf3; border-radius:10px; padding:10px 12px; }
  textarea { min-height:140px; resize:vertical; }
  .muted { color:#8aa0b5; font-size:12px; }
  .btnbar { display:flex; gap:10px; }
  .btn { border:1px solid #2a3b5f; background:#15243a; color:#e6edf3; padding:10px 14px; border-radius:12px; cursor:pointer; }
  .btn.primary { background:#1e3a8a; border-color:#2b4fb6; }
  .btn.ghost { background:transparent; }
  .table { width:100%; border-collapse: collapse; }
  .table th, .table td { border-bottom:1px solid #1c2740; padding:10px; text-align:left; }
  .table th { color:#9bb0c5; font-weight:600; font-size:13px; }
  .row-actions { display:flex; gap:6px; }
  .chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#132136; border:1px solid #1f3351; font-size:12px; }
  .switch { display:inline-flex; align-items:center; gap:8px; }
  .switch input { transform: scale(1.1); }
  .help { font-size:12px; color:#89a2b8; }
  @media (max-width: 960px){ .sidebar{ position:fixed; left:0; top:0; height:auto; width:100%; z-index:20; }
    .content{ padding-top:140px; } .grid, .grid-3, .grid-4 { grid-template-columns: 1fr; }
  }
</style>

<div class="layout">
  <!-- Sidebar from partial -->
  <div class="content">
    <form method="post" action="/visions/save" enctype="multipart/form-data" id="vision-form">
      <!-- CSRF -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <?php if (!empty($vision['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$vision['id'] ?>">
      <?php endif; ?>

      <!-- Header -->
      <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <div>
          <div class="chip">Vision</div>
          <h2 style="margin:8px 0 4px 0;"><?= isset($vision['id']) ? 'Edit Vision' : 'New Vision' ?></h2>
          <div class="muted">Turn a dream into a scoped project—deadlines, deliverables, documents, roles.</div>
        </div>
        <div class="btnbar">
          <a class="btn ghost" href="/visions">Cancel</a>
          <button class="btn" type="submit" name="save_close" value="1">Save & Close</button>
          <button class="btn primary" type="submit">Save</button>
        </div>
      </div>

      <!-- Basics -->
      <div class="card">
        <h3>Basics</h3>
        <div class="grid">
          <div class="field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($vision['title'] ?? '') ?>" placeholder="e.g., 3-part Patagonia Mini-Series">
          </div>
          <div class="grid">
            <div class="field">
              <label for="start_date">Start date</label>
              <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($vision['start_date'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="end_date">End date</label>
              <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($vision['end_date'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="field" style="margin-top:12px;">
          <label for="description">Description</label>
          <textarea id="description" name="description" placeholder="Scope, deliverables, creative direction... (Trix-ready)"><?= htmlspecialchars($vision['description'] ?? '') ?></textarea>
          <div class="help">Tip: You can swap this textarea for Trix or TipTap later; keep name="description".</div>
        </div>
      </div>

      <!-- Relations -->
      <div class="card">
        <h3>Relations</h3>
        <div class="grid">
          <div class="field">
            <label for="dream_id">Link Dream (optional)</label>
            <select id="dream_id" name="dream_id">
              <option value="">— none —</option>
              <?php foreach (($dreams ?? []) as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= (!empty($vision['dream_id']) && (int)$vision['dream_id']===(int)$d['id'])?'selected':'' ?>><?= htmlspecialchars($d['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="mood_ids[]">Attach Mood Boards (multi)</label>
            <select id="mood_ids" name="mood_ids[]" multiple size="4">
              <?php foreach (($mood_boards ?? []) as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= (!empty($vision['mood_ids']) && in_array($m['id'],$vision['mood_ids']))?'selected':'' ?>><?= htmlspecialchars($m['title']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="help">Hold Ctrl/Cmd to select multiple.</div>
          </div>
        </div>
      </div>

      <!-- Milestones -->
      <div class="card">
        <h3>Goals & Milestones</h3>
        <table class="table" id="milestones-table">
          <thead>
            <tr><th style="width:36px;">Done</th><th>Milestone</th><th style="width:180px;">Due date</th><th style="width:40px;"></th></tr>
          </thead>
          <tbody>
            <?php if (!empty($milestones)): foreach ($milestones as $i=>$m): ?>
              <tr>
                <td><input type="checkbox" name="milestones[<?= $i ?>][done]" <?= !empty($m['done'])?'checked':'' ?>></td>
                <td><input type="text" name="milestones[<?= $i ?>][text]" value="<?= htmlspecialchars($m['text'] ?? '') ?>" placeholder="e.g., Sign brand agreement"></td>
                <td><input type="date" name="milestones[<?= $i ?>][due_date]" value="<?= htmlspecialchars($m['due_date'] ?? '') ?>"></td>
                <td class="row-actions"><button type="button" class="btn ghost" onclick="removeRow(this)">✕</button></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
        <div class="btnbar" style="margin-top:10px;">
          <button type="button" class="btn" onclick="addMilestone()">＋ Add milestone</button>
          <span class="muted">Completed: <span id="milestone-progress">0%</span></span>
        </div>
      </div>

      <!-- Budget -->
      <div class="card">
        <h3>Budget</h3>
        <table class="table" id="budget-table">
          <thead>
            <tr><th>Item</th><th style="width:160px;">Amount</th><th style="width:120px;">Currency</th><th style="width:120px;">Paid?</th><th style="width:40px;"></th></tr>
          </thead>
          <tbody>
            <?php if (!empty($budget)): foreach ($budget as $i=>$b): ?>
              <tr>
                <td><input type="text" name="budget[<?= $i ?>][label]" value="<?= htmlspecialchars($b['label'] ?? '') ?>" placeholder="e.g., Travel, Gear, Talent"></td>
                <td><input type="number" step="0.01" name="budget[<?= $i ?>][amount]" value="<?= htmlspecialchars($b['amount'] ?? '') ?>"></td>
                <td>
                  <select name="budget[<?= $i ?>][currency]"><option>DKK</option><option>USD</option><option>EUR</option><option>BRL</option></select>
                </td>
                <td>
                  <select name="budget[<?= $i ?>][paid]"><option value="0" <?= empty($b['paid'])?'selected':'' ?>>Unpaid</option><option value="1" <?= !empty($b['paid'])?'selected':'' ?>>Paid</option></select>
                </td>
                <td class="row-actions"><button type="button" class="btn ghost" onclick="removeRow(this)">✕</button></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
        <div class="btnbar" style="margin-top:10px;">
          <button type="button" class="btn" onclick="addBudget()">＋ Add budget line</button>
          <span class="muted">Totals: <span id="budget-total">0</span> <span id="budget-currency">mixed</span></span>
        </div>
      </div>

      <!-- Roles Matrix -->
      <div class="card">
        <h3>Roles & Permissions</h3>
        <div class="help" style="margin-bottom:8px;">Owner can grant roles. Delegates act "on behalf" of the owner (actions are logged).</div>
        <table class="table" id="roles-table">
          <thead>
          <tr><th>User</th><th style="width:200px;">Role</th><th style="width:40px;"></th></tr>
          </thead>
          <tbody>
          <?php if (!empty($roles)): foreach ($roles as $i=>$r): ?>
            <tr>
              <td>
                <input type="hidden" name="roles[<?= $i ?>][user_id]" value="<?= (int)$r['user_id'] ?>">
                <?= htmlspecialchars($r['name']) ?>
              </td>
              <td>
                <select name="roles[<?= $i ?>][role]">
                  <?php foreach (['Owner','Co-owner','Editor','Viewer','Delegate'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($r['role']??'')===$opt?'selected':'' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="row-actions"><button type="button" class="btn ghost" onclick="removeRow(this)">✕</button></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
        <div class="btnbar" style="margin-top:10px;">
          <button type="button" class="btn" onclick="addRole()">＋ Add user</button>
        </div>
      </div>

      <!-- Contacts -->
      <div class="card">
        <h3>Contacts</h3>
        <div class="grid">
          <div class="field">
            <label>Linked Contacts</label>
            <div id="linked-contacts" class="chips">
              <?php if (!empty($contacts)): foreach ($contacts as $c): ?>
                <span class="chip" data-id="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?> <button type="button" class="btn ghost" onclick="removeContact(<?= (int)$c['id'] ?>)">✕</button>
                  <input type="hidden" name="contact_ids[]" value="<?= (int)$c['id'] ?>"></span>
              <?php endforeach; endif; ?>
            </div>
          </div>
          <div class="field">
            <label for="contact-picker">Add Contact</label>
            <select id="contact-picker">
              <option value="">— choose —</option>
              <?php foreach (($all_contacts ?? []) as $ac): ?>
                <option value="<?= (int)$ac['id'] ?>" data-name="<?= htmlspecialchars($ac['name']) ?>"><?= htmlspecialchars($ac['name']) ?> (<?= htmlspecialchars($ac['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div class="card">
        <h3>Documents</h3>
        <div class="grid">
          <div class="field">
            <label for="docs">Upload files</label>
            <input type="file" id="docs" name="docs[]" multiple>
            <div class="help">Files are stored encrypted at rest and streamed on demand.</div>
          </div>
          <div class="field">
            <label for="doc-status-default">Default status for new uploads</label>
            <select id="doc-status-default" name="doc_status_default">
              <option>Draft</option>
              <option>Waiting Brand</option>
              <option>Final</option>
              <option>Signed</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Approval / Delegation -->
      <div class="card">
        <h3>Workflow</h3>
        <div class="grid">
          <div class="field">
            <label class="switch">
              <input type="checkbox" name="approval_required" value="1" <?= !empty($vision['approval_required'])?'checked':'' ?>>
              <span>Changes require owner approval</span>
            </label>
          </div>
          <div class="field">
            <label for="delegate_user_id">Delegate (acts on behalf of owner)</label>
            <select name="delegate_user_id" id="delegate_user_id">
              <option value="">— none —</option>
              <?php foreach (($roles ?? []) as $r): if ($r['role']!=='Owner'): ?>
                <option value="<?= (int)$r['user_id'] ?>" <?= (!empty($vision['delegate_user_id']) && (int)$vision['delegate_user_id']===(int)$r['user_id'])?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
              <?php endif; endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Submit bottom -->
      <div class="card" style="display:flex; justify-content:flex-end; gap:10px;">
        <a class="btn ghost" href="/visions">Cancel</a>
        <button class="btn" type="submit" name="save_close" value="1">Save & Close</button>
        <button class="btn primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Row helpers
  function removeRow(btn){ const tr = btn.closest('tr'); tr.parentNode.removeChild(tr); recalcMilestones(); recalcBudget(); }
  function addMilestone(){
    const tbody = document.querySelector('#milestones-table tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" name="milestones[${i}][done]"></td>
      <td><input type="text" name="milestones[${i}][text]" placeholder="e.g., Shoot Day 1"></td>
      <td><input type="date" name="milestones[${i}][due_date]"></td>
      <td class="row-actions"><button type="button" class="btn ghost" onclick="removeRow(this)">✕</button></td>`;
    tbody.appendChild(tr);
  }
  function addBudget(){
    const tbody = document.querySelector('#budget-table tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="budget[${i}][label]" placeholder="e.g., Travel"></td>
      <td><input type="number" step="0.01" name="budget[${i}][amount]"></td>
      <td><select name="budget[${i}][currency]"><option>DKK</option><option>USD</option><option>EUR</option><option>BRL</option></select></td>
      <td><select name="budget[${i}][paid]"><option value="0">Unpaid</option><option value="1">Paid</option></select></td>
      <td class="row-actions"><button type="button" class="btn ghost" onclick="removeRow(this)">✕</button></td>`;
    tbody.appendChild(tr);
  }

  function recalcMilestones(){
    const rows = Array.from(document.querySelectorAll('#milestones-table tbody tr'));
    if(!rows.length){ document.getElementById('milestone-progress').textContent = '0%'; return; }
    const done = rows.filter(r => r.querySelector('input[type=checkbox]').checked).length;
    document.getElementById('milestone-progress').textContent = Math.round((done/rows.length)*100) + '%';
  }
  function recalcBudget(){
    const rows = Array.from(document.querySelectorAll('#budget-table tbody tr'));
    let total = 0; let currency = null; let mixed=false;
    rows.forEach(r=>{
      const amt = parseFloat(r.querySelector('input[type=number]')?.value || '0');
      const cur = r.querySelector('select[name$="[currency]"]')?.value;
      total += isNaN(amt)?0:amt;
      if(currency===null) currency = cur; else if(cur!==currency) mixed=true;
    });
    document.getElementById('budget-total').textContent = total.toFixed(2);
    document.getElementById('budget-currency').textContent = mixed? 'mixed' : (currency||'—');
  }
  document.addEventListener('input', (e)=>{
    if(e.target.closest('#milestones-table')) recalcMilestones();
    if(e.target.closest('#budget-table')) recalcBudget();
  });
  document.addEventListener('change', (e)=>{
    if(e.target.closest('#milestones-table')) recalcMilestones();
    if(e.target.closest('#budget-table')) recalcBudget();
  });
  window.addEventListener('DOMContentLoaded', ()=>{ recalcMilestones(); recalcBudget(); });

  // Contacts picker
  const picker = document.getElementById('contact-picker');
  if(picker){ picker.addEventListener('change', ()=>{
    const id = picker.value; if(!id) return; const name = picker.options[picker.selectedIndex].dataset.name;
    addContactChip(id, name); picker.value='';
  });}
  function addContactChip(id, name){
    const wrap = document.getElementById('linked-contacts');
    if([...wrap.querySelectorAll('input[name="contact_ids[]"]')].some(i=>i.value===String(id))) return; // no duplicates
    const span = document.createElement('span');
    span.className='chip'; span.dataset.id=id; span.innerHTML = `${name} <button type="button" class="btn ghost" onclick="removeContact(${id})">✕</button><input type="hidden" name="contact_ids[]" value="${id}">`;
    wrap.appendChild(span);
  }
  function removeContact(id){
    const el = document.querySelector(`#linked-contacts .chip[data-id="${id}"]`); if(el) el.remove();
  }
</script>
