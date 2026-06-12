<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div><div class="page-title">Caixas</div><div class="page-subtitle">Contas, cartões e carteiras</div></div>
  <button class="btn btn-accent" onclick="abrirModal()">
    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nova caixa
  </button>
</div>

<div class="card">
  <div class="table-wrap" id="table-area"><div class="empty-state"><p>Carregando...</p></div></div>
</div>

<div class="modal-backdrop" id="modal-caixa">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-title">Nova Caixa</span>
      <button class="modal-close" onclick="closeModal('modal-caixa')">✕</button>
    </div>
    <form id="form-caixa">
      <div class="modal-body">
        <input type="hidden" name="id" value="0">
        <div class="form-grid">
          <div class="form-group full">
            <label>Nome</label>
            <input type="text" name="nmcaixa" required placeholder="Ex: Banco do Brasil CC">
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select name="tipo">
              <option value="conta">Conta Corrente</option>
              <option value="poupanca">Poupança</option>
              <option value="cartao">Cartão de Crédito</option>
              <option value="especie">Dinheiro / Espécie</option>
              <option value="investimento">Investimento</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="A">Ativo</option>
              <option value="I">Inativo</option>
            </select>
          </div>
          <div class="form-group">
            <label>Dia vencimento (cartão)</label>
            <input type="number" name="vencimento" min="1" max="31" placeholder="Ex: 10">
          </div>
          <div class="form-group">
            <label>Dia fechamento (cartão)</label>
            <input type="number" name="fechamento" min="1" max="31" placeholder="Ex: 3">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('modal-caixa')">Cancelar</button>
        <button type="submit" class="btn btn-accent">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
const TIPOS = {conta:'Conta Corrente',poupanca:'Poupança',cartao:'Cartão',especie:'Espécie',investimento:'Investimento',outro:'Outro'};

async function load(){
  const res = await api('/api/caixas.php?action=list');
  const ta = document.getElementById('table-area');
  if(!res.length){ta.innerHTML='<div class="empty-state"><p>Nenhuma caixa cadastrada</p></div>';return;}
  let h=`<table><thead><tr><th>Nome</th><th>Tipo</th><th>Vencto</th><th>Fechto</th><th>Status</th><th></th></tr></thead><tbody>`;
  res.forEach(r=>{
    h+=`<tr>
      <td style="font-weight:500">${r.NMCAIXA}</td>
      <td>${TIPOS[r.TIPO]||r.TIPO||'—'}</td>
      <td>${r.VENCIMENTO||'—'}</td><td>${r.FECHAMENTO||'—'}</td>
      <td><span class="badge ${r.STATUS==='A'?'badge-green':'badge-gray'}">${r.STATUS==='A'?'Ativo':'Inativo'}</span></td>
      <td><div class="flex gap-2">
        <button class="btn btn-icon btn-sm" onclick="editar(${r.ID})"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <button class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete('/api/caixas.php?id=${r.ID}')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></button>
      </div></td>
    </tr>`;
  });
  h+='</tbody></table>';ta.innerHTML=h;
}
function abrirModal(){
  document.getElementById('form-caixa').reset();
  document.getElementById('modal-title').textContent='Nova Caixa';
  openModal('modal-caixa');
}
async function editar(id){
  const r=await api(`/api/caixas.php?action=get&id=${id}`);
  const f=document.getElementById('form-caixa');
  f.querySelector('[name=id]').value=r.ID;f.querySelector('[name=nmcaixa]').value=r.NMCAIXA;
  f.querySelector('[name=tipo]').value=r.TIPO||'conta';f.querySelector('[name=status]').value=r.STATUS||'A';
  f.querySelector('[name=vencimento]').value=r.VENCIMENTO||'';f.querySelector('[name=fechamento]').value=r.FECHAMENTO||'';
  document.getElementById('modal-title').textContent='Editar Caixa';openModal('modal-caixa');
}
document.getElementById('form-caixa').addEventListener('submit',async e=>{
  e.preventDefault();
  try{const res=await api('/api/caixas.php?action=save',{method:'POST',body:Object.fromEntries(new FormData(e.target))});
    showToast(res.message);closeModal('modal-caixa');load();
  }catch(err){showToast(err.message,'error');}
});
load();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
