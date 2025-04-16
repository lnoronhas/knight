<?php
include '../includes/db.php';
include '../includes/functions.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$contratoId = $_POST['contrato_id'];

// Buscar nome do contrato
$stmt = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
$stmt->execute([$contratoId]);
$contrato = $stmt->fetch();

if (!$contrato) {
    die("Contrato não encontrado.");
}

$pastaContrato = "../contratos/" . preg_replace('/[^a-zA-Z0-9-_]/', '', $contrato['nome']) . "/files/";

if (!file_exists($pastaContrato)) {
    die("Pasta do contrato não encontrada.");
}

if (isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    
    // Validar arquivo
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nomeArquivo = uniqid() . '.' . $extensao;
    $caminhoCompleto = $pastaContrato . $nomeArquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "Nenhum arquivo enviado.";
}