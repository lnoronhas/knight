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

// Listar usuários visíveis conforme permissão
if ($tipoLogado === 'master') {
  $usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll();
} elseif ($tipoLogado === 'infra') {
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tipo IN ('infra', 'financeiro') ORDER BY nome");
  $stmt->execute();
  $usuarios = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tipo = 'financeiro' ORDER BY nome");
  $stmt->execute();
  $usuarios = $stmt->fetchAll();
}
?>

<h2>👥 Gerenciar Usuários</h2>
<a href="usuario_form.php" class="btn">➕ Novo Usuário</a>

<table>
  <thead>
    <tr>
      <th>Nome</th>
      <th>Email</th>
      <th>Tipo</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= $u['nome'] ?></td>
        <td><?= $u['email'] ?></td>
        <td><?= ucfirst($u['tipo']) ?></td>
        <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
        <td>
          <?php if (podeEditarTipo($u['tipo'])): ?>
            <a href="usuario_form.php?id=<?= $u['id'] ?>">✏️</a>
            <a href="usuario_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Deseja realmente excluir este usuário?')">🗑️</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>
