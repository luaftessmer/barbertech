<?php
// =====================================================
// BARBERTECH - Cálculo de Horários Disponíveis
// Função reutilizada por cliente, barbeiro e admin
// para calcular quais horários estão livres.
// =====================================================

// -------------------------------------------------------
// Retorna um array com os horários livres de um barbeiro
// em uma data específica, dado o tempo total do serviço.
//
// Parâmetros:
//   $conn         - conexão com o banco
//   $id_barbeiro  - ID do barbeiro
//   $data         - data no formato 'Y-m-d'
//   $duracao_total - duração total dos serviços em minutos
//
// Retorna:
//   Array de strings no formato 'HH:MM', ex: ['09:00', '09:30', ...]
// -------------------------------------------------------
function get_horarios_disponiveis($conn, $id_barbeiro, $data, $duracao_total) {

    $horarios_livres = [];

    // Descobre o dia da semana da data (0=Domingo, 1=Segunda, ..., 6=Sábado)
    $dia_semana = (int) date('w', strtotime($data));

    // Busca o horário de funcionamento da barbearia nesse dia
    $sql  = "SELECT hora_inicio, hora_fim, intervalo_inicio, intervalo_fim, aberto
             FROM horario_funcionamento
             WHERE dia_semana = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $dia_semana);
    $stmt->execute();
    $horario = $stmt->get_result()->fetch_assoc();

    // Se a barbearia estiver fechada nesse dia, retorna vazio
    if (!$horario || $horario['aberto'] == 0) {
        return [];
    }

    // Converte os horários para minutos desde meia-noite (facilita a comparação)
    $abertura   = hora_para_minutos($horario['hora_inicio']);
    $fechamento = hora_para_minutos($horario['hora_fim']);

    // Intervalo de almoço (pode ser nulo se não tiver)
    $almoco_inicio = $horario['intervalo_inicio'] ? hora_para_minutos($horario['intervalo_inicio']) : null;
    $almoco_fim    = $horario['intervalo_fim']    ? hora_para_minutos($horario['intervalo_fim'])    : null;

    // Busca os agendamentos já marcados para esse barbeiro nessa data
    // Só considera os pendentes (cancelados liberam o horário)
    $sql  = "SELECT a.data_hora, SUM(s.duracao) AS duracao_total
             FROM atendimento a
             JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
             JOIN servico s ON ats.id_servico = s.id_servico
             WHERE a.id_barbeiro = ?
               AND DATE(a.data_hora) = ?
               AND a.status = 'pendente'
             GROUP BY a.id_atendimento";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id_barbeiro, $data);
    $stmt->execute();
    $agendamentos_existentes = $stmt->get_result();

    // Monta um array com os blocos ocupados: [inicio_em_minutos => fim_em_minutos]
    $blocos_ocupados = [];
    while ($ag = $agendamentos_existentes->fetch_assoc()) {
        $inicio_ag = hora_para_minutos(date('H:i', strtotime($ag['data_hora'])));
        $fim_ag    = $inicio_ag + (int) $ag['duracao_total'];
        $blocos_ocupados[] = ['inicio' => $inicio_ag, 'fim' => $fim_ag];
    }

    // Gera os slots a cada 30 minutos dentro do horário de funcionamento
    // O slot só é válido se o atendimento inteiro couber antes do fechamento
    $slot_atual = $abertura;

    while ($slot_atual + $duracao_total <= $fechamento) {
        $slot_fim = $slot_atual + $duracao_total;

        // Verifica se o slot cai dentro do intervalo de almoço
        $no_almoco = false;
        if ($almoco_inicio !== null && $almoco_fim !== null) {
            // Conflito se o slot começa antes do fim do almoço E termina depois do início do almoço
            if ($slot_atual < $almoco_fim && $slot_fim > $almoco_inicio) {
                $no_almoco = true;
            }
        }

        // Verifica se o slot conflita com algum agendamento existente
        $ocupado = false;
        foreach ($blocos_ocupados as $bloco) {
            // Há conflito se os períodos se sobrepõem
            if ($slot_atual < $bloco['fim'] && $slot_fim > $bloco['inicio']) {
                $ocupado = true;
                break;
            }
        }

        // Se não cai no almoço e não está ocupado, é um horário livre
        if (!$no_almoco && !$ocupado) {
            $horarios_livres[] = minutos_para_hora($slot_atual);
        }

        // Avança 30 minutos para o próximo slot
        $slot_atual += 30;
    }

    return $horarios_livres;
}

// -------------------------------------------------------
// Converte "HH:MM:SS" ou "HH:MM" para minutos desde meia-noite
// Exemplo: "09:30" → 570
// -------------------------------------------------------
function hora_para_minutos($hora) {
    $partes = explode(':', $hora);
    return (int)$partes[0] * 60 + (int)$partes[1];
}

// -------------------------------------------------------
// Converte minutos desde meia-noite para "HH:MM"
// Exemplo: 570 → "09:30"
// -------------------------------------------------------
function minutos_para_hora($minutos) {
    $h = intdiv($minutos, 60);  // parte inteira da divisão
    $m = $minutos % 60;          // resto da divisão
    return sprintf('%02d:%02d', $h, $m); // formata com zero à esquerda
}

// -------------------------------------------------------
// Retorna as datas disponíveis para agendamento.
// Regra: clientes podem agendar para a semana atual.
// A agenda abre todo domingo para a semana seguinte.
// Não mostra datas passadas.
// -------------------------------------------------------
function get_datas_disponiveis($conn) {
    $datas = [];
    $hoje  = time();

    // Descobre qual é o domingo da semana atual (início da semana)
    $dia_semana_hoje = (int) date('w'); // 0=Dom, 6=Sab
    $domingo_atual   = strtotime('-' . $dia_semana_hoje . ' days'); // domingo desta semana

    // A agenda mostra a semana atual (dom a sab)
    // Se hoje é domingo, mostra a próxima semana também
    $inicio = $domingo_atual;
    $fim    = strtotime('+6 days', $domingo_atual); // sábado desta semana

    // Percorre cada dia da semana
    for ($d = $inicio; $d <= $fim; $d = strtotime('+1 day', $d)) {
        // Não mostra dias que já passaram
        if ($d < strtotime('today')) {
            continue;
        }

        $dia_semana = (int) date('w', $d);

        // Verifica se a barbearia está aberta nesse dia
        $sql  = "SELECT aberto FROM horario_funcionamento WHERE dia_semana = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $dia_semana);
        $stmt->execute();
        $horario = $stmt->get_result()->fetch_assoc();

        if ($horario && $horario['aberto'] == 1) {
            $datas[] = date('Y-m-d', $d);
        }
    }

    return $datas;
}
?>
