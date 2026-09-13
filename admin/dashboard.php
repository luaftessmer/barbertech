<?php
// =====================================================
// BARBERTECH - Dashboard do Administrador
// Tela inicial do admin com resumo geral da barbearia.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

// Data de hoje no formato do MySQL
$hoje = date('Y-m-d');

// -------------------------------------------------------
// Busca o total de agendamentos de hoje (pendentes)
// -------------------------------------------------------
$sql = "SELECT COUNT(*) as total FROM atendimento WHERE DATE(data_hora) = ? AND status = 'pendente'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hoje);
$stmt->execute();
$agendamentos_hoje = $stmt->get_result()->fetch_assoc()['total'];

// -------------------------------------------------------
// Busca o faturamento do mês atual (apenas concluídos)
// -------------------------------------------------------
$mes_atual = date('Y-m');
$sql = "SELECT SUM(valor_total) as total FROM atendimento WHERE DATE_FORMAT(data_hora, '%Y-%m') = ? AND status = 'concluido'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mes_atual);
$stmt->execute();
$faturamento_mes = $stmt->get_result()->fetch_assoc()['total'];
$faturamento_mes = $faturamento_mes ?? 0; // se for nulo, usa 0

// -------------------------------------------------------
// Busca o total de clientes cadastrados
// -------------------------------------------------------
$sql = "SELECT COUNT(*) as total FROM cliente";
$total_clientes = $conn->query($sql)->fetch_assoc()['total'];

// -------------------------------------------------------
// Busca o total de barbeiros cadastrados
// -------------------------------------------------------
$sql = "SELECT COUNT(*) as total FROM barbeiro";
$total_barbeiros = $conn->query($sql)->fetch_assoc()['total'];

// -------------------------------------------------------
// Busca os agendamentos de hoje com detalhes
// -------------------------------------------------------
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            a.status,
            a.valor_total,
            u_cliente.nome  AS nome_cliente,
            u_barbeiro.nome AS nome_barbeiro
        FROM atendimento a
        JOIN cliente  c ON a.id_cliente  = c.id_cliente
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario u_cliente  ON c.id_usuario = u_cliente.id_usuario
        JOIN usuario u_barbeiro ON b.id_usuario = u_barbeiro.id_usuario
        WHERE DATE(a.data_hora) = ?
        ORDER BY a.data_hora ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hoje);
$stmt->execute();
$agendamentos_lista = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Painel Admin</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <!-- Menu de navegação do admin -->
    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Painel do Administrador</h2>

        <!-- -----------------------------------------------
             CARDS DE RESUMO
             ----------------------------------------------- -->
        <div class="grade-cards">

            <div class="card">
                <div class="card-numero"><?php echo $agendamentos_hoje; ?></div>
                <div class="card-titulo">Agendamentos Hoje</div>
            </div>

            <div class="card">
                <div class="card-numero">R$ <?php echo number_format($faturamento_mes, 2, ',', '.'); ?></div>
                <div class="card-titulo">Faturamento do Mês</div>
            </div>

            <div class="card">
                <div class="card-numero"><?php echo $total_clientes; ?></div>
                <div class="card-titulo">Clientes Cadastrados</div>
            </div>

            <div class="card">
                <div class="card-numero"><?php echo $total_barbeiros; ?></div>
                <div class="card-titulo">Barbeiros</div>
            </div>

        </div>

        <!-- -----------------------------------------------
             AÇÕES RÁPIDAS
             ----------------------------------------------- -->
        <div style="display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="agendamentos.php?acao=novo" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none;">
                + Novo Agendamento
            </a>
            <a href="servicos.php?acao=novo" class="btn-secundario">
                + Novo Serviço
            </a>
            <a href="barbeiros.php?acao=novo" class="btn-secundario">
                + Novo Barbeiro
            </a>
        </div>

        <!-- -----------------------------------------------
             AGENDAMENTOS DE HOJE
             ----------------------------------------------- -->
        <h3 style="margin-bottom: 16px; color: #444;">Agendamentos de Hoje — <?php echo date('d/m/Y'); ?></h3>

        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Cliente</th>
                        <th>Barbeiro</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($agendamentos_lista->num_rows > 0): ?>
                        <?php while ($a = $agendamentos_lista->fetch_assoc()): ?>
                        <tr>
                            <!-- Formata a data/hora para exibir só o horário -->
                            <td><?php echo date('H:i', strtotime($a['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_barbeiro']); ?></td>
                            <td>R$ <?php echo number_format($a['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <!-- Badge de status colorido -->
                                <span class="badge badge-<?php echo $a['status']; ?>">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($a['status'] == 'pendente'): ?>
                                    <!-- Botão para cancelar com confirmação -->
                                    <a href="agendamentos.php?acao=cancelar&id=<?php echo $a['id_atendimento']; ?>"
                                       class="btn-perigo"
                                       onclick="return pedirConfirmacao(this, 'Cancelar este agendamento?')">
                                        Cancelar
                                    </a>
                                <?php else: ?>
                                    <span style="color: #aaa; font-size: 13px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum agendamento para hoje.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div><!-- fim conteudo-principal -->

</body>
</html>
