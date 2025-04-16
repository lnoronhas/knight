<?php
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

// Iniciar sessão e verificar autenticação
session_start();
if (!isset($_SESSION['usuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Verificar se o usuário tem permissão (master ou infra)
$usuarioLogado = $_SESSION['usuario'];
if (!in_array($usuarioLogado['tipo'], ['master', 'infra'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

// Desativar todos os agendamentos ativos antes de criar um novo
if (isset($_POST['primeira_semana']) || isset($_POST['dia_semana']) || isset($_POST['dia_mes'])) {
    try {
        $pdo->beginTransaction();

        // Desativar agendamentos ativos existentes
        $pdo->exec("UPDATE agendamentos_checagem SET ativo = 0 WHERE ativo = 1");
        // No tratamento do POST, adicione:
        $todosClientes = isset($_POST['todos_clientes']) ? 1 : 0;
        $clientesIds = null;

        if (!$todosClientes && isset($_POST['clientes_selecionados'])) {
            // Garante que só temos IDs válidos
            $clientesIds = implode(',', array_filter(
                $_POST['clientes_selecionados'],
                function($id) { return is_numeric($id) && $id > 0; }
            ));
        }
        // Preparar dados para inserção
        $agendamento = [
            'dia_semana' => $_POST['frequencia'] === 'semanal' ? $_POST['dia_semana'] : null,
            'dia_mes' => $_POST['frequencia'] === 'mensal' ? $_POST['dia_mes'] : null,
            'primeira_semana' => $_POST['frequencia'] === 'primeira_semana' ? 1 : 0,
            'hora_execucao' => $_POST['hora_execucao'],
            'data_inicio' => !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
            'data_fim' => !empty($_POST['data_fim']) ? $_POST['data_fim'] : null,
            'ativo' => 1,
            'criado_por' => $usuarioLogado['id'],
            'todos_clientes' => $todosClientes,
            'clientes_ids' => $clientesIds,
            'proxima_execucao' => calcularProximaExecucao($_POST) // Você precisará criar esta função

        ];

        // Inserir novo agendamento
        $stmt = $pdo->prepare("INSERT INTO agendamentos_checagem 
                              (dia_semana, dia_mes, primeira_semana, hora_execucao, data_inicio, data_fim, ativo, criado_por)
                              VALUES (:dia_semana, :dia_mes, :primeira_semana, :hora_execucao, :data_inicio, :data_fim, :ativo, :criado_por)");

        $stmt->execute($agendamento);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Agendamento salvo com sucesso']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar agendamento: ' . $e->getMessage()]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
}