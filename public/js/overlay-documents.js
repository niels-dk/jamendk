document.addEventListener('DOMContentLoaded', () => {
  const form   = document.getElementById('documentUploadForm');

  if (!form) return;
  const tableBody = document.querySelector('#docsTable tbody');
  const statusEl  = document.getElementById('uploadStatus');

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const slug = form.querySelector('input[name="slug"]').value;
    const data = new FormData(form);

    statusEl.textContent = 'Uploading…';

    try {
      const res  = await fetch(`/api/visions/${slug}/documents`, { method:'POST', body:data });
      const json = await res.json();

      if (!res.ok || !json.success) {
        statusEl.textContent = '❌ ' + (json.error || 'Upload failed');
        return;
      }

      // Append each uploaded file as a new row (at top)
      for (const f of json.files) {
        const tr = document.createElement('tr');
        const sizeKB = (f.size/1024).toFixed(2);
        tr.innerHTML = `
          <td>${f.file_name}</td>
          <td>${f.version}</td>
          <td>${f.status.charAt(0).toUpperCase()+f.status.slice(1)}</td>
          <td>${sizeKB} KB</td>
          <td><a href="${f.download_url}">Download</a></td>
        `;
        tableBody.prepend(tr);
      }

      // Show partial errors (if any)
      if (json.errors && json.errors.length) {
        statusEl.textContent = '✅ Uploaded with warnings on some files.';
      } else {
        statusEl.textContent = '✅ Uploaded';
      }

      form.reset(); // keep overlay open, clear file input
    } catch (e) {
      console.error(e);
      statusEl.textContent = '❌ Upload failed';
    }
  });
});
