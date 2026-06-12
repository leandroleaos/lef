<?php
// Listas para o select
$db = getDB();
$caixasOpts = $db->prepare("SELECT ID,NMCAIXA FROM CAIXA WHERE IDUSER=? AND STATUS='A' ORDER BY NMCAIXA");
$caixasOpts->execute([$IDUSER]);
$caixasList = $caixasOpts->fetchAll();

$catsOpts = $db->prepare("SELECT ID,NMCAT,TIPO FROM CATEGORIAS WHERE IDUSER=? ORDER BY TIPO,NMCAT");
$catsOpts->execute([$IDUSER]);
$catsList = $catsOpts->fetchAll();

$portsOpts = $db->prepare("SELECT ID,NMPORTADOR FROM PORTADORES WHERE IDUSER=? ORDER BY NMPORTADOR");
$portsOpts->execute([$IDUSER]);
$portsList = $portsOpts->fetchAll();
?>
<div class="modal-backdrop" id="modal-lanc">
  <div class="modal-container">
    <div class="modal-header">
      <span class="modal-title" id="modal-lanc-title">Novo Lançamento</span>
      <button class="modal-close" onclick="closeModal('modal-lanc');document.getElementById('form-lanc').reset();editId=0;document.getElementById('modal-lanc-title').textContent='Novo Lançamento'">✕</button>
    </div>
    <form id="form-lanc">
      <div class="modal-body">
        <input type="hidden" name="id" value="0">
        <div class="form-grid">

          <div class="form-group">
            <label>Tipo</label>
            <select name="dc">
              <option value="C">↓ Saída / Despesa</option>
              <option value="D">↑ Entrada / Receita</option>
            </select>
          </div>

          <div class="form-group">
            <label>Competência (mês)</label>
            <input type="month" name="compt" value="<?= date('Y-m') ?>">
          </div>


          <div class="form-group">
            <label>Valor total (R$)</label>
            <input type="number" name="valor" step="0.01" min="0" placeholder="0,00" required>
          </div>

          <div class="form-group ">
            <label>Dt. Vencimento</label>
            <input type="date" name="dtvenc" value="<?= date('Y-m-d') ?>">
          </div>


          <div class="form-group">
            <label>Caixa / Conta</label>
            <select name="idcaixa">
              <option value="">-- Selecione --</option>
              <?php foreach ($caixasList as $c): ?>
                <option value="<?=$c['ID']?>"><?= htmlspecialchars($c['NMCAIXA']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Categoria</label>
            <select name="idcat">
              <option value="">-- Selecione --</option>
              <?php
              $grupo = '';
              foreach ($catsList as $c):
                if ($c['TIPO'] !== $grupo) {
                    if ($grupo) echo '</optgroup>';
                    $grupo = $c['TIPO'];
                    $glabel = $grupo === 'E' ? '↑ Entradas' : '↓ Saídas';
                    echo "<optgroup label='$glabel'>";
                }
              ?>
                <option value="<?=$c['ID']?>"><?= htmlspecialchars($c['NMCAT']) ?></option>
              <?php endforeach; if ($grupo) echo '</optgroup>'; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Portador</label>
            <select name="idportador">
              <option value="">-- Selecione --</option>
              <?php foreach ($portsList as $p): ?>
                <option value="<?=$p['ID']?>"><?= htmlspecialchars($p['NMPORTADOR']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group full">
            <label>Descrição</label>
            <textarea name="descricao" placeholder="Descrição do lançamento..."></textarea>
          </div>


          <div class="form-row-inline" style="display:flex;gap:1.5rem;align-items:center">
            <label class="form-check">
              <input type="checkbox" name="pago"> Pago
            </label>
            <label class="form-check">
              <input type="checkbox" name="conciliado"> Conciliado
            </label>
            <label class="form-check">
              <input type="checkbox" name="parcelado" id="chk-parcelado"> Parcelado
            </label>
          </div>

          <div class="form-group full" id="parcela-box" style="display:none;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group">
              <label>Nº de parcelas</label>
              <input type="number" name="qtdparcelas" min="2" max="60" placeholder="Ex: 12">
            </div>
            <div class="form-group">
              <label style="color:var(--accent2)">O sistema gerará as parcelas automaticamente</label>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('modal-lanc')">Cancelar</button>
        <button type="submit" class="btn btn-accent">Salvar lançamento</button>
      </div>
    </form>
  </div>
</div>

<style>
#parcela-box { display: none; }
#parcela-box.visible { display: grid; }
</style>
<script>
document.getElementById('chk-parcelado').addEventListener('change', function() {
  document.getElementById('parcela-box').style.display = this.checked ? 'grid' : 'none';
});
</script>
