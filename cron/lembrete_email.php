<?php
// =====================================================
// BARBERTECH - Lembrete de Agendamento por Email
// Deve ser executado automaticamente às 8h todos os dias.
//
// COMO CONFIGURAR NO WINDOWS (Agendador de Tarefas):
//   1. Abra "Agendador de Tarefas" no Windows
//   2. Crie uma tarefa básica com gatilho diário às 08:00
//   3. Ação: iniciar programa
//      Programa: C:\xampp\php\php.exe
//      Argumentos: C:\xampp\htdocs\barbertech\cron\lembrete_email.php
//
// Para testar manualmente, execute no terminal:
//   C:\xampp\php\php.exe C:\xampp\htdocs\barbertech\cron\lembrete_email.php
// =====================================================

// Garante que só pode ser executado via linha de comando (não pelo navegador)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}

// Inclui a conexão com o banco e o helper de email
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/email.php';

$hoje = date('Y-m-d');
$enviados = 0;
$erros    = 0;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando envio de lembretes para $hoje...\n";

// Busca todos os agendamentos pendentes de HOJE com dados do cliente, barbeiro e serviços
$sql = "SELECT
            a.id_atendimento,
            a.data_hora,
            u_c.nome  AS nome_cliente,
            u_c.email AS email_cliente,
            u_b.nome  AS nome_barbeiro,
            GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
        FROM atendimento a
        JOIN cliente  c ON a.id_cliente  = c.id_cliente
        JOIN barbeiro b ON a.id_barbeiro = b.id_barbeiro
        JOIN usuario u_c ON c.id_usuario = u_c.id_usuario
        JOIN usuario u_b ON b.id_usuario = u_b.id_usuario
        LEFT JOIN atendimento_servico ats ON a.id_atendimento = ats.id_atendimento
        LEFT JOIN servico s ON ats.id_servico = s.id_servico
        WHERE DATE(a.data_hora) = ?
          AND a.status = 'pendente'
        GROUP BY a.id_atendimento
        ORDER BY a.data_hora ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hoje);
$stmt->execute();
$agendamentos = $stmt->get_result();

if ($agendamentos->num_rows == 0) {
    echo "Nenhum agendamento para hoje.\n";
    exit(0);
}

// Envia o email de lembrete para cada cliente
while ($a = $agendamentos->fetch_assoc()) {
    $ok = email_lembrete(
        $a['email_cliente'],
        $a['nome_cliente'],
        $a['data_hora'],
        $a['nome_barbeiro'],
        $a['servicos'] ?? '—'
    );

    if ($ok) {
        $enviados++;
        echo "  OK  → {$a['email_cliente']} ({$a['nome_cliente']}) - {$a['data_hora']}\n";
    } else {
        $erros++;
        echo "  ERRO → {$a['email_cliente']} ({$a['nome_cliente']})\n";
    }
}

echo "\nConcluído: $enviados enviados, $erros com erro.\n";
?>
