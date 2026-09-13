<?php
// =====================================================
// BARBERTECH - Novo Agendamento (Cliente)
// Fluxo em 3 passos:
//   Passo 1 → Escolher serviço(s)
//   Passo 2 → Escolher barbeiro
//   Passo 3 → Escolher data e horário (combinados) e confirmar
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('cliente');

require_once '../conexao.php';
require_once '../includes/horarios_disponiveis.php';

$id_cliente = $_SESSION['id_cliente'];
$passo      = (int) ($_GET['passo'] ?? 1);
$mensagem   = '';
$erro       = '';

// -------------------------------------------------------
// SALVAR O AGENDAMENTO NO BANCO (enviado via POST do passo 3)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {

    $id_barbeiro   = (int) $_POST['id_barbeiro'];
    $data          = $_POST['data'];
    $horario       = $_POST['horario'];
    $servicos_ids  = json_decode($_POST['servicos_json'], true);

    $data_hora = $data . ' ' . $horario . ':00';

    $placeholders = implode(',', array_fill(0, count($servicos_ids), '?'));
    $tipos        = str_repeat('i', count($servicos_ids));
    $sql  = "SELECT SUM(preco) AS total FROM servico WHERE id_servico IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$servicos_ids);
    $stmt->execute();
    $valor_total = $stmt->get_result()->fetch_assoc()['total'];

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

    header('Location: dashboard.php?sucesso=agendamento');
    exit();
}

// -------------------------------------------------------
// PASSO 1: LISTA OS SERVIÇOS DISPONÍVEIS
// -------------------------------------------------------
if ($passo == 1) {
    $servicos = $conn->query("SELECT DISTINCT s.id_servico, s.nome, s.preco, s.duracao
                              FROM servico s
                              JOIN barbeiro_servico bs ON s.id_servico = bs.id_servico
                              WHERE bs.ativo = 1
                              ORDER BY s.nome ASC");
}

// -------------------------------------------------------
// PASSO 2: LISTA OS BARBEIROS QUE FAZEM OS SERVIÇOS
// -------------------------------------------------------
if ($passo == 2) {
    $raw_srv = $_GET['servicos'] ?? '';
    $servicos_ids = is_array($raw_srv)
        ? array_map('intval', $raw_srv)
        : array_map('intval', explode(',', $raw_srv));

    if (empty($servicos_ids) || $servicos_ids[0] == 0) {
        header('Location: novo_agendamento.php?passo=1&erro=Selecione pelo menos um serviço.');
        exit();
    }

    $total_servicos = count($servicos_ids);
    $placeholders   = implode(',', array_fill(0, $total_servicos, '?'));
    $tipos          = str_repeat('i', $total_servicos);

    $sql  = "SELECT b.id_barbeiro, u.nome
             FROM barbeiro b
             JOIN usuario u ON b.id_usuario = u.id_usuario
             JOIN barbeiro_servico bs ON b.id_barbeiro = bs.id_barbeiro
             WHERE bs.id_servico IN ($placeholders) AND bs.ativo = 1
             GROUP BY b.id_barbeiro
             HAVING COUNT(DISTINCT bs.id_servico) = ?
             ORDER BY u.nome ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos . 'i', ...[...$servicos_ids, $total_servicos]);
    $stmt->execute();
    $barbeiros = $stmt->get_result();

    $sql2  = "SELECT SUM(duracao) AS total, SUM(preco) AS valor, GROUP_CONCAT(nome SEPARATOR ', ') AS nomes FROM servico WHERE id_servico IN ($placeholders)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param($tipos, ...$servicos_ids);
    $stmt2->execute();
    $info_servicos = $stmt2->get_result()->fetch_assoc();
}

// -------------------------------------------------------
// PASSO 3: DATA + HORÁRIO (combinados)
// -------------------------------------------------------
if ($passo == 3) {
    $raw_srv = $_GET['servicos'] ?? '';
    $servicos_ids = is_array($raw_srv)
        ? array_map('intval', $raw_srv)
        : array_map('intval', explode(',', $raw_srv));
    $id_barbeiro  = (int) ($_GET['barbeiro'] ?? 0);
    $data         = $_GET['data'] ?? '';

    // Datas disponíveis para escolha
    $datas_disponiveis = get_datas_disponiveis($conn);

    // Nome do barbeiro
    $sql  = "SELECT u.nome FROM barbeiro b JOIN usuario u ON b.id_usuario = u.id_usuario WHERE b.id_barbeiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_barbeiro);
    $stmt->execute();
    $barbeiro_nome = $stmt->get_result()->fetch_assoc()['nome'] ?? '';

    $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // Se uma data já foi selecionada, carrega os horários e info dos serviços
    $horarios      = [];
    $info_servicos = null;
    $duracao_total = 0;

    if ($data) {
        $placeholders = implode(',', array_fill(0, count($servicos_ids), '?'));
        $tipos        = str_repeat('i', count($servicos_ids));
        $sql  = "SELECT SUM(duracao) AS total, SUM(preco) AS valor, GROUP_CONCAT(nome SEPARATOR ', ') AS nomes FROM servico WHERE id_servico IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($tipos, ...$servicos_ids);
        $stmt->execute();
        $info_servicos = $stmt->get_result()->fetch_assoc();
        $duracao_total = (int) $info_servicos['total'];

        $horarios = get_horarios_disponiveis($conn, $id_barbeiro, $data, $duracao_total);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Novo Agendamento</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        .passos {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .passo-item {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            background: #e0e0e0;
            color: #666;
        }
        .passo-item.ativo {
            background: #333;
            color: #fff;
        }

        /* Serviços */
        .grid-servicos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .card-servico {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            user-select: none;
        }
        .card-servico:hover {
            border-color: #999;
        }
        .card-servico.selecionado {
            border-color: #333;
            background: #333;
            color: #fff;
        }
        .card-servico .nome-servico {
            font-weight: bold;
            font-size: 14px;
        }
        .card-servico .preco-servico {
            font-size: 13px;
            margin-top: 6px;
            color: #555;
        }
        .card-servico.selecionado .preco-servico {
            color: #ccc;
        }

        /* Barbeiros */
        .grid-barbeiros {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .card-barbeiro {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: border-color 0.2s, background 0.2s;
            display: block;
        }
        .card-barbeiro:hover {
            border-color: #333;
            background: #f9f9f9;
        }
        .card-barbeiro .icone-barbeiro {
            font-size: 32px;
            margin-bottom: 8px;
        }

        /* Datas */
        .grid-datas {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .card-data {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 18px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: border-color 0.2s, background 0.2s;
            min-width: 72px;
        }
        .card-data:hover {
            border-color: #333;
        }
        .card-data.selecionada {
            border-color: #333;
            background: #333;
            color: #fff;
        }
        .card-data.selecionada .dia-nome,
        .card-data.selecionada .mes-nome {
            color: #ccc;
        }
        .card-data .dia-nome {
            font-size: 11px;
            color: #888;
        }
        .card-data .dia-num {
            font-size: 22px;
            font-weight: bold;
        }
        .card-data .mes-nome {
            font-size: 11px;
            color: #888;
        }

        /* Horários */
        .secao-horarios {
            border-top: 1px solid #e0e0e0;
            padding-top: 24px;
            margin-top: 4px;
        }
        .grid-horarios {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .btn-horario {
            border: 2px solid #333;
            border-radius: 6px;
            padding: 10px 20px;
            background: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-family: 'Inter', Arial, sans-serif;
        }
        .btn-horario:hover, .btn-horario.selecionado {
            background: #333;
            color: #fff;
        }

        /* Resumo */
        .resumo-box {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .resumo-box p {
            margin-bottom: 6px;
            font-size: 14px;
            color: #555;
        }
        .resumo-box strong {
            color: #222;
        }
    </style>
</head>
<body>

    <?php require_once '../includes/menu_cliente.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Novo Agendamento</h2>

        <!-- Indicador de passos -->
        <div class="passos">
            <span class="passo-item <?php echo $passo == 1 ? 'ativo' : ''; ?>">1. Serviço</span>
            <span class="passo-item <?php echo $passo == 2 ? 'ativo' : ''; ?>">2. Barbeiro</span>
            <span class="passo-item <?php echo $passo == 3 ? 'ativo' : ''; ?>">3. Data e Horário</span>
        </div>

        <?php if (isset($_GET['erro'])): ?>
            <div class="mensagem-erro" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($_GET['erro']); ?>
            </div>
        <?php endif; ?>


        <?php /* ==================== PASSO 1: SERVIÇOS ==================== */ ?>
        <?php if ($passo == 1): ?>

            <p style="color: #666; margin-bottom: 20px;">Selecione um ou mais serviços:</p>

            <div class="grid-servicos">
                <?php if ($servicos->num_rows > 0): ?>
                    <?php while ($s = $servicos->fetch_assoc()): ?>
                    <div class="card-servico" data-id="<?php echo $s['id_servico']; ?>"
                         onclick="toggleServico(this)">
                        <div class="nome-servico"><?php echo htmlspecialchars($s['nome']); ?></div>
                        <div class="preco-servico">
                            R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?>
                            · <?php echo $s['duracao']; ?> min
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #aaa;">Nenhum serviço disponível no momento.</p>
                <?php endif; ?>
            </div>

            <div id="aviso-servico" style="display:none; color:#a00; font-size:13px; margin-bottom:12px;">
                Selecione pelo menos um serviço para continuar.
            </div>

            <button type="button" class="btn-primario" style="width: auto; padding: 10px 28px;"
                    onclick="continuar()">
                Continuar →
            </button>

            <script>
                function toggleServico(card) {
                    card.classList.toggle('selecionado');
                    document.getElementById('aviso-servico').style.display = 'none';
                }

                function continuar() {
                    var selecionados = document.querySelectorAll('.card-servico.selecionado');
                    if (selecionados.length === 0) {
                        document.getElementById('aviso-servico').style.display = 'block';
                        return;
                    }
                    var ids = Array.from(selecionados).map(c => c.dataset.id).join(',');
                    window.location.href = 'novo_agendamento.php?passo=2&servicos=' + ids;
                }
            </script>


        <?php /* ==================== PASSO 2: BARBEIRO ==================== */ ?>
        <?php elseif ($passo == 2): ?>

            <div class="resumo-box">
                <p><strong>Serviços:</strong> <?php echo htmlspecialchars($info_servicos['nomes']); ?></p>
                <p><strong>Duração total:</strong> <?php echo $info_servicos['total']; ?> minutos</p>
                <p><strong>Valor total:</strong> R$ <?php echo number_format($info_servicos['valor'], 2, ',', '.'); ?></p>
            </div>

            <p style="color: #666; margin-bottom: 20px;">Escolha o barbeiro:</p>

            <div class="grid-barbeiros">
                <?php $servicos_str = implode(',', $servicos_ids); ?>
                <?php if ($barbeiros->num_rows > 0): ?>
                    <?php while ($b = $barbeiros->fetch_assoc()): ?>
                    <a href="novo_agendamento.php?passo=3&servicos=<?php echo $servicos_str; ?>&barbeiro=<?php echo $b['id_barbeiro']; ?>"
                       class="card-barbeiro">
                        <?php echo htmlspecialchars($b['nome']); ?>
                    </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #aaa;">Nenhum barbeiro disponível para os serviços selecionados.</p>
                <?php endif; ?>
            </div>

            <a href="novo_agendamento.php?passo=1" class="btn-secundario">← Voltar</a>


        <?php /* ==================== PASSO 3: DATA + HORÁRIO ==================== */ ?>
        <?php elseif ($passo == 3): ?>

            <?php $servicos_str = implode(',', $servicos_ids); ?>

            <div class="resumo-box">
                <p><strong>Barbeiro:</strong> <?php echo htmlspecialchars($barbeiro_nome); ?></p>
                <?php if ($info_servicos): ?>
                <p><strong>Serviços:</strong> <?php echo htmlspecialchars($info_servicos['nomes']); ?></p>
                <p><strong>Duração:</strong> <?php echo $duracao_total; ?> minutos</p>
                <p><strong>Valor total:</strong> R$ <?php echo number_format($info_servicos['valor'], 2, ',', '.'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Seleção de data -->
            <p style="color: #666; margin-bottom: 16px; font-weight: 600;">Escolha a data:</p>

            <div class="grid-datas">
                <?php if (!empty($datas_disponiveis)): ?>
                    <?php foreach ($datas_disponiveis as $d): ?>
                    <?php
                        $ts      = strtotime($d);
                        $dia_idx = (int) date('w', $ts);
                        $dia_num = date('d', $ts);
                        $mes_idx = (int) date('n', $ts);
                        $ativa   = ($d === $data) ? 'selecionada' : '';
                    ?>
                    <a href="novo_agendamento.php?passo=3&servicos=<?php echo $servicos_str; ?>&barbeiro=<?php echo $id_barbeiro; ?>&data=<?php echo $d; ?>"
                       class="card-data <?php echo $ativa; ?>">
                        <div class="dia-nome"><?php echo $dias_semana[$dia_idx]; ?></div>
                        <div class="dia-num"><?php echo $dia_num; ?></div>
                        <div class="mes-nome"><?php echo $meses[$mes_idx]; ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #aaa;">Nenhuma data disponível esta semana.</p>
                <?php endif; ?>
            </div>

            <!-- Horários aparecem após escolher a data -->
            <?php if ($data): ?>
            <div class="secao-horarios">

                <p style="color: #666; margin-bottom: 16px; font-weight: 600;">
                    Escolha o horário para <?php echo date('d/m/Y', strtotime($data)); ?>:
                </p>

                <?php if (!empty($horarios)): ?>

                    <form action="novo_agendamento.php" method="POST" id="form-confirmar">
                        <input type="hidden" name="confirmar"     value="1">
                        <input type="hidden" name="id_barbeiro"   value="<?php echo $id_barbeiro; ?>">
                        <input type="hidden" name="data"          value="<?php echo $data; ?>">
                        <input type="hidden" name="servicos_json" value="<?php echo htmlspecialchars(json_encode($servicos_ids)); ?>">
                        <input type="hidden" name="horario"       id="horario-selecionado" value="">

                        <div class="grid-horarios">
                            <?php foreach ($horarios as $h): ?>
                            <button type="button" class="btn-horario"
                                    onclick="selecionarHorario('<?php echo $h; ?>', this)">
                                <?php echo $h; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div id="area-confirmar" style="display: none; margin-top: 8px;">
                            <p style="margin-bottom: 14px; font-size: 15px;">
                                Confirmar agendamento para as <strong id="txt-horario"></strong>?
                            </p>
                            <button type="submit" class="btn-primario" style="width: auto; padding: 10px 28px;">
                                Confirmar Agendamento
                            </button>
                        </div>
                    </form>

                    <script>
                        function selecionarHorario(horario, botao) {
                            document.querySelectorAll('.btn-horario').forEach(b => b.classList.remove('selecionado'));
                            botao.classList.add('selecionado');
                            document.getElementById('horario-selecionado').value = horario;
                            document.getElementById('area-confirmar').style.display = 'block';
                            document.getElementById('txt-horario').textContent = horario;
                        }
                    </script>

                <?php else: ?>
                    <div class="mensagem-erro">
                        Não há horários disponíveis para esta data. Escolha outra data acima.
                    </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <div style="margin-top: 24px;">
                <a href="novo_agendamento.php?passo=2&servicos=<?php echo $servicos_str; ?>" class="btn-secundario">← Voltar</a>
            </div>

        <?php endif; ?>

    </div>

</body>
</html>
