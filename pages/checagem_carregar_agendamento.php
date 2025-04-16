<?php
include '../includes/db.php';
include '../includes/defs.php';

header('Content-Type: application/json');

// Verificar autenticação
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit();
}

$agendamentoId = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM agendamentos_checagem WHERE id = ?");
$stmt->execute([$agendamentoId]);
$agendamento = $stmt->fetch();

if ($agendamento) {
    // Buscar nomes dos clientes se não for para todos
    if (!$agendamento['todos_clientes'] && !empty($agendamento['clientes_ids'])) {
        $clientesIds = explode(',', $agendamento['clientes_ids']);
        $placeholders = implode(',', array_fill(0, count($clientesIds), '?'));
        
        $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE id IN ($placeholders) ORDER BY nome");
        $stmt->execute($clientesIds);
        $agendamento['clientes_selecionados'] = $stmt->fetchAll();
    }
    
    echo json_encode(['success' => true, 'data' => $agendamento]);
}

if (!$agendamento) {
    echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado']);
    exit();
}

echo json_encode(['success' => true, 'data' => $agendamento]);