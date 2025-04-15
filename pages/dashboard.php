<?php 
include '../includes/header.php'; 
include '../includes/defs.php';
?>


<div class="row">
  <div class="col-12">
    <h2 class="mb-4"><i class="fas fa-rocket"></i> Painel Principal</h2>
  </div>
</div>

<div class="row g-4 mb-4 justify-content-center"> <!-- Adicionei mb-4 para margem inferior -->
  <div class="col-lg-6 col-md-6 col-sm-6 col-12">
    <div class="card bg-knight h-100">
      <div class="card-body text-center d-flex flex-column">
        <h3 class="card-title h5 mb-3"><i class="fas fa-file-contract fa-2x mb-3 text-knight"></i></h3>
        <h4 class="card-title">Contratos</h4>
        <p class="card-text">Contratos e modalidades</p>
        <div class="mt-auto"> <!-- Esta div empurra o botão para baixo -->
          <a href="contratos.php" class="btn btn-knight stretched-link btn-lg">
            Gerenciar 
            <i class="bi bi-arrow-up-right-square"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6 col-md-6 col-sm-6 col-12">
    <div class="card bg-knight h-100">
      <div class="card-body text-center d-flex flex-column">
        <h3 class="card-title h5 mb-3"><i class="fas fa-satellite-dish fa-2x mb-3 text-knight"></i></h3>
        <h4 class="card-title">Checagem</h4>
        <p class="card-text">Monitorar equipamentos</p>
        <div class="mt-auto">
          <a href="checagem.php" class="btn btn-knight stretched-link btn-lg">
            Ver todos
            <i class="bi bi-arrow-up-right-square"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 justify-content-center"> <!-- Segunda linha -->
  <div class="col-lg-6 col-md-6 col-sm-6 col-12">
    <div class="card bg-knight h-100">
      <div class="card-body text-center d-flex flex-column">
        <h3 class="card-title h5 mb-3"><i class="fas fa-chart-bar fa-2x mb-3 text-knight"></i></h3>
        <h4 class="card-title">Comparativo</h4>
        <p class="card-text">Análise e visualização</p>
        <div class="mt-auto">
          <a href="comparativo.php" class="btn btn-knight stretched-link btn-lg">
            Acessar
            <i class="bi bi-arrow-up-right-square"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6 col-md-6 col-sm-6 col-12">
    <div class="card bg-knight h-100">
      <div class="card-body text-center d-flex flex-column">
        <h3 class="card-title h5 mb-3"><i class="fas fa-users fa-2x mb-3 text-knight"></i></h3>
        <h4 class="card-title">Usuários</h4>
        <p class="card-text">Gerenciar usuários do sistema</p>
        <div class="mt-auto">
          <a href="usuario.php" class="btn btn-knight stretched-link btn-lg">
            Administrar
            <i class="bi bi-arrow-up-right-square"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>