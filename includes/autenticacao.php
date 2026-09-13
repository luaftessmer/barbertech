<?php
// =====================================================
// BARBERTECH - Arquivo de Autenticação
// Incluído no topo de cada página protegida.
// Verifica se o usuário está logado e tem o perfil certo.
//
// Como usar:
//   require_once '../includes/autenticacao.php';
//   verificar_acesso('admin');       // só admin entra
//   verificar_acesso('barbeiro');    // só barbeiro entra
//   verificar_acesso('cliente');     // só cliente entra
// =====================================================

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função que verifica se o usuário está logado e tem o tipo certo
function verificar_acesso($tipo_necessario) {

    // Se não há sessão ativa, vai para o login
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ../index.php');
        exit();
    }

    // Se o tipo do usuário não bate com o necessário, vai para o login
    if ($_SESSION['tipo'] !== $tipo_necessario) {
        header('Location: ../index.php');
        exit();
    }
}
?>
