<?php
include '../includes/header.php';
include '../includes/search.php';
include '../includes/db.php';
include '../includes/defs.php';
include '../includes/functions.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

// Buscar agendamento ativo
$agendamentoAtivo = $pdo->query("SELECT * FROM agendamentos_checagem WHERE ativo = 1 ORDER BY id DESC LIMIT 1")->fetch();

// Buscar todos os clientes com informações da última checagem
$query = "SELECT c.*, 
          ch.id as checagem_id, ch.data as ultima_checagem, 
          ch.resultado_json, ch.status, 
          (SELECT COUNT(*) FROM checagens WHERE cliente_id = c.id) as total_checagens
          FROM clientes c
          LEFT JOIN checagens ch ON ch.id = (
              SELECT id FROM checagens 
              WHERE cliente_id = c.id 
              ORDER BY data DESC 
              LIMIT 1
          )
          ORDER BY c.nome";

$clientes = $pdo->query($query)->fetchAll();

$clientes = aplicarBuscaGlobal(null, 'nome', $clientes);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-satellite-dish"></i> Checagem de Equipamentos</h2>
    </div>
    <div class="col-md-4 text-md-end">
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#agendamentoModal">
            <i class="fas fa-calendar-alt"></i> Agendar Checagem
        </button>
        <button class="btn btn-success" id="gerarRelatorioGeral">
            <i class="fas fa-file-pdf"></i> Relatório Geral
        </button>
    </div>
</div>

<!-- Status do agendamento -->
<?php if ($agendamentoAtivo): ?>
    <div class="alert alert-info mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle"></i>
                Próxima checagem agendada:
                <?php
                if ($agendamentoAtivo['primeira_semana']) {
                    echo "Primeira semana do mês";
                } elseif ($agendamentoAtivo['dia_mes']) {
                    echo "Dia " . $agendamentoAtivo['dia_mes'] . " de cada mês";
                } elseif ($agendamentoAtivo['dia_semana']) {
                    echo ucfirst($agendamentoAtivo['dia_semana']) . "-feira";
                }
                echo " às " . date('H:i', strtotime($agendamentoAtivo['hora_execucao']));

                if ($agendamentoAtivo['data_inicio'] || $agendamentoAtivo['data_fim']) {
                    echo " (";
                    if ($agendamentoAtivo['data_inicio']) {
                        echo "a partir de " . date('d/m/Y', strtotime($agendamentoAtivo['data_inicio']));
                    }
                    if ($agendamentoAtivo['data_fim']) {
                        echo " até " . date('d/m/Y', strtotime($agendamentoAtivo['data_fim']));
                    }
                    echo ")";
                }
                ?>
            </div>
            <?php if ($tipoLogado === 'master' || $tipoLogado === 'infra'): ?>
                <button class="btn btn-sm btn-outline-danger" id="cancelarAgendamento" data-id="<?= $agendamentoAtivo['id'] ?>">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card bg-knight">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover table-knight">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-nowrap">Última Checagem</th>
                        <th scope="col">Resultado</th>
                        <th scope="col">Ações</th>
                        <th scope="col" style="width: 30px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td data-label="Cliente"><?= $cliente['nome'] ?></td>

                            <td data-label="Status">
                                <?php if ($cliente['ultima_checagem']): ?>
                                    <?php
                                    // Define as cores baseadas no status armazenado no banco
                                    $cor = match ($cliente['status']) {
                                        'sucesso' => 'success',
                                        'erro' => 'danger',
                                        'aviso' => 'warning',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $cor ?>"><?= ucfirst($cliente['status']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nunca checado</span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Última Checagem" class="text-nowrap">
                                <?= $cliente['ultima_checagem'] ? date('d/m/Y H:i', strtotime($cliente['ultima_checagem'])) : '-' ?>
                            </td>

                            <td data-label="Resultado">
                                <?php if ($cliente['ultima_checagem'] && isset($resultado['resumo'])): ?>
                                    <?= htmlspecialchars($resultado['resumo']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>

                            <td data-label="Ações" class="text-nowrap">
                                <button class="btn btn-sm btn-danger btn-checar-agora"
                                    data-cliente-id="<?= $cliente['id'] ?>">
                                    <i class="fas fa-sync-alt"></i> Checar Agora
                                </button>

                                <?php if ($cliente['ultima_checagem']): ?>
                                    <button class="btn btn-sm btn-info btn-relatorio" data-cliente-id="<?= $cliente['id'] ?>"
                                        data-checagem-id="<?= $cliente['checagem_id'] ?>">
                                        <i class="fas fa-file-pdf"></i> Relatório
                                    </button>
                                <?php endif; ?>
                            </td>

                            <td data-label="">
                                <?php if ($cliente['total_checagens'] > 0): ?>
                                    <button class="btn btn-sm btn-outline-info handle-expand collapsed"
                                        data-bs-toggle="collapse" data-bs-target="#historico-<?= $cliente['id'] ?>"
                                        aria-expanded="false">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-outline-secondary" disabled>
                                        <i class="fas fa-minus"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php if ($cliente['total_checagens'] > 0): ?>
                            <tr>
                                <td colspan="6" class="p-0">
                                    <div id="historico-<?= $cliente['id'] ?>" class="collapse">
                                        <div class="card bg-dark border-secondary m-3">
                                            <div class="card-header">
                                                <h5 class="mb-0">Histórico de Checagens</h5>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="table-responsive">
                                                    <table class="table table-dark table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Data/Hora</th>
                                                                <th>Status</th>
                                                                <th>Resumo</th>
                                                                <th>Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $historico = $pdo->prepare("SELECT * FROM checagens 
                                WHERE cliente_id = ? 
                                ORDER BY data DESC 
                                LIMIT 10");
                                                            $historico->execute([$cliente['id']]);
                                                            while ($checagem = $historico->fetch()):
                                                                // Usar o status diretamente da tabela checagens em vez do JSON
                                                                $status = $checagem['status'] ?? 'desconhecido';
                                                                $cor = match ($status) {
                                                                    'sucesso' => 'success',
                                                                    'erro' => 'danger',
                                                                    'aviso' => 'warning',
                                                                    default => 'secondary'
                                                                };

                                                                // Ainda podemos pegar o resumo do JSON se necessário
                                                                $resultado = json_decode($checagem['resultado_json'], true);
                                                                $resumo = $checagem['resumo'] ?? ($resultado['resumo'] ?? '-');
                                                                ?>
                                                                <tr>
                                                                    <td class="text-nowrap">
                                                                        <?= date('d/m/Y H:i', strtotime($checagem['data'])) ?>
                                                                    </td>
                                                                    <td>
                                                                        <span
                                                                            class="badge bg-<?= $cor ?>"><?= ucfirst($status) ?></span>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($resumo) ?></td>
                                                                    <td class="text-nowrap">
                                                                        <button class="btn btn-sm btn-info btn-relatorio"
                                                                            onclick="mostrarDetalhesChecagem(<?= $checagem['id'] ?>)">
                                                                            <i class="fas fa-eye"></i> Ver Detalhes
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- ADICIONE AQUI O CÓDIGO DO PASSO 5 -->
                                                <?php if ($temAgendamento): ?>
                                                    <div class="card bg-dark border-info mt-3">
                                                        <div class="card-header border-info">
                                                            <h6 class="mb-0 text-white">
                                                                <i class="fas fa-calendar-alt"></i> Agendamento
                                                                <div class="float-end">
                                                                    <button
                                                                        class="btn btn-sm btn-outline-warning btn-editar-agendamento"
                                                                        data-agendamento-id="<?= $temAgendamento['id'] ?>">
                                                                        <i class="fas fa-edit"></i> Editar
                                                                    </button>
                                                                    <button
                                                                        class="btn btn-sm btn-outline-danger btn-cancelar-agendamento"
                                                                        data-agendamento-id="<?= $temAgendamento['id'] ?>">
                                                                        <i class="fas fa-times"></i> Cancelar
                                                                    </button>
                                                                </div>
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <strong>Frequência:</strong>
                                                                    <?php
                                                                    if ($temAgendamento['primeira_semana']) {
                                                                        echo "Primeira semana do mês";
                                                                    } elseif ($temAgendamento['dia_mes']) {
                                                                        echo "Dia " . $temAgendamento['dia_mes'] . " de cada mês";
                                                                    } elseif ($temAgendamento['dia_semana']) {
                                                                        echo ucfirst($temAgendamento['dia_semana']) . "-feira";
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong>Hora:</strong>
                                                                    <?= date('H:i', strtotime($temAgendamento['hora_execucao'])) ?>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <strong>Vigência:</strong>
                                                                    <?= $temAgendamento['data_inicio'] ? date('d/m/Y', strtotime($temAgendamento['data_inicio'])) : 'Indefinida' ?>
                                                                    <?= $temAgendamento['data_fim'] ? ' - ' . date('d/m/Y', strtotime($temAgendamento['data_fim'])) : '' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-center mt-2">
                                                    <button class="btn btn-sm btn-outline-info"
                                                        onclick="gerarRelatorioCliente(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nome']) ?>')">
                                                        <i class="fas fa-file-pdf"></i> Gerar Relatório Completo
                                                    </button>
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

<!-- Modal para Detalhes da Checagem -->
<div class="modal fade" id="detalhesChecagemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Checagem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detalhesChecagemBody">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Agendamento -->
<div class="modal fade" id="agendamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title">Agendar Checagem Automática</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formAgendamento">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Frequência:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequencia"
                                        id="frequenciaSemanal" value="semanal" checked>
                                    <label class="form-check-label" for="frequenciaSemanal">
                                        Semanal
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequencia" id="frequenciaMensal"
                                        value="mensal">
                                    <label class="form-check-label" for="frequenciaMensal">
                                        Mensal (dia fixo)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequencia"
                                        id="frequenciaPrimeiraSemana" value="primeira_semana">
                                    <label class="form-check-label" for="frequenciaPrimeiraSemana">
                                        Primeira semana do mês
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3" id="diaSemanaContainer">
                                <label for="dia_semana" class="form-label">Dia da semana:</label>
                                <select class="form-select" id="dia_semana" name="dia_semana">
                                    <option value="segunda">Segunda-feira</option>
                                    <option value="terca">Terça-feira</option>
                                    <option value="quarta">Quarta-feira</option>
                                    <option value="quinta">Quinta-feira</option>
                                    <option value="sexta">Sexta-feira</option>
                                    <option value="sabado">Sábado</option>
                                    <option value="domingo">Domingo</option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="diaMesContainer">
                                <label for="dia_mes" class="form-label">Dia do mês (1-31):</label>
                                <input type="number" class="form-control" id="dia_mes" name="dia_mes" min="1" max="31">
                            </div>

                            <div class="mb-3">
                                <label for="hora_execucao" class="form-label">Hora da execução:</label>
                                <input type="time" class="form-control" id="hora_execucao" name="hora_execucao"
                                    value="00:00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Clientes:</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="todosClientes"
                                        name="todos_clientes" checked>
                                    <label class="form-check-label" for="todosClientes">
                                        Todos os clientes
                                    </label>
                                </div>

                                <div id="seletorClientesContainer" style="display: none;">
                                    <select id="seletorClientes" name="clientes_selecionados[]"
                                        class="form-select bg-dark text-light" multiple="multiple"
                                        style="width: 100%; color: #f8f9fa !important;">
                                        <!-- Opções serão carregadas via AJAX -->
                                    </select>
                                    <small class="text-muted">Digite para buscar clientes (mínimo 1 caractere)</small>
                                </div>
                            </div>

                            <div class="row g-2 mb-3"> <!-- Adicionei g-2 para gap entre colunas -->
                                <div class="col-md-6">
                                    <div class="form-floating"> <!-- Form-floating para labels flutuantes -->
                                        <input type="date" class="form-control bg-dark text-light" id="data_inicio"
                                            name="data_inicio" placeholder="Data início">
                                        <label for="data_inicio" class="text-light">Data de início (opcional)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control bg-dark text-light" id="data_fim"
                                            name="data_fim" placeholder="Data fim">
                                        <label for="data_fim" class="text-light">Data de fim (opcional)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar Agendamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Checagem -->
<div class="modal fade" id="confirmarChecagemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Checagem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Deseja executar a checagem agora para <strong id="nomeClienteChecagem"></strong>?</p>
                <input type="hidden" id="clienteIdChecagem">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarChecagem">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title text-light">Visualizar Relatório</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfViewer" style="width: 100%; height: 80vh; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    // Variáveis globais para os modais
    let agendamentoModal;
    let confirmarChecagemModal;
    let pdfViewerModal;

    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar modais
        agendamentoModal = new bootstrap.Modal(document.getElementById('agendamentoModal'));
        confirmarChecagemModal = new bootstrap.Modal(document.getElementById('confirmarChecagemModal'));
        pdfViewerModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));

        // Comportamento dos radios de frequência
        document.querySelectorAll('input[name="frequencia"]').forEach(radio => {
            radio.addEventListener('change', function () {
                document.getElementById('diaSemanaContainer').classList.toggle('d-none', this.value !== 'semanal');
                document.getElementById('diaMesContainer').classList.toggle('d-none', this.value !== 'mensal');
            });
        });

        // Botões "Checar Agora"
        document.querySelectorAll('.btn-checar-agora').forEach(btn => {
            btn.addEventListener('click', function () {
                const clienteId = this.getAttribute('data-cliente-id');
                const clienteNome = this.closest('tr').querySelector('td[data-label="Cliente"]').textContent;

                if (confirm(`Deseja executar a checagem agora para ${clienteNome}?`)) {
                    executarChecagem(clienteId);
                }
            });
        });

        // Confirmar checagem
        document.getElementById('confirmarChecagem')?.addEventListener('click', function () {
            const clienteId = document.getElementById('clienteIdChecagem').value;
            executarChecagem(clienteId);
            confirmarChecagemModal.hide();
        });

        // Cancelar agendamento
        document.getElementById('cancelarAgendamento')?.addEventListener('click', function () {
            if (confirm('Deseja realmente cancelar o agendamento atual?')) {
                fetch('checagem_cancelar_agendamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + this.getAttribute('data-id')
                })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.success ? 'Agendamento cancelado com sucesso!' : 'Erro ao cancelar: ' + data.message);
                        if (data.success) location.reload();
                    })
                    .catch(err => alert('Erro na requisição: ' + err.message));
            }
        });

        // Submissão do formulário de agendamento
        document.getElementById('formAgendamento')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
            submitBtn.disabled = true;

            fetch('checagem_salvar_agendamento.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.success ? 'Agendamento salvo com sucesso!' : 'Erro ao salvar: ' + data.message);
                    if (data.success) location.reload();
                })
                .catch(err => alert('Erro na requisição: ' + err.message))
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Gerar relatório geral
        document.getElementById('gerarRelatorioGeral')?.addEventListener('click', function () {
            window.open('checagem_gerar_relatorio_geral.php', '_blank');
        });

        // Botões de relatório individual
        document.querySelectorAll('.btn-relatorio').forEach(btn => {
            btn.addEventListener('click', function () {
                const clienteId = this.getAttribute('data-cliente-id');
                const checagemId = this.getAttribute('data-checagem-id');
                if (checagemId) {
                    const url = `checagem_gerar_relatorio.php?cliente_id=${clienteId}&checagem_id=${checagemId}`;
                    document.getElementById('pdfViewer').src = url;
                    pdfViewerModal.show();
                }
            });
        });

        // Fechar modais ao clicar no backdrop
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    bootstrap.Modal.getInstance(this).hide();
                }
            });

            modal.addEventListener('hidden.bs.modal', function () {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            });
        });

        // Botões de editar agendamento
        document.querySelectorAll('.btn-editar-agendamento').forEach(btn => {
            btn.addEventListener('click', function () {
                const agendamentoId = this.getAttribute('data-agendamento-id');
                fetch(`checagem_carregar_agendamento.php?id=${agendamentoId}`)
                    .then(res => res.json())
                    .then(data => {
                        // Preencher o modal com os dados recebidos
                        agendamentoModal.show();
                    });
            });
        });

        // Botões de cancelar agendamento
        document.querySelectorAll('.btn-cancelar-agendamento').forEach(btn => {
            btn.addEventListener('click', function () {
                const agendamentoId = this.getAttribute('data-agendamento-id');
                if (confirm('Deseja realmente cancelar este agendamento?')) {
                    fetch('checagem_cancelar_agendamento.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${agendamentoId}`
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) location.reload();
                            else alert('Erro ao cancelar: ' + data.message);
                        });
                }
            });
        });

        // Select2 para selecionar clientes
        $('#seletorClientes').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Selecione os clientes',
            allowClear: true,
            ajax: {
                url: 'clientes_busca.php',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term, page: params.page }),
                processResults: (data, params) => ({
                    results: data.results,
                    pagination: { more: data.pagination.more }
                }),
                cache: true
            },
            minimumInputLength: 1
        });

        const todosClientes = document.getElementById('todosClientes');
        const seletorContainer = document.getElementById('seletorClientesContainer');

        todosClientes.addEventListener('change', function () {
            seletorContainer.style.display = this.checked ? 'none' : 'block';
            if (this.checked) $('#seletorClientes').val(null).trigger('change');
        });

        $('#seletorClientes').on('select2:select', function () {
            todosClientes.checked = false;
            seletorContainer.style.display = 'block';
        });
    });

    // Corrigir foco e cores no campo Select2
    $(document).on('select2:open', () => {
        const input = document.querySelector('.select2-search__field');
        input?.focus();
        setTimeout(() => {
            if (input) {
                input.style.color = '#f8f9fa';
                input.style.backgroundColor = '#212529';
            }
        }, 100);
    });

    // Função global para gerar relatório de um cliente
    function gerarRelatorioCliente(clienteId, clienteNome) {
        const url = `checagem_gerar_relatorio.php?cliente_id=${clienteId}&todos=1`;
        window.open(url, '_blank');
    }

    // Função global para executar a checagem
    async function executarChecagem(clienteId) {
        const btn = document.querySelector(`.btn-checar-agora[data-cliente-id="${clienteId}"]`);
        const originalHTML = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';
        btn.disabled = true;

        try {
            const response = await fetch('checagem_executar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cliente_id=${clienteId}`
            });

            // Verificar se a resposta está vazia
            const responseText = await response.text();

            if (!responseText.trim()) {
                throw new Error('Resposta vazia do servidor');
            }

            // Tentar parsear o JSON
            const data = JSON.parse(responseText);

            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }

            // Atualizar interface
            const statusCell = btn.closest('tr').querySelector('td[data-label="Status"]');
            if (statusCell) {
                const statusClass = data.resultado.status === 'sucesso' ? 'success' :
                    data.resultado.status === 'erro' ? 'danger' : 'warning';

                statusCell.innerHTML = `
                <span class="badge bg-${statusClass}">${data.resultado.status}</span>
                <small>${data.resultado.resumo}</small>
            `;
            }

            mostrarToast(data.message, 'success');

        } catch (error) {
            console.error('Erro na checagem:', error);
            mostrarToast(`Falha na checagem: ${error.message}`, 'danger');

            // Log detalhado para depuração
            if (error.response) {
                const errorResponse = await error.response.text();
                console.error('Resposta completa:', errorResponse);
            }
        } finally {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    function formatarResultadosChecagem(resultado) {
        // Verificação inicial dos dados
        if (!resultado) {
            return '<div class="alert alert-warning">Dados da checagem não disponíveis</div>';
        }

        let html = '<div class="mb-3">';

        // Verificar qual tipo de resultado temos
        if (resultado.status_aparelhos) {
            // Formato "cadastrados"
            html += `
            <h5>Status dos Aparelhos Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-sm table-dark">
                    <thead>
                        <tr>
                            <th>Aparelho (AET)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

            resultado.status_aparelhos.forEach(aparelho => {
                const badgeClass = aparelho.situacao === 'ENVIANDO' ? 'success' :
                    (aparelho.situacao === 'SEM ENVIOS' ? 'secondary' : 'warning');
                html += `
                <tr>
                    <td>${aparelho.aet || 'N/A'}</td>
                    <td><span class="badge bg-${badgeClass}">${aparelho.situacao || 'DESCONHECIDO'}</span></td>
                </tr>`;
            });

            html += `</tbody></table></div>`;

        } else if (resultado.detalhes_aparelhos) {
            // Formato "completa"
            html += `
            <h5>Detalhes Completo dos Aparelhos</h5>
            <div class="table-responsive">
                <table class="table table-sm table-dark">
                    <thead>
                        <tr>
                            <th>Aparelho (AET)</th>
                            <th>Descrição</th>
                            <th>Nome da Estação</th>
                            <th>Modalidade</th>
                            <th>IP</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

            resultado.detalhes_aparelhos.forEach(aparelho => {
                const badgeClass = aparelho.SITUACAO === 'COM ENVIOS' ? 'success' : 'warning';
                html += `
                <tr>
                    <td>${aparelho.aet || 'N/A'}</td>
                    <td>${aparelho.ae_desc || 'N/A'}</td>
                    <td>${aparelho.station_name || 'N/A'}</td>
                    <td>${aparelho.modality || 'N/A'}</td>
                    <td>${aparelho.ip || 'N/A'}</td>
                    <td><span class="badge bg-${badgeClass}">${aparelho.SITUACAO || 'DESCONHECIDO'}</span></td>
                </tr>`;
            });

            html += `</tbody></table></div>`;
        } else {
            // Formato desconhecido
            return '<div class="alert alert-warning">Formato de dados não reconhecido</div>';
        }

        html += '</div>';
        return html;
    }

    function mostrarDetalhesChecagem(checagemId) {
        fetch(`checagem_obter_detalhes.php?id=${checagemId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao obter detalhes');
                }
                return response.json();
            })
            .then(data => {
                const modalBody = document.getElementById('detalhesChecagemBody');
                modalBody.innerHTML = formatarResultadosChecagem(data);
                new bootstrap.Modal(document.getElementById('detalhesChecagemModal')).show();
            })
            .catch(error => {
                console.error('Erro ao obter detalhes:', error);
                const modalBody = document.getElementById('detalhesChecagemBody');
                modalBody.innerHTML = `
            <div class="alert alert-danger">
                Falha ao carregar detalhes: ${error.message}
            </div>
        `;
                new bootstrap.Modal(document.getElementById('detalhesChecagemModal')).show();
            });
    }
</script>

<?php include '../includes/footer.php'; ?>