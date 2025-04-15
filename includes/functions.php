<?php

function podeEditarTipo($tipoAlvo) {
  global $tipoLogado;
  
  // Usuário master pode fazer tudo
  if ($tipoLogado === 'master') return true;
  
  // Usuário infra pode editar infra e financeiro
  if ($tipoLogado === 'infra') return in_array($tipoAlvo, ['infra', 'financeiro']);
  
  // Usuário financeiro só pode editar financeiro
  if ($tipoLogado === 'financeiro') return $tipoAlvo === 'financeiro';
  
  // Qualquer outro caso, retorna false
  return false;
}


?>