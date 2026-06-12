<?php
// 1. Define imediatamente que a resposta é puramente JSON
header('Content-Type: application/json; charset=utf-8');

// 2. IMPORTANTE: Em vez de carregar o header.php visual, carregamos apenas a lógica de sessão e banco
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega as configurações essenciais (onde estão o banco getDB() e o authCheck())
require_once __DIR__ . '/../includes/config.php'; 

// Valida se o usuário está logado e pega o ID sem renderizar nenhuma tag HTML na tela
$IDUSER = authCheck(); 

if (!$IDUSER) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$db = getDB(); 

// Pega os filtros vindos da requisição GET
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));
$caixa = isset($_GET['caixa']) && $_GET['caixa'] !== '' ? intval($_GET['caixa']) : null;
$dc = isset($_GET['dc']) && $_GET['dc'] !== '' ? $_GET['dc'] : null;
$pago = isset($_GET['pago']) && $_GET['pago'] !== '' ? $_GET['pago'] : null;

// Formata o período para bater com o padrão de data do banco (ex: '2026-06-%')
$periodo = sprintf('%04d-%02d-%%', $ano, $mes);

try {
    // 1. QUERY DOS LANÇAMENTOS
    $sql = "SELECT l.*, c.NMCAT, cx.NMCAIXA, p.NMPORTADOR 
            FROM LANC l
            LEFT JOIN CATEGORIAS c ON l.IDCAT = c.ID
            LEFT JOIN CAIXA cx ON l.IDCAIXA = cx.ID
            LEFT JOIN PORTADORES p ON l.IDPORTADOR = p.ID
            WHERE l.IDUSER = ? AND (l.DTLANC LIKE ? OR l.DTVENC LIKE ?)";
    
    $params = [$IDUSER, $periodo, $periodo];

    if ($caixa) { $sql .= " AND l.IDCAIXA = ?"; $params[] = $caixa; }
    if ($dc) { $sql .= " AND l.DC = ?"; $params[] = $dc; }
    if ($pago) { $sql .= " AND l.PAGO = ?"; $params[] = $pago; }

    $sql .= " ORDER BY l.DTVENC DESC, l.DTLANC DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. CÁLCULO DOS CARD-SUMMARIES
    $receitas = 0;
    $despesas = 0;

    foreach ($lancamentos as $l) {
        $valor = floatval($l['VALOR']);
        if ($l['DC'] === 'C') {
            $receitas += $valor;
        } else {
            $despesas += $valor;
        }
    }
    $saldo = $receitas - $despesas;

    // 3. ESTRUTURAÇÃO DOS GRÁFICOS
    // Gráfico mensal por dia
    $fluxoDias = [];
    foreach ($lancamentos as $l) {
        $dataRef = $l['DTVENC'] ? $l['DTVENC'] : $l['DTLANC'];
        $dia = date('d', strtotime($dataRef));
        if (!isset($fluxoDias[$dia])) {
            $fluxoDias[$dia] = ['receitas' => 0, 'despesas' => 0];
        }
        if ($l['DC'] === 'C') $fluxoDias[$dia]['receitas'] += floatval($l['VALOR']);
        else $fluxoDias[$dia]['despesas'] += floatval($l['VALOR']);
    }
    ksort($fluxoDias);

    // Gráfico de Rosca por Categorias de Despesa
    $catAgrupadas = [];
    foreach ($lancamentos as $l) {
        if ($l['DC'] === 'D') {
            $nomeCat = $l['NMCAT'] ? $l['NMCAT'] : 'Sem Categoria';
            if (!isset($catAgrupadas[$nomeCat])) $catAgrupadas[$nomeCat] = 0;
            $catAgrupadas[$nomeCat] += floatval($l['VALOR']);
        }
    }

    // 4. RETORNO DO JSON TOTALMENTE LIMPO
    echo json_encode([
        'summary' => [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'saldo' => $saldo
        ],
        'lancamentos' => $lancamentos,
        'charts' => [
            'fluxo' => [
                'labels' => array_keys($fluxoDias),
                'receitas' => array_column($fluxoDias, 'receitas'),
                'despesas' => array_column($fluxoDias, 'despesas')
            ],
            'categorias' => [
                'labels' => array_keys($catAgrupadas),
                'valores' => array_values($catAgrupadas)
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}