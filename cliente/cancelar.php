<?php
// =====================================================
// BARBERTECH - Cancelar Agendamento (Cliente)
// O cliente cancela um dos próprios agendamentos.
// Valida que o agendamento pertence ao cliente logado.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('cliente');

require_once '../conexao.php';

$id_cliente    = $_SESSION['id_cliente'];
$id_atendimento = (int) ($_GET['id'] ?? 0);

if ($id_atendimento > 0) {

    // Verifica se o agendamento pertence ao cliente logado e está pendente
    // (segurança: cliente não pode cancelar agendamento de outro cliente)
    $sql  = "SELECT id_atendimento FROM atendimento
             WHERE id_atendimento = ? AND id_cliente = ? AND status = 'pendente'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id_atendimento, $id_cliente);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Cancela o agendamento (o horário volta a ficar disponível automaticamente)
        $sql2  = "UPDATE atendimento SET status = 'cancelado' WHERE id_atendimento = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param('i', $id_atendimento);
        $stmt2->execute();
    }
}

// Redireciona de volta ao dashboard
header('Location: dashboard.php');
exit();
?>
