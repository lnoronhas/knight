<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

// Verificar permissão
if (!in_array($tipoLogado, ['master', 'infra'])) {
    header('Location: contratos.php');
    exit();
}

$cliente_id = $_GET['cliente_id'] ?? null;
$id = $_GET['id'] ?? null; // ID da conexão (para edição)

// Buscar dados da conexão existente
$conexao = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT c.*, cli.nome as cliente_nome 
                          FROM conexoes c
                          JOIN clientes cli ON c.cliente_id = cli.id
                          WHERE c.id = ?");
    $stmt->execute([$id]);
    $conexao = $stmt->fetch();

    if ($conexao) {
        $cliente_id = $conexao['cliente_id'];
        $cliente_nome = $conexao['cliente_nome'];
    }
}

// Se não veio por conexão existente, buscar dados do cliente
if (!$conexao && $cliente_id) {
    $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    $cliente_nome = $cliente['nome'] ?? '';
}

if (!$cliente_id) {
    header('Location: contratos.php');
    exit();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>
            <i class="fas fa-network-wired"></i>
            <?= $id ? 'Editar Conexão' : 'Nova Conexão' ?> - <?= htmlspecialchars($cliente_nome) ?>
            <?php if ($id): ?>
                <small class="text-muted">(ID: <?= $id ?>)</small>
            <?php endif; ?>
        </h2>
    </div>
</div>

<div class="card bg-knight">
    <div class="card-body">
        <form method="POST" action="conexoes_save.php">
            <input type="hidden" name="id" value="<?= $conexao['id'] ?? '' ?>">
            <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="ipv6" class="form-label">IPv6:</label>
                    <input type="text" class="form-control" id="ipv6" name="ipv6" value="<?= $conexao['ipv6'] ?? '' ?>"
                        required>
                </div>
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Usuário:</label>
                    <input type="text" class="form-control" id="usuario" name="usuario"
                        value="<?= $conexao['usuario'] ?? '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" class="form-control" id="senha" name="senha"
                        value="<?= $conexao['senha'] ?? '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="tipo_banco" class="form-label">Tipo de Banco:</label>
                    <select class="form-select" id="tipo_banco" name="tipo_banco" required>
                        <option value="">Selecione...</option>
                        <option value="mysql" <?= ($conexao['tipo_banco'] ?? '') === 'mysql' ? 'selected' : '' ?>>MySQL
                        </option>
                        <option value="postgres" <?= ($conexao['tipo_banco'] ?? '') === 'postgres' ? 'selected' : '' ?>>
                            PostgreSQL</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Versão da Infra:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="versao_infra" id="versao_legado"
                                value="legado" <?= ($conexao['versao_infra'] ?? '') === 'legado' ? 'checked' : '' ?>
                                required>
                            <label class="form-check-label" for="versao_legado">Legado</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="versao_infra" id="versao_atual"
                                value="atual" <?= ($conexao['versao_infra'] ?? '') === 'atual' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="versao_atual">Atual</label>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($conexao): ?>
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="conexoes_delete.php?id=<?= $conexao['id'] ?>" class="btn btn-outline-danger"
                            onclick="return confirm('Tem certeza que deseja excluir esta conexão?')">
                            <i class="fas fa-trash"></i> Excluir Conexão
                        </a>
                    </div>
                    <div>
                        <a href="contratos.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-knight">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between mt-4">
                    <a href="contratos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-knight">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>