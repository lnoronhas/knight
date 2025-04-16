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

// Verificar se há arquivo enviado
if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] != 0) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload']);
    exit;
}

// Obter dados do formulário
$contratoId = $_POST['contrato_id'] ?? 0;
$contratoNome = $_POST['contrato_nome'] ?? '';

if (empty($contratoId) || empty($contratoNome)) {
    echo json_encode(['success' => false, 'message' => 'Informações de contrato inválidas']);
    exit;
}

// Verificar se o contrato existe
$stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
$stmt->execute([$contratoId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Contrato não encontrado']);
    exit;
}

// Sanitizar nome do contrato para uso em pasta
$pastaNome = sanitizeFolderName($contratoNome);
$dirPath = "../contratos/" . $pastaNome . "/files";

// Criar diretório se não existir
if (!file_exists($dirPath)) {
    if (!mkdir($dirPath, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório para arquivos']);
        exit;
    }
}

// Nome do arquivo
$fileName = $_FILES['arquivo']['name'];
// Sanitizar nome do arquivo
$fileName = preg_replace('/[^a-zA-Z0-9-_.]/', '', $fileName);
$targetFile = $dirPath . '/' . $fileName;

// Verificar se arquivo já existe
if (file_exists($targetFile)) {
    $fileInfo = pathinfo($fileName);
    $fileName = $fileInfo['filename'] . '_' . date('YmdHis') . '.' . $fileInfo['extension'];
    $fileName = preg_replace('/[^a-zA-Z0-9-_.]/', '', $fileName);
    $targetFile = $dirPath . '/' . $fileName;
}

// Fazer upload
if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $targetFile)) {
    echo json_encode(['success' => true, 'message' => 'Arquivo enviado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar arquivo']);
}
?>