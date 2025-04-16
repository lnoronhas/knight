<!-- Barra de busca global (só mostra em páginas que não são a dashboard) -->
<?php if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php'): ?>
    <div class="row mb-4 d-print-none">
      <div class="col-md-12">
        <form method="get" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>" class="search-form">
          <div class="input-group">
            <input type="text" name="busca" class="form-control" placeholder="Buscar..." 
                   value="<?= isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : '' ?>">
            <button class="btn btn-outline-light" type="submit">
              <i class="fas fa-search"></i>
            </button>
            <!-- Adiciona todos os outros parâmetros GET como hidden inputs -->
            <?php foreach($_GET as $key => $value): ?>
              <?php if ($key !== 'busca'): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>