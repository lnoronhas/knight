<?php
include '../includes/header.php';
include '../includes/db.php';

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
    echo "<p style='color:red;'>Você não tem permissão para editar este usuário.</p>";
    include '../includes/footer.php';
    exit();
  }
}
?>

<h2><?= $usuario ? '✏️ Editar Usuário' : '➕ Novo Usuário' ?></h2>
<form method="POST" action="usuario_salvar.php">
  <input type="hidden" name="id" value="<?= $usuario['id'] ?? '' ?>">
  <label>Nome:</label>
  <input type="text" name="nome" value="<?= $usuario['nome'] ?? '' ?>" required>
  <label>Email:</label>
  <input type="email" name="email" value="<?= $usuario['email'] ?? '' ?>" required>
  <label>Senha: <small>(deixe em branco para manter)</small></label>
  <input type="password" name="senha">
  <label>Tipo:</label>
  <select name="tipo" required>
    <?php if ($tipoLogado === 'master' || $tipoLogado === 'infra'): ?>
      <option value="infra" <?= ($usuario['tipo'] ?? '') === 'infra' ? 'selected' : '' ?>>Infra</option>
    <?php endif; ?>
    <option value="financeiro" <?= ($usuario['tipo'] ?? '') === 'financeiro' ? 'selected' : '' ?>>Financeiro</option>
    <?php if ($tipoLogado === 'master'): ?>
      <option value="master" <?= ($usuario['tipo'] ?? '') === 'master' ? 'selected' : '' ?>>Master</option>
    <?php endif; ?>
  </select>
  <label>
    <input type="checkbox" name="ativo" <?= !isset($usuario) || $usuario['ativo'] ? 'checked' : '' ?>> Ativo
  </label>
  <button type="submit">Salvar</button>
</form>

<?php include '../includes/footer.php'; ?>
