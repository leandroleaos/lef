<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Visão geral das suas finanças</div>
  </div>
  <button class="btn btn-accent" onclick="openModal('modal-lanc')">
    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Novo lançamento
  </button>
</div>

<!-- Filtros -->
<div class="filters">
  <div class="filter-group">
    <label>Mês</label>
    <select id="f-mes">
      <?php
      $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
      for ($i=1; $i<=12; $i++) {
          $sel = $i == (int)date('n') ? 'selected' : '';
          echo "<option value='$i' $sel>{$meses[$i-1]}</option>";
      }
      ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Ano</label>
    <select id="f-ano">
      <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): $sel=$y==date('Y')?'selected':''; ?>
        <option value="<?=$y?>" <?=$sel?>><?=$y?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Caixa</label>
    <select id="f-caixa">
      <option value="0">Todos</option>
      <?php
      $cxs = getDB()->prepare("SELECT ID,NMCAIXA FROM CAIXA WHERE IDUSER=? AND STATUS='A' ORDER BY NMCAIXA");
      $cxs->execute([$IDUSER]);
      foreach ($cxs->fetchAll() as $cx)
          echo "<option value='{$cx['ID']}'>{$cx['NMCAIXA']}</option>";
      ?>
    </select>
  </div>
  <div class="filter-group">
    <label>Tipo</label>
    <select id="f-dc">
      <option value="">Todos</option>
      <option value="D">Entradas</option>
      <option value="C">Saídas</option>
    </select>
  </div>
  <div class="filter-group">
    <label>Status</label>
    <select id="f-pago">
      <option value="">Todos</option>
      <option value="N">Pendentes</option>
      <option value="S">Pagos</option>
    </select>
  </div>
  <button class="btn btn-accent" onclick="loadDashboard()">
    <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.56"/></svg>
    Atualizar
  </button>
</div>

<!-- Metric Cards -->
<div class="metrics" id="metrics-area">
  <div class="metric"><div class="metric-label">Entradas</div><div class="metric-value green" id="m-rec">—</div></div>
  <div class="metric"><div class="metric-label">Saídas</div><div class="metric-value red" id="m-desp">—</div></div>
  <div class="metric"><div class="metric-label">Saldo do mês</div><div class="metric-value" id="m-saldo">—</div></div>
  <div class="metric"><div class="metric-label">Lançamentos</div><div class="metric-value blue" id="m-qtd">—</div></div>
</div>

<!-- Gráfico 12 meses -->
<div class="chart-wrap mb-3" id="chart-wrap">
  <div class="chart-title">Fluxo — últimos 6 e próximos 6 meses</div>
  <div class="bar-chart" id="bar-chart"></div>
  <div style="display:flex;gap:16px;margin-top:.75rem">
    <span style="font-size:.72rem;color:var(--success)">■ Entradas</span>
    <span style="font-size:.72rem;color:var(--danger)">■ Saídas</span>
  </div>
</div>

<!-- Saldo por Caixa -->
<div class="card mb-3" id="card-caixas">
  <div class="chart-title">Saldo por caixa no período</div>
  <div id="caixas-list" style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.75rem"></div>
</div>

<!-- Tabela de Lançamentos -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
    <span style="font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)">Lançamentos</span>
    <span id="count-info" class="text-sm text-muted"></span>
  </div>
  <div class="table-wrap" id="table-area">
    <div class="empty-state">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p>Carregando...</p>
    </div>
  </div>
</div>

<!-- Modal Lançamento -->
<?php include __DIR__ . '/pages/modal_lancamento.php'; ?>

<script>
let editId = 0;

async function loadDashboard() {
  const mes = document.getElementById('f-mes').value;
  const ano = document.getElementById('f-ano').value;
  const idcaixa = document.getElementById('f-caixa').value;
  const dc = document.getElementById('f-dc').value;
  const pago = document.getElementById('f-pago').value;

  const qs = `mes=${mes}&ano=${ano}&idcaixa=${idcaixa}&dc=${dc}&pago=${pago}`;

  // Lançamentos + métricas
  const res = await api(`/api/lancamentos.php?action=list&${qs}`);
  const rows = res.data;
  const tot = res.totais;

  document.getElementById('m-rec').textContent   = fmtMoeda(tot.receber);
  document.getElementById('m-desp').textContent  = fmtMoeda(tot.pagar);
  const saldoEl = document.getElementById('m-saldo');
  saldoEl.textContent = fmtMoeda(tot.saldo);
  saldoEl.className = 'metric-value ' + (tot.saldo >= 0 ? 'green' : 'red');
  document.getElementById('m-qtd').textContent = rows.length;
  document.getElementById('count-info').textContent = `${rows.length} registros`;

  // Tabela
  const ta = document.getElementById('table-area');
  if (!rows.length) {
    ta.innerHTML = `<div class="empty-state"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>Nenhum lançamento no período</p></div>`;
    return;
  }

  let html = `<table><thead><tr>
    <th>Vencimento</th><th>Descrição</th><th>Caixa</th><th>Categoria</th><th>Portador</th>
    <th>Tipo</th><th>Valor</th><th>Pago</th><th>Conc.</th><th></th>
  </tr></thead><tbody>`;

  rows.forEach(r => {
    const val = r.VALOR_PAGO != null && r.VALOR_PAGO !== '' ? r.VALOR_PAGO
              : r.VALOR_CALCULADO != null && r.VALOR_CALCULADO !== '' ? r.VALOR_CALCULADO
              : r.VALOR;
    const isPago = r.PAGO === 'S';
    const isConc = r.CONCILIADO === 'S';
    const isGerador = r.GERADOR_PARCELA === 'S';
    const dcClass = r.DC === 'D' ? 'dc-d' : 'dc-c';
    const dcLabel = r.DC === 'D' ? '↑ Entrada' : '↓ Saída';
    const descExtra = r.PARCELADO === 'S' && !isGerador ? ` <span class="badge badge-blue">${r.NUMEROPARCELA}/${r.QTDPARCELAS}</span>` : '';
    const genBadge = isGerador ? `<span class="badge badge-gray">Gerador</span> ` : '';

    html += `<tr>
      <td>${fmtData(r.DTVENC)}</td>
      <td>${genBadge}${r.DESCRICAO || '—'}${descExtra}</td>
      <td>${r.NMCAIXA || '—'}</td>
      <td>${r.NMCAT || '—'}</td>
      <td>${r.NMPORTADOR || '—'}</td>
      <td><span class="${dcClass}">${dcLabel}</span></td>
      <td style="font-weight:600">${fmtMoeda(val)}</td>
      <td><span class="badge ${isPago?'badge-green':'badge-yellow'}" style="cursor:pointer" onclick="togglePago(${r.ID})">${isPago?'Pago':'Pendente'}</span></td>
      <td><span class="badge ${isConc?'badge-green':'badge-gray'}">${isConc?'✓':'—'}</span></td>
      <td>
        <div class="flex gap-2">
          <button class="btn btn-icon btn-sm" onclick="editLanc(${r.ID})" title="Editar">
            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete('/api/lancamentos.php?id=${r.ID}','Excluir este lançamento?')" title="Excluir">
            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  });

  html += '</tbody></table>';
  ta.innerHTML = html;

  // Gráfico
  loadChart(mes, ano, idcaixa);
  loadSaldoCaixas(mes, ano);
}

async function loadChart(mes, ano, idcaixa) {
  const data = await api(`/api/lancamentos.php?action=chart12&mes=${mes}&ano=${ano}&idcaixa=${idcaixa}`);
  const bc = document.getElementById('bar-chart');
  const maxVal = Math.max(...data.map(d => Math.max(d.rec, d.desp)), 1);
  bc.innerHTML = data.map(d => {
    const hr = Math.max(4, (d.rec / maxVal) * 80);
    const hd = Math.max(4, (d.desp / maxVal) * 80);
    return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">
      <div style="display:flex;gap:2px;align-items:flex-end;height:80px;width:100%">
        <div class="bar inc" style="height:${hr}px" title="Entradas: ${fmtMoeda(d.rec)}"></div>
        <div class="bar exp" style="height:${hd}px" title="Saídas: ${fmtMoeda(d.desp)}"></div>
      </div>
      <div class="bar-label">${d.label}</div>
    </div>`;
  }).join('');
}

async function loadSaldoCaixas(mes, ano) {
  const data = await api(`/api/lancamentos.php?action=saldo_caixas&mes=${mes}&ano=${ano}`);
  const el = document.getElementById('caixas-list');
  if (!data.length) { el.innerHTML = '<span class="text-muted text-sm">Nenhum caixa</span>'; return; }
  el.innerHTML = data.map(c => {
    const s = parseFloat(c.saldo);
    const cls = s >= 0 ? 'green' : 'red';
    return `<div class="metric" style="min-width:160px">
      <div class="metric-label">${c.NMCAIXA}</div>
      <div class="metric-value ${cls}">${fmtMoeda(s)}</div>
      <div class="metric-badge">${c.TIPO||''}</div>
    </div>`;
  }).join('');
}

async function editLanc(id) {
  editId = id;
  const r = await api(`/api/lancamentos.php?action=get&id=${id}`);
  const f = document.getElementById('form-lanc');
  f.querySelector('[name=id]').value          = r.ID;
  f.querySelector('[name=dtlanc]').value      = r.DTLANC || '';
  f.querySelector('[name=dtvenc]').value      = r.DTVENC || '';
  f.querySelector('[name=compt]').value       = r.COMPT ? r.COMPT.substr(0,7) : '';
  f.querySelector('[name=idcaixa]').value     = r.IDCAIXA || '';
  f.querySelector('[name=idcat]').value       = r.IDCAT || '';
  f.querySelector('[name=idportador]').value  = r.IDPORTADOR || '';
  f.querySelector('[name=dc]').value          = r.DC || 'C';
  f.querySelector('[name=valor]').value       = r.VALOR || '';
  f.querySelector('[name=valor_calculado]').value = r.VALOR_CALCULADO || '';
  f.querySelector('[name=valor_pago]').value  = r.VALOR_PAGO || '';
  f.querySelector('[name=descricao]').value   = r.DESCRICAO || '';
  f.querySelector('[name=pago]').checked      = r.PAGO === 'S';
  f.querySelector('[name=conciliado]').checked= r.CONCILIADO === 'S';
  document.getElementById('modal-lanc-title').textContent = 'Editar Lançamento';
  openModal('modal-lanc');
}

document.getElementById('form-lanc').addEventListener('submit', async e => {
  e.preventDefault();
  const f = e.target;
  const data = Object.fromEntries(new FormData(f));
  data.pago       = f.querySelector('[name=pago]').checked ? 'S' : 'N';
  data.conciliado = f.querySelector('[name=conciliado]').checked ? 'S' : 'N';
  data.parcelado  = f.querySelector('[name=parcelado]')?.checked ? 'S' : 'N';
  data.compt      = data.compt ? data.compt + '-01' : '';
  try {
    const res = await api('/api/lancamentos.php?action=save', { method:'POST', body: data });
    showToast(res.message);
    closeModal('modal-lanc');
    f.reset();
    editId = 0;
    loadDashboard();
  } catch(err) {
    showToast(err.message, 'error');
  }
});

loadDashboard();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
