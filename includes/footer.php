<?php
include '../includes/defs.php';
?>
</main>
  <footer class="mt-auto py-3">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between g-2">
            <!-- Botão Home - Esquerda -->
            <div class="col-auto">
                <a href="../pages/dashboard.php" class="btn btn-info btn-sm">
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
  
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Scripts -->
  <script src="../assets/js/scripts.js"></script>
  
<script>
// Atualiza data/hora
function updateDateTime() {
  const now = new Date();
  const dateStr = now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
  document.getElementById('current-date').textContent = dateStr;
}

// Usando API do INMET (estações automáticas)
async function fetchINMETWeather() {
  try {
    // Código da estação de Santa Maria (pode precisar atualização)
    const response = await fetch('https://apitempo.inmet.gov.br/estacao/d/83936');
    const data = await response.json();
    
    // Pega o último registro (array está ordenado por data)
    const lastRecord = data[data.length - 1];
    if(lastRecord && lastRecord.TEM_INS) {
      document.getElementById('weather-temp').textContent = lastRecord.TEM_INS;
    }
  } catch (error) {
    console.log('Falha ao acessar INMET, tentando alternativa...');
    fallbackWeather();
  }
}

// Fallback alternativo
async function fallbackWeather() {
  try {
    const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-29.6842&longitude=-53.8069&current_weather=true');
    const data = await response.json();
    document.getElementById('weather-temp').textContent = Math.round(data.current_weather.temperature);
  } catch (error) {
    document.getElementById('weather-temp').textContent = '--';
  }
}

// Inicia
updateDateTime();
fetchINMETWeather();
setInterval(updateDateTime, 60000);
setInterval(fetchINMETWeather, 300000);
</script>
  
</body>
</html>