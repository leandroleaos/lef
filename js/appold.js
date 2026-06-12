/* LEF – app.js */

/* ── Toast ────────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  let t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.className = 'show ' + type;
  clearTimeout(t._tid);
  t._tid = setTimeout(() => { t.className = ''; }, 3200);
}

/* ── Modal ────────────────────────────────────────────────── */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

/* ── API helper ───────────────────────────────────────────── */
async function api(url, opts = {}) {
  opts.headers = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...opts.headers };
  if (opts.body && typeof opts.body === 'object') opts.body = JSON.stringify(opts.body);
  const res = await fetch(url, opts);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Erro desconhecido');
  return data;
}

/* ── Format helpers ───────────────────────────────────────── */
function fmtMoeda(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
}
function fmtData(str) {
  if (!str) return '—';
  const [y, m, d] = str.split('-');
  return `${d}/${m}/${y}`;
}

/* ── Parcelamento auto-fill ───────────────────────────────── */
function initParcelado() {
  const chk = document.getElementById('chk-parcelado');
  const box = document.getElementById('parcela-box');
  if (!chk || !box) return;
  chk.addEventListener('change', () => {
    box.style.display = chk.checked ? 'grid' : 'none';
  });
}

/* ── Confirm delete ───────────────────────────────────────── */
function confirmDelete(url, msg = 'Confirmar exclusão?') {
  if (!confirm(msg)) return;
  api(url, { method: 'DELETE' })
    .then(d => { showToast(d.message || 'Excluído'); setTimeout(() => location.reload(), 600); })
    .catch(e => showToast(e.message, 'error'));
}

/* ── Toggle pago ──────────────────────────────────────────── */
function togglePago(id) {
  api(`/api/lancamentos.php?action=toggle_pago&id=${id}`, { method: 'POST' })
    .then(d => { showToast(d.message); setTimeout(() => location.reload(), 500); })
    .catch(e => showToast(e.message, 'error'));
}

document.addEventListener('DOMContentLoaded', () => {
  initParcelado();
});
