<?php
// =====================================================
// BARBERTECH - Menu do Barbeiro
// Incluído no topo de todas as páginas do barbeiro.
// =====================================================
?>
<script src="/barbertech/includes/confirmar.js"></script>
<nav class="menu-topo">
    <div style="display: flex; gap: 20px; align-items: center; flex: 1; justify-content: center;">
        <a href="/barbertech/barbeiro/dashboard.php">Início</a>
        <a href="/barbertech/barbeiro/agenda.php">Minha Agenda</a>
        <a href="/barbertech/barbeiro/agendamentos.php">Agendamentos</a>
        <a href="/barbertech/barbeiro/historico.php">Histórico de Clientes</a>
        <a href="/barbertech/barbeiro/financeiro.php">Financeiro</a>
    </div>

    <div class="menu-usuario">
        <span>Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
        <a href="/barbertech/logout.php" class="btn-sair">Sair</a>
    </div>
</nav>
