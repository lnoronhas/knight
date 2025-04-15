<?php
include '../includes/db.php';
include '../includes/functions.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_POST['id'] ?? '';
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];
$tipo = $_POST['tipo'];
$ativo = isset($_POST['ativo']) ? 1 : 0;

if (!podeEditarTipo($tipo)) {
  die("Você não tem permissão para definir este tipo de usuário.");
}

if ($id) {
  if ($senha) {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, tipo = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $senhaHash, $tipo, $ativo, $id]);
  } else {
    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$nome, $email, $tipo, $ativo, $id]);
  }
} else {
  $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$nome, $email, $senhaHash, $tipo, $ativo]);
}

header('Location: usuario.php');
exit();
