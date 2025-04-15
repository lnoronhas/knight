<?php
include '../includes/header.php';
include '../includes/db.php';
include '../includes/defs.php';

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_GET['id'] ?? '';
$contrato = null;
$modalidadesContrato = [];

// Buscar todas as modalidades disponíveis
$modalidades = $pdo->query("SELECT * FROM modalidades ORDER BY sigla")->fetchAll();

if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
  $stmt->execute([$id]);
  $contrato = $stmt->fetch();
  
  if ($contrato) {
    // Buscar modalidades do cliente
    $stmt = $pdo->prepare("
      SELECT cm.*, m.sigla, m.nome as modalidade_nome 
      FROM clientes_modalidades cm
      JOIN modalidades m ON cm.modalidade_id = m.id
      WHERE cm.cliente_id = ?
    ");
    $stmt->execute([$id]);
    $modalidadesContrato = $stmt->fetchAll();
  }
}

?>

<div class="row mb-4">
  <div class="col-12">
    <h2><?= $contrato ? '<i class="fas fa-edit"></i> Editar Contrato' : '<i class="fas fa-plus-circle"></i> Novo Contrato' ?></h2>
  </div>
</div>

<div class="card bg-knight">
  <div class="card-body">
    <form method="POST" action="contratos_save.php">
      <input type="hidden" name="id" value="<?= $contrato['id'] ?? '' ?>">
      
      <div class="row mb-3">
        <div class="col-md-12">
          <label for="nome" class="form-label">Nome do Cliente:</label>
          <input type="text" class="form-control" id="nome" name="nome" value="<?= $contrato['nome'] ?? '' ?>" required>
        </div>
      </div>
      
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Bilhetagem:</label>
          <div class="mt-2">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="bilhetagem" id="bilhetagem_sim" value="1" 
                     <?= (!isset($contrato) || $contrato['bilhetagem'] == 1) ? 'checked' : '' ?> required>
              <label class="form-check-label" for="bilhetagem_sim">Sim</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="bilhetagem" id="bilhetagem_nao" value="0" 
                     <?= (isset($contrato) && $contrato['bilhetagem'] == 0) ? 'checked' : '' ?>>
              <label class="form-check-label" for="bilhetagem_nao">Não</label>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <label for="qtd_bilhetagem" class="form-label">Quantidade de Bilhetagem:</label>
          <input type="number" class="form-control" id="qtd_bilhetagem" name="qtd_bilhetagem" value="<?= $contrato['qtd_bilhetagem'] ?? '' ?>" required>
        </div>
      </div>
      
      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" 
                 <?= !isset($contrato) || $contrato['ativo'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="ativo">Ativo</label>
        </div>
      </div>
      
      <hr>
      <h4 class="mb-3">Modalidades Contratadas</h4>
      
      <div id="modalidades-container">
        <?php
        // Criar mapa de modalidades já contratadas para facilitar o acesso
        $modalidadesMap = [];
        foreach ($modalidadesContrato as $mc) {
          $modalidadesMap[$mc['modalidade_id']] = $mc;
        }
        ?>
        
        <div class="row mb-3">
		  <div class="col-md-4"><strong>Modalidade</strong></div>
		  <div class="col-md-3"><strong>Quantidade</strong></div>
		  <div class="col-md-3"><strong>Incluir</strong></div>
		  <div class="col-md-2"></div>
		</div>
        
        <?php if (!empty($modalidades)): ?>
          <?php foreach ($modalidades as $m): ?>
            <div class="row mb-3 modalidade-item existing-modalidade">
			  <div class="col-md-4">
				<?= $m['sigla'] ?> - <?= $m['nome'] ?>
				<input type="hidden" name="modalidades[<?= $m['id'] ?>][modalidade_id]" value="<?= $m['id'] ?>">
			  </div>
			  <div class="col-md-3">
				<input type="number" class="form-control" name="modalidades[<?= $m['id'] ?>][quantidade]" 
					   value="<?= isset($modalidadesMap[$m['id']]) ? $modalidadesMap[$m['id']]['quantidade'] : '' ?>"
					   <?= isset($modalidadesMap[$m['id']]) ? '' : 'disabled' ?>>
			  </div>
			  <div class="col-md-3">
				<div class="form-check form-switch">
				  <input class="form-check-input toggle-modalidade" type="checkbox" role="switch" 
						 id="modalidade_<?= $m['id'] ?>" 
						 data-modalidade-id="<?= $m['id'] ?>"
						 <?= isset($modalidadesMap[$m['id']]) ? 'checked' : '' ?>>
				  <label class="form-check-label" for="modalidade_<?= $m['id'] ?>"></label>
				</div>
			  </div>
			  <div class="col-md-2"></div>
			</div>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Template para novas modalidades -->
        <div id="new-modalidades">
          <!-- Novas modalidades serão adicionadas aqui -->
        </div>
        
        <div class="row mb-3">
          <div class="col-12">
            <button type="button" class="btn btn-outline-info" id="add-new-modalidade">
              <i class="fas fa-plus"></i> Adicionar Nova Modalidade
            </button>
          </div>
        </div>
        
        <!-- Template para nova modalidade (oculto) -->
        <template id="new-modalidade-template">
		  <div class="row mb-3 modalidade-item new-modalidade">
			<div class="col-md-4">
				<label class="form-label">Nome</label>
				<input type="text" class="form-control" name="novas_modalidades[{index}][nome]" placeholder="Nome da Modalidade" maxlength="100" required>
			</div>
			<div class="col-md-3">
				<label class="form-label">Quantidade</label>
				<input type="number" class="form-control" name="novas_modalidades[{index}][quantidade]" placeholder="Quantidade" required>
			</div>
			<div class="col-md-3">
				<label class="form-label">Sigla</label>
				<input type="text" class="form-control" name="novas_modalidades[{index}][sigla]" placeholder="Sigla" maxlength="20" required>
			</div>
			<div class="col-md-2 d-flex align-items-end">
			  <button type="button" class="btn btn-outline-danger remove-modalidade">
				<i class="fas fa-trash"></i>
			  </button>
			</div>
		  </div>
		</template>
      </div>
      
      <div class="d-flex justify-content-between mt-4">
        <a href="contratos.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button type="submit" class="btn btn-knight">
          <i class="fas fa-save"></i> Salvar
        </button>
      </div>
    </form>
  </div>
</div>


<?php include '../includes/footer.php'; ?>