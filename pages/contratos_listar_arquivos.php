<?php
include '../includes/db.php';
include '../includes/functions.php';
session_start();

$contratoId = $_GET['id'];
$contratoNome = $_GET['nome'];

$pastaContrato = "../contratos/" . preg_replace('/[^a-zA-Z0-9-_]/', '', $contratoNome) . "/files/";

if (!file_exists($pastaContrato)) {
    echo '<div class="alert alert-info">Nada por aqui ainda.</div>';
    exit;
}

$arquivos = scandir($pastaContrato);
$arquivos = array_diff($arquivos, array('.', '..'));

if (empty($arquivos)) {
    echo '<div class="alert alert-info">Nada por aqui ainda.</div>';
} else {
    echo '<ul class="list-group">';
    foreach ($arquivos as $arquivo) {
        $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
        $tamanho = filesize($pastaContrato . $arquivo) / 1024; // KB
        
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        if ($extensao === 'pdf') {
            echo '<a href="#" onclick="visualizarPDF(\'' . htmlspecialchars($arquivo) . '\', \'' . htmlspecialchars($contratoNome) . '\')">';
            echo htmlspecialchars($arquivo);
            echo '</a>';
        } else {
            echo '<a href="contratos_download.php?nome=' . urlencode($contratoNome) . '&arquivo=' . urlencode($arquivo) . '" target="_blank">';
            echo htmlspecialchars($arquivo);
            echo '</a>';
        }
        echo '<span class="badge bg-secondary rounded-pill">' . round($tamanho, 2) . ' KB</span>';
        echo '</li>';
    }
    echo '</ul>';
}