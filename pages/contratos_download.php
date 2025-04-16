<?php
include '../includes/db.php';
include '../includes/functions.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$contratoNome = $_GET['nome'];
$arquivoNome = $_GET['arquivo'];

$pastaContrato = "../contratos/" . preg_replace('/[^a-zA-Z0-9-_]/', '', $contratoNome) . "/files/";
$caminhoCompleto = $pastaContrato . $arquivoNome;

if (file_exists($caminhoCompleto)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($caminhoCompleto) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($caminhoCompleto));
    readfile($caminhoCompleto);
    exit;
} else {
    die("Arquivo não encontrado.");
}