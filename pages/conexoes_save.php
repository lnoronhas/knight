<?php
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/defs.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

// Verificar permissão
if (!in_array($tipoLogado, ['master', 'infra'])) {
    header('Location: contratos.php');
    exit();
}

$id = $_POST['id'] ?? '';
$cliente_id = $_POST['cliente_id'];
$ipv6 = $_POST['ipv6'];
$usuario = $_POST['usuario'];
$senha = $_POST['senha'];
$tipo_banco = $_POST['tipo_banco'];
$versao_infra = $_POST['versao_infra'];
$dbname = $_POST['dbname'];

try {
    $pdo->beginTransaction();

    if ($id) {
        // Atualizar conexão existente
        $stmt = $pdo->prepare("UPDATE conexoes SET 
                              ipv6 = ?, usuario = ?, senha = ?, tipo_banco = ?, 
                              versao_infra = ?, dbname = ?
                              WHERE id = ?");
        $stmt->execute([$ipv6, $usuario, $senha, $tipo_banco, $versao_infra, $dbname, $id]);
    } else {
        // Criar nova conexão
        $stmt = $pdo->prepare("INSERT INTO conexoes 
                              (cliente_id, ipv6, usuario, senha, tipo_banco, versao_infra, dbname) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cliente_id, $ipv6, $usuario, $senha, $tipo_banco, $versao_infra, $dbname]);
    }

    $pdo->commit();

    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => 'Conexão ' . ($id ? 'atualizada' : 'cadastrada') . ' com sucesso!'
    ];

    header("Location: contratos.php");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();

    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro ao salvar conexão: ' . $e->getMessage()
    ];

    header("Location: " . ($id ? "conexoes_form.php?id=$id" : "conexoes_form.php?cliente_id=$cliente_id"));
    exit();
}