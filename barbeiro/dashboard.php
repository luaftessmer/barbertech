<?php
// =====================================================
// BARBERTECH - Dashboard do Barbeiro
// Tela inicial do barbeiro com os agendamentos do dia
// e um resumo do próprio financeiro.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('barbeiro');

require_once '../conexao.php';

$hoje       = date('Y-m-d');
$mes_atual  = date('Y-m');
$id_barbeiro = $_SESSION['id_barbeiro']; // ID do barbeiro logado

// -------------------------------------------------------
// Agendamentos de hoje do barbeiro logado (pendentes)
// -------------------------------------------------------
$sql = "SELECT COUNT(*) as total FROM atendimento
        WHERE id_barbeiro = ? AND DATE(data_hora) = ? AND status = 'pendente'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_barbeiro, $hoje);
$stmt->execute();
$total_hoje = $stmt->get_result()->fetch_assoc()['total'];

// -------------------------------------------------------
// Faturamento do barbeiro no mês atual (concluídos)
// -------------------------------------------------------
$sql = "SELECT SUM(valor_total) as total FROM atendimento
        WHERE id_barbeiro = ? AND DATE_FORMAT(data_hora, '%Y-%m') = ? AND status = 'concluido'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_barbeiro, $mes_atual);
$stmt->execute();
$faturamento_mes = $stmt->get_result()->fetch_assoc()['total'];
$faturamento_mes = $faturamento_mes ?? 0;

// -------------------------------------------------------
// Total de atendimentos concluídos no mês
// -------------------------------------------------------
$sql = "SELECT COUNT(*) as total FROM atendimento
        WHERE id_barbeiro = ? AND DATE_FORMAT(data_hora, '%Y-%m') = ? AND status = 'concluido'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_barbeiro, $mes_atual);
$stmt->execute();
$atendimentos_mes = $stmt->get_result()->fetch_assoc()['total'];

// -------------------------------------------------------
// Lista de agendamentos de hoje com detalhes
// -------------------------------------------------------
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            a.status,
            a.valor_total,
            u.nome AS nome_cliente
        FROM atendimento a
        JOIN cliente c ON a.id_cliente = c.id_cliente
        JOIN usuario  u ON c.id_usuario = u.id_usuario
        WHERE a.id_barbeiro = ? AND DATE(a.data_hora) = ?
        ORDER BY a.data_hora ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_barbeiro, $hoje);
$stmt->execute();
$agendamentos_lista = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Painel Barbeiro</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_barbeiro.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Meu Painel</h2>

        <!-- Cards de resumo -->
        <div class="grade-cards">

            <div class="card">
                <div class="card-numero"><?php echo $total_hoje; ?></div>
                <div class="card-titulo">Agendamentos Hoje</div>
            </div>

            <div class="card">
                <div class="card-numero"><?php echo $atendimentos_mes; ?></div>
                <div class="card-titulo">Atendimentos no Mês</div>
            </div>

            <div class="card">
                <div class="card-numero">R$ <?php echo number_format($faturamento_mes, 2, ',', '.'); ?></div>
                <div class="card-titulo">Meu Faturamento no Mês</div>
            </div>

        </div>

        <!-- Ação rápida -->
        <div style="margin-bottom: 30px;">
            <a href="agendamentos.php?acao=novo" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none;">
                + Novo Agendamento
            </a>
        </div>

        <!-- Agendamentos de hoje -->
        <h3 style="margin-bottom: 16px; color: #444;">Meus Agendamentos Hoje — <?php echo date('d/m/Y'); ?></h3>

        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($agendamentos_lista->num_rows > 0): ?>
                        <?php while ($a = $agendamentos_lista->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($a['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_cliente']); ?></td>
                            <td>R$ <?php echo number_format($a['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $a['status']; ?>">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($a['status'] == 'pendente'): ?>
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
                            <td colspan="5" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum agendamento para hoje.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
