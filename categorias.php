<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div><div class="page-title">Categorias</div><div class="page-subtitle">Classificação de receitas e despesas</div></div>
  <button class="btn btn-accent" onclick="abrirModal()">
    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nova categoria
  </button>
</div>

<div class="card">
  <div class="table-wrap" id="table-area"><div class="empty-state"><p>Carregando...</p></div></div>
</div>

<div class="modal-backdrop" id="modal-cat">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-title">Nova Categoria</span>
      <button class="modal-close" onclick="closeModal('modal-cat')">✕</button>
    </div>
    <form id="form-cat">
      <div class="modal-body">
        <input type="hidden" name="id" value="0">
        <div class="form-grid">
          <div class="form-group full">
            <label>Nome</label>
            <input type="text" name="nmcat" required placeholder="Ex: Alimentação">
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select name="tipo">
              <option value="S">↓ Saída / Despesa</option>
              <option value="E">↑ Entrada / Receita</option>
            </select>
          </div>
          <div class="form-group">
            <label>Grupo</label>
            <input type="text" name="grupo" placeholder="Ex: Básico, Lazer...">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('modal-cat')">Cancelar</button>
        <button type="submit" class="btn btn-accent">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
async function load(){
  const res=await api('/api/categorias.php?action=list');
  const ta=document.getElementById('table-area');
  if(!res.length){ta.innerHTML='<div class="empty-state"><p>Nenhuma categoria</p></div>';return;}
  let h=`<table><thead><tr><th>Nome</th><th>Tipo</th><th>Grupo</th><th></th></tr></thead><tbody>`;
  res.forEach(r=>{
    h+=`<tr>
      <td style="font-weight:500">${r.NMCAT}</td>
      <td><span class="badge ${r.TIPO==='E'?'badge-green':'badge-red'}">${r.TIPO==='E'?'↑ Entrada':'↓ Saída'}</span></td>
      <td>${r.GRUPO||'—'}</td>
      <td><div class="flex gap-2">
        <button class="btn btn-icon btn-sm" onclick="editar(${r.ID})"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <button class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete('/api/categorias.php?id=${r.ID}')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></button>
      </div></td>
    </tr>`;
  });
  h+='</tbody></table>';ta.innerHTML=h;
}
function abrirModal(){document.getElementById('form-cat').reset();document.getElementById('modal-title').textContent='Nova Categoria';openModal('modal-cat');}
async function editar(id){
  const r=await api(`/api/categorias.php?action=get&id=${id}`);
  const f=document.getElementById('form-cat');
  f.querySelector('[name=id]').value=r.ID;f.querySelector('[name=nmcat]').value=r.NMCAT;
  f.querySelector('[name=tipo]').value=r.TIPO||'S';f.querySelector('[name=grupo]').value=r.GRUPO||'';
  document.getElementById('modal-title').textContent='Editar Categoria';openModal('modal-cat');
}
document.getElementById('form-cat').addEventListener('submit',async e=>{
  e.preventDefault();
  try{const res=await api('/api/categorias.php?action=save',{method:'POST',body:Object.fromEntries(new FormData(e.target))});
    showToast(res.message);closeModal('modal-cat');load();
  }catch(err){showToast(err.message,'error');}
});
load();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
