<?php
session_start();
require_once 'config.php';
$IDUSER = authCheck();
$db = getDB();

$entity = basename($_SERVER['PHP_SELF'], '.php'); // caixas|categorias|portadores
$method = $_SERVER['REQUEST_METHOD'];

$body = [];
if ($method !== 'GET') {
    $raw = file_get_contents('php://input');
    if ($raw) $body = json_decode($raw, true) ?? [];
    $body = array_merge($_POST, $body);
}

$tableMap = [
    'caixas'     => ['table'=>'CAIXA',      'label'=>'Caixa'],
    'categorias' => ['table'=>'CATEGORIAS', 'label'=>'Categoria'],
    'portadores' => ['table'=>'PORTADORES', 'label'=>'Portador'],
];

if (!isset($tableMap[$entity])) respJSON(['error'=>'Entidade inválida'], 400);
$t = $tableMap[$entity]['table'];
$label = $tableMap[$entity]['label'];

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'list':
        $stmt = $db->prepare("SELECT * FROM $t WHERE IDUSER=? ORDER BY 3");
        $stmt->execute([$IDUSER]);
        respJSON($stmt->fetchAll());

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM $t WHERE ID=? AND IDUSER=?");
        $stmt->execute([$id, $IDUSER]);
        $row = $stmt->fetch();
        if (!$row) respJSON(['error'=>'Não encontrado'], 404);
        respJSON($row);

    case 'save':
        $id = (int)($body['id'] ?? 0);

        if ($entity === 'caixas') {
            $nm   = trim($body['nmcaixa'] ?? '');
            $tipo = $body['tipo'] ?? 'conta';
            $venc = (int)($body['vencimento'] ?? 0) ?: null;
            $fech = (int)($body['fechamento'] ?? 0) ?: null;
            $stat = ($body['status'] ?? 'A') === 'I' ? 'I' : 'A';
            if (!$nm) respJSON(['error'=>'Nome obrigatório'], 422);
            if ($id) {
                $db->prepare("UPDATE CAIXA SET NMCAIXA=?,TIPO=?,VENCIMENTO=?,FECHAMENTO=?,STATUS=? WHERE ID=? AND IDUSER=?")
                   ->execute([$nm,$tipo,$venc,$fech,$stat,$id,$IDUSER]);
            } else {
                $db->prepare("INSERT INTO CAIXA (IDUSER,NMCAIXA,TIPO,VENCIMENTO,FECHAMENTO,STATUS) VALUES (?,?,?,?,?,?)")
                   ->execute([$IDUSER,$nm,$tipo,$venc,$fech,$stat]);
            }
        } elseif ($entity === 'categorias') {
            $nm    = trim($body['nmcat'] ?? '');
            $tipo  = strtoupper($body['tipo'] ?? 'S');
            $grupo = trim($body['grupo'] ?? '');
            if (!$nm) respJSON(['error'=>'Nome obrigatório'], 422);
            if ($id) {
                $db->prepare("UPDATE CATEGORIAS SET NMCAT=?,TIPO=?,GRUPO=? WHERE ID=? AND IDUSER=?")
                   ->execute([$nm,$tipo,$grupo,$id,$IDUSER]);
            } else {
                $db->prepare("INSERT INTO CATEGORIAS (IDUSER,NMCAT,TIPO,GRUPO) VALUES (?,?,?,?)")
                   ->execute([$IDUSER,$nm,$tipo,$grupo]);
            }
        } elseif ($entity === 'portadores') {
            $nm = trim($body['nmportador'] ?? '');
            if (!$nm) respJSON(['error'=>'Nome obrigatório'], 422);
            if ($id) {
                $db->prepare("UPDATE PORTADORES SET NMPORTADOR=? WHERE ID=? AND IDUSER=?")
                   ->execute([$nm,$id,$IDUSER]);
            } else {
                $db->prepare("INSERT INTO PORTADORES (IDUSER,NMPORTADOR) VALUES (?,?)")
                   ->execute([$IDUSER,$nm]);
            }
        }
        respJSON(['message'=>"$label salvo!"]);

    default:
        if ($method === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            try {
                $db->prepare("DELETE FROM $t WHERE ID=? AND IDUSER=?")->execute([$id, $IDUSER]);
                respJSON(['message'=>"$label excluído"]);
            } catch (PDOException $e) {
                respJSON(['error'=>"Não é possível excluir: existem registros vinculados."], 409);
            }
        }
        respJSON(['error'=>'Ação inválida'], 400);
}
