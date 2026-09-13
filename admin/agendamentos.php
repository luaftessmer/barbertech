<?php
// =====================================================
// BARBERTECH - Agendamentos (Admin)
// O admin visualiza todos os agendamentos, pode criar
// novos e cancelar qualquer um.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';
require_once '../includes/horarios_disponiveis.php';

$mensagem = '';
$erro     = '';
$acao     = $_GET['acao'] ?? 'listar';

// -------------------------------------------------------
// AÇÃO: CANCELAR AGENDAMENTO
// -------------------------------------------------------
if ($acao == 'cancelar' && isset($_GET['id'])) {
    $id_atendimento = (int) $_GET['id'];
    $sql  = "UPDATE atendimento SET status = 'cancelado' WHERE id_atendimento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_atendimento);
    $stmt->execute();
    $mensagem = 'Agendamento cancelado.';
    $acao = 'listar';
}

// -------------------------------------------------------
// AÇÃO: CONCLUIR AGENDAMENTO
// -------------------------------------------------------
if ($acao == 'concluir' && isset($_GET['id'])) {
    $id_atendimento = (int) $_GET['id'];
    $sql  = "UPDATE atendimento SET status = 'concluido' WHERE id_atendimento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_atendimento);
    $stmt->execute();
    $mensagem = 'Atendimento marcado como concluído.';
    $acao = 'listar';
}

// -------------------------------------------------------
// AÇÃO: SALVAR NOVO AGENDAMENTO (POST)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $acao == 'salvar') {
    $id_cliente  = (int) $_POST['id_cliente'];
    $id_barbeiro = (int) $_POST['id_barbeiro'];
    $data        = $_POST['data'];
    $horario     = $_POST['horario'];
    $servicos_ids = array_map('intval', $_POST['servicos'] ?? []);

    if (!$id_cliente || !$id_barbeiro || empty($data) || empty($horario) || empty($servicos_ids)) {
        $erro = 'Preencha todos os campos.';
        $acao = 'novo';
    } else {
        $data_hora = $data . ' ' . $horario . ':00';

        // Calcula valor total
        $placeholders = implode(',', array_fill(0, count($servicos_ids), '?'));
        $tipos        = str_repeat('i', count($servicos_ids));
        $sql  = "SELECT SUM(preco) AS total FROM servico WHERE id_servico IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($tipos, ...$servicos_ids);
        $stmt->execute();
        $valor_total = $stmt->get_result()->fetch_assoc()['total'];

        // Insere atendimento
        $sql  = "INSERT INTO atendimento (id_cliente, id_barbeiro, data_hora, status, valor_total) VALUES (?, ?, ?, 'pendente', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisd', $id_cliente, $id_barbeiro, $data_hora, $valor_total);
        $stmt->execute();
        $id_atendimento = $conn->insert_id;

        foreach ($servicos_ids as $id_servico) {
            $sql2  = "INSERT INTO atendimento_servico (id_atendimento, id_servico) VALUES (?, ?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('ii', $id_atendimento, $id_servico);
            $stmt2->execute();
        }

        $mensagem = 'Agendamento criado com sucesso.';
        $acao = 'listar';
    }
}

// -------------------------------------------------------
// DADOS PARA O FORMULÁRIO DE NOVO AGENDAMENTO
// -------------------------------------------------------
if ($acao == 'novo') {
    $clientes  = $conn->query("SELECT c.id_cliente, u.nome FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario ORDER BY u.nome ASC");
    $barbeiros = $conn->query("SELECT b.id_barbeiro, u.nome FROM barbeiro b JOIN usuario u ON b.id_usuario = u.id_usuario ORDER BY u.nome ASC");
    $servicos  = $conn->query("SELECT id_servico, nome, preco, duracao FROM servico ORDER BY nome ASC");
    $datas     = get_datas_disponiveis($conn);
}

// -------------------------------------------------------
// HORÁRIOS DISPONÍVEIS VIA AJAX (chamado pelo JavaScript)
// -------------------------------------------------------
if (isset($_GET['ajax_horarios'])) {
    $id_barbeiro   = (int) $_GET['id_barbeiro'];
    $data          = $_GET['data'];
    $duracao_total = (int) $_GET['duracao'];

    $horarios = get_horarios_disponiveis($conn, $id_barbeiro, $data, $duracao_total);

    // Retorna os horários em formato JSON para o JavaScript processar
    header('Content-Type: application/json');
    echo json_encode($horarios);
    exit();
}

// -------------------------------------------------------
// FILTROS DA LISTAGEM
// -------------------------------------------------------
$filtro_data     = $_GET['data']     ?? '';
$filtro_barbeiro = (int) ($_GET['barbeiro'] ?? 0);
$filtro_status   = $_GET['status']   ?? '';

// Monta a query com os filtros opcionais
$where  = ' WHERE 1=1';
$params = [];
$tipos  = '';

if ($filtro_data) {
    $where   .= ' AND DATE(a.data_hora) = ?';
    $params[] = $filtro_data;
    $tipos   .= 's';
}
if ($filtro_barbeiro) {
    $where   .= ' AND a.id_barbeiro = ?';
    $params[] = $filtro_barbeiro;
    $tipos   .= 'i';
}
if ($filtro_status) {
    $where   .= ' AND a.status = ?';
    $params[] = $filtro_status;
    $tipos   .= 's';
}

$sql = "SELECT
            a.id_atendimento, a.data_hora, a.status, a.valor_total,
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
        $where
        GROUP BY a.id_atendimento
        ORDER BY a.data_hora DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$atendimentos = $stmt->get_result();

// Lista de barbeiros para o filtro
$barbeiros_filtro = $conn->query("SELECT b.id_barbeiro, u.nome FROM barbeiro b JOIN usuario u ON b.id_usuario = u.id_usuario ORDER BY u.nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Agendamentos</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Agendamentos</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="mensagem-erro" style="margin-bottom: 20px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             FORMULÁRIO: NOVO AGENDAMENTO
             ----------------------------------------------- -->
        <?php if ($acao == 'novo'): ?>

        <div class="form-padrao" style="max-width: 700px; margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">Novo Agendamento</h3>

            <form action="agendamentos.php?acao=salvar" method="POST">

                <div class="linha-dupla">
                    <div class="campo-form">
                        <label>Cliente *</label>
                        <select name="id_cliente" required>
                            <option value="">Selecione o cliente</option>
                            <?php while ($c = $clientes->fetch_assoc()): ?>
                                <option value="<?php echo $c['id_cliente']; ?>">
                                    <?php echo htmlspecialchars($c['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="campo-form">
                        <label>Barbeiro *</label>
                        <select name="id_barbeiro" id="sel-barbeiro" required onchange="atualizarHorarios()">
                            <option value="">Selecione o barbeiro</option>
                            <?php while ($b = $barbeiros->fetch_assoc()): ?>
                                <option value="<?php echo $b['id_barbeiro']; ?>">
                                    <?php echo htmlspecialchars($b['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="campo-form">
                    <label>Serviços * (pode selecionar mais de um)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        <?php while ($s = $servicos->fetch_assoc()): ?>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="servicos[]"
                                   value="<?php echo $s['id_servico']; ?>"
                                   data-duracao="<?php echo $s['duracao']; ?>"
                                   onchange="atualizarHorarios()">
                            <?php echo htmlspecialchars($s['nome']); ?>
                            (<?php echo $s['duracao']; ?> min · R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?>)
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="linha-dupla">
                    <div class="campo-form">
                        <label>Data *</label>
                        <select name="data" id="sel-data" required onchange="atualizarHorarios()">
                            <option value="">Selecione a data</option>
                            <?php foreach ($datas as $d): ?>
                                <option value="<?php echo $d; ?>">
                                    <?php echo date('d/m/Y', strtotime($d)); ?>
                                    (<?php
                                        $dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                                        echo $dias[(int)date('w', strtotime($d))];
                                    ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo-form">
                        <label>Horário *</label>
                        <select name="horario" id="sel-horario" required>
                            <option value="">Selecione barbeiro, data e serviço primeiro</option>
                        </select>
                    </div>
                </div>

                <div class="acoes-form">
                    <button type="submit" class="btn-primario" style="width: auto; padding: 10px 24px;">
                        Criar Agendamento
                    </button>
                    <a href="agendamentos.php" class="btn-secundario">Cancelar</a>
                </div>

            </form>
        </div>

        <script>
        // Busca os horários disponíveis via AJAX quando barbeiro, data ou serviço mudam
        function atualizarHorarios() {
            var barbeiro = document.getElementById('sel-barbeiro').value;
            var data     = document.getElementById('sel-data').value;

            // Soma a duração de todos os serviços marcados
            var checkboxes = document.querySelectorAll('input[name="servicos[]"]:checked');
            var duracao = 0;
            checkboxes.forEach(function(c) {
                duracao += parseInt(c.dataset.duracao || 0);
            });

            var select = document.getElementById('sel-horario');

            if (!barbeiro || !data || duracao == 0) {
                select.innerHTML = '<option value="">Selecione barbeiro, data e serviço primeiro</option>';
                return;
            }

            select.innerHTML = '<option value="">Carregando...</option>';

            // Faz a requisição para o mesmo arquivo com parâmetros de AJAX
            var url = 'agendamentos.php?ajax_horarios=1&id_barbeiro=' + barbeiro + '&data=' + data + '&duracao=' + duracao;

            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(horarios) {
                    if (horarios.length === 0) {
                        select.innerHTML = '<option value="">Nenhum horário disponível</option>';
                    } else {
                        select.innerHTML = '<option value="">Selecione o horário</option>';
                        horarios.forEach(function(h) {
                            select.innerHTML += '<option value="' + h + '">' + h + '</option>';
                        });
                    }
                });
        }
        </script>

        <?php else: ?>
            <div style="margin-bottom: 20px;">
                <a href="agendamentos.php?acao=novo" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none;">
                    + Novo Agendamento
                </a>
            </div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             FILTROS
             ----------------------------------------------- -->
        <form action="agendamentos.php" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; align-items: flex-end;">
            <div>
                <label style="display: block; font-size: 13px; color: #555; margin-bottom: 4px;">Data</label>
                <input type="date" name="data" value="<?php echo $filtro_data; ?>"
                       style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; color: #555; margin-bottom: 4px;">Barbeiro</label>
                <select name="barbeiro" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px;">
                    <option value="">Todos</option>
                    <?php while ($b = $barbeiros_filtro->fetch_assoc()): ?>
                        <option value="<?php echo $b['id_barbeiro']; ?>" <?php echo $filtro_barbeiro == $b['id_barbeiro'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; color: #555; margin-bottom: 4px;">Status</label>
                <select name="status" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px;">
                    <option value="">Todos</option>
                    <option value="pendente"  <?php echo $filtro_status == 'pendente'  ? 'selected' : ''; ?>>Pendente</option>
                    <option value="concluido" <?php echo $filtro_status == 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="cancelado" <?php echo $filtro_status == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <button type="submit" class="btn-secundario">Filtrar</button>
            <a href="agendamentos.php" class="btn-secundario">Limpar</a>
        </form>

        <!-- -----------------------------------------------
             TABELA DE AGENDAMENTOS
             ----------------------------------------------- -->
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Data e Hora</th>
                        <th>Cliente</th>
                        <th>Barbeiro</th>
                        <th>Serviços</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
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
                            <td>
                                <span class="badge badge-<?php echo $a['status']; ?>">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </td>
                            <td style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?php if ($a['status'] == 'pendente'): ?>
                                    <a href="agendamentos.php?acao=concluir&id=<?php echo $a['id_atendimento']; ?>"
                                       class="btn-secundario" style="font-size: 12px;"
                                       onclick="return pedirConfirmacao(this, 'Marcar como concluído?')">
                                        Concluir
                                    </a>
                                    <a href="agendamentos.php?acao=cancelar&id=<?php echo $a['id_atendimento']; ?>"
                                       class="btn-perigo" style="font-size: 12px;"
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
                            <td colspan="7" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum agendamento encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
