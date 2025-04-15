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

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT cliente_id FROM conexoes WHERE id = ?");
$stmt->execute([$id]);
$conexao = $stmt->fetch();

if (!$conexao) {
    $_SESSION['mensagem'] = [
        'tipo' => 'warning',
        'texto' => 'Conexão não encontrada!'
    ];
    header('Location: contratos.php');
    exit();
}

if ($id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM conexoes WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Conexão excluída com sucesso!'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();

        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao excluir conexão: ' . $e->getMessage()
        ];
    }
}

header('Location: contratos.php');
exit();