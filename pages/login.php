<?php
	include '../includes/defs.php';
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
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/style.css">
  
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
  <div class="login-container p-4 w-100" style="max-width: 400px;">
    <h2 class="text-center mb-4"><i class="fas fa-shield-halved"></i> Knight</h2>
    
    <?php if ($erro): ?>
    <div class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle"></i> Usuário ou senha inválidos.
    </div>
    <?php endif; ?>
    
    <form method="POST" action="../includes/login.php">
      <div class="mb-3">
        <label for="email" class="form-label">Usuário:</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary">
            <i class="fas fa-user text-white"></i>
          </span>
          <input type="email" class="form-control" name="email" id="email" required>
        </div>
      </div>
      <div class="mb-3">
        <label for="senha" class="form-label">Senha:</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary">
            <i class="fas fa-lock"></i>
          </span>
          <input type="password" class="form-control" name="senha" id="senha" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-sign-in-alt"></i> Entrar
      </button>
    </form>
    <div class="login-footer text-center mt-3">
      <small>Painel de controle de contratos e checagens</small>
    </div>
  </div>

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>