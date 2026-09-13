<?php
// =====================================================
// BARBERTECH - Controle Financeiro (Admin)
// Exibe o faturamento da barbearia com filtros
// por dia, semana, mês ou geral.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

// Período selecionado (padrão: mês atual)
$periodo = $_GET['periodo'] ?? 'mes';

// -------------------------------------------------------
// Define o filtro de data conforme o período escolhido
// -------------------------------------------------------
$hoje        = date('Y-m-d');
$where_data  = '';

switch ($periodo) {
    case 'dia':
        // Somente hoje
        $where_data = "AND DATE(a.data_hora) = '$hoje'";
        $titulo_periodo = 'Hoje (' . date('d/m/Y') . ')';
        break;

    case 'semana':
        // Da segunda-feira até o domingo desta semana
        $inicio_semana = date('Y-m-d', strtotime('monday this week'));
        $fim_semana    = date('Y-m-d', strtotime('sunday this week'));
        $where_data    = "AND DATE(a.data_hora) BETWEEN '$inicio_semana' AND '$fim_semana'";
        $titulo_periodo = 'Esta semana (' . date('d/m', strtotime($inicio_semana)) . ' – ' . date('d/m/Y', strtotime($fim_semana)) . ')';
        break;

    case 'mes':
        // Mês atual
        $mes_atual  = date('Y-m');
        $where_data = "AND DATE_FORMAT(a.data_hora, '%Y-%m') = '$mes_atual'";
        $meses_pt   = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                       'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $titulo_periodo = $meses_pt[(int) date('n')] . ' de ' . date('Y');
        break;

    case 'geral':
    default:
        // Sem filtro de data
        $where_data     = '';
        $titulo_periodo = 'Todo o período';
        break;
}

// -------------------------------------------------------
// TOTAL GERAL DO PERÍODO (atendimentos concluídos)
// -------------------------------------------------------
$sql   = "SELECT COUNT(*) AS total_atendimentos, SUM(a.valor_total) AS faturamento
          FROM atendimento a
          WHERE a.status = 'concluido' $where_data";
$resumo = $conn->query($sql)->fetch_assoc();
$faturamento_total  = $resumo['faturamento']        ?? 0;
$total_atendimentos = $resumo['total_atendimentos'] ?? 0;

// -------------------------------------------------------
// FATURAMENTO POR BARBEIRO
// -------------------------------------------------------
$sql = "SELECT
            u.nome AS nome_barbeiro,
            COUNT(a.id_atendimento) AS total_atendimentos,
            SUM(a.valor_total)      AS faturamento
        FROM atendimento a
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario  u ON b.id_usuario  = u.id_usuario
        WHERE a.status = 'concluido' $where_data
        GROUP BY a.id_barbeiro
        ORDER BY faturamento DESC";
$por_barbeiro = $conn->query($sql);

// -------------------------------------------------------
// FATURAMENTO POR SERVIÇO
// -------------------------------------------------------
$sql = "SELECT
            s.nome AS nome_servico,
            COUNT(ats.id_servico) AS quantidade,
            SUM(s.preco)          AS faturamento
        FROM atendimento_servico ats
        JOIN servico     s ON ats.id_servico     = s.id_servico
        JOIN atendimento a ON ats.id_atendimento = a.id_atendimento
        WHERE a.status = 'concluido' $where_data
        GROUP BY ats.id_servico
        ORDER BY quantidade DESC";
$por_servico = $conn->query($sql);

// -------------------------------------------------------
// LISTA DOS ATENDIMENTOS DO PERÍODO
// -------------------------------------------------------
$sql = "SELECT
            a.data_hora, a.valor_total,
            u_c.nome AS nome_cliente,
            u_b.nome AS nome_barbeiro,
            GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
        FROM atendimento a
        JOIN cliente  c ON a.id_cliente  = c.id_cliente
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario u_c ON c.id_usuario = u_c.id_usuario
        JOIN usuario u_b ON b.id_usuario = u_b.id_usuario
        LEFT JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
        LEFT JOIN servico s ON ats.id_servico = s.id_servico
        WHERE a.status = 'concluido' $where_data
        GROUP BY a.id_atendimento
        ORDER BY a.data_hora DESC";
$atendimentos = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Financeiro</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Controle Financeiro</h2>

        <!-- Filtros de período -->
        <div style="display: flex; gap: 10px; margin-bottom: 28px; flex-wrap: wrap;">
            <a href="financeiro.php?periodo=dia"
               class="<?php echo $periodo == 'dia'    ? 'btn-primario' : 'btn-secundario'; ?>"
               style="padding: 8px 18px; text-decoration: none;">Hoje</a>

            <a href="financeiro.php?periodo=semana"
               class="<?php echo $periodo == 'semana' ? 'btn-primario' : 'btn-secundario'; ?>"
               style="padding: 8px 18px; text-decoration: none;">Esta semana</a>

            <a href="financeiro.php?periodo=mes"
               class="<?php echo $periodo == 'mes'    ? 'btn-primario' : 'btn-secundario'; ?>"
               style="padding: 8px 18px; text-decoration: none;">Este mês</a>

            <a href="financeiro.php?periodo=geral"
               class="<?php echo $periodo == 'geral'  ? 'btn-primario' : 'btn-secundario'; ?>"
               style="padding: 8px 18px; text-decoration: none;">Geral</a>
        </div>

        <p style="color: #888; margin-bottom: 20px; font-size: 14px;">
            Período: <strong><?php echo $titulo_periodo; ?></strong>
            · Apenas atendimentos <strong>concluídos</strong>
        </p>

        <!-- Cards de resumo -->
        <div class="grade-cards" style="margin-bottom: 30px;">
            <div class="card">
                <div class="card-numero">R$ <?php echo number_format($faturamento_total, 2, ',', '.'); ?></div>
                <div class="card-titulo">Faturamento Total</div>
            </div>
            <div class="card">
                <div class="card-numero"><?php echo $total_atendimentos; ?></div>
                <div class="card-titulo">Atendimentos Realizados</div>
            </div>
            <div class="card">
                <div class="card-numero">
                    R$ <?php echo $total_atendimentos > 0 ? number_format($faturamento_total / $total_atendimentos, 2, ',', '.') : '0,00'; ?>
                </div>
                <div class="card-titulo">Valor Médio</div>
            </div>
        </div>

        <!-- Faturamento por barbeiro -->
        <h3 style="margin-bottom: 14px; color: #444;">Por Barbeiro</h3>
        <div class="container-tabela" style="margin-bottom: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>Barbeiro</th>
                        <th>Atendimentos</th>
                        <th>Faturamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($por_barbeiro->num_rows > 0): ?>
                        <?php while ($b = $por_barbeiro->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['nome_barbeiro']); ?></td>
                            <td><?php echo $b['total_atendimentos']; ?></td>
                            <td>R$ <?php echo number_format($b['faturamento'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#aaa;padding:20px;">Nenhum dado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Faturamento por serviço -->
        <h3 style="margin-bottom: 14px; color: #444;">Por Serviço</h3>
        <div class="container-tabela" style="margin-bottom: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Quantidade</th>
                        <th>Faturamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($por_servico->num_rows > 0): ?>
                        <?php while ($s = $por_servico->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nome_servico']); ?></td>
                            <td><?php echo $s['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($s['faturamento'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#aaa;padding:20px;">Nenhum dado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Lista de atendimentos do período -->
        <h3 style="margin-bottom: 14px; color: #444;">Atendimentos Concluídos</h3>
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Barbeiro</th>
                        <th>Serviços</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($atendimentos->num_rows > 0): ?>
                        <?php while ($a = $atendimentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_barbeiro']); ?></td>
                            <td><?php echo htmlspecialchars($a['servicos'] ?? '—'); ?></td>
                            <td>R$ <?php echo number_format($a['valor_total'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;color:#aaa;padding:20px;">Nenhum atendimento no período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
