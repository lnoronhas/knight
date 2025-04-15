<?php
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/defs.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_GET['id'] ?? null;
$acao = $_GET['acao'] ?? 'desativar';

if (!$id) {
  header('Location: contratos.php');
  exit();
}

// Verificar permissões
if ($acao === 'apagar' && !podeEditarTipo('master')) {
  // Tentativa de apagar sem permissão de master
  $_SESSION['mensagem'] = [
    'tipo' => 'danger',
    'texto' => 'Você não tem permissão para apagar contratos.'
  ];
  header('Location: contratos.php');
  exit();
}

// Obter status atual do contrato
$stmt = $pdo->prepare("SELECT ativo FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$contrato = $stmt->fetch();

if (!$contrato) {
  $_SESSION['mensagem'] = [
    'tipo' => 'danger',
    'texto' => 'Contrato não encontrado.'
  ];
  header('Location: contratos.php');
  exit();
}

try {
  $pdo->beginTransaction();
  
  if ($acao === 'apagar') {
    // Verificar se usuário tem permissão para apagar (apenas master)
    if (podeEditarTipo('master')) {
      // Excluir registros relacionados
      
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
      
      $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => 'Contrato excluído com sucesso.'
      ];
    } else {
      throw new Exception("Você não tem permissão para excluir contratos.");
    }
  } else {
    // Desativar/Ativar contrato (alternar status)
    $novoStatus = $contrato['ativo'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE clientes SET ativo = ?, atualizado_em = NOW() WHERE id = ?");
    $stmt->execute([$novoStatus, $id]);
    
    $_SESSION['mensagem'] = [
      'tipo' => 'success',
      'texto' => $novoStatus ? 'Contrato ativado com sucesso.' : 'Contrato desativado com sucesso.'
    ];
  }
  
  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollBack();
  
  $_SESSION['mensagem'] = [
    'tipo' => 'danger',
    'texto' => 'Erro ao processar ação: ' . $e->getMessage()
  ];
}

header('Location: contratos.php');
exit();