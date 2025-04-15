<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

$csuarioLogado = $_SESSION['usuario'];
$tipoLogado = $csuarioLogado['tipo'];
$contratos = $pdo->query("SELECT * FROM clientes ORDER BY nome")->fetchAll();
?>

<div class="row mb-4">
  <div class="col-md-8">
    <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>
  </div>
  <div class="col-md-4 text-md-end">
    <a href="usuario_form.php" class="btn btn-success">
      <i class="fas fa-plus-circle"></i> Novo Contrato
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
            <th scope="col">Bilhetagem</th>
            <th scope="col">Qtd Bilhetagem</th>
            <th scope="col">Status</th>
            <th scope="col">Cadastrado em</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contratos as $c): ?>
            <tr>
              <td data-label="Nome"><?= $c['nome'] ?></td>
              <td data-label="Bilhetagem"><?= $c['bilhetagem'] ?></td>
              <td data-label="Qtd Bilhetagem"><span class="badge bg-secondary"><?= ucfirst($c['tipo']) ?></span></td>
              <td data-label="Status">
                <?php if($c['ativo']): ?>
                  <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                  <span class="badge bg-danger">Inativo</span>
                <?php endif; ?>
              </td>
              <td data-label="Ações">
				  <?php if (podeEditarTipo($c['tipo'])): ?>
					<a href="usuario_form.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info" title="Editar">
					  <i class="fas fa-edit"></i>
					</a>
					
					<?php if ($c['id'] != $usuarioLogado['id']): ?>
					  <a href="usuario_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" 
						 onclick="return confirm('Deseja realmente excluir este usuário?')" title="Excluir">
						<i class="fas fa-trash"></i>
					  </a>
					<?php else: ?>
					  <button class="btn btn-sm btn-outline-secondary" onclick="alert('Você não pode excluir seu próprio usuário.')" title="Excluir" disabled>
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