<?php
include '../includes/defs.php';
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
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- icons bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/style.css">
  
  <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
</head>
<body>
  <header>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-knight">
      <div class="container">
        <a class="navbar-brand" href="../pages/dashboard.php">
          <i class="fas fa-satellite"></i> Knight - Painel de Controle
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="../pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Painel</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../pages/contratos.php"><i class="fas fa-file-contract"></i> Contratos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../pages/checagem.php"><i class="fas fa-satellite-dish"></i> Checagem</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../pages/comparativo.php"><i class="fas fa-chart-bar"></i> Comparativo</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../pages/usuario.php"><i class="fas fa-users"></i> Usuários</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>
  <main class="container py-4">