<?php
  session_start();
  if (isset($_SESSION['usuario'])) {
    header('Location: ../pages/dashboard.php');
    exit();
  }

  $erro = $_GET['erro'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Knight - Login</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
</head>
<body>
  <div class="login-container">
    <h2><i class="fas fa-shield-halved"></i> Knight</h2>
    <form method="POST" action="../includes/login.php">
      <div class="form-group">
        <label>Usuário:</label>
        <input type="email" name="email" id="email" required>
      </div>
      <div class="form-group">
        <label for="senha">Senha</label>
        <input type="password" name="senha" id="senha" required>
      </div>
      <button type="submit" class="btn">Entrar</button>
    </form>
    <div class="login-footer">
      Painel de controle de contratos e checagens
  </div>
</body>
</html>
