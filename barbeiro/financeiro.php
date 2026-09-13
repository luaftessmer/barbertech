<?php
// =====================================================
// BARBERTECH - Financeiro do Barbeiro
// O barbeiro vê apenas o próprio desempenho:
// total de atendimentos e valor recebido.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('barbeiro');

require_once '../conexao.php';

$id_barbeiro = $_SESSION['id_barbeiro'];
$periodo     = $_GET['periodo'] ?? 'mes';

$hoje       = date('Y-m-d');
$where_data = '';

switch ($periodo) {
    case 'dia':
        $where_data     = "AND DATE(a.data_hora) = '$hoje'";
        $titulo_periodo = 'Hoje (' . date('d/m/Y') . ')';
        break;
    case 'semana':
        $inicio = date('Y-m-d', strtotime('monday this week'));
        $fim    = date('Y-m-d', strtotime('sunday this week'));
        $where_data     = "AND DATE(a.data_hora) BETWEEN '$inicio' AND '$fim'";
        $titulo_periodo = 'Esta semana';
        break;
    case 'mes':
        $mes            = date('Y-m');
        $where_data     = "AND DATE_FORMAT(a.data_hora, '%Y-%m') = '$mes'";
        $titulo_periodo = date('F \d\e Y');
        break;
    default:
        $where_data     = '';
        $titulo_periodo = 'Todo o período';
        break;
}

// Total de atendimentos e valor
$sql    = "SELECT COUNT(*) AS total, SUM(valor_total) AS faturamento
           FROM atendimento a
           WHERE a.id_barbeiro = ? AND a.status = 'concluido' $where_data";
$stmt   = $conn->prepare($sql);
$stmt->bind_param('i', $id_barbeiro);
$stmt->execute();
$resumo = $stmt->get_result()->fetch_assoc();

// Serviços mais realizados
$sql = "SELECT s.nome, COUNT(*) AS quantidade
        FROM atendimento_servico ats
        JOIN atendimento a ON ats.id_atendimento = a.id_atendimento
        JOIN servico s     ON ats.id_servico     = s.id_servico
        WHERE a.id_barbeiro = ? AND a.status = 'concluido' $where_data
        GROUP BY ats.id_servico
        ORDER BY quantidade DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_barbeiro);
$stmt->execute();
$por_servico = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Meu Financeiro</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_barbeiro.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Meu Financeiro</h2>

        <!-- Filtros -->
        <div style="display: flex; gap: 10px; margin-bottom: 28px; flex-wrap: wrap;">
            <a href="financeiro.php?periodo=dia"    class="<?php echo $periodo=='dia'    ? 'btn-primario':'btn-secundario'; ?>" style="padding:8px 18px;text-decoration:none;">Hoje</a>
            <a href="financeiro.php?periodo=semana" class="<?php echo $periodo=='semana' ? 'btn-primario':'btn-secundario'; ?>" style="padding:8px 18px;text-decoration:none;">Esta semana</a>
            <a href="financeiro.php?periodo=mes"    class="<?php echo $periodo=='mes'    ? 'btn-primario':'btn-secundario'; ?>" style="padding:8px 18px;text-decoration:none;">Este mês</a>
            <a href="financeiro.php?periodo=geral"  class="<?php echo $periodo=='geral'  ? 'btn-primario':'btn-secundario'; ?>" style="padding:8px 18px;text-decoration:none;">Geral</a>
        </div>

        <p style="color:#888;margin-bottom:20px;font-size:14px;">
            Período: <strong><?php echo $titulo_periodo; ?></strong>
        </p>

        <!-- Cards -->
        <div class="grade-cards" style="margin-bottom:30px;">
            <div class="card">
                <div class="card-numero"><?php echo $resumo['total'] ?? 0; ?></div>
                <div class="card-titulo">Atendimentos Realizados</div>
            </div>
            <div class="card">
                <div class="card-numero">R$ <?php echo number_format($resumo['faturamento'] ?? 0, 2, ',', '.'); ?></div>
                <div class="card-titulo">Total Recebido</div>
            </div>
        </div>

        <!-- Serviços mais realizados -->
        <h3 style="margin-bottom:14px;color:#444;">Serviços Realizados</h3>
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($por_servico->num_rows > 0): ?>
                        <?php while ($s = $por_servico->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nome']); ?></td>
                            <td><?php echo $s['quantidade']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align:center;color:#aaa;padding:20px;">Nenhum atendimento no período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
