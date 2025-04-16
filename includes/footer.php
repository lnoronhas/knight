<?php
include '../includes/defs.php';
?>
</main>
<footer class="mt-auto py-3">
  <div class="container-fluid">
    <div class="row align-items-center justify-content-between g-2">
      <!-- Botão Home - Esquerda -->
      <div class="col-auto">
        <a href="../pages/dashboard.php" class="btn btn-success btn-sm">
          <i class="fas fa-home"></i> <span class="d-none d-sm-inline">Home</span>
        </a>
      </div>

      <!-- Data/Hora e Temperatura - Centro -->
      <div class="col-auto flex-grow-1 text-center">
        <span class="badge bg-light text-dark fs-6 px-2 py-1">
          <i class="fas fa-clock"></i> <span id="current-date"><?= date('d/m/Y H:i') ?></span> |
          <i class="fas fa-temperature-half"></i> <span id="weather-temp">--</span>°C
        </span>
      </div>

      <!-- Botão Voltar - Direita -->
      <div class="col-auto">
        <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
          <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Voltar</span>
        </a>
      </div>
    </div>
  </div>
</footer>

<!-- jQuery primeiro (apenas uma vez) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Apenas um Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script src="../assets/js/scripts.js"></script>
<script>
  // Inicia
  updateDateTime();
  fetchINMETWeather();
  setInterval(updateDateTime, 60000);
  setInterval(fetchINMETWeather, 300000);
</script>

</body>

</html>