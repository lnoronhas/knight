<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

// Buscar clientes com suas modalidades
$query = "SELECT c.*, COUNT(cm.id) as total_modalidades 
          FROM clientes c 
          LEFT JOIN clientes_modalidades cm ON c.id = cm.cliente_id 
          GROUP BY c.id 
          ORDER BY c.nome";

$contratos = $pdo->query($query)->fetchAll();

// Função para buscar modalidades de um cliente
function getModalidadesCliente($clienteId) {
  global $pdo;
  
  $query = "SELECT cm.id, cm.quantidade, m.sigla, m.nome as modalidade_nome 
            FROM clientes_modalidades cm
            JOIN modalidades m ON cm.modalidade_id = m.id
            WHERE cm.cliente_id = ?
            ORDER BY m.sigla";
            
  $stmt = $pdo->prepare($query);
  $stmt->execute([$clienteId]);
  return $stmt->fetchAll();
}
?>

<div class="row mb-4">
  <div class="col-md-8">
    <h2><i class="fas fa-file-contract"></i> Gerenciar Contratos</h2>
  </div>
  <div class="col-md-4 text-md-end">
    <a href="contratos_form.php" class="btn btn-success">
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
            <th scope="col" style="width: 30px;"></th>
            <th scope="col">Nome</th>
            <th scope="col">Bilhetagem</th>
            <th scope="col">Qtd Bilhetagem</th>
            <th scope="col">Status</th>
            <th scope="col">Cadastrado em</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contratos as $c): ?>
            <tr class="<?= $c['total_modalidades'] > 0 ? 'accordion-toggle' : '' ?>" 
                <?= $c['total_modalidades'] > 0 ? 'data-bs-toggle="collapse" data-bs-target="#modalidades-'.$c['id'].'"' : '' ?>>
              <td>
                <?php if ($c['total_modalidades'] > 0): ?>
                  <button class="btn btn-sm btn-outline-info">
                    <i class="fas fa-chevron-down"></i>
                  </button>
                <?php else: ?>
                  <span class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="fas fa-minus"></i>
                  </span>
                <?php endif; ?>
              </td>
              <td data-label="Nome"><?= $c['nome'] ?></td>
              <td data-label="Bilhetagem">
                <?php if($c['bilhetagem']): ?>
                  <span class="badge bg-success">Sim</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Não</span>
                <?php endif; ?>
              </td>
              <td data-label="Qtd Bilhetagem"><?= $c['qtd_bilhetagem'] ?></td>
              <td data-label="Status">
                <?php if($c['ativo']): ?>
                  <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                  <span class="badge bg-danger">Inativo</span>
                <?php endif; ?>
              </td>
              <td data-label="Cadastrado em"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
              <td data-label="Ações">
                <a href="contratos_form.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="contratos_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" 
                   onclick="return confirm('Deseja realmente excluir este contrato?')" title="Excluir">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php if ($c['total_modalidades'] > 0): ?>
              <tr>
                <td colspan="7" class="p-0">
                  <div id="modalidades-<?= $c['id'] ?>" class="collapse">
                    <div class="card bg-secondary-dark m-3">
                      <div class="card-header">
                        <h5 class="mb-0">Modalidades Contratadas</h5>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <?php 
                          $modalidades = getModalidadesCliente($c['id']);
                          foreach ($modalidades as $m): 
                          ?>
                            <div class="col-md-3 mb-2">
                              <div class="card bg-dark">
                                <div class="card-body py-2">
                                  <strong><?= $m['sigla'] ?>:</strong> <?= $m['quantidade'] ?>
                                  <small class="d-block text-muted"><?= $m['modalidade_nome'] ?></small>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>