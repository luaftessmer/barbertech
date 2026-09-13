<?php
// =====================================================
// BARBERTECH - Dashboard do Cliente
// Tela inicial do cliente com próximo agendamento,
// agendamentos futuros e histórico recente.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('cliente');

require_once '../conexao.php';

$id_cliente = $_SESSION['id_cliente']; // ID do cliente logado
$hoje       = date('Y-m-d H:i:s');

// -------------------------------------------------------
// Próximo agendamento do cliente (o mais próximo no futuro)
// -------------------------------------------------------
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            a.status,
            a.valor_total,
            u.nome AS nome_barbeiro
        FROM atendimento a
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario  u ON b.id_usuario  = u.id_usuario
        WHERE a.id_cliente = ? AND a.data_hora > ? AND a.status = 'pendente'
        ORDER BY a.data_hora ASC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_cliente, $hoje);
$stmt->execute();
$proximo = $stmt->get_result()->fetch_assoc();

// -------------------------------------------------------
// Todos os agendamentos futuros (pendentes)
// -------------------------------------------------------
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            a.valor_total,
            u.nome AS nome_barbeiro
        FROM atendimento a
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario  u ON b.id_usuario  = u.id_usuario
        WHERE a.id_cliente = ? AND a.data_hora > ? AND a.status = 'pendente'
        ORDER BY a.data_hora ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id_cliente, $hoje);
$stmt->execute();
$futuros = $stmt->get_result();

// -------------------------------------------------------
// Histórico recente (últimos 5 atendimentos concluídos ou cancelados)
// -------------------------------------------------------
$sql = "SELECT
            a.data_hora,
            a.status,
            a.valor_total,
            u.nome AS nome_barbeiro
        FROM atendimento a
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario  u ON b.id_usuario  = u.id_usuario
        WHERE a.id_cliente = ? AND a.status IN ('concluido', 'cancelado')
        ORDER BY a.data_hora DESC
        LIMIT 5";
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
    <title>BarberTech - Minha Área</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_cliente.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Olá, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h2>

        <!-- -----------------------------------------------
             PRÓXIMO AGENDAMENTO EM DESTAQUE
             ----------------------------------------------- -->
        <?php if ($proximo): ?>
        <div class="card-destaque">
            <div class="card-destaque-titulo">Próximo Agendamento</div>
            <div class="card-destaque-info">
                <!-- Formata a data: ex "Segunda-feira, 25 de Agosto de 2026 às 14:30" -->
                <strong><?php echo strftime('%A, %d de %B de %Y', strtotime($proximo['data_hora'])); ?>
                    às <?php echo date('H:i', strtotime($proximo['data_hora'])); ?>
                </strong>
            </div>
            <div class="card-destaque-info">
                Barbeiro: <?php echo htmlspecialchars($proximo['nome_barbeiro']); ?>
            </div>
            <div class="card-destaque-info">
                Valor: R$ <?php echo number_format($proximo['valor_total'], 2, ',', '.'); ?>
            </div>
            <!-- Botão para cancelar o próximo agendamento -->
            <a href="cancelar.php?id=<?php echo $proximo['id_atendimento']; ?>"
               class="btn-perigo"
               style="display: inline-block; margin-top: 12px;"
               onclick="return pedirConfirmacao(this, 'Cancelar este agendamento?')">
                Cancelar Agendamento
            </a>
        </div>
        <?php else: ?>
        <div class="card-destaque sem-agendamento">
            <div class="card-destaque-titulo">Nenhum agendamento futuro</div>
            <p style="color: #888; margin-top: 8px;">Que tal agendar um horário agora?</p>
            <a href="novo_agendamento.php" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none; display: inline-block; margin-top: 12px;">
                Agendar agora
            </a>
        </div>
        <?php endif; ?>

        <!-- Botão de novo agendamento -->
        <div style="margin: 24px 0 30px;">
            <a href="novo_agendamento.php" class="btn-primario" style="width: auto; padding: 10px 24px; text-decoration: none; display: inline-block;">
                + Novo Agendamento
            </a>
        </div>

        <!-- -----------------------------------------------
             AGENDAMENTOS FUTUROS
             ----------------------------------------------- -->
        <h3 style="margin-bottom: 16px; color: #444;">Meus Agendamentos</h3>

        <div class="container-tabela" style="margin-bottom: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <th>Barbeiro</th>
                        <th>Valor</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($futuros->num_rows > 0): ?>
                        <?php while ($a = $futuros->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($a['nome_barbeiro']); ?></td>
                            <td>R$ <?php echo number_format($a['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="cancelar.php?id=<?php echo $a['id_atendimento']; ?>"
                                   class="btn-perigo"
                                   onclick="return pedirConfirmacao(this, 'Cancelar este agendamento?')">
                                    Cancelar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #aaa; padding: 20px;">
                                Nenhum agendamento futuro.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- -----------------------------------------------
             HISTÓRICO RECENTE
             ----------------------------------------------- -->
        <h3 style="margin-bottom: 16px; color: #444;">Histórico Recente</h3>

        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <th>Barbeiro</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historico->num_rows > 0): ?>
                        <?php while ($h = $historico->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($h['data_hora'])); ?></td>
                            <td><?php echo htmlspecialchars($h['nome_barbeiro']); ?></td>
                            <td>R$ <?php echo number_format($h['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $h['status']; ?>">
                                    <?php echo ucfirst($h['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #aaa; padding: 20px;">
                                Nenhum atendimento no histórico.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Link para ver histórico completo -->
        <div style="text-align: right; margin-top: 10px;">
            <a href="historico.php" style="color: #555; font-size: 13px;">Ver histórico completo →</a>
        </div>

    </div>

</body>
</html>
