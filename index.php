<?php
// Estrutura inicial do projeto Knight
// Arquivo: index.php (redireciona para login ou dashboard)

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./pages/login.php');
    exit;
} else {
    header('Location: ./pages/dashboard.php');
    exit;
}