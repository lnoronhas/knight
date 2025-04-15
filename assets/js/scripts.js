document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap já tem toggle para sidebar, mas mantemos nossa própria implementação caso seja necessário
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Inicializar popovers do Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
    
    // Handle form submissions with AJAX
    document.querySelectorAll('form.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
            submitBtn.disabled = true;
            
            fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showAlert('success', data.message);
                        if (data.resetForm) {
                            this.reset();
                        }
                    }
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Erro na requisição: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    });
    
    // Função showAlert usando componentes Bootstrap
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.prepend(alertDiv);
        
        // Auto-close after 5 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
    
    // Bootstrap já tem implementação de modal, mas adaptamos nossa implementação personalizada
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const modalElement = document.querySelector(target);
            
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        });
    });
});

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

  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function () {
        const passwordInput = this.previousElementSibling;
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

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

  document.querySelectorAll('tbody tr').forEach(row => {
    row.addEventListener('click', (e) => {
      // Se o clique não foi no botão de expandir, para a propagação
      if (!e.target.closest('.handle-expand')) {
        e.stopPropagation();
      }
    });
  });

  // Opcional: Alternar ícone de seta quando expandir/retrair
  document.querySelectorAll('.handle-expand').forEach(btn => {
    btn.addEventListener('click', function () {
      const icon = this.querySelector('i');
      if (icon.classList.contains('fa-chevron-down')) {
        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
      } else {
        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Inicializar ícones corretamente
    document.querySelectorAll('.handle-expand').forEach(btn => {
        const target = document.querySelector(btn.getAttribute('data-bs-target'));
        const icon = btn.querySelector('i');
        
        // Verificar estado inicial
        if (target.classList.contains('show')) {
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            btn.classList.remove('collapsed');
        } else {
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            btn.classList.add('collapsed');
        }
        
        // Configurar evento de clique
        btn.addEventListener('click', function() {
            setTimeout(() => {
                if (btn.classList.contains('collapsed')) {
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                } else {
                    icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                }
            }, 10); // Pequeno delay para garantir que o Bootstrap tenha atualizado as classes
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Configurar toggles de modalidades existentes
    document.querySelectorAll('.toggle-modalidade').forEach(toggle => {
      toggle.addEventListener('change', function() {
        const modalidadeId = this.getAttribute('data-modalidade-id');
        const quantidadeInput = document.querySelector(`input[name="modalidades[${modalidadeId}][quantidade]"]`);
        
        if (this.checked) {
          quantidadeInput.disabled = false;
          quantidadeInput.value = quantidadeInput.value || '1'; // Default value
          quantidadeInput.required = true;
        } else {
          quantidadeInput.disabled = true;
          quantidadeInput.required = false;
        }
      });
    });
    
    // Adicionar nova modalidade
    let newModalidadeIndex = 0;
    const addModalidadeBtn = document.getElementById('add-new-modalidade');
    const newModalidadesContainer = document.getElementById('new-modalidades');
    const modalidadeTemplate = document.getElementById('new-modalidade-template').content;
    
    addModalidadeBtn.addEventListener('click', function() {
      const clone = document.importNode(modalidadeTemplate, true);
      
      // Substituir o placeholder {index} pelo índice atual
      clone.querySelectorAll('[name*="{index}"]').forEach(input => {
        input.name = input.name.replace('{index}', newModalidadeIndex);
      });
      
      // Configurar o botão de remover
      clone.querySelector('.remove-modalidade').addEventListener('click', function() {
        this.closest('.modalidade-item').remove();
      });
      
      newModalidadesContainer.appendChild(clone);
      newModalidadeIndex++;
    });
  });
  
  document.getElementById('bilhetagem_sim').addEventListener('change', function() {
    document.getElementById('qtd_bilhetagem').disabled = !this.checked;
    document.getElementById('qtd_bilhetagem').required = this.checked;
	});

	document.getElementById('bilhetagem_nao').addEventListener('change', function() {
		document.getElementById('qtd_bilhetagem').disabled = this.checked;
		document.getElementById('qtd_bilhetagem').required = !this.checked;
	});

	// Verificar estado inicial ao carregar a página
	document.addEventListener('DOMContentLoaded', function() {
		const bilhetagemNao = document.getElementById('bilhetagem_nao');
		if (bilhetagemNao.checked) {
			document.getElementById('qtd_bilhetagem').disabled = true;
			document.getElementById('qtd_bilhetagem').required = false;
		}
	});
  
  

