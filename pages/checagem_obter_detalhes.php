<?php
include '../includes/db.php';
include '../includes/defs.php';

header('Content-Type: application/json');


try {
    $checagemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$checagemId) {
        throw new Exception("ID da checagem inválido");
    }

    $stmt = $pdo->prepare("SELECT resultado_json FROM checagens WHERE id = ?");
    $stmt->execute([$checagemId]);
    $checagem = $stmt->fetch();

    if (!$checagem || !$checagem['resultado_json']) {
        throw new Exception("Checagem não encontrada");
    }

    // Decodificar e codificar novamente para garantir que é JSON válido
    $resultado = json_decode($checagem['resultado_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Dados da checagem inválidos");
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'erro',
        'resumo' => 'Erro ao carregar detalhes',
        'detalhes' => null
    ]);
}