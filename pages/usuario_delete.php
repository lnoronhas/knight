<?php
include '../includes/db.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

function podeEditarTipo($tipoAlvo) {
  global $tipoLogado;
  if ($tipoLogado === 'master') return true;
  if ($tipoLogado === 'infra') return in_array($tipoAlvo, ['infra', 'financeiro']);
  if ($tipoLogado === 'financeiro') return $tipoAlvo === 'financeiro';
  return false;
}

$id = $_GET['id'] ?? null;
if ($id) {
  $stmt = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
  $stmt->execute([$id]);
  $alvo = $stmt->fetch();

  if ($alvo && podeEditarTipo($alvo['tipo'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
  }
}

header('Location: usuario.php');
exit();
