<?php
session_start();
require_once 'config.php';
$IDUSER = authCheck();
$db = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON body
$body = [];
if ($method !== 'GET') {
    $raw = file_get_contents('php://input');
    if ($raw) $body = json_decode($raw, true) ?? [];
    $body = array_merge($_POST, $body);
}

switch ($action) {

    /* ── LIST ─────────────────────────────────────────────── */
    case 'list':
        $mes    = (int)($_GET['mes']    ?? date('n'));
        $ano    = (int)($_GET['ano']    ?? date('Y'));
        $idcaixa= (int)($_GET['idcaixa']?? 0);
        $dc     = $_GET['dc'] ?? '';
        $pago   = $_GET['pago'] ?? '';

        $sql = "SELECT L.*,
                       C.NMCAIXA, CA.NMCAT, P.NMPORTADOR
                FROM LANC L
                LEFT JOIN CAIXA      C  ON C.ID  = L.IDCAIXA
                LEFT JOIN CATEGORIAS CA ON CA.ID = L.IDCAT
                LEFT JOIN PORTADORES P  ON P.ID  = L.IDPORTADOR
                WHERE L.IDUSER = :u
                  AND YEAR(L.COMPT)  = :ano
                  AND MONTH(L.COMPT) = :mes";
        $params = [':u'=>$IDUSER, ':ano'=>$ano, ':mes'=>$mes];

        if ($idcaixa) { $sql .= " AND L.IDCAIXA = :caixa"; $params[':caixa'] = $idcaixa; }
        if ($dc)      { $sql .= " AND L.DC = :dc";         $params[':dc']    = $dc; }
        if ($pago !== '') { $sql .= " AND L.PAGO = :pago"; $params[':pago']  = $pago; }

        $sql .= " ORDER BY L.DTVENC ASC, L.ID ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Totais
        $totReceber = $totPagar = $totPago = 0;
        foreach ($rows as $r) {
            $val = $r['VALOR_PAGO'] ?? ($r['VALOR_CALCULADO'] ?? $r['VALOR']);
            if ($r['DC'] === 'D') $totReceber += $val;
            else                  $totPagar   += $val;
            if ($r['PAGO'] === 'S') $totPago  += ($r['DC']==='D' ? $val : -$val);
        }

        respJSON(['data'=>$rows,'totais'=>['receber'=>$totReceber,'pagar'=>$totPagar,'saldo'=>$totReceber-$totPagar]]);

    /* ── CHART 12 MONTHS ──────────────────────────────────── */
    case 'chart12':
        $mes  = (int)($_GET['mes'] ?? date('n'));
        $ano  = (int)($_GET['ano'] ?? date('Y'));
        $idcaixa = (int)($_GET['idcaixa'] ?? 0);

        // -5 a +6 meses
        $meses = [];
        for ($i = -5; $i <= 6; $i++) {
            $dt = new DateTime("$ano-$mes-01");
            $dt->modify("$i months");
            $meses[] = ['ano'=>(int)$dt->format('Y'),'mes'=>(int)$dt->format('n'),'label'=>$dt->format('M/y')];
        }

        $result = [];
        foreach ($meses as $m) {
            $extra = $idcaixa ? "AND IDCAIXA = $idcaixa" : '';
            $stmt = $db->prepare("SELECT
                SUM(CASE WHEN DC='D' THEN COALESCE(VALOR_PAGO,COALESCE(VALOR_CALCULADO,VALOR)) ELSE 0 END) AS rec,
                SUM(CASE WHEN DC='C' THEN COALESCE(VALOR_PAGO,COALESCE(VALOR_CALCULADO,VALOR)) ELSE 0 END) AS desp
                FROM LANC
                WHERE IDUSER=:u AND YEAR(COMPT)=:a AND MONTH(COMPT)=:m $extra");
            $stmt->execute([':u'=>$IDUSER,':a'=>$m['ano'],':m'=>$m['mes']]);
            $row = $stmt->fetch();
            $result[] = ['label'=>$m['label'],'rec'=>(float)$row['rec'],'desp'=>(float)$row['desp'],'saldo'=>(float)$row['rec']-(float)$row['desp']];
        }
        respJSON($result);

    /* ── SALDO POR CAIXA ──────────────────────────────────── */
    case 'saldo_caixas':
        $mes  = (int)($_GET['mes'] ?? date('n'));
        $ano  = (int)($_GET['ano'] ?? date('Y'));

        $stmt = $db->prepare("SELECT C.ID, C.NMCAIXA, C.TIPO,
            SUM(CASE WHEN L.DC='D' THEN COALESCE(L.VALOR_PAGO,COALESCE(L.VALOR_CALCULADO,L.VALOR)) ELSE 0 END) -
            SUM(CASE WHEN L.DC='C' THEN COALESCE(L.VALOR_PAGO,COALESCE(L.VALOR_CALCULADO,L.VALOR)) ELSE 0 END) AS saldo
            FROM CAIXA C
            LEFT JOIN LANC L ON L.IDCAIXA = C.ID
                AND YEAR(L.COMPT) = :ano AND MONTH(L.COMPT) = :mes
            WHERE C.IDUSER = :u AND C.STATUS = 'A'
            GROUP BY C.ID
            ORDER BY C.NMCAIXA");
        $stmt->execute([':u'=>$IDUSER,':ano'=>$ano,':mes'=>$mes]);
        respJSON($stmt->fetchAll());

    /* ── GET ONE ──────────────────────────────────────────── */
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM LANC WHERE ID=? AND IDUSER=?");
        $stmt->execute([$id, $IDUSER]);
        $row = $stmt->fetch();
        if (!$row) respJSON(['error'=>'Não encontrado'], 404);
        respJSON($row);

    /* ── SAVE (INSERT/UPDATE) ─────────────────────────────── */
    case 'save':
        $id          = (int)($body['id'] ?? 0);
        $dtlanc      = $body['dtlanc']    ?: date('Y-m-d');
        $dtvenc      = $body['dtvenc']    ?: null;
        $compt       = $body['compt']     ?: date('Y-m-01');
        $idcaixa     = (int)($body['idcaixa']  ?? 0) ?: null;
        $idcat       = (int)($body['idcat']    ?? 0) ?: null;
        $idportador  = (int)($body['idportador']?? 0) ?: null;
        $dc          = strtoupper($body['dc'] ?? 'C');
        $valor       = (float)str_replace(',','.',($body['valor']??0));
        $valCalc     = $body['valor_calculado']!=='' ? (float)str_replace(',','.',($body['valor_calculado']??'')) : null;
        $valPago     = $body['valor_pago']!==''       ? (float)str_replace(',','.',($body['valor_pago']??''))      : null;
        $descricao   = $body['descricao']  ?? '';
        $pago        = ($body['pago']??'N')==='S'?'S':'N';
        $conciliado  = ($body['conciliado']??'N')==='S'?'S':'N';
        $parcelado   = ($body['parcelado']??'N')==='S'?'S':'N';
        $qtd         = (int)($body['qtdparcelas'] ?? 0);

        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE LANC SET DTLANC=:dtl,DTVENC=:dtv,COMPT=:cpt,IDCAIXA=:icx,IDCAT=:icat,
                    IDPORTADOR=:iprt,DC=:dc,VALOR=:val,VALOR_CALCULADO=:vcalc,VALOR_PAGO=:vpago,
                    DESCRICAO=:desc,PAGO=:pago,CONCILIADO=:conc
                    WHERE ID=:id AND IDUSER=:u";
            $db->prepare($sql)->execute([
                ':dtl'=>$dtlanc,':dtv'=>$dtvenc,':cpt'=>$compt,
                ':icx'=>$idcaixa,':icat'=>$idcat,':iprt'=>$idportador,
                ':dc'=>$dc,':val'=>$valor,':vcalc'=>$valCalc,':vpago'=>$valPago,
                ':desc'=>$descricao,':pago'=>$pago,':conc'=>$conciliado,
                ':id'=>$id,':u'=>$IDUSER
            ]);
            respJSON(['message'=>'Lançamento atualizado!']);
        }

        // INSERT
        if ($parcelado === 'S' && $qtd > 1) {
            // Registra o gerador (valor=0)
            $stmtG = $db->prepare("INSERT INTO LANC (IDUSER,DTLANC,DTVENC,COMPT,IDCAIXA,IDCAT,IDPORTADOR,DC,VALOR,VALOR_CALCULADO,DESCRICAO,PAGO,CONCILIADO,PARCELADO,QTDPARCELAS,NUMEROPARCELA,GERADOR_PARCELA)
                VALUES (:u,:dtl,:dtv,:cpt,:icx,:icat,:iprt,:dc,0,:vcalc,:desc,:pago,:conc,'S',:qtd,0,'S')");
            $stmtG->execute([
                ':u'=>$IDUSER,':dtl'=>$dtlanc,':dtv'=>$dtvenc,':cpt'=>$compt,
                ':icx'=>$idcaixa,':icat'=>$idcat,':iprt'=>$idportador,
                ':dc'=>$dc,':vcalc'=>$valor,':desc'=>$descricao,
                ':pago'=>$pago,':conc'=>$conciliado,':qtd'=>$qtd
            ]);

            // Gera parcelas
            $valorParcela = round($valor / $qtd, 2);
            $stmtP = $db->prepare("INSERT INTO LANC (IDUSER,DTLANC,DTVENC,COMPT,IDCAIXA,IDCAT,IDPORTADOR,DC,VALOR,VALOR_CALCULADO,DESCRICAO,PAGO,CONCILIADO,PARCELADO,QTDPARCELAS,NUMEROPARCELA,GERADOR_PARCELA)
                VALUES (:u,:dtl,:dtv,:cpt,:icx,:icat,:iprt,:dc,:val,:vcalc,:desc,'N','N','S',:qtd,:num,'N')");

            $dtBase = new DateTime($dtvenc ?: $dtlanc);
            $cptBase = new DateTime($compt);
            for ($i = 1; $i <= $qtd; $i++) {
                $dtParc = clone $dtBase; if ($i > 1) $dtParc->modify("+".($i-1)." months");
                $cptParc = clone $cptBase; if ($i > 1) $cptParc->modify("+".($i-1)." months");
                $stmtP->execute([
                    ':u'=>$IDUSER,':dtl'=>$dtlanc,
                    ':dtv'=>$dtParc->format('Y-m-d'),
                    ':cpt'=>$cptParc->format('Y-m-01'),
                    ':icx'=>$idcaixa,':icat'=>$idcat,':iprt'=>$idportador,
                    ':dc'=>$dc,':val'=>$valor,':vcalc'=>$valorParcela,
                    ':desc'=>$descricao." ($i/$qtd)",
                    ':qtd'=>$qtd,':num'=>$i
                ]);
            }
            respJSON(['message'=>"$qtd parcelas criadas!"]);
        }

        // Lançamento simples
        $stmtI = $db->prepare("INSERT INTO LANC (IDUSER,DTLANC,DTVENC,COMPT,IDCAIXA,IDCAT,IDPORTADOR,DC,VALOR,VALOR_CALCULADO,VALOR_PAGO,DESCRICAO,PAGO,CONCILIADO,PARCELADO,QTDPARCELAS)
            VALUES (:u,:dtl,:dtv,:cpt,:icx,:icat,:iprt,:dc,:val,:vcalc,:vpago,:desc,:pago,:conc,:parc,:qtd)");
        $stmtI->execute([
            ':u'=>$IDUSER,':dtl'=>$dtlanc,':dtv'=>$dtvenc,':cpt'=>$compt,
            ':icx'=>$idcaixa,':icat'=>$idcat,':iprt'=>$idportador,
            ':dc'=>$dc,':val'=>$valor,':vcalc'=>$valCalc,':vpago'=>$valPago,
            ':desc'=>$descricao,':pago'=>$pago,':conc'=>$conciliado,
            ':parc'=>$parcelado,':qtd'=>$qtd
        ]);
        respJSON(['message'=>'Lançamento criado!', 'id'=>$db->lastInsertId()]);

    /* ── TOGGLE PAGO ──────────────────────────────────────── */
    case 'toggle_pago':
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        $stmt = $db->prepare("SELECT PAGO FROM LANC WHERE ID=? AND IDUSER=?");
        $stmt->execute([$id, $IDUSER]);
        $row = $stmt->fetch();
        if (!$row) respJSON(['error'=>'Não encontrado'], 404);
        $novo = $row['PAGO'] === 'S' ? 'N' : 'S';
        $db->prepare("UPDATE LANC SET PAGO=? WHERE ID=? AND IDUSER=?")->execute([$novo, $id, $IDUSER]);
        respJSON(['message'=> $novo==='S' ? 'Marcado como pago' : 'Marcado como pendente', 'pago'=>$novo]);

    /* ── DELETE ───────────────────────────────────────────── */
    default:
        if ($method === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            $db->prepare("DELETE FROM LANC WHERE ID=? AND IDUSER=?")->execute([$id, $IDUSER]);
            respJSON(['message'=>'Excluído']);
        }
        respJSON(['error'=>'Ação inválida'], 400);
}
