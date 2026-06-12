<?php 
require_once __DIR__ . '/includes/header.php'; 

// Carrega as listas do banco de dados para os selects
$db = getDB();
$caixasOpts = $db->prepare("SELECT ID, NMCAIXA, TIPO, VENCIMENTO, FECHAMENTO FROM CAIXA WHERE IDUSER=? AND STATUS='A' ORDER BY NMCAIXA");
$caixasOpts->execute([$IDUSER]);
$caixasList = $caixasOpts->fetchAll();

$catsOpts = $db->prepare("SELECT ID, NMCAT, TIPO FROM CATEGORIAS WHERE IDUSER=? ORDER BY TIPO,NMCAT");
$catsOpts->execute([$IDUSER]);
$catsList = $catsOpts->fetchAll();

$portsOpts = $db->prepare("SELECT ID, NMPORTADOR FROM PORTADORES WHERE IDUSER=? ORDER BY NMPORTADOR");
$portsOpts->execute([$IDUSER]);
$portsList = $portsOpts->fetchAll();

// Verifica se é uma Edição ou Novo Lançamento
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lancamento = null;

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM LANC WHERE ID = ? AND IDUSER = ?");
    $stmt->execute([$id, $IDUSER]);
    $lancamento = $stmt->fetch();
}
?>

<div class="main-content">
  <div class="page-header">
    <div>
      <div class="page-title"><?= $id > 0 ? 'Editar Lançamento' : 'Novo Lançamento' ?></div>
      <div class="page-subtitle">Preencha os dados financeiros abaixo</div>
    </div>
    <a href="index.php" class="btn" style="background: var(--surface2); color: var(--text);">
      Voltar para o Dashboard
    </a>
  </div>

  <div class="card" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <form id="form-lanc-tela">
      <input type="hidden" name="id" id="lanc-id" value="<?= $id ?>">
      
      <div class="form-grid">
        <div class="form-group">
          <label>Fluxo</label>
          <select name="dc" id="txt-dc">
            <option value="D" <?= ($lancamento && $lancamento['DC'] == 'D') ? 'selected' : '' ?>>Débito (-)</option>
            <option value="C" <?= ($lancamento && $lancamento['DC'] == 'C') ? 'selected' : '' ?>>Crédito (+)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Caixa / Conta</label>
          <select name="idcaixa" id="txt-idcaixa" onchange="ajustarFluxoCamposTela()">
            <?php foreach($caixasList as $c): ?>
              <option value="<?= $c['ID'] ?>" 
                      data-tipo="<?= $c['TIPO'] ?>" 
                      data-vencimento="<?= $c['VENCIMENTO'] ?>"
                      <?= ($lancamento && $lancamento['IDCAIXA'] == $c['ID']) ? 'selected' : '' ?>>
                <?= $c['NMCAIXA'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Valor Principal</label>
          <input type="number" name="valor" step="0.01" required placeholder="R$ 0,00" value="<?= $lancamento ? $lancamento['VALOR'] : '' ?>">
        </div>

        <div class="form-group">
          <label>Categoria</label>
          <select name="idcat">
            <?php foreach($catsList as $cat): ?>
              <option value="<?= $cat['ID'] ?>" <?= ($lancamento && $lancamento['IDCAT'] == $cat['ID']) ? 'selected' : '' ?>><?= $cat['NMCAT'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Portador</label>
          <select name="idportador">
            <?php foreach($portsList as $p): ?>
              <option value="<?= $p['ID'] ?>" <?= ($lancamento && $lancamento['IDPORTADOR'] == $p['ID']) ? 'selected' : '' ?>><?= $p['NMPORTADOR'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group full">
          <label>Descrição</label>
          <input type="text" name="descricao" required placeholder="Ex: Supermercado" value="<?= $lancamento ? $lancamento['DESCRICAO'] : '' ?>">
        </div>

        <div class="form-group campo-dinamico" id="box-dtlanc">
          <label>Data do Lançamento</label>
          <input type="date" name="dtlanc" id="txt-dtlanc" value="<?= $lancamento ? $lancamento['DTLANC'] : '' ?>">
        </div>

        <div class="form-group campo-dinamico" id="box-dtvenc">
          <label>Data de Vencimento</label>
          <input type="date" name="dtvenc" id="txt-dtvenc" value="<?= $lancamento ? $lancamento['DTVENC'] : '' ?>">
        </div>

        <div class="form-group campo-dinamico" id="box-compt">
          <label>Competência</label>
          <input type="date" name="compt" id="txt-compt" value="<?= $lancamento ? $lancamento['COMPT'] : '' ?>">
        </div>

        <div class="form-group campo-dinamico" id="box-vcalculado">
          <label>Valor Calculado</label>
          <input type="number" name="valor_calculado" step="0.01" value="<?= $lancamento ? $lancamento['VALOR_CALCULADO'] : '' ?>">
        </div>

        <div class="form-group campo-dinamico" id="box-vpago">
          <label>Valor Pago</label>
          <input type="number" name="valor_pago" step="0.01" value="<?= $lancamento ? $lancamento['VALOR_PAGO'] : '' ?>">
        </div>

        <div class="form-row-inline">
          <label class="form-check">
            <input type="checkbox" name="pago" id="chk-pago" <?= ($lancamento && $lancamento['PAGO'] == 'S') ? 'checked' : '' ?>> Pago
          </label>
          <label class="form-check">
            <input type="checkbox" name="conciliado" id="chk-conciliado" <?= ($lancamento && $lancamento['CONCILIADO'] == 'S') ? 'checked' : '' ?>> Conciliado
          </label>
          <label class="form-check">
            <input type="checkbox" name="parcelado" id="chk-parcelado" <?= ($lancamento && $lancamento['PARCELADO'] == 'S') ? 'checked' : '' ?>> Parcelado
          </label>
        </div>

        <div class="form-group full" id="parcela-box-tela" style="display: none;">
          <label>Quantidade de Parcelas</label>
          <input type="number" name="qtdparcelas" min="2" max="60" value="<?= $lancamento ? $lancamento['QTDPARCELAS'] : '' ?>">
        </div>

      </div>

      <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
        <a href="index.php" class="btn" style="background: transparent; border: 1px solid var(--border);">Cancelar</a>
        <button type="submit" class="btn btn-accent">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<script>
function ajustarFluxoCamposTela() {
  const idLanc = parseInt(document.getElementById('lanc-id').value) || 0;
  const selectCaixa = document.getElementById('txt-idcaixa');
  if (!selectCaixa) return;

  const opt = selectCaixa.options[selectCaixa.selectedIndex];
  const tipoCaixa = opt ? opt.getAttribute('data-tipo') : '';
  
  const dtLanc = document.getElementById('txt-dtlanc');
  const dtVenc = document.getElementById('txt-dtvenc');
  const compt = document.getElementById('txt-compt');

  // Se for NOVO REGISTRO (ID = 0), esconde o excesso de campos baseado no tipo de Caixa
  if (idLanc === 0) {
    if (!dtLanc.value) dtLanc.value = new Date().toISOString().split('T')[0];

    document.getElementById('box-vcalculado').style.display = 'none';
    document.getElementById('box-vpago').style.display = 'none';

    if (tipoCaixa === 'CONTA') {
      document.getElementById('box-dtlanc').style.display = 'none';
      document.getElementById('box-compt').style.display = 'none';
      document.getElementById('box-dtvenc').style.display = 'flex'; // Foco no vencimento da conta
    } else if (tipoCaixa === 'CARTAO') {
      document.getElementById('box-dtlanc').style.display = 'none';
      document.getElementById('box-dtvenc').style.display = 'none'; // Calculado pela fatura automaticamente
      document.getElementById('box-compt').style.display = 'flex'; // Foco no mês de referência da compra
    } else {
      definirVisibilidadeBloco('flex');
    }
  } else {
    // Se for EDIÇÃO, força exibição total com scroll nativo do smartphone
    definirVisibilidadeBloco('flex');
  }
}

function definirVisibilidadeBloco(status) {
  document.getElementById('box-dtlanc').style.display = status;
  document.getElementById('box-dtvenc').style.display = status;
  document.getElementById('box-compt').style.display = status;
  document.getElementById('box-vcalculado').style.display = status;
  document.getElementById('box-vpago').style.display = status;
}

// Controle do checkbox de parcelas
document.getElementById('chk-parcelado').addEventListener('change', function() {
  document.getElementById('parcela-box-tela').style.display = this.checked ? 'block' : 'none';
});

// Inicialização automática
document.addEventListener('DOMContentLoaded', () => {
  ajustarFluxoCamposTela();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>