<?php
// Incluir necessários
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

// Validar acesso
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar permissões
$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

// Somente usuários com tipo adequado podem excluir
if (!in_array($tipoLogado, ['master', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permissão negada']);
    exit;
}

// Obter dados da requisição
$arquivo = $_POST['arquivo'] ?? '';
$contratoNome = $_POST['contrato_nome'] ?? '';
$contratoId = $_POST['contrato_id'] ?? 0;

if (empty($arquivo) || empty($contratoNome) || empty($contratoId)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

// Caminho do arquivo
$filePath = "../contratos/" . sanitizeFolderName($contratoNome) . "/files/" . $arquivo;

// Verificar se o arquivo existe
if (!file_exists($filePath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado']);
    exit;
}

// Tentar excluir o arquivo
if (unlink($filePath)) {
    echo json_encode(['success' => true, 'message' => 'Arquivo excluído com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir arquivo']);
}
?>