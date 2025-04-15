<?php
include '../includes/db.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_GET['id'] ?? null;
if ($id) {
  try {
    // Inicia transação
    $pdo->beginTransaction();
    
    // Verificar e excluir registros em tabelas relacionadas
    
    // 1. Excluir registros da tabela clientes_modalidades
    $stmt = $pdo->prepare("DELETE FROM clientes_modalidades WHERE cliente_id = ?");
    $stmt->execute([$id]);
    
    // 2. Verificar se há registros em checagens
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM checagens WHERE cliente_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
      // Excluir registros em checagens
      $stmt = $pdo->prepare("DELETE FROM checagens WHERE cliente_id = ?");
      $stmt->execute([$id]);
    }
    
    // 3. Verificar se há registros em conexoes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM conexoes WHERE cliente_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
      // Excluir registros em conexoes
      $stmt = $pdo->prepare("DELETE FROM conexoes WHERE cliente_id = ?");
      $stmt->execute([$id]);
    }
    
    // 4. Finalmente, excluir o cliente
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    
    // Commit da transação
    $pdo->commit();
    
  } catch (Exception $e) {
    // Rollback em caso de erro
    $pdo->rollBack();
    die("Erro ao excluir contrato: " . $e->getMessage());
  }
}

header('Location: contratos.php');
exit();