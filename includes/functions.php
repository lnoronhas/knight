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

function sanitizeFolderName($name) {
  // Remove acentos e caracteres especiais
  $name = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
  // Substitui espaços por underlines
  $name = str_replace(' ', '_', $name);
  return $name;
}

?>