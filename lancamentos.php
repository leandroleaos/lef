<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-title">Lançamentos</div>
    <div class="page-subtitle">Gerenciar todas as movimentações</div>
  </div>
  <button class="btn btn-accent" onclick="openModal('modal-lanc')">
    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Novo lançamento
  </button>
</div>

<div class="filters">
  <div class="filter-group">
    <label>Mês</label>
    <select id="f-mes">
      <?php $meses=['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
      for($i=1;$i<=12;$i++){$sel=$i==(int)date('n')?'selected':'';echo "<option value='$i' $sel>{$meses[$i-1]}</option>";} ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Ano</label>
    <select id="f-ano">
      <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++){$sel=$y==date('Y')?'selected':'';echo "<option value='$y' $sel>$y</option>";} ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Tipo</label>
    <select id="f-dc">
      <option value="">Todos</option><option value="D">Entradas</option><option value="C">Saídas</option>
    </select>
  </div>
  <div class="filter-group">
    <label>Status</label>
    <select id="f-pago">
      <option value="">Todos</option><option value="N">Pendentes</option><option value="S">Pagos</option>
    </select>
  </div>
  <div class="filter-group">
    <label>&nbsp;</label>
    <button class="btn btn-accent" onclick="loadLancs()">Filtrar</button>
  </div>
</div>

<div class="metrics mb-2">
  <div class="metric"><div class="metric-label">Entradas</div><div class="metric-value green" id="m-rec">—</div></div>
  <div class="metric"><div class="metric-label">Saídas</div><div class="metric-value red" id="m-desp">—</div></div>
  <div class="metric"><div class="metric-label">Saldo</div><div class="metric-value" id="m-saldo">—</div></div>
  <div class="metric"><div class="metric-label">Total</div><div class="metric-value blue" id="m-qtd">—</div></div>
</div>

<div class="card">
  <div class="table-wrap" id="table-area">
    <div class="empty-state"><p>Carregando...</p></div>
  </div>
</div>

<?php include __DIR__ . '/pages/modal_lancamento.php'; ?>

<script>
let editId = 0;
async function loadLancs() {
  const mes=document.getElementById('f-mes').value,ano=document.getElementById('f-ano').value;
  const dc=document.getElementById('f-dc').value,pago=document.getElementById('f-pago').value;
  const res = await api(`/api/lancamentos.php?action=list&mes=${mes}&ano=${ano}&dc=${dc}&pago=${pago}`);
  const rows=res.data,tot=res.totais;
  document.getElementById('m-rec').textContent=fmtMoeda(tot.receber);
  document.getElementById('m-desp').textContent=fmtMoeda(tot.pagar);
  const sEl=document.getElementById('m-saldo');
  sEl.textContent=fmtMoeda(tot.saldo);sEl.className='metric-value '+(tot.saldo>=0?'green':'red');
  document.getElementById('m-qtd').textContent=rows.length;
  const ta=document.getElementById('table-area');
  if(!rows.length){ta.innerHTML='<div class="empty-state"><p>Nenhum lançamento</p></div>';return;}
  let h=`<table><thead><tr><th>Vencimento</th><th>Competência</th><th>Descrição</th><th>Caixa</th><th>Categoria</th><th>Portador</th><th>Tipo</th><th>Valor</th><th>Pago</th><th>Conc.</th><th></th></tr></thead><tbody>`;
  rows.forEach(r=>{
    const val=r.VALOR_PAGO!=null&&r.VALOR_PAGO!==''?r.VALOR_PAGO:r.VALOR_CALCULADO!=null&&r.VALOR_CALCULADO!==''?r.VALOR_CALCULADO:r.VALOR;
    const isGerador=r.GERADOR_PARCELA==='S';
    const dcClass=r.DC==='D'?'dc-d':'dc-c',dcLabel=r.DC==='D'?'↑ Entrada':'↓ Saída';
    const descExtra=r.PARCELADO==='S'&&!isGerador?` <span class="badge badge-blue">${r.NUMEROPARCELA}/${r.QTDPARCELAS}</span>`:'';
    const genBadge=isGerador?'<span class="badge badge-gray">Gerador</span> ':'';
    h+=`<tr>
      <td>${fmtData(r.DTVENC)}</td>
      <td>${r.COMPT?r.COMPT.substr(0,7):'—'}</td>
      <td>${genBadge}${r.DESCRICAO||'—'}${descExtra}</td>
      <td>${r.NMCAIXA||'—'}</td><td>${r.NMCAT||'—'}</td><td>${r.NMPORTADOR||'—'}</td>
      <td><span class="${dcClass}">${dcLabel}</span></td>
      <td style="font-weight:600">${fmtMoeda(val)}</td>
      <td><span class="badge ${r.PAGO==='S'?'badge-green':'badge-yellow'}" style="cursor:pointer" onclick="togglePago(${r.ID})">${r.PAGO==='S'?'Pago':'Pendente'}</span></td>
      <td><span class="badge ${r.CONCILIADO==='S'?'badge-green':'badge-gray'}">${r.CONCILIADO==='S'?'✓':'—'}</span></td>
      <td><div class="flex gap-2">
        <button class="btn btn-icon btn-sm" onclick="editLanc(${r.ID})"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <button class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete('/api/lancamentos.php?id=${r.ID}','Excluir este lançamento?')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>
      </div></td>
    </tr>`;
  });
  h+='</tbody></table>';ta.innerHTML=h;
}
async function editLanc(id){
  editId=id;const r=await api(`/api/lancamentos.php?action=get&id=${id}`);
  const f=document.getElementById('form-lanc');
  f.querySelector('[name=id]').value=r.ID;f.querySelector('[name=dtlanc]').value=r.DTLANC||'';
  f.querySelector('[name=dtvenc]').value=r.DTVENC||'';f.querySelector('[name=compt]').value=r.COMPT?r.COMPT.substr(0,7):'';
  f.querySelector('[name=idcaixa]').value=r.IDCAIXA||'';f.querySelector('[name=idcat]').value=r.IDCAT||'';
  f.querySelector('[name=idportador]').value=r.IDPORTADOR||'';f.querySelector('[name=dc]').value=r.DC||'C';
  f.querySelector('[name=valor]').value=r.VALOR||'';f.querySelector('[name=valor_calculado]').value=r.VALOR_CALCULADO||'';
  f.querySelector('[name=valor_pago]').value=r.VALOR_PAGO||'';f.querySelector('[name=descricao]').value=r.DESCRICAO||'';
  f.querySelector('[name=pago]').checked=r.PAGO==='S';f.querySelector('[name=conciliado]').checked=r.CONCILIADO==='S';
  document.getElementById('modal-lanc-title').textContent='Editar Lançamento';openModal('modal-lanc');
}
document.getElementById('form-lanc').addEventListener('submit',async e=>{
  e.preventDefault();const f=e.target;
  const data=Object.fromEntries(new FormData(f));
  data.pago=f.querySelector('[name=pago]').checked?'S':'N';
  data.conciliado=f.querySelector('[name=conciliado]').checked?'S':'N';
  data.parcelado=f.querySelector('[name=parcelado]')?.checked?'S':'N';
  data.compt=data.compt?data.compt+'-01':'';
  try{const res=await api('/api/lancamentos.php?action=save',{method:'POST',body:data});
    showToast(res.message);closeModal('modal-lanc');f.reset();editId=0;loadLancs();
  }catch(err){showToast(err.message,'error');}
});
loadLancs();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
