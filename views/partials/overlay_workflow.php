<?php
// Expects: $vision (id, slug, workflow_status, workflow_notes), $presentationFlags
$slug   = htmlspecialchars($vision['slug'] ?? '', ENT_QUOTES);
$status = (string)($vision['workflow_status'] ?? 'not_started');
$notes  = (string)($vision['workflow_notes']  ?? '');
$show   = array_key_exists('workflow', $presentationFlags ?? [])
            ? (int)$presentationFlags['workflow']
            : 1;

$STATUSES = [
  'not_started' => 'Not started',
  'in_progress' => 'In progress',
  'complete'    => 'Complete',
];
?>

<div class="overlay-header">
  <h2>Workflow</h2>
</div>

<form id="workflowForm" class="overlay-form" data-slug="<?= $slug ?>">
  <label for="wfStatus">Status</label>
  <select id="wfStatus" name="status">
    <?php foreach ($STATUSES as $key => $label): ?>
      <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
  </select>

  <label for="wfNotes">Notes</label>
  <textarea id="wfNotes" name="notes" rows="4" placeholder="Anything worth tracking — blockers, next steps, decisions…"><?= htmlspecialchars($notes, ENT_QUOTES) ?></textarea>

  <h4>Visibility</h4>
  <label class="switch switch-row">
    <span class="switch-label">Show section</span>
    <input class="switch-input" type="checkbox" name="show_workflow" <?= $show ? 'checked' : '' ?>>
    <span class="knob" aria-hidden="true"></span>
  </label>

  <p id="wfStatusMsg" style="opacity:.6;font-size:.85em;margin:.5rem 0 0;"></p>
</form>

<style>
  #workflowForm select, #workflowForm textarea {
    width: 100%; box-sizing: border-box;
    background: #15161A; border: 1px solid #2b3346; color: #ddd;
    padding: .5rem .7rem; border-radius: 8px;
    margin-bottom: .9rem;
  }
  #workflowForm textarea { min-height: 96px; resize: vertical; }
</style>

<script>
(() => {
  const form     = document.getElementById('workflowForm');
  if (!form) return;
  const slug     = form.dataset.slug;
  const status   = form.querySelector('#wfStatus');
  const notes    = form.querySelector('#wfNotes');
  const showCb   = form.querySelector('[name="show_workflow"]');
  const msg      = form.querySelector('#wfStatusMsg');

  function save() {
    const p = new URLSearchParams();
    p.set('status', status.value);
    p.set('notes', notes.value);
    p.set('show_workflow', showCb.checked ? '1' : '0');
    msg.textContent = 'Saving…';
    fetch(`/api/visions/${slug}/workflow`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: p.toString()
    }).then(r => r.json()).then(j => {
      msg.textContent = j.success ? 'Saved' : ('⚠ ' + (j.error || 'Save failed'));
    }).catch(e => {
      msg.textContent = '⚠ Network error';
      console.error(e);
    });
  }

  status.addEventListener('change', save);
  showCb.addEventListener('change', save);

  let notesTimer;
  notes.addEventListener('input', () => {
    clearTimeout(notesTimer);
    notesTimer = setTimeout(save, 600);
  });
  notes.addEventListener('blur', save);
})();
</script>
