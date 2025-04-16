<?php
include '../includes/db.php';
include '../includes/functions.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$contratoNome = $_GET['nome'];
$arquivoNome = $_GET['arquivo'];
$visualizar = isset($_GET['visualizar']) ? true : false; // Novo parâmetro

$pastaContrato = "../contratos/" . preg_replace('/[^a-zA-Z0-9-_]/', '', $contratoNome) . "/files/";
$caminhoCompleto = $pastaContrato . $arquivoNome;

if (file_exists($caminhoCompleto)) {
    // Detectar o tipo MIME do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $caminhoCompleto);
    finfo_close($finfo);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    
    // Se for para visualizar, não inclui o header que força download
    if (!$visualizar) {
        header('Content-Disposition: attachment; filename="' . basename($caminhoCompleto) . '"');
    } else {
        header('Content-Disposition: inline; filename="' . basename($caminhoCompleto) . '"');
    }
    
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($caminhoCompleto));
    readfile($caminhoCompleto);
    exit;
} else {
    die("Arquivo não encontrado.");
}