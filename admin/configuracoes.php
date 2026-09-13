<?php
// =====================================================
// BARBERTECH - Configurações de Funcionamento (Admin)
// O admin define os horários de abertura, fechamento
// e intervalo de almoço para cada dia da semana.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

$mensagem = '';

// Nomes dos dias da semana para exibição
$nomes_dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

// -------------------------------------------------------
// SALVAR CONFIGURAÇÕES
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Percorre os 7 dias da semana (0 a 6)
    for ($dia = 0; $dia <= 6; $dia++) {

        // Verifica se o dia está marcado como aberto
        $aberto          = isset($_POST['aberto_' . $dia]) ? 1 : 0;
        $hora_inicio     = $_POST['hora_inicio_'    . $dia] ?? '09:00';
        $hora_fim        = $_POST['hora_fim_'        . $dia] ?? '18:00';
        $intervalo_ini   = $_POST['intervalo_ini_'   . $dia] ?? null;
        $intervalo_fim   = $_POST['intervalo_fim_'   . $dia] ?? null;

        // Trata campos vazios como NULL no banco
        $intervalo_ini = ($intervalo_ini === '') ? null : $intervalo_ini;
        $intervalo_fim = ($intervalo_fim === '') ? null : $intervalo_fim;

        // Verifica se já existe um registro para esse dia
        $sql  = "SELECT id_horario FROM horario_funcionamento WHERE dia_semana = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $dia);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();

        if ($existe) {
            // Atualiza o registro existente
            $sql  = "UPDATE horario_funcionamento
                     SET aberto = ?, hora_inicio = ?, hora_fim = ?,
                         intervalo_inicio = ?, intervalo_fim = ?
                     WHERE dia_semana = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $aberto, $hora_inicio, $hora_fim, $intervalo_ini, $intervalo_fim, $dia);
            $stmt->execute();
        } else {
            // Insere novo registro (caso não exista)
            $sql  = "INSERT INTO horario_funcionamento (dia_semana, hora_inicio, hora_fim, intervalo_inicio, intervalo_fim, aberto)
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $dia, $hora_inicio, $hora_fim, $intervalo_ini, $intervalo_fim, $aberto);
            $stmt->execute();
        }
    }

    $mensagem = 'Configurações salvas com sucesso.';
}

// -------------------------------------------------------
// BUSCA AS CONFIGURAÇÕES ATUAIS
// -------------------------------------------------------
$sql       = "SELECT * FROM horario_funcionamento ORDER BY dia_semana ASC";
$resultado = $conn->query($sql);

// Organiza em um array indexado pelo dia da semana
$horarios = [];
while ($h = $resultado->fetch_assoc()) {
    $horarios[$h['dia_semana']] = $h;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Configurações</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        /* Estilo da tabela de configurações */
        .tabela-config {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .tabela-config th {
            background: #f0f0f0;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            color: #444;
        }
        .tabela-config td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .tabela-config input[type="time"] {
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
        }
        /* Quando o dia está fechado, os campos de hora ficam esmaecidos */
        .linha-fechada td:nth-child(n+3) {
            opacity: 0.35;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Configurações de Funcionamento</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <p style="color:#666; margin-bottom: 24px; font-size: 14px;">
           

        <form action="configuracoes.php" method="POST">

            <div style="overflow-x: auto;">
            <table class="tabela-config">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Aberto</th>
                        <th>Abertura</th>
                        <th>Fechamento</th>
                        <th>Início do Almoço</th>
                        <th>Fim do Almoço</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($dia = 0; $dia <= 6; $dia++): ?>
                    <?php
                        // Pega os valores salvos ou usa padrões
                        $h        = $horarios[$dia] ?? [];
                        $aberto   = $h['aberto']           ?? 1;
                        $h_ini    = $h['hora_inicio']      ?? '09:00';
                        $h_fim    = $h['hora_fim']         ?? '18:00';
                        $al_ini   = $h['intervalo_inicio'] ?? '12:00';
                        $al_fim   = $h['intervalo_fim']    ?? '13:00';

                        // Formata para HH:MM (remove segundos se houver)
                        $h_ini  = substr($h_ini,  0, 5);
                        $h_fim  = substr($h_fim,  0, 5);
                        $al_ini = $al_ini ? substr($al_ini, 0, 5) : '';
                        $al_fim = $al_fim ? substr($al_fim, 0, 5) : '';
                    ?>
                    <tr id="linha_<?php echo $dia; ?>" class="<?php echo $aberto ? '' : 'linha-fechada'; ?>">

                        <td><strong><?php echo $nomes_dias[$dia]; ?></strong></td>

                        <td>
                            <!-- Checkbox de aberto/fechado — ao mudar, aplica/remove o estilo esmaecido -->
                            <input type="checkbox" name="aberto_<?php echo $dia; ?>"
                                   <?php echo $aberto ? 'checked' : ''; ?>
                                   onchange="toggleDia(<?php echo $dia; ?>, this.checked)"
                                   style="width:18px;height:18px;cursor:pointer;">
                        </td>

                        <td>
                            <input type="time" name="hora_inicio_<?php echo $dia; ?>"
                                   value="<?php echo $h_ini; ?>" required>
                        </td>

                        <td>
                            <input type="time" name="hora_fim_<?php echo $dia; ?>"
                                   value="<?php echo $h_fim; ?>" required>
                        </td>

                        <td>
                            <input type="time" name="intervalo_ini_<?php echo $dia; ?>"
                                   value="<?php echo $al_ini; ?>">
                        </td>

                        <td>
                            <input type="time" name="intervalo_fim_<?php echo $dia; ?>"
                                   value="<?php echo $al_fim; ?>">
                        </td>

                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-primario" style="width:auto;padding:10px 28px;">
                    Salvar Configurações
                </button>
            </div>

        </form>

    </div>

    <script>
    // Quando o checkbox de "Aberto" muda, aplica ou remove o estilo esmaecido da linha
    function toggleDia(dia, aberto) {
        var linha = document.getElementById('linha_' + dia);
        if (aberto) {
            linha.classList.remove('linha-fechada');
        } else {
            linha.classList.add('linha-fechada');
        }
    }
    </script>

</body>
</html>
