<?php

function podeEditarTipo($tipoAlvo)
{
  global $tipoLogado;

  // Usuário master pode fazer tudo
  if ($tipoLogado === 'master')
    return true;

  // Usuário infra pode editar infra e financeiro
  if ($tipoLogado === 'infra')
    return in_array($tipoAlvo, ['infra', 'financeiro']);

  // Usuário financeiro só pode editar financeiro
  if ($tipoLogado === 'financeiro')
    return $tipoAlvo === 'financeiro';

  // Qualquer outro caso, retorna false
  return false;
}

// Função para buscar modalidades de um cliente
function getModalidadesCliente($clienteId)
{
  global $pdo;

  $query = "SELECT cm.id, cm.quantidade, m.sigla, m.nome as modalidade_nome 
            FROM clientes_modalidades cm
            JOIN modalidades m ON cm.modalidade_id = m.id
            WHERE cm.cliente_id = ?
            ORDER BY m.sigla";

  $stmt = $pdo->prepare($query);
  $stmt->execute([$clienteId]);
  return $stmt->fetchAll();
}

function sanitizeFolderName($name)
{
  // Remove acentos e caracteres especiais
  $name = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
  // Substitui espaços por underlines
  $name = str_replace(' ', '_', $name);
  return $name;
}

function filtrarPorBusca($itens, $termoBusca, $campo = 'nome')
{
  if (empty($termoBusca)) {
    return $itens;
  }

  return array_filter($itens, function ($item) use ($termoBusca, $campo) {
    // Verifica se o campo existe no item
    if (!isset($item[$campo])) {
      return false;
    }

    // Busca case-insensitive no campo especificado
    return stripos($item[$campo], $termoBusca) !== false;
  });
}

function aplicarBuscaGlobal($tabela = null, $campo = 'nome', $itens = null) {
  global $pdo;
  
  $termoBusca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
  
  if (empty($termoBusca)) {
      return $itens ?? [];
  }

  // Se receber itens diretamente, filtra o array
  if (is_array($itens)) {
      return filtrarPorBusca($itens, $termoBusca, $campo);
  }
  
  // Se não receber itens mas receber tabela, busca no banco
  if ($tabela !== null) {
      $query = "SELECT * FROM {$tabela} WHERE {$campo} LIKE :busca";
      $stmt = $pdo->prepare($query);
      $stmt->bindValue(':busca', '%'.$termoBusca.'%');
      $stmt->execute();
      return $stmt->fetchAll();
  }
  
  return [];
}

function calcularProximaExecucao($dadosAgendamento) {
  $now = new DateTime();
  $hora = new DateTime($dadosAgendamento['hora_execucao']);
  
  if ($dadosAgendamento['frequencia'] === 'semanal') {
      $diaSemana = $dadosAgendamento['dia_semana'];
      $dias = [
          'segunda' => 1, 'terca' => 2, 'quarta' => 3, 
          'quinta' => 4, 'sexta' => 5, 'sabado' => 6, 'domingo' => 7
      ];
      $diaNumero = $dias[$diaSemana];
      $now->modify('next ' . $diaSemana);
  } 
  elseif ($dadosAgendamento['frequencia'] === 'mensal') {
      $diaMes = min($dadosAgendamento['dia_mes'], 28); // Evitar problemas com meses curtos
      $now->setDate($now->format('Y'), $now->format('m'), $diaMes);
      if ($now < new DateTime()) {
          $now->modify('+1 month');
      }
  }
  else { // primeira_semana
      $now->modify('first day of next month')->modify('+6 days'); // Fim da primeira semana
  }
  
  $now->setTime($hora->format('H'), $hora->format('i'));
  return $now->format('Y-m-d H:i:s');
}



