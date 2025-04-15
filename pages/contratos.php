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
            <tr class="<?= $c['total_modalidades'] > 0 ? 'accordion-toggle' : '' ?>" <?= $c['total_modalidades'] > 0 ? 'data-bs-toggle="collapse" data-bs-target="#modalidades-' . $c['id'] . '"' : '' ?>>
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

              <td data-label="Nome">
                <?= $c['nome'] ?>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM conexoes WHERE cliente_id = ?");
                $stmt->execute([$c['id']]);
                if ($stmt->fetchColumn() > 0): ?>
                  <span class="badge bg-info" title="Possui conexão cadastrada">
                    <i class="fas fa-link"></i>
                  </span>
                <?php endif; ?>
              </td>

              <td data-label="Bilhetagem">
                <?php if ($c['bilhetagem']): ?>
                  <span class="badge bg-success">Sim</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Não</span>
                <?php endif; ?>
              </td>
              <td data-label="Qtd Bilhetagem"><?= $c['qtd_bilhetagem'] ?></td>
              <td data-label="Status">
                <?php if ($c['ativo']): ?>
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

                <?php if ($tipoLogado === 'master'): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger handle-contrato-action"
                    data-id="<?= $c['id'] ?>" data-nome="<?= $c['nome'] ?>" data-ativo="<?= $c['ativo'] ?>"
                    title="Gerenciar Contrato">
                    <i class="fas fa-trash"></i>
                  </button>
                  <?php if (in_array($tipoLogado, ['master', 'infra'])): ?>
                    <?php
                    // Verificar se já existe conexão para este cliente
                    $stmt = $pdo->prepare("SELECT id FROM conexoes WHERE cliente_id = ? LIMIT 1");
                    $stmt->execute([$c['id']]);
                    $conexao_id = $stmt->fetchColumn();
                    ?>

                    <a href="conexoes_form.php?<?= $conexao_id ? 'id=' . $conexao_id : 'cliente_id=' . $c['id'] ?>"
                      class="btn btn-sm btn-outline-<?= $conexao_id ? 'warning' : 'success' ?>"
                      title="<?= $conexao_id ? 'Editar Conexão' : 'Adicionar Conexão' ?>">
                      <i class="fas fa-network-wired"></i>
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-outline-danger desativar-contrato" data-id="<?= $c['id'] ?>"
                    data-nome="<?= $c['nome'] ?>" data-ativo="<?= $c['ativo'] ?>" title="Desativar Contrato">
                    <i class="fas fa-power-off"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($c['total_modalidades'] > 0): ?>
              <tr>
                <td colspan="7" class="p-0">
                  <div id="modalidades-<?= $c['id'] ?>" class="collapse">
                    <div class="card bg-dark border-secondary m-3">
                      <div class="card-header">
                        <h5 class="mb-0">Modalidades Contratadas</h5>
                      </div>
                      <div class="card-body text-white py-3">

                        <?php if (in_array($tipoLogado, ['master', 'infra'])): ?>
                          <div class="col-md-12 mb-3">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM conexoes WHERE cliente_id = ?");
                            $stmt->execute([$c['id']]);
                            $conexao = $stmt->fetch();
                            ?>

                            <?php if ($conexao): ?>
                              <div class="card bg-dark border-info">
                                <div class="card-header border-info">
                                  <h6 class="mb-0 text-white">
                                    <i class="fas fa-network-wired"></i> Dados de Conexão
                                    <a href="conexoes_form.php?id=<?= $conexao['id'] ?>"
                                      class="btn btn-sm btn-outline-warning float-end">
                                      <i class="fas fa-edit"></i> Editar
                                    </a>
                                  </h6>
                                </div>
                                <div class="card-body">
                                  <div class="row">
                                    <div class="col-md-4">
                                      <strong>IPv6:</strong> <?= $conexao['ipv6'] ?>
                                    </div>
                                    <div class="col-md-4">
                                      <strong>Usuário:</strong> <?= $conexao['usuario'] ?>
                                    </div>
                                    <div class="col-md-4">
                                      <strong>Tipo Banco:</strong> <?= strtoupper($conexao['tipo_banco']) ?>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <div class="row">
                          <?php
                          $modalidades = getModalidadesCliente($c['id']);
                          foreach ($modalidades as $m):
                            ?>
                            <div class="col-md-3 mb-2">
                              <div class="card bg-dark">
                                <div class="card-body py-2 bg-secondary">
                                  <strong><?= $m['sigla'] ?>:</strong> <?= $m['quantidade'] ?>
                                  <small class="d-block text-light"><?= $m['modalidade_nome'] ?></small>
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

<!-- Modal para usuários Master - Escolher entre Apagar ou Desativar -->
<div class="modal fade" id="controleContratoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Gerenciar Contrato</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Escolha a ação para o contrato <strong id="contrato-nome-modal"></strong>:</p>
        <input type="hidden" id="contrato-id-modal" value="">
        <input type="hidden" id="contrato-ativo-modal" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-desativar-modal">
          <i class="fas fa-power-off"></i> <span id="btn-desativar-texto">Desativar</span>
        </button>
        <button type="button" class="btn btn-danger" id="btn-apagar-modal">
          <i class="fas fa-trash"></i> Apagar Permanentemente
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de confirmação para desativação -->
<div class="modal fade" id="desativarContratoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Ação</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Você tem certeza que deseja <span id="acao-contrato-texto">desativar</span> o contrato <strong
            id="contrato-nome-desativar"></strong>?</p>
        <input type="hidden" id="contrato-id-desativar" value="">
        <input type="hidden" id="contrato-acao" value="desativar">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btn-confirmar-desativar">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Gerenciar modal para usuários master
    const controleModal = new bootstrap.Modal(document.getElementById('controleContratoModal'));
    const desativarModal = new bootstrap.Modal(document.getElementById('desativarContratoModal'));

    // Botões de gerenciamento de contrato (para usuários master)
    document.querySelectorAll('.handle-contrato-action').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const nome = this.getAttribute('data-nome');
        const ativo = parseInt(this.getAttribute('data-ativo'));

        document.getElementById('contrato-id-modal').value = id;
        document.getElementById('contrato-nome-modal').textContent = nome;
        document.getElementById('contrato-ativo-modal').value = ativo;

        // Ajustar o texto do botão de desativar/ativar
        const btnDesativar = document.getElementById('btn-desativar-texto');
        if (ativo) {
          btnDesativar.textContent = 'Desativar';
        } else {
          btnDesativar.textContent = 'Ativar';
        }

        controleModal.show();
      });
    });

    // Botões de desativação (para outros usuários)
    document.querySelectorAll('.desativar-contrato').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const nome = this.getAttribute('data-nome');
        const ativo = parseInt(this.getAttribute('data-ativo'));

        document.getElementById('contrato-id-desativar').value = id;
        document.getElementById('contrato-nome-desativar').textContent = nome;
        document.getElementById('contrato-acao').value = 'desativar';

        // Ajustar o texto da ação
        if (ativo) {
          document.getElementById('acao-contrato-texto').textContent = 'desativar';
        } else {
          document.getElementById('acao-contrato-texto').textContent = 'ativar';
        }

        desativarModal.show();
      });
    });

    // Ação de desativar/ativar do modal master
    document.getElementById('btn-desativar-modal').addEventListener('click', function () {
      const id = document.getElementById('contrato-id-modal').value;
      const nome = document.getElementById('contrato-nome-modal').textContent;
      const ativo = parseInt(document.getElementById('contrato-ativo-modal').value);

      document.getElementById('contrato-id-desativar').value = id;
      document.getElementById('contrato-nome-desativar').textContent = nome;
      document.getElementById('contrato-acao').value = 'desativar';

      // Ajustar o texto da ação
      if (ativo) {
        document.getElementById('acao-contrato-texto').textContent = 'desativar';
      } else {
        document.getElementById('acao-contrato-texto').textContent = 'ativar';
      }

      controleModal.hide();
      desativarModal.show();
    });

    // Ação de apagar do modal master
    document.getElementById('btn-apagar-modal').addEventListener('click', function () {
      const id = document.getElementById('contrato-id-modal').value;
      const nome = document.getElementById('contrato-nome-modal').textContent;

      document.getElementById('contrato-id-desativar').value = id;
      document.getElementById('contrato-nome-desativar').textContent = nome;
      document.getElementById('contrato-acao').value = 'apagar';
      document.getElementById('acao-contrato-texto').textContent = 'apagar permanentemente';

      controleModal.hide();
      desativarModal.show();
    });

    // Confirmação final
    document.getElementById('btn-confirmar-desativar').addEventListener('click', function () {
      const id = document.getElementById('contrato-id-desativar').value;
      const acao = document.getElementById('contrato-acao').value;

      window.location.href = `contratos_action.php?id=${id}&acao=${acao}`;
    });
  });
</script>

<?php include '../includes/footer.php'; ?>