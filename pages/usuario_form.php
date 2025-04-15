<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

function podeEditarTipo($tipoAlvo) {
  global $tipoLogado;
  if ($tipoLogado === 'master') return true;
  if ($tipoLogado === 'infra') return in_array($tipoAlvo, ['infra', 'financeiro']);
  if ($tipoLogado === 'financeiro') return $tipoAlvo === 'financeiro';
  return false;
}

$id = $_GET['id'] ?? '';
$usuario = null;

if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
  $stmt->execute([$id]);
  $usuario = $stmt->fetch();
  if (!$usuario || !podeEditarTipo($usuario['tipo'])) {
    echo '<div class="alert alert-danger">Você não tem permissão para editar este usuário.</div>';
    include '../includes/footer.php';
    exit();
  }
}
?>

<div class="row mb-4">
  <div class="col-12">
    <h2><?= $usuario ? '<i class="fas fa-edit"></i> Editar Usuário' : '<i class="fas fa-plus-circle"></i> Novo Usuário' ?></h2>
  </div>
</div>

<div class="card bg-knight">
  <div class="card-body">
    <form method="POST" action="usuario_salvar.php">
      <input type="hidden" name="id" value="<?= $usuario['id'] ?? '' ?>">
      
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="nome" class="form-label">Nome:</label>
          <input type="text" class="form-control" id="nome" name="nome" value="<?= $usuario['nome'] ?? '' ?>" required>
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label">Email:</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= $usuario['email'] ?? '' ?>" required>
        </div>
      </div>
      
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="senha" class="form-label">Senha: <small class="text-muted">(deixe em branco para manter)</small></label>
          <input type="password" class="form-control" id="senha" name="senha">
        </div>
        <div class="col-md-6">
          <label for="tipo" class="form-label">Tipo:</label>
          <select class="form-select" id="tipo" name="tipo" required>
            <?php if ($tipoLogado === 'master' || $tipoLogado === 'infra'): ?>
              <option value="infra" <?= ($usuario['tipo'] ?? '') === 'infra' ? 'selected' : '' ?>>Infra</option>
            <?php endif; ?>
            <option value="financeiro" <?= ($usuario['tipo'] ?? '') === 'financeiro' ? 'selected' : '' ?>>Financeiro</option>
            <?php if ($tipoLogado === 'master'): ?>
              <option value="master" <?= ($usuario['tipo'] ?? '') === 'master' ? 'selected' : '' ?>>Master</option>
            <?php endif; ?>
          </select>
        </div>
      </div>
      
      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" 
                 <?= !isset($usuario) || $usuario['ativo'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Ativo</label>
        </div>
      </div>
      
      <div class="d-flex justify-content-between">
        <a href="usuario.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button type="submit" class="btn btn-knight">
          <i class="fas fa-save"></i> Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>