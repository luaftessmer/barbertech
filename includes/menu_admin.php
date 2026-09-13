<?php
// =====================================================
// BARBERTECH - Menu do Administrador
// Incluído no topo de todas as páginas do admin.
// =====================================================
?>
<script src="/barbertech/includes/confirmar.js"></script>
<nav class="menu-topo">
    <!-- Links de navegação -->
    <div style="display: flex; gap: 20px; align-items: center; flex: 1; justify-content: center;">
        <a href="/barbertech/admin/dashboard.php">Início</a>
        <a href="/barbertech/admin/agendamentos.php">Agendamentos</a>
        <a href="/barbertech/admin/clientes.php">Clientes</a>
        <a href="/barbertech/admin/barbeiros.php">Barbeiros</a>
        <a href="/barbertech/admin/servicos.php">Serviços</a>
        <a href="/barbertech/admin/financeiro.php">Financeiro</a>
        <a href="/barbertech/admin/configuracoes.php">Configurações</a>
    </div>

    <!-- Nome do usuário logado e botão sair -->
    <div class="menu-usuario">
        <span>Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
        <a href="/barbertech/logout.php" class="btn-sair">Sair</a>
    </div>
</nav>
