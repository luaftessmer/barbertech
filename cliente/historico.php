<?php
// =====================================================
// BARBERTECH - Histórico Completo do Cliente
// Exibe todos os atendimentos do cliente logado.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('cliente');

require_once '../conexao.php';

$id_cliente = $_SESSION['id_cliente'];

$sql = "SELECT
            a.data_hora, a.status, a.valor_total,
            u.nome AS nome_barbeiro,
            GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
        FROM atendimento a
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario  u ON b.id_usuario  = u.id_usuario
        LEFT JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
        LEFT JOIN servico s ON ats.id_servico = s.id_servico
        WHERE a.id_cliente = ?
        GROUP BY a.id_atendimento
        ORDER BY a.data_hora DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_cliente);
$stmt->execute();
$historico = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Meu Histórico</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_cliente.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Meu Histórico de Atendimentos</h2>

        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <th>Barbeiro</th>
                        <th>Serviços</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historico->num_rows > 0): ?>
                        <?php while ($a = $historico->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_barbeiro']); ?></td>
                            <td><?php echo htmlspecialchars($a['servicos'] ?? '—'); ?></td>
                            <td>R$ <?php echo number_format($a['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $a['status']; ?>">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:#aaa;padding:30px;">
                                Nenhum atendimento no histórico ainda.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
