<?php
// =====================================================
// BARBERTECH - Histórico de Clientes (Barbeiro)
// O barbeiro pode buscar um cliente e ver o histórico
// dos atendimentos que ele mesmo realizou.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('barbeiro');

require_once '../conexao.php';

$id_barbeiro = $_SESSION['id_barbeiro'];
$busca       = trim($_GET['busca'] ?? '');
$like        = '%' . $busca . '%';

// Busca clientes que já foram atendidos por este barbeiro
$sql = "SELECT DISTINCT c.id_cliente, u.nome, u.telefone
        FROM cliente c
        JOIN usuario u ON c.id_usuario = u.id_usuario
        JOIN atendimento a ON a.id_cliente = c.id_cliente
        WHERE a.id_barbeiro = ? AND (u.nome LIKE ? OR u.telefone LIKE ?)
        ORDER BY u.nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $id_barbeiro, $like, $like);
$stmt->execute();
$clientes = $stmt->get_result();

// Se um cliente específico foi selecionado, mostra o histórico dele
$cliente_selecionado  = null;
$historico_atendimentos = null;

if (isset($_GET['id_cliente'])) {
    $id_cliente = (int) $_GET['id_cliente'];

    // Dados do cliente
    $sql  = "SELECT u.nome, u.telefone FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario WHERE c.id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $cliente_selecionado = $stmt->get_result()->fetch_assoc();

    // Atendimentos desse cliente realizados por este barbeiro
    $sql = "SELECT
                a.data_hora, a.status, a.valor_total,
                GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
            FROM atendimento a
            LEFT JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
            LEFT JOIN servico s ON ats.id_servico = s.id_servico
            WHERE a.id_cliente = ? AND a.id_barbeiro = ?
            GROUP BY a.id_atendimento
            ORDER BY a.data_hora DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id_cliente, $id_barbeiro);
    $stmt->execute();
    $historico_atendimentos = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Histórico de Clientes</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_barbeiro.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Histórico de Clientes</h2>

        <!-- Busca de cliente -->
        <form action="historico.php" method="GET" style="display:flex;gap:10px;margin-bottom:24px;">
            <input type="text" name="busca" placeholder="Buscar cliente por nome ou telefone..."
                   value="<?php echo htmlspecialchars($busca); ?>"
                   style="padding:9px 12px;border:1px solid #ccc;border-radius:5px;font-size:14px;width:300px;">
            <button type="submit" class="btn-secundario">Buscar</button>
        </form>

        <!-- Lista de clientes encontrados -->
        <?php if ($clientes->num_rows > 0): ?>
        <div class="container-tabela" style="margin-bottom:30px;">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['nome']); ?></td>
                        <td><?php echo htmlspecialchars($c['telefone']); ?></td>
                        <td>
                            <a href="historico.php?id_cliente=<?php echo $c['id_cliente']; ?>&busca=<?php echo urlencode($busca); ?>"
                               class="btn-secundario" style="font-size:13px;">
                                Ver histórico
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($busca): ?>
            <p style="color:#aaa;">Nenhum cliente encontrado.</p>
        <?php endif; ?>

        <!-- Histórico do cliente selecionado -->
        <?php if ($cliente_selecionado && $historico_atendimentos): ?>
        <h3 style="margin-bottom:14px;color:#444;">
            Histórico de <?php echo htmlspecialchars($cliente_selecionado['nome']); ?>
        </h3>
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <th>Serviços</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historico_atendimentos->num_rows > 0): ?>
                        <?php while ($a = $historico_atendimentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['data_hora'])); ?></td>
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
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Nenhum atendimento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
