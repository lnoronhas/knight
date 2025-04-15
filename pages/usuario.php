<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

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

<div class="row mb-4">
  <div class="col-md-8">
    <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>
  </div>
  <div class="col-md-4 text-md-end">
    <a href="usuario_form.php" class="btn btn-success">
      <i class="fas fa-plus-circle"></i> Novo Usuário
    </a>
  </div>
</div>

<div class="card bg-knight">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-dark table-striped table-hover table-knight">
        <thead>
          <tr>
            <th scope="col">Nome</th>
            <th scope="col">Email</th>
            <th scope="col">Tipo</th>
            <th scope="col">Status</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td data-label="Nome"><?= $u['nome'] ?></td>
              <td data-label="Email"><?= $u['email'] ?></td>
              <td data-label="Tipo"><span class="badge bg-secondary"><?= ucfirst($u['tipo']) ?></span></td>
              <td data-label="Status">
                <?php if ($u['ativo']): ?>
                  <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                  <span class="badge bg-danger">Inativo</span>
                <?php endif; ?>
              </td>
              <td data-label="Ações">
                <?php if (podeEditarTipo($u['tipo'])): ?>
                  <a href="usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-info" title="Editar">
                    <i class="fas fa-edit"></i>
                  </a>

                  <?php if ($u['id'] != $usuarioLogado['id']): ?>
                    <a href="usuario_delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Deseja realmente excluir este usuário?')" title="Excluir">
                      <i class="fas fa-trash"></i>
                    </a>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary"
                      onclick="alert('Você não pode excluir seu próprio usuário.')" title="Excluir" disabled>
                      <i class="fas fa-trash"></i>
                    </button>
                  <?php endif; ?>

                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>