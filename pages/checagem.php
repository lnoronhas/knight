<?php
include '../includes/functions.php';
include '../includes/db.php';
include '../includes/defs.php';

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
if (isset($_GET['action']) && $_GET['action'] === 'reload' && isset($_GET['cliente_id'])) {
    try {
        include_once '../includes/functions.php'; // Certifique-se de incluir o arquivo de funções
        $clienteId = (int) $_GET['cliente_id'];

        // Chama a função para obter os dados da última checagem
        $dadosChecagem = obterUltimaChecagemCliente($clienteId);

        // Retornar os dados como JSON
        header('Content-Type: application/json');
        echo json_encode($dadosChecagem);

        // Interromper a execução do restante do código
        exit;
    } catch (Exception $e) {
        // Retornar erro em formato JSON
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'status' => 'erro',
            'resumo' => 'Erro ao carregar detalhes',
            'detalhes' => null
        ]);

        // Interromper a execução do restante do código
        exit;
    }
}


include '../includes/header.php';
$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];
include '../includes/search.php';
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
<div class="row mb-4">
    <div class="col-md-8">
        <div class="dropdown d-inline-block me-2" id="dropdownAcoes" style="display: none;">
            <button class="btn btn-primary dropdown-toggle" type="button" id="btnAcoesMultiplas"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-tasks"></i> Ações em Massa <span class="badge bg-danger"
                    id="contadorSelecionados">0</span>
            </button>
            <!-- Botão de reload geral -->
            <button class="btn btn-warning" id="btnReloadPagina">
                <i class="fas fa-sync-alt"></i> Recarregar Página
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li><a class="dropdown-item" href="#" id="checarStatusMultiplos" data-tipo-checagem="status">
                        <i class="fas fa-sync-alt"></i> Aparelhos cadastrados</a></li>
                <li><a class="dropdown-item" href="#" id="checarDetalhesMultiplos" data-tipo-checagem="detalhes">
                        <i class="fas fa-list-ul"></i> Todos os aparelhos</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#" id="checarTodosMultiplos" data-tipo-checagem="ambos">
                        <i class="fas fa-check-double"></i> Checagem completa</a></li>
            </ul>
        </div>
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
            <table id="tabelaResultados" class="table table-dark table-striped table-hover table-knight">
                <thead>
                    <tr>
                        <th data-label="Selecionar todos:" scope="col" style="width: 40px;">
                            <div class="form-check d-flex justify-content-center">
                                <input class="form-check-input" type="checkbox" id="checkTodos">
                                <label class="form-check-label visually-hidden" for="checkTodos">Selecionar
                                    todos</label>
                            </div>
                        </th>
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
                            <td data-label="">
                                <div class="form-check">
                                    <input class="form-check-input check-cliente" type="checkbox"
                                        value="<?= $cliente['id'] ?>" data-nome="<?= htmlspecialchars($cliente['nome']) ?>">
                                </div>
                            </td>
                            <td data-label="Cliente"><?= $cliente['nome'] ?></td>
                            <td data-label="Status">
                                <?php if ($cliente['ultima_checagem']): ?>
                                    <?php
                                    // Define as cores baseadas no status armazenado no banco
                                    $cor = match ($cliente['status']) {
                                        'sucesso' => 'success',
                                        'erro' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span
                                        class="badge bg-<?= $cor ?>"><?= ucfirst($cliente['status'] ?? 'desconhecido') ?></span>
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
                                <div class="d-flex flex-column gap-2">
                                    <!-- Botão Checagem -->
                                    <div class="dropdown d-inline-block w-100">
                                        <button class="btn btn-sm btn-danger w-100 text-start dropdown-toggle" type="button"
                                            id="btnChecarDropdown-<?= $cliente['id'] ?>" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fas fa-sync-alt"></i> Checagem
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark"
                                            aria-labelledby="btnChecarDropdown-<?= $cliente['id'] ?>">
                                            <li>
                                                <a class="dropdown-item btn-checar-agora" href="#"
                                                    data-cliente-id="<?= $cliente['id'] ?>"
                                                    data-tipo-checagem="status">Aparelhos cadastrados</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item btn-checar-agora" href="#"
                                                    data-cliente-id="<?= $cliente['id'] ?>"
                                                    data-tipo-checagem="detalhes">Todos os aparelhos</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item btn-checar-agora" href="#"
                                                    data-cliente-id="<?= $cliente['id'] ?>"
                                                    data-tipo-checagem="ambos">Checagem completa</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <!-- Botão Relatório -->
                                    <?php if ($cliente['ultima_checagem']): ?>
                                        <button class="btn btn-sm btn-info w-100 text-start btn-relatorio"
                                            data-cliente-id="<?= $cliente['id'] ?>"
                                            data-checagem-id="<?= $cliente['checagem_id'] ?>">
                                            <i class="fas fa-file-pdf"></i> Relatório
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Detalhes: ">
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
                                                <?php
                                                // Add this near the top of the file, before line 264
                                                $temAgendamento = false;

                                                // Then initialize it properly when loading client data
                                                if (isset($cliente['id'])) {
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos_checagem WHERE id = ?");
                                                    $stmt->execute([$cliente['id']]);
                                                    $temAgendamento = (bool) $stmt->fetchColumn();
                                                } ?>
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

<!-- Modal de Configuração de Checagem -->
<div class="modal fade" id="configChecagemModal">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Checagem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formConfigChecagem">
                    <div class="mb-3">
                        <label>Tipo de Checagem</label>
                        <select class="form-select" id="tipoChecagem">
                            <option value="status">Aparelhos cadastrados</option>
                            <option value="detalhes">Todos os aparelhos</option>
                            <option value="nova_checagem">Checagem completa</option>
                        </select>
                    </div>
                    <!-- Campos dinâmicos baseados na seleção -->
                    <div id="camposAdicionais"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="executarChecagemConfig">Executar</button>
            </div>
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
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title">Executando Checagens</h5>
            </div>
            <div class="modal-body">
                <p>Processando checagens em lote. Por favor, aguarde...</p>
                <div class="progress mb-3">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar" style="width: 0%"></div>
                </div>
                <p class="mb-1">Cliente atual: <span id="progressCliente">-</span></p>
                <p class="text-center"><span id="progressAtual">0</span> de <span id="progressTotal">0</span></p>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <!-- Os toasts serão inseridos aqui dinamicamente -->
</div>

<script>
    console.log('Script carregado com sucesso!');
    // Variáveis globais para os modais
    let agendamentoModal;
    let confirmarChecagemModal;
    let pdfViewerModal;
    let reloadTriggered = false;

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

        // Confirmar checagem
        document.getElementById('confirmarChecagem')?.addEventListener('click', function () {
            const clienteId = document.getElementById('clienteIdChecagem').value;
            executarChecagem(clienteId);
            confirmarChecagemModal.hide();
        });

        // Verificar se existe agendamento
        async function verificarAgendamento(clienteId) {
            try {
                const response = await fetch(`checagem_carregar_agendamento.php?id=${clienteId}`);
                const data = await response.json();
                return data.success && data.data;
            } catch (error) {
                console.error('Erro ao verificar agendamento:', error);
                return false;
            }
        }

        // Atualizar variável temAgendamento
        const clienteId = document.querySelector('[data-cliente-id]')?.dataset.clienteId;
        if (clienteId) {
            verificarAgendamento(clienteId).then(temAgendamento => {
                window.temAgendamento = temAgendamento;
            });
        }

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

        // Adicionar evento para checkbox "selecionar todos"
        const checkTodos = document.getElementById('checkTodos');
        checkTodos?.addEventListener('change', function () {
            document.querySelectorAll('.check-cliente').forEach(check => {
                check.checked = this.checked;
            });
            atualizarBotaoAcoes();
        });

        // Adicionar evento para checkboxes individuais usando delegação de eventos
        document.querySelector('table')?.addEventListener('change', function (e) {
            if (e.target.classList.contains('check-cliente')) {
                // Atualizar estado do "selecionar todos"
                const checkTodos = document.getElementById('checkTodos');
                const totalChecks = document.querySelectorAll('.check-cliente').length;
                const totalChecados = document.querySelectorAll('.check-cliente:checked').length;
                checkTodos.checked = totalChecks === totalChecados;

                atualizarBotaoAcoes();
            }
        });

        // Função para atualizar o contador e visibilidade do botão de ações
        function atualizarBotaoAcoes() {
            const selecionados = document.querySelectorAll('.check-cliente:checked');
            const contador = document.getElementById('contadorSelecionados');
            const dropdownAcoes = document.getElementById('dropdownAcoes');

            if (contador && dropdownAcoes) {
                contador.textContent = selecionados.length;
                dropdownAcoes.style.display = selecionados.length > 0 ? 'inline-block' : 'none';
            }
        }

        // Delegação de eventos para botões de checagem individual
        document.querySelectorAll('.btn-checar-agora[data-cliente-id][data-tipo-checagem]').forEach(link => {
            link.addEventListener('click', async function (e) {
                e.preventDefault();
                const clienteId = this.getAttribute('data-cliente-id');
                const tipoChecagem = this.getAttribute('data-tipo-checagem');
                const clienteNome = this.closest('tr')?.querySelector('td[data-label="Cliente"]')?.textContent || 'Cliente';

                if (confirm(`Deseja executar a checagem de ${tipoChecagem === 'status' ? 'status dos aparelhos' : 'detalhes completos'} para ${clienteNome}?`)) {
                    const resultado = await executarChecagem(clienteId, tipoChecagem);
                    if (resultado.success) {
                        alert('Checagem concluída com sucesso!');
                        location.reload(); // Recarregar a página após a checagem individual
                    } else {
                        alert(`Erro na checagem: ${resultado.message}`);
                    }
                }
            });
        });

        async function executarChecagem(clienteId, tipoChecagem) {
            const overlay = document.getElementById('loadingOverlay');
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingProgress = document.getElementById('loadingProgress');

            try {
                console.log(`Iniciando checagem para clienteId: ${clienteId}, tipoChecagem: ${tipoChecagem}`);

                // Mostrar o overlay
                overlay.style.display = 'flex';
                loadingProgress.textContent = '';

                if (tipoChecagem === 'ambos') {
                    // Executar as checagens individuais para "status" e "detalhes"
                    const tipos = ['status', 'detalhes'];
                    for (let i = 0; i < tipos.length; i++) {
                        const tipoAtual = tipos[i];
                        loadingMessage.textContent = `Executando checagem do tipo ${tipoAtual === 'status' ? 'Aparelhos cadastrados' : 'Todos os aparelhos'}...`;

                        const resultado = await executarChecagem(clienteId, tipoAtual); // Chamada recursiva para cada tipo
                        if (!resultado.success) {
                            throw new Error(`Erro na checagem do tipo ${tipoAtual}: ${resultado.message}`);
                        }
                    }

                    loadingMessage.textContent = 'Checagem completa finalizada!';
                } else {
                    // Exibe o tipo de checagem no overlay
                    loadingMessage.textContent = `Executando checagem do tipo ${tipoChecagem === 'status' ? 'Aparelhos cadastrados' : 'Todos os aparelhos'}...`;

                    // Faz a requisição para o servidor
                    const response = await fetch('checagem_executar.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `cliente_id=${clienteId}&tipo_checagem=${tipoChecagem}`
                    });

                    console.log('Resposta recebida do servidor:', response);

                    // Verifica se a resposta foi bem-sucedida
                    if (!response.ok) {
                        throw new Error(`Erro HTTP: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log('Dados retornados pelo servidor:', data);

                    // Verifica se o back-end retornou sucesso
                    if (!data.success) {
                        throw new Error(data.message || 'Erro desconhecido');
                    }

                    // Retorna sucesso
                    return data; // Retorna o objeto JSON do servidor
                }
            } catch (error) {
                console.error('Erro na checagem:', error);
                // Retorna erro
                return { success: false, message: error.message };
            } finally {
                // Ocultar o overlay apenas ao final de todas as checagens
                overlay.style.display = 'none';
            }
        }

        document.getElementById('checarStatusMultiplos')?.addEventListener('click', function (e) {
            e.preventDefault();
            executarChecagemMultipla('status');
        });

        document.getElementById('checarDetalhesMultiplos')?.addEventListener('click', function (e) {
            e.preventDefault();
            executarChecagemMultipla('detalhes');
        });

        document.getElementById('checarTodosMultiplos')?.addEventListener('click', function (e) {
            e.preventDefault();
            executarChecagemMultipla('ambos');
        });

        // Definição da função executarChecagemMultipla
        async function executarChecagemMultipla(tipoChecagem) {
            const selecionados = document.querySelectorAll('.check-cliente:checked');
            if (selecionados.length === 0) {
                mostrarToast('Selecione pelo menos um cliente', 'warning');
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingProgress = document.getElementById('loadingProgress');

            const clientesIds = Array.from(selecionados).map(check => check.value);
            const nomes = Array.from(selecionados).map(check => check.getAttribute('data-nome'));

            const tiposTexto = {
                'status': 'status dos aparelhos',
                'detalhes': 'todos os aparelhos',
                'ambos': 'checagem completa'
            };

            let resultados = []; // Array para acumular os resultados

            if (confirm(`Deseja executar a checagem de ${tiposTexto[tipoChecagem]} para ${selecionados.length} clientes selecionados?`)) {
                // Mostrar overlay
                overlay.style.display = 'flex';
                loadingMessage.textContent = `Executando checagem de ${tiposTexto[tipoChecagem]}...`;

                for (let i = 0; i < clientesIds.length; i++) {
                    const clienteId = clientesIds[i];
                    const nome = nomes[i];

                    // Atualizar progresso
                    loadingProgress.textContent = `Executando ${i + 1} de ${clientesIds.length}: ${nome}`;

                    try {
                        const resultado = await executarChecagem(clienteId, tipoChecagem);
                        resultados.push(resultado);
                    } catch (error) {
                        console.error(`Erro na checagem do cliente ${nome}:`, error);
                        resultados.push({ nome, status: 'erro', mensagem: error.message });
                    }
                }

                overlay.style.display = 'none';

                // Exibir resumo dos resultados
                const sucesso = resultados.filter(r => r.status === 'sucesso').length;
                const erros = resultados.filter(r => r.status === 'erro');
                let mensagem = `Checagem concluída! ${sucesso} clientes processados com sucesso.`;

                if (erros.length > 0) {
                    mensagem += `\n\nErros (${erros.length}):\n`;
                    erros.forEach(erro => {
                        mensagem += `- ${erro.nome}: ${erro.mensagem}\n`;
                    });
                }

                alert(mensagem); // Exibir resumo final

                // Garantir que o reload ocorra apenas uma vez
                if (!reloadTriggered) {
                    reloadTriggered = true; // Marcar como já executado
                    location.reload();
                }
            }
        }

        // Evento para o botão de reload
        const btnReloadPagina = document.getElementById('btnReloadPagina');
        if (btnReloadPagina) {
            btnReloadPagina.addEventListener('click', function () {
                location.reload(); // Recarregar a página
            });
        }
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

    function formatarResultadosChecagem(resultado) {
        if (!resultado) {
            return '<div class="alert alert-warning">Dados da checagem não disponíveis</div>';
        }

        let html = '<div class="accordion" id="accordionResultados">';

        // Adicionar seção para "Aparelhos cadastrados"
        if (resultado.status_aparelhos) {
            html += `
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingStatus">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus" aria-expanded="false" aria-controls="collapseStatus">
                    Aparelhos cadastrados
                </button>
            </h2>
            <div id="collapseStatus" class="accordion-collapse collapse" aria-labelledby="headingStatus" data-bs-parent="#accordionResultados">
                <div class="accordion-body">
                    ${formatarStatusAparelhos(resultado.status_aparelhos)}
                </div>
            </div>
        </div>`;
        }

        // Adicionar seção para "Todos os aparelhos"
        if (resultado.detalhes_aparelhos) {
            html += `
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingDetalhes">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetalhes" aria-expanded="false" aria-controls="collapseDetalhes">
                    Todos os aparelhos
                </button>
            </h2>
            <div id="collapseDetalhes" class="accordion-collapse collapse" aria-labelledby="headingDetalhes" data-bs-parent="#accordionResultados">
                <div class="accordion-body">
                    ${formatarDetalhesAparelhos(resultado.detalhes_aparelhos)}
                </div>
            </div>
        </div>`;
        }

        // Adicionar seção para "Nova Checagem" (se existir)
        if (resultado.nova_checagem) {
            html += `
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNovaChecagem">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNovaChecagem" aria-expanded="false" aria-controls="collapseNovaChecagem">
                    Nova Checagem
                </button>
            </h2>
            <div id="collapseNovaChecagem" class="accordion-collapse collapse" aria-labelledby="headingNovaChecagem" data-bs-parent="#accordionResultados">
                <div class="accordion-body">
                    ${formatarNovaChecagem(resultado.nova_checagem)}
                </div>
            </div>
        </div>`;
        }

        html += '</div>'; // Fechar o accordion
        return html;
    }

    function mostrarDetalhesChecagem(checagemId) {
        const modal = new bootstrap.Modal(document.getElementById('detalhesChecagemModal'));
        const modalBody = document.getElementById('detalhesChecagemBody');

        // Mostrar loading
        modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>`;

        modal.show();

        // Buscar os detalhes
        fetch(`checagem_obter_detalhes.php?id=${checagemId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao obter detalhes');
                }
                return response.json();
            })
            .then(data => {
                modalBody.innerHTML = formatarResultadosChecagem(data);
            })
            .catch(error => {
                console.error('Erro ao obter detalhes:', error);
                modalBody.innerHTML = `
                <div class="alert alert-danger">
                    Falha ao carregar detalhes: ${error.message}
                </div>`;
            });
    }



    // Atualizar contador de selecionados e visibilidade do botão de ações
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('check-cliente')) {
            atualizarBotaoAcoes();
        }
    });

    function atualizarBotaoAcoes() {
        const selecionados = document.querySelectorAll('.check-cliente:checked');
        const contador = document.getElementById('contadorSelecionados');
        const dropdownAcoes = document.getElementById('dropdownAcoes');
        contador.textContent = selecionados.length;
        dropdownAcoes.style.display = selecionados.length > 0 ? 'inline-block' : 'none';
    }

    // Limpar seleção
    document.getElementById('limparSelecao').addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('.check-cliente').forEach(check => {
            check.checked = false;
        });
        document.getElementById('checkTodos').checked = false;
        atualizarBotaoAcoes();
    });



    function mostrarToast(mensagem, tipo = 'info') {
        const container = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${mensagem}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();

        // Remover elemento após fechar
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Função para cada template específico
    function formatarNovaChecagem(dados) {
        return `
  <h5>Nova Checagem</h5>
  <div class="table-responsive">
      <table class="table table-sm table-dark">
          <thead>
              <tr>
                  <th>Campo 1</th>
                  <th>Campo 2</th>
              </tr>
          </thead>
          <tbody>
              ${dados.map(item => `
                  <tr>
                      <td>${item.campo1}</td>
                      <td>${item.campo2}</td>
                  </tr>
              `).join('')}
          </tbody>
      </table>
  </div>`;
    }

    function formatarStatusAparelhos(dados) {
        if (!dados || dados.length === 0) {
            return '<p>Nenhum dado disponível</p>';
        }

        return `
    <h5>Aparelhos cadastrados</h5>
    <div class="table-responsive">
        <table class="table table-sm table-dark">
            <thead>
                <tr>
                    <th>AET</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                ${dados.map(item => `
                    <tr>
                        <td>${item.aet}</td>
                        <td>
                            <span class="badge ${item.situacao === 'ENVIANDO' ? 'bg-success' : 'bg-danger'}">
                                ${item.situacao}
                            </span>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>`;
    }

    function formatarDetalhesAparelhos(dados) {
        if (!dados || dados.length === 0) {
            return '<p>Nenhum dado disponível</p>';
        }

        return `
    <h5>Todos os aparelhos</h5>
    <div class="table-responsive">
        <table class="table table-sm table-dark">
            <thead>
                <tr>
                    <th>AET</th>
                    <th>Descrição</th>
                    <th>Estação</th>
                    <th>Modalidade</th>
                    <th>IP</th>
                    <th>Status</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                ${dados.map(item => `
                    <tr>
                        <td>${item.aet}</td>
                        <td>${item.ae_desc || ''}</td>
                        <td>${item.station_name || ''}</td>
                        <td>${item.modality || ''}</td>
                        <td>${item.ip || ''}</td>
                        <td>${item.status || ''}</td>
                        <td>
                            <span class="badge ${item.SITUACAO === 'COM ENVIOS' ? 'bg-success' : 'bg-danger'}">
                                ${item.SITUACAO}
                            </span>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    </div>`;
    }
</script>
<div id="loadingOverlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1050; align-items: center; justify-content: center; flex-direction: column;">

    <!-- Texto e ícone -->
    <div style="color: white; text-align: center; margin-bottom: 20px; font-size: 1.25rem;">
        <i class="fas fa-search fa-shake" style="margin-right: 10px;"></i> <!-- ícone com efeito animado -->
        <span id="loadingMessage">Checagem em andamento, por favor aguarde...</span>
    </div>

    <!-- Rodinha girante -->
    <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Carregando...</span>
    </div>

    <!-- Progresso -->
    <div id="loadingProgress" style="color: white; margin-top: 20px; font-size: 1rem;"></div>
</div>
<?php include '../includes/footer.php'; ?>