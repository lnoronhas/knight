<?php
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/defs.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_POST['id'] ?? '';
$nome = $_POST['nome'];
$bilhetagem = isset($_POST['bilhetagem']) ? (int)$_POST['bilhetagem'] : 0;
$qtd_bilhetagem = $_POST['qtd_bilhetagem'];
$ativo = isset($_POST['ativo']) ? 1 : 0;
$modalidades = $_POST['modalidades'] ?? [];
$novasModalidades = $_POST['novas_modalidades'] ?? [];

// Início da transação
$pdo->beginTransaction();

try {
  // Salvar ou atualizar cliente
  if ($id) {
    $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, bilhetagem = ?, qtd_bilhetagem = ?, ativo = ?, atualizado_em = NOW() WHERE id = ?");
    $stmt->execute([$nome, $bilhetagem, $qtd_bilhetagem, $ativo, $id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO clientes (nome, bilhetagem, qtd_bilhetagem, ativo, criado_em, atualizado_em) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$nome, $bilhetagem, $qtd_bilhetagem, $ativo]);
    $id = $pdo->lastInsertId();
    
    // Criar pastas para o novo contrato
    $pastaContrato = "../contratos/" . preg_replace('/[^a-zA-Z0-9-_]/', '', $nome);
    if (!file_exists($pastaContrato)) {
        mkdir($pastaContrato, 0777, true);
        mkdir($pastaContrato . "/checagens", 0777);
        mkdir($pastaContrato . "/files", 0777);
    }
  }
  
  // Processar novas modalidades (inserir na tabela modalidades)
  foreach ($novasModalidades as $novaModalidade) {
    if (!empty($novaModalidade['sigla']) && !empty($novaModalidade['nome'])) {
      // Verificar se a sigla já existe
      $stmt = $pdo->prepare("SELECT id FROM modalidades WHERE sigla = ?");
      $stmt->execute([$novaModalidade['sigla']]);
      $existingModalidade = $stmt->fetch();
      
      if ($existingModalidade) {
        // Usar a modalidade existente
        $modalidadeId = $existingModalidade['id'];
      } else {
        // Inserir nova modalidade
        $stmt = $pdo->prepare("INSERT INTO modalidades (sigla, nome) VALUES (?, ?)");
        $stmt->execute([$novaModalidade['sigla'], $novaModalidade['nome']]);
        $modalidadeId = $pdo->lastInsertId();
      }
      
      // Associar nova modalidade ao cliente
      if (!empty($novaModalidade['quantidade'])) {
        $modalidades[$modalidadeId] = [
          'modalidade_id' => $modalidadeId,
          'quantidade' => $novaModalidade['quantidade']
        ];
      }
    }
  }
  
  // Remover todas as modalidades existentes para este cliente
  $stmt = $pdo->prepare("DELETE FROM clientes_modalidades WHERE cliente_id = ?");
  $stmt->execute([$id]);
  
  // Inserir modalidades selecionadas
  if (!empty($modalidades)) {
    foreach ($modalidades as $modalidadeId => $dados) {
      // Verificar se o checkbox foi marcado (deduzido pela disponibilidade do campo quantidade)
      if (isset($dados['quantidade']) && !empty($dados['quantidade'])) {
        $stmt = $pdo->prepare("INSERT INTO clientes_modalidades (cliente_id, modalidade_id, quantidade) VALUES (?, ?, ?)");
        $stmt->execute([$id, $modalidadeId, $dados['quantidade']]);
      }
    }
  }
  
  // Commit da transação
  $pdo->commit();
  
  header('Location: contratos.php');
  exit();
} catch (Exception $e) {
  // Rollback em caso de erro
  $pdo->rollBack();
  die("Erro ao salvar contrato: " . $e->getMessage());
}