<?php
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/defs.php';
session_start();

$usuarioLogado = $_SESSION['usuario'];
$tipoLogado = $usuarioLogado['tipo'];

$id = $_GET['id'] ?? null;

// Redirecionar para o novo sistema de ação
header('Location: contratos_action.php?id=' . $id . '&acao=apagar');
exit();