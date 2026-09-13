<?php
// =====================================================
// BARBERTECH - Menu do Cliente
// Incluído no topo de todas as páginas do cliente.
// =====================================================
?>
<script src="/barbertech/includes/confirmar.js"></script>
<nav class="menu-topo">
    <div style="display: flex; gap: 20px; align-items: center; flex: 1; justify-content: center;">
        <a href="/barbertech/cliente/dashboard.php">Início</a>
        <a href="/barbertech/cliente/novo_agendamento.php">Agendar</a>
        <a href="/barbertech/cliente/historico.php">Histórico</a>
    </div>

    <div class="menu-usuario">
        <span>Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
        <a href="/barbertech/logout.php" class="btn-sair">Sair</a>
    </div>
</nav>
