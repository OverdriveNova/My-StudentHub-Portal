<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practical 13 — Asynchronous JSON API & Fetch CRUD | StudentHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0b0f19;
  --surface: #131b2e;
  --surface2: #1c2640;
  --surface3: #253354;
  --accent: #6366f1;
  --accent-glow: rgba(99, 102, 241, 0.25);
  --teal: #14b8a6;
  --danger: #ef4444;
  --success: #22c55e;
  --warning: #f59e0b;
  --text: #f8fafc;
  --text-muted: #94a3b8;
  --border: #283553;
  --radius: 12px;
  --mono: 'JetBrains Mono', monospace;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  line-height: 1.5;
}
.header {
  background: linear-gradient(135deg, #0284c7 0%, #6366f1 100%);
  padding: 1.25rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.header-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.header-brand h1 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
}
.header-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}
.header-nav {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.header-nav a {
  color: #ffffff;
  text-decoration: none;
  font-size: 0.85rem;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.12);
  transition: all 0.2s;
}
.header-nav a:hover {
  background: rgba(255, 255, 255, 0.25);
}
.container {
  max-width: 1300px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}
.toolbar-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.75rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.filters-group {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}
.input-control {
  padding: 0.65rem 0.9rem;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-size: 0.88rem;
  font-family: inherit;
  transition: all 0.2s;
}
.input-control:focus {
  outline: none;
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15);
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.65rem 1.2rem;
  font-size: 0.88rem;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.btn-primary {
  background: linear-gradient(135deg, #0284c7 0%, #6366f1 100%);
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
}
.btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
.btn-secondary {
  background: var(--surface2);
  border: 1px solid var(--border);
  color: var(--text);
}
.btn-secondary:hover { background: var(--surface3); }
.btn-danger {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}
.btn-danger:hover {
  background: rgba(239, 68, 68, 0.35);
  color: #ffffff;
}

.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast.show {
  transform: translateY(0);
  opacity: 1;
}
.toast-success { background: #064e3b; border: 1px solid #059669; color: #6ee7b7; }
.toast-error   { background: #7f1d1d; border: 1px solid #dc2626; color: #fca5a5; }

.event-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}
.event-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s, opacity 0.3s;
}
.event-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}
.event-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}
.badge-category {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--teal);
  letter-spacing: 0.05em;
}
.badge-status {
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
}
.status-Open          { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.status-Limited-Seats { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.status-Coming-Soon   { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
.status-Closed        { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

.event-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.5rem;
}
.event-desc {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  flex: 1;
}
.event-details {
  border-top: 1px solid var(--border);
  padding-top: 0.75rem;
  margin-bottom: 1rem;
  font-size: 0.8rem;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.event-details-row {
  display: flex;
  justify-content: space-between;
}
.event-card-actions {
  display: flex;
  gap: 0.6rem;
}
.event-card-actions .btn {
  flex: 1;
}

.spinner {
  display: none;
  text-align: center;
  padding: 3rem;
  color: var(--teal);
  font-size: 1.1rem;
}
.spinner.active {
  display: block;
}

.modal {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  z-index: 999;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.modal.open {
  display: flex;
}
.modal-content {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 1.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0.75rem;
}
.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 600;
}
.modal-close {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.5rem;
  cursor: pointer;
}
.modal-close:hover { color: var(--text); }
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group.full {
  grid-column: 1 / -1;
}
.form-group label {
  display: block;
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-bottom: 0.3rem;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
  border-top: 1px solid var(--border);
  padding-top: 1rem;
}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <h1>StudentHub Portal</h1>
    <span class="header-badge">Practical 13</span>
  </div>
  <nav class="header-nav">
    <a href="../Practical10/index.php">P10: Auth</a>
    <a href="../Practical11/index.php">P11: Students</a>
    <a href="../Practical12/index.php">P12: Events Upload</a>
    <a href="../Practical14/index.php">P14: Admin RBAC</a>
    <a href="../Practical15/index.php">P15: Full Integration</a>
  </nav>
</header>

<main class="container">

  <div class="toolbar-card">
    <div class="filters-group">
      <input type="text" id="searchInput" class="input-control" placeholder="Search events asynchronously (debounced)..." style="min-width: 280px;">

      <select id="categorySelect" class="input-control">
        <option value="">All Categories</option>
        <option value="Technical">Technical</option>
        <option value="Sports">Sports</option>
        <option value="Cultural">Cultural</option>
        <option value="Career">Career</option>
      </select>

      <select id="statusSelect" class="input-control">
        <option value="">All Statuses</option>
        <option value="Open">Open</option>
        <option value="Limited Seats">Limited Seats</option>
        <option value="Coming Soon">Coming Soon</option>
        <option value="Closed">Closed</option>
      </select>

      <button id="resetBtn" class="btn btn-secondary">Reset</button>
    </div>

    <button id="openAddModalBtn" class="btn btn-primary">+ Create Event (Async)</button>
  </div>

  <div id="loadingSpinner" class="spinner">
    Fetching latest event data via Fetch API...
  </div>

  <div id="eventGrid" class="event-grid"></div>

</main>

<div class="modal" id="eventModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitle">Create New Event</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="eventForm">
      <input type="hidden" id="eventId" name="id">

      <div class="form-grid">
        <div class="form-group full">
          <label>Event Title *</label>
          <input type="text" id="eventTitleInput" name="title" class="input-control" style="width:100%;" required placeholder="e.g. NextGen Web Hackathon">
        </div>

        <div class="form-group">
          <label>Category *</label>
          <select id="eventCategoryInput" name="category" class="input-control" style="width:100%;" required>
            <option value="Technical">Technical</option>
            <option value="Sports">Sports</option>
            <option value="Cultural">Cultural</option>
            <option value="Career">Career</option>
          </select>
        </div>

        <div class="form-group">
          <label>Event Date *</label>
          <input type="date" id="eventDateInput" name="event_date" class="input-control" style="width:100%;" required>
        </div>

        <div class="form-group full">
          <label>Venue Location *</label>
          <input type="text" id="eventVenueInput" name="venue" class="input-control" style="width:100%;" required placeholder="Auditorium Block C">
        </div>

        <div class="form-group full">
          <label>Registration Status *</label>
          <select id="eventStatusInput" name="status" class="input-control" style="width:100%;" required>
            <option value="Open">Open</option>
            <option value="Limited Seats">Limited Seats</option>
            <option value="Coming Soon">Coming Soon</option>
            <option value="Closed">Closed</option>
          </select>
        </div>

        <div class="form-group full">
          <label>Description</label>
          <textarea id="eventDescInput" name="description" rows="3" class="input-control" style="width:100%; resize:vertical;" placeholder="Details, guidelines, rules..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" id="saveEventBtn" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="toast" id="toastBox">
  <span id="toastMsg">Action completed</span>
</div>

<script>
let searchDebounceTimer = null;
const eventGrid = document.getElementById('eventGrid');
const loadingSpinner = document.getElementById('loadingSpinner');
const searchInput = document.getElementById('searchInput');
const categorySelect = document.getElementById('categorySelect');
const statusSelect = document.getElementById('statusSelect');
const resetBtn = document.getElementById('resetBtn');
const eventModal = document.getElementById('eventModal');
const eventForm = document.getElementById('eventForm');
const modalTitle = document.getElementById('modalTitle');
const toastBox = document.getElementById('toastBox');
const toastMsg = document.getElementById('toastMsg');

function showToast(message, isSuccess = true) {
  toastMsg.textContent = message;
  toastBox.className = 'toast show ' + (isSuccess ? 'toast-success' : 'toast-error');
  setTimeout(() => {
    toastBox.classList.remove('show');
  }, 3500);
}

async function loadEvents() {
  loadingSpinner.classList.add('active');
  const params = new URLSearchParams({
    action: 'list',
    search: searchInput.value.trim(),
    category: categorySelect.value,
    status: statusSelect.value
  });

  try {
    const res = await fetch('api.php?' + params.toString());
    const json = await res.json();
    loadingSpinner.classList.remove('active');

    if (json.success) {
      renderEvents(json.data);
    } else {
      showToast(json.message || 'Failed to fetch events', false);
    }
  } catch (err) {
    loadingSpinner.classList.remove('active');
    showToast('Network error while communicating with JSON API', false);
  }
}

function renderEvents(events) {
  eventGrid.innerHTML = '';
  if (!events || events.length === 0) {
    eventGrid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:3rem; color:var(--text-muted); background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);">No events found matching your criteria.</div>';
    return;
  }

  events.forEach(ev => {
    const cleanStatus = ev.status.replace(/\s+/g, '-');
    const card = document.createElement('div');
    card.className = 'event-card';
    card.id = 'event-card-' + ev.id;
    card.innerHTML = `
      <div class="event-card-header">
        <span class="badge-category">${escapeHtml(ev.category)}</span>
        <span class="badge-status status-${cleanStatus}">${escapeHtml(ev.status)}</span>
      </div>
      <h3 class="event-title">${escapeHtml(ev.title)}</h3>
      <p class="event-desc">${escapeHtml(ev.description || 'No description provided.')}</p>
      <div class="event-details">
        <div class="event-details-row">
          <span>Date:</span>
          <strong style="color:var(--text); font-family:var(--mono);">${escapeHtml(ev.event_date)}</strong>
        </div>
        <div class="event-details-row">
          <span>Venue:</span>
          <span>${escapeHtml(ev.venue || 'TBA')}</span>
        </div>
        <div class="event-details-row">
          <span>Registrations:</span>
          <strong style="color:var(--teal);">${ev.registration_count || 0} registered</strong>
        </div>
      </div>
      <div class="event-card-actions">
        <button class="btn btn-secondary" onclick='openEditModal(${JSON.stringify(ev)})'>Edit</button>
        <button class="btn btn-danger" onclick='deleteEvent(${ev.id}, "${escapeHtml(addslashes(ev.title))}")'>Delete</button>
      </div>
    `;
    eventGrid.appendChild(card);
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function addslashes(str) {
  return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

searchInput.addEventListener('input', () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    loadEvents();
  }, 300);
});

categorySelect.addEventListener('change', loadEvents);
statusSelect.addEventListener('change', loadEvents);

resetBtn.addEventListener('click', () => {
  searchInput.value = '';
  categorySelect.value = '';
  statusSelect.value = '';
  loadEvents();
});

document.getElementById('openAddModalBtn').addEventListener('click', () => {
  eventForm.reset();
  document.getElementById('eventId').value = '';
  modalTitle.textContent = 'Create New Event';
  const today = new Date();
  today.setDate(today.getDate() + 7);
  document.getElementById('eventDateInput').value = today.toISOString().split('T')[0];
  eventModal.classList.add('open');
});

function openEditModal(ev) {
  document.getElementById('eventId').value = ev.id;
  document.getElementById('eventTitleInput').value = ev.title;
  document.getElementById('eventCategoryInput').value = ev.category;
  document.getElementById('eventDateInput').value = ev.event_date;
  document.getElementById('eventVenueInput').value = ev.venue || '';
  document.getElementById('eventStatusInput').value = ev.status;
  document.getElementById('eventDescInput').value = ev.description || '';
  modalTitle.textContent = 'Edit Event #' + ev.id;
  eventModal.classList.add('open');
}

function closeModal() {
  eventModal.classList.remove('open');
}

window.addEventListener('click', e => {
  if (e.target === eventModal) closeModal();
});

eventForm.addEventListener('submit', async e => {
  e.preventDefault();
  const id = document.getElementById('eventId').value;
  const action = id ? 'update' : 'create';

  const payload = {
    id: id ? parseInt(id, 10) : undefined,
    title: document.getElementById('eventTitleInput').value.trim(),
    category: document.getElementById('eventCategoryInput').value,
    event_date: document.getElementById('eventDateInput').value,
    venue: document.getElementById('eventVenueInput').value.trim(),
    status: document.getElementById('eventStatusInput').value,
    description: document.getElementById('eventDescInput').value.trim()
  };

  try {
    const res = await fetch('api.php?action=' + action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (json.success) {
      closeModal();
      showToast(json.message);
      loadEvents();
    } else {
      showToast(json.message || 'Operation failed', false);
    }
  } catch (err) {
    showToast('Failed to communicate with JSON API', false);
  }
});

async function deleteEvent(id, title) {
  if (!confirm(`Are you sure you want to delete event "${title}"?`)) return;

  try {
    const res = await fetch('api.php?action=delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id: id })
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message);
      const card = document.getElementById('event-card-' + id);
      if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => card.remove(), 300);
      } else {
        loadEvents();
      }
    } else {
      showToast(json.message || 'Delete operation failed', false);
    }
  } catch (err) {
    showToast('Failed to execute delete request', false);
  }
}

document.addEventListener('DOMContentLoaded', loadEvents);
</script>

</body>
</html>
