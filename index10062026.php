<?php require_once __DIR__ . '/includes/header.php'; ?> 

<div class="page-header flex-column-mobile">
  <div>
    <div class="page-title">Dashboard</div> 
    <div class="page-subtitle">Visão geral das suas finanças</div> 
  </div>
  
  <div class="header-actions">
    <a href="cadastro_lancamento.php" class="btn btn-accent btn-icon-only" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="3">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>Novo lançamento</span>
    </a>
  </div>
</div>

<div class="mobile-filter-bar" onclick="openModal('modal-filtros')">
  <div class="filter-summary">
    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2">
      <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
    </svg>
    <span id="filter-text-summary">Filtrando: Este Mês</span>
  </div>
  <span class="filter-badge-count">✎</span>
</div>

<div class="filters desktop-filters-only">
  <div class="filter-group">
    <label>Mês</label>
    <select id="f-mes">
      <?php
      $meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
      $mAtual = intval(date('m'));
      for($i=1; $i<=12; $i++){
        $sel = ($i === $mAtual)? 'selected':'';
        echo "<option value='$i' $sel>".$meses[$i-1]."</option>";
      }
      ?>
    </select>
  </div>
  
  <div class="filter-group">
    <label>Ano</label>
    <select id="f-ano">
      <?php
      $anoAtual = intval(date('Y'));
      for($a=$anoAtual-2; $a<=$anoAtual+2; $a++){
        $sel = ($a === $anoAtual)? 'selected':'';
        echo "<option value='$a' $sel>$a</option>";
      }
      ?>
    </select>
  </div>

  <div class="filter-group">
    <label>Caixa / Conta</label>
    <select id="f-caixa">
      <option value="">Todos</option>
      <?php
      $db = getDB();
      $cq = $db->prepare("SELECT ID, NMCAIXA FROM CAIXA WHERE IDUSER=? AND STATUS='A' ORDER BY NMCAIXA");
      $cq->execute([$IDUSER]);
      foreach($cq->fetchAll() as $c){
        echo "<option value='{$c['ID']}'>{$c['NMCAIXA']}</option>";
      }
      ?>
    </select>
  </div>

  <div class="filter-group">
    <label>Fluxo</label>
    <select id="f-dc">
      <option value="">Todos</option>
      <option value="D">Débito (-)</option>
      <option value="C">Crédito (+)</option>
    </select>
  </div>

  <div class="filter-group">
    <label>Status</label>
    <select id="f-pago">
      <option value="">Todos</option>
      <option value="S">Pago</option>
      <option value="N">Pendente</option>
    </select>
  </div>
</div>



<div class="summary-cards" id="summary-container">
  <div class="card card-summary loading"></div>
  <div class="card card-summary loading"></div>
  <div class="card card-summary loading"></div>
</div>

<div class="dashboard-grid">
  <div class="card" style="min-height:320px">
    <div class="card-title">Fluxo de Caixa Mensal</div>
    <div style="position:relative; width:100%; height:240px">
      <canvas id="chart-fluxo"></canvas>
    </div>
  </div>
  
  <div class="card" style="min-height:320px">
    <div class="card-title">Despesas por Categoria</div>
    <div style="position:relative; width:100%; height:240px">
      <canvas id="chart-categorias"></canvas>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-title" style="margin-bottom:1rem">Últimos Lançamentos</div>
  <div class="table-responsive">
    <table class="table-custom" id="table-lancamentos">
      <thead>
        <tr>
          <th>Data</th>
          <th>Descrição</th>
          <th>Categoria</th>
          <th>Caixa</th>
          <th>Portador</th>
          <th class="text-right">Valor</th>
          <th class="text-center">Status</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="8" class="text-center text-muted">Carregando lançamentos...</td></tr>
      </tbody>
    </table>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Armazenamento dos gráficos globais
let chartFluxo = null;
let chartCats = null;

// Salva e carrega o estado dos filtros no localStorage do navegador
function saveFilterState() {
  const state = {
    mes: document.getElementById('f-mes').value,
    ano: document.getElementById('f-ano').value,
    caixa: document.getElementById('f-caixa').value,
    dc: document.getElementById('f-dc').value,
    pago: document.getElementById('f-pago').value
  };
  localStorage.setItem('lef_filters', JSON.stringify(state));
}

function loadFilterState() {
  const saved = localStorage.getItem('lef_filters');
  if (saved) {
    try {
      const state = JSON.parse(saved);
      if(state.mes) document.getElementById('f-mes').value = state.mes;
      if(state.ano) document.getElementById('f-ano').value = state.ano;
      if(state.caixa) document.getElementById('f-caixa').value = state.caixa;
      if(state.dc) document.getElementById('f-dc').value = state.dc;
      if(state.pago) document.getElementById('f-pago').value = state.pago;
    } catch(e) { console.error("Erro ao carregar filtros", e); }
  }
}

// Atualiza o resumo de texto exibido na barra móvel
function updateFilterSummary() {
  const mesSel = document.getElementById('f-mes');
  const txtMes = mesSel.options[mesSel.selectedIndex]?.text || '';
  const txtAno = document.getElementById('f-ano').value;
  const summaryEl = document.getElementById('filter-text-summary');
  if (summaryEl) {
    summaryEl.textContent = `Filtrando: ${txtMes} de ${txtAno}`;
  }
}

// Faz a requisição à API para puxar os dados atualizados do backend
async function loadDashboard() {
  const m = document.getElementById('f-mes').value;
  const a = document.getElementById('f-ano').value;
  const cx = document.getElementById('f-caixa').value;
  const dc = document.getElementById('f-dc').value;
  const pg = document.getElementById('f-pago').value;

  try {
    const res = await fetch(`api/dashboard.php?mes=${m}&ano=${a}&caixa=${cx}&dc=${dc}&pago=${pg}`);
    const d = await res.json();
    if (!res.ok) throw new Error(d.error || 'Erro na requisição');

    renderSummary(d.summary);
    renderTable(d.lancamentos);
    renderCharts(d.charts);
  } catch(e) {
    showToast(e.message, 'error');
  }
}

function renderSummary(s) {
  const container = document.getElementById('summary-container');
  container.innerHTML = `
    <div class="card card-summary">
      <div class="text-muted text-sm">Receitas</div>
      <div class="value text-success">${fmtMoeda(s.receitas)}</div>
    </div>
    <div class="card card-summary">
      <div class="text-muted text-sm">Despesas</div>
      <div class="value text-danger">${fmtMoeda(s.despesas)}</div>
    </div>
    <div class="card card-summary">
      <div class="text-muted text-sm">Saldo Mensal</div>
      <div class="value ${s.saldo >= 0 ? 'text-success' : 'text-danger'}">${fmtMoeda(s.saldo)}</div>
    </div>
  `;
}

function renderTable(list) {
  const tbody = document.querySelector('#table-lancamentos tbody');
  if (!list || list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Nenhum lançamento encontrado para os filtros selecionados.</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(l => `
    <tr>
      <td>${fmtData(l.DTVENC || l.DTLANC)}</td>
      <td><strong>${l.DESCRICAO}</strong></td>
      <td><span class="badge" style="background:var(--surface2)">${l.NMCAT || 'Sem Cat'}</span></td>
      <td><span class="text-muted">${l.NMCAIXA || '—'}</span></td>
      <td><span class="text-muted">${l.NMPORTADOR || '—'}</span></td>
      <td class="text-right ${l.DC === 'C' ? 'text-success' : 'text-danger'}">
        <strong>${l.DC === 'C' ? '+' : '-'} ${fmtMoeda(l.VALOR)}</strong>
      </td>
      <td class="text-center">
        <button onclick="togglePago(${l.ID})" class="badge-status ${l.PAGO === 'S' ? 'pago' : 'pendente'}">
          ${l.PAGO === 'S' ? 'Pago' : 'Pendente'}
        </button>
      </td>
      <td class="text-center">
        <div class="flex gap-2" style="justify-content:center">
          <a href="cadastro_lancamento.php?id=${l.ID}" class="btn-action" title="Editar">✏️</a>
          <button onclick="confirmDelete('api/lancamentos.php?id=${l.ID}')" class="btn-action btn-delete" title="Excluir">❌</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderCharts(cData) {
  // Gráfico de Barras: Fluxo Mensal
  if (chartFluxo) chartFluxo.destroy();
  const ctxFluxo = document.getElementById('chart-fluxo').getContext('2d');
  chartFluxo = new Chart(ctxFluxo, {
    type: 'bar',
    data: {
      labels: cData.fluxo.labels,
      datasets: [
        { label: 'Receitas', data: cData.fluxo.receitas, backgroundColor: '#5de0a0', borderRadius: 4 },
        { label: 'Despesas', data: cData.fluxo.despesas, backgroundColor: '#f05060', borderRadius: 4 }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { grid: { color: '#2a2d35' } }, x: { grid: { display: false } } } }
  });

  // Gráfico de Rosca: Categorias
  if (chartCats) chartCats.destroy();
  const ctxCats = document.getElementById('chart-categorias').getContext('2d');
  chartCats = new Chart(ctxCats, {
    type: 'doughnut',
    data: {
      labels: cData.categorias.labels,
      datasets: [{ data: cData.categorias.valores, backgroundColor: ['#60d4f0','#c8f060','#f0b060','#f05060','#5de0a0','#7a7f8e'], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
  });
}

// Apenas sincroniza os valores caso use o mobile, sem mover as divs do lugar
function setupResponsiveFilters() {
  // Mantido vazio pois agora usamos a duplicação estática segura via CSS para evitar quebras de layout
}

function applyMobileFilters() {
  // Sincroniza os selects do modal mobile para os selects oficiais do desktop
  const filtros = ['mes', 'ano', 'caixa', 'dc', 'pago'];
  filtros.forEach(f => {
    const mEl = document.getElementById(`m-f-${f}`);
    const dEl = document.getElementById(`f-${f}`);
    if (mEl && dEl) dEl.value = mEl.value;
  });

  saveFilterState();
  updateFilterSummary();
  closeModal('modal-filtros');
  loadDashboard();
}





function bindFilterChangeEvents() {
  const filters = ['f-mes', 'f-ano', 'f-caixa', 'f-dc', 'f-pago'];
  filters.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('change', () => {
        saveFilterState();
        updateFilterSummary();
        if (window.innerWidth > 768) {
          loadDashboard();
        }
      });
    }
  });
}

window.addEventListener('resize', setupResponsiveFilters);

document.addEventListener('DOMContentLoaded', () => {
  loadFilterState();
  setupResponsiveFilters();
  bindFilterChangeEvents();
  updateFilterSummary();
  loadDashboard();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>