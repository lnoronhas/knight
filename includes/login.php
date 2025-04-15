<?php
session_start();
include '../includes/defs.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  include '../includes/db.php';

  $email = $_POST['email'] ?? '';
  $senha = $_POST['senha'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
  $stmt->execute([$email]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario && password_verify($senha, $usuario['senha'])) {
    $_SESSION['usuario'] = [
      'id' => $usuario['id'],
      'nome' => $usuario['nome'],
      'email' => $usuario['email'],
      'tipo' => $usuario['tipo']
    ];
    header('Location: ../pages/dashboard.php');
    exit();
  }
}

header('Location: ../pages/login.php?erro=1');
exit();
