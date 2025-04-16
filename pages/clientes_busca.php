<?php
include '../includes/db.php';
include '../includes/defs.php';

header('Content-Type: application/json');

$termo = isset($_GET['q']) ? trim($_GET['q']) : '';
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite = 20;

$query = "SELECT id, nome FROM clientes WHERE ativo = 1";
$params = [];

if (!empty($termo)) {
    $query .= " AND nome LIKE ?";
    $params[] = "%$termo%";
}

$query .= " ORDER BY nome LIMIT ?, ?";
$params[] = ($pagina - 1) * $limite;
$params[] = $limite;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$resultados = array_map(function($cliente) {
    return [
        'id' => $cliente['id'],
        'text' => $cliente['nome']
    ];
}, $clientes);

echo json_encode([
    'results' => $resultados,
    'pagination' => ['more' => count($resultados) === $limite]
]);