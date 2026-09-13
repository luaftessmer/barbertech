<?php
// =====================================================
// BARBERTECH - Histórico de Atendimentos de um Cliente
// Acessado pelo admin a partir da tela de clientes.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

if (!isset($_GET['id'])) {
    header('Location: clientes.php');
    exit();
}

$id_cliente = (int) $_GET['id'];

// Busca dados do cliente
$sql  = "SELECT u.nome, u.email, u.telefone
         FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario
         WHERE c.id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    header('Location: clientes.php');
    exit();
}

// Busca todos os atendimentos do cliente com os serviços de cada um
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            a.status,
            a.valor_total,
            a.observacao,
            u.nome AS nome_barbeiro,
            GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR ', ') AS servicos
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
$atendimentos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Histórico do Cliente</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">
            Histórico de <?php echo htmlspecialchars($cliente['nome']); ?>
        </h2>

        <!-- Dados do cliente -->
        <div style="background: #fff; padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
        </div>

        <!-- Tabela de atendimentos -->
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
                    <?php if ($atendimentos->num_rows > 0): ?>
                        <?php while ($a = $atendimentos->fetch_assoc()): ?>
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
                            <td colspan="5" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum atendimento encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <a href="clientes.php" class="btn-secundario">← Voltar para Clientes</a>
        </div>

    </div>

</body>
</html>
