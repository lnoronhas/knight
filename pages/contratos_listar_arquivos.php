<?php
// Incluir necessários
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

// Validar acesso
session_start();
if (!isset($_SESSION['usuario'])) {
    echo '<div class="alert alert-danger">Acesso não autorizado</div>';
    exit;
}

// Obter parâmetros
$contratoId = $_GET['id'] ?? 0;
$contratoNome = $_GET['nome'] ?? '';

if (empty($contratoId) || empty($contratoNome)) {
    echo '<div class="alert alert-warning">Informações de contrato inválidas</div>';
    exit;
}

// Verificar se diretório existe
$dirPath = "../contratos/" . $contratoNome . "/files";

if (!file_exists($dirPath)) {
    // Criar pasta se não existir
    if (!mkdir($dirPath, 0755, true)) {
        echo '<div class="alert alert-danger">Erro ao criar diretório para arquivos</div>';
        exit;
    }
}

// Listar arquivos
$arquivos = array_diff(scandir($dirPath), array('..', '.'));

if (count($arquivos) == 0) {
    echo '<div class="alert alert-info">Nenhum arquivo encontrado para este contrato.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-dark table-striped">';
    echo '<thead><tr><th>Arquivo</th><th>Tamanho</th><th>Data</th><th>Ações</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($arquivos as $arquivo) {
        $filePath = $dirPath . '/' . $arquivo;
        $fileSize = filesize($filePath);
        $fileDate = date('d/m/Y H:i', filemtime($filePath));
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($arquivo) . '</td>';
        echo '<td>' . formatBytes($fileSize) . '</td>';
        echo '<td>' . $fileDate . '</td>';
        echo '<td>';
        
        // Adicionar botões baseados no tipo de arquivo
        $extension = pathinfo($arquivo, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'pdf') {
            echo '<button type="button" class="btn btn-sm btn-info me-1" onclick="visualizarPDF(\'' . htmlspecialchars($arquivo) . '\', \'' . htmlspecialchars($contratoNome) . '\')"><i class="fas fa-eye"></i></button>';
        }
        
        // Usar o script de download existente
        echo '<a href="contratos_download.php?nome=' . urlencode($contratoNome) . '&arquivo=' . urlencode($arquivo) . '" class="btn btn-sm btn-success me-1"><i class="fas fa-download"></i></a>';
        
        echo '<button type="button" class="btn btn-sm btn-danger" onclick="excluirArquivo(\'' . htmlspecialchars($arquivo) . '\', \'' . htmlspecialchars($contratoNome) . '\', ' . $contratoId . ')"><i class="fas fa-trash"></i></button>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
}

// Função para formatar bytes em KB, MB, etc.
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
