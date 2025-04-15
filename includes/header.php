<?php
  session_start();
  if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit();
  }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Knight - Controle de Equipamentos</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
  <header>
    <h1>🛰️ Knight - Painel de Controle</h1>
    <nav>
      <a href="../pages/dashboard.php">Painel</a>
      <a href="../pages/contratos.php">Contratos</a>
      <a href="../pages/checagem.php">Checagem</a>
      <a href="../pages/comparativo.php">Comparativo</a>
      <a href="../pages/usuario.php">Usuários</a>
      <a href="../pages/logout.php">Logout</a>
    </nav>
  </header>
  <main>