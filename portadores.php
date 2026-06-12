<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div><div class="page-title">Portadores</div><div class="page-subtitle">Pessoas responsáveis pelas movimentações</div></div>
  <button class="btn btn-accent" onclick="abrirModal()">
    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Novo portador
  </button>
</div>

<div class="card">
  <div class="table-wrap" id="table-area"><div class="empty-state"><p>Carregando...</p></div></div>
</div>

<div class="modal-backdrop" id="modal-port">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-title">Novo Portador</span>
      <button class="modal-close" onclick="closeModal('modal-port')">✕</button>
    </div>
    <form id="form-port">
      <div class="modal-body">
        <input type="hidden" name="id" value="0">
        <div class="form-group">
          <label>Nome do portador</label>
          <input type="text" name="nmportador" required placeholder="Ex: João Silva">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('modal-port')">Cancelar</button>
        <button type="submit" class="btn btn-accent">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
async function load(){
  const res=await api('/api/portadores.php?action=list');
  const ta=document.getElementById('table-area');
  if(!res.length){ta.innerHTML='<div class="empty-state"><p>Nenhum portador</p></div>';return;}
  let h=`<table><thead><tr><th>Nome</th><th></th></tr></thead><tbody>`;
  res.forEach(r=>{
    const ini=r.NMPORTADOR.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
    h+=`<tr>
      <td><div class="flex items-center gap-2">
        <div class="user-avatar" style="background:var(--accent-dim);color:var(--accent)">${ini}</div>
        <span style="font-weight:500">${r.NMPORTADOR}</span>
      </div></td>
      <td style="text-align:right"><div class="flex gap-2" style="justify-content:flex-end">
        <button class="btn btn-icon btn-sm" onclick="editar(${r.ID})"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <button class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete('/api/portadores.php?id=${r.ID}')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></button>
      </div></td>
    </tr>`;
  });
  h+='</tbody></table>';ta.innerHTML=h;
}
function abrirModal(){document.getElementById('form-port').reset();document.getElementById('modal-title').textContent='Novo Portador';openModal('modal-port');}
async function editar(id){
  const r=await api(`/api/portadores.php?action=get&id=${id}`);
  const f=document.getElementById('form-port');
  f.querySelector('[name=id]').value=r.ID;f.querySelector('[name=nmportador]').value=r.NMPORTADOR;
  document.getElementById('modal-title').textContent='Editar Portador';openModal('modal-port');
}
document.getElementById('form-port').addEventListener('submit',async e=>{
  e.preventDefault();
  try{const res=await api('/api/portadores.php?action=save',{method:'POST',body:Object.fromEntries(new FormData(e.target))});
    showToast(res.message);closeModal('modal-port');load();
  }catch(err){showToast(err.message,'error');}
});
load();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
