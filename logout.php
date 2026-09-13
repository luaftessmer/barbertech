<?php
// =====================================================
// BARBERTECH - Logout
// Destrói a sessão do usuário e volta para o login.
// =====================================================

session_start();

// Apaga todos os dados da sessão
session_destroy();

// Redireciona para a tela de login
header('Location: index.php');
exit();
?>
