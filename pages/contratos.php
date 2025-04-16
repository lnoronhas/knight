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

            <th scope="col">Nome</th>
            <th scope="col" class="text-nowrap">Bilhetagem</th>
            <th scope="col" class="text-nowrap">Qtd Bilhetagem</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-nowrap">Cadastrado em</th>
            <th scope="col">Ações</th>
            <th scope="col" style="width: 30px;">Modalidades</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contratos as $c): ?>
            <tr>

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
              <td data-label="Qtd Bilhetagem" class="text-nowrap" style="min-width: 80px;">
                <span class="d-inline-block" style="min-width: 30px; text-align: center;">
                  <?= $c['qtd_bilhetagem'] ?>
                </span>
              </td>
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
                    title="Desativar/Remover Contrato">
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
                <button type="button" class="btn btn-sm btn-info btn-arquivos-new"
                  onclick="abrirModalArquivos(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>')">
                  <i class="fas fa-file"></i> Arquivos
                </button>
              </td>

              <td data-label="Modalidades">
                <?php if ($c['total_modalidades'] > 0): ?>
                  <button class="btn btn-sm btn-outline-info handle-expand collapsed" data-bs-toggle="collapse"
                    data-bs-target="#modalidades-<?= $c['id'] ?>" aria-expanded="false">
                    <i class="fas fa-chevron-down"></i>
                  </button>
                <?php else: ?>
                  <span class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="fas fa-minus"></i>
                  </span>
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
        <h5 class="modal-title">Desativar/Remover Contrato</h5>
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

<!-- Modal Arquivos -->
<div class="modal fade" id="arquivosModal" tabindex="-1" aria-labelledby="arquivosModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="arquivosModalLabel">Arquivos do Contrato: <span id="nomeContratoArquivos"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Formulário de upload -->
        <form id="uploadForm" enctype="multipart/form-data">
          <input type="hidden" name="contrato_id" id="contrato_id_upload">
          <input type="hidden" name="contrato_nome" id="contrato_nome_upload">
          <div class="mb-3">
            <label for="arquivo" class="form-label">Selecione arquivo para upload:</label>
            <input class="form-control" type="file" id="arquivo" name="arquivo" required>
          </div>
          <button type="submit" class="btn btn-primary">Enviar Arquivo</button>
        </form>

        <hr class="border-secondary">

        <!-- Lista de arquivos -->
        <div id="listaArquivos" class="mt-3">
          <p class="text-muted">Carregando arquivos...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content bg-dark">
      <div class="modal-header">
        <h5 class="modal-title text-light">Visualizar PDF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="pdfViewer" style="width: 100%; height: 80vh; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
// Variáveis globais para o modal
let arquivosModal;
let pdfViewerModal;

// Inicializar os modais quando o documento estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar os objetos de modal do Bootstrap
    arquivosModal = new bootstrap.Modal(document.getElementById('arquivosModal'));
    pdfViewerModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    
    // Configurar o envio do formulário
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Mostrar indicador de carregamento
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
        submitBtn.disabled = true;
        
        // Enviar via fetch (alternativa ao $.ajax)
        fetch('contratos_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert('Arquivo enviado com sucesso!');
            document.getElementById('arquivo').value = '';
            
            // Recarregar a lista de arquivos
            const contratoId = document.getElementById('contrato_id_upload').value;
            const contratoNome = document.getElementById('contrato_nome_upload').value;
            carregarArquivos(contratoId, contratoNome);
        })
        .catch(error => {
            alert('Erro ao enviar arquivo: ' + error.message);
        })
        .finally(() => {
            // Restaurar o botão
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

// Função para abrir o modal de arquivos
function abrirModalArquivos(id, nome) {
    // Configurar os campos do modal
    document.getElementById('nomeContratoArquivos').textContent = nome;
    document.getElementById('contrato_id_upload').value = id;
    document.getElementById('contrato_nome_upload').value = nome;
    
    // Carregar lista de arquivos
    carregarArquivos(id, nome);
    
    // Abrir o modal
    arquivosModal.show();
}

// Função para carregar arquivos
function carregarArquivos(contratoId, contratoNome) {
    const listaArquivos = document.getElementById('listaArquivos');
    listaArquivos.innerHTML = '<p class="text-center"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Carregando arquivos...</p>';
    
    // Fazer requisição para listar arquivos
    fetch(`contratos_listar_arquivos.php?id=${contratoId}&nome=${encodeURIComponent(contratoNome)}`)
        .then(response => response.text())
        .then(html => {
            listaArquivos.innerHTML = html;
        })
        .catch(error => {
            listaArquivos.innerHTML = '<div class="alert alert-danger">Erro ao carregar arquivos: ' + error.message + '</div>';
        });
}

// Função para visualizar PDF
function visualizarPDF(nomeArquivo, contratoNome) {
    // Usando o script de download para visualizar PDF
    const viewerSrc = `contratos_download.php?nome=${encodeURIComponent(contratoNome)}&arquivo=${encodeURIComponent(nomeArquivo)}`;
    document.getElementById('pdfViewer').src = viewerSrc;
    pdfViewerModal.show();
}
</script>
<?php include '../includes/footer.php'; ?>