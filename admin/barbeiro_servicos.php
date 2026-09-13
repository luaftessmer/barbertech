<?php
// =====================================================
// BARBERTECH - Serviços por Barbeiro (Admin)
// Permite ativar ou desativar serviços específicos
// para cada barbeiro individualmente.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';
require_once '../includes/email.php';

// Precisa do ID do barbeiro na URL
if (!isset($_GET['id'])) {
    header('Location: barbeiros.php');
    exit();
}

$id_barbeiro = (int) $_GET['id'];
$mensagem    = '';

// Busca o nome do barbeiro
$sql  = "SELECT u.nome FROM barbeiro b JOIN usuario u ON b.id_usuario = u.id_usuario WHERE b.id_barbeiro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_barbeiro);
$stmt->execute();
$barbeiro = $stmt->get_result()->fetch_assoc();

if (!$barbeiro) {
    header('Location: barbeiros.php');
    exit();
}

// -------------------------------------------------------
// SALVAR ALTERAÇÕES DOS SERVIÇOS
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Pega todos os serviços existentes
    $todos_servicos = $conn->query("SELECT id_servico FROM servico");

    while ($s = $todos_servicos->fetch_assoc()) {
        $id_servico = $s['id_servico'];

        // Se o checkbox do serviço foi marcado, ativo = 1, senão = 0
        $ativo = isset($_POST['servico_' . $id_servico]) ? 1 : 0;

        // Verifica se já existe o vínculo na tabela barbeiro_servico
        $sql  = "SELECT id_barbeiro FROM barbeiro_servico WHERE id_barbeiro = ? AND id_servico = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id_barbeiro, $id_servico);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Já existe: apenas atualiza o campo "ativo"
            $sql2  = "UPDATE barbeiro_servico SET ativo = ? WHERE id_barbeiro = ? AND id_servico = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('iii', $ativo, $id_barbeiro, $id_servico);
            $stmt2->execute();
        } else {
            // Não existe: insere o vínculo
            $sql2  = "INSERT INTO barbeiro_servico (id_barbeiro, id_servico, ativo) VALUES (?, ?, ?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('iii', $id_barbeiro, $id_servico, $ativo);
            $stmt2->execute();
        }

        // Se o serviço foi DESATIVADO, cancela os agendamentos futuros
        if ($ativo == 0) {
            $hoje = date('Y-m-d H:i:s');

            // Busca agendamentos futuros que contêm esse serviço para esse barbeiro
            $sql3 = "SELECT a.id_atendimento, a.data_hora
                     FROM atendimento a
                     JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
                     WHERE a.id_barbeiro = ?
                       AND ats.id_servico = ?
                       AND a.data_hora > ?
                       AND a.status = 'pendente'";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param('iis', $id_barbeiro, $id_servico, $hoje);
            $stmt3->execute();
            $agendamentos_cancelar = $stmt3->get_result();

            // Busca o nome do serviço para o email
            $sql_srv  = "SELECT nome FROM servico WHERE id_servico = ?";
            $stmt_srv = $conn->prepare($sql_srv);
            $stmt_srv->bind_param('i', $id_servico);
            $stmt_srv->execute();
            $nome_servico = $stmt_srv->get_result()->fetch_assoc()['nome'] ?? 'Serviço';

            // Busca o nome do barbeiro para o email
            $sql_barb  = "SELECT u.nome FROM barbeiro b JOIN usuario u ON b.id_usuario = u.id_usuario WHERE b.id_barbeiro = ?";
            $stmt_barb = $conn->prepare($sql_barb);
            $stmt_barb->bind_param('i', $id_barbeiro);
            $stmt_barb->execute();
            $nome_barbeiro_email = $stmt_barb->get_result()->fetch_assoc()['nome'] ?? '';

            // Cancela cada agendamento encontrado e envia email ao cliente
            while ($ag = $agendamentos_cancelar->fetch_assoc()) {
                $sql4  = "UPDATE atendimento SET status = 'cancelado' WHERE id_atendimento = ?";
                $stmt4 = $conn->prepare($sql4);
                $stmt4->bind_param('i', $ag['id_atendimento']);
                $stmt4->execute();

                // Busca dados do cliente para o email
                $sql5  = "SELECT u.email, u.nome
                          FROM atendimento a
                          JOIN cliente c ON a.id_cliente = c.id_cliente
                          JOIN usuario  u ON c.id_usuario = u.id_usuario
                          WHERE a.id_atendimento = ?";
                $stmt5 = $conn->prepare($sql5);
                $stmt5->bind_param('i', $ag['id_atendimento']);
                $stmt5->execute();
                $dados_cliente = $stmt5->get_result()->fetch_assoc();

                if ($dados_cliente) {
                    email_cancelamento(
                        $dados_cliente['email'],
                        $dados_cliente['nome'],
                        $ag['data_hora'] ?? '',
                        $nome_barbeiro_email,
                        $nome_servico
                    );
                }
            }
        }
    }

    $mensagem = 'Serviços atualizados com sucesso.';
}

// -------------------------------------------------------
// BUSCA TODOS OS SERVIÇOS COM O STATUS DE CADA UM
// -------------------------------------------------------
$sql = "SELECT s.id_servico, s.nome, s.preco, s.duracao,
               COALESCE(bs.ativo, 1) AS ativo
        FROM servico s
        LEFT JOIN barbeiro_servico bs ON s.id_servico = bs.id_servico AND bs.id_barbeiro = ?
        ORDER BY s.nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_barbeiro);
$stmt->execute();
$servicos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Serviços do Barbeiro</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">
            Serviços de <?php echo htmlspecialchars($barbeiro['nome']); ?>
        </h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <p style="color: #666; margin-bottom: 24px;">
            Marque os serviços que este barbeiro pode realizar.
            Ao desmarcar um serviço, os agendamentos futuros desse serviço com este barbeiro serão cancelados automaticamente.
        </p>

        <form action="barbeiro_servicos.php?id=<?php echo $id_barbeiro; ?>" method="POST">

            <div class="container-tabela" style="margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Ativo</th>
                            <th>Serviço</th>
                            <th>Preço</th>
                            <th>Duração</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($servicos->num_rows > 0): ?>
                            <?php while ($s = $servicos->fetch_assoc()): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <!-- Checkbox para ativar/desativar o serviço -->
                                    <input type="checkbox"
                                           name="servico_<?php echo $s['id_servico']; ?>"
                                           <?php echo $s['ativo'] ? 'checked' : ''; ?>
                                           style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <td><?php echo htmlspecialchars($s['nome']); ?></td>
                                <td>R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?></td>
                                <td><?php echo $s['duracao']; ?> min</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #aaa; padding: 30px;">
                                    Nenhum serviço cadastrado ainda.
                                    <a href="servicos.php">Cadastrar serviços</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primario" style="width: auto; padding: 10px 24px;">
                    Salvar Alterações
                </button>
                <a href="barbeiros.php" class="btn-secundario">Voltar</a>
            </div>

        </form>

    </div>

</body>
</html>
