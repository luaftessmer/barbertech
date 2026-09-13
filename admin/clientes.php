<?php
// =====================================================
// BARBERTECH - Gerenciar Clientes (Admin)
// Lista, edita e exclui clientes.
// Também permite visualizar o histórico de cada cliente.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

$mensagem = '';
$erro     = '';
$acao     = $_GET['acao'] ?? 'listar';

// -------------------------------------------------------
// AÇÃO: EXCLUIR CLIENTE
// -------------------------------------------------------
if ($acao == 'excluir' && isset($_GET['id'])) {
    $id_cliente = (int) $_GET['id'];

    // Busca o id_usuario vinculado ao cliente
    $sql  = "SELECT id_usuario FROM cliente WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado) {
        $sql  = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $resultado['id_usuario']);
        $stmt->execute();
        $mensagem = 'Cliente excluído com sucesso.';
    }
    $acao = 'listar';
}

// -------------------------------------------------------
// AÇÃO: SALVAR EDIÇÃO DO CLIENTE
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cliente = (int) $_POST['id_cliente'];
    $nome       = trim($_POST['nome']);
    $email      = trim($_POST['email']);
    $telefone   = trim($_POST['telefone']);

    if (empty($nome) || empty($email) || empty($telefone)) {
        $erro = 'Preencha todos os campos.';
        $acao = 'editar';
    } else {
        $sql  = "UPDATE usuario u
                 JOIN cliente c ON c.id_usuario = u.id_usuario
                 SET u.nome = ?, u.email = ?, u.telefone = ?
                 WHERE c.id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $nome, $email, $telefone, $id_cliente);
        $stmt->execute();
        $mensagem = 'Cliente atualizado com sucesso.';
        $acao = 'listar';
    }
}

// -------------------------------------------------------
// BUSCAR DADOS PARA EDIÇÃO
// -------------------------------------------------------
$cliente_editar = null;
if ($acao == 'editar' && isset($_GET['id'])) {
    $id_cliente = (int) $_GET['id'];
    $sql  = "SELECT c.id_cliente, u.nome, u.email, u.telefone
             FROM cliente c JOIN usuario u ON c.id_usuario = u.id_usuario
             WHERE c.id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $cliente_editar = $stmt->get_result()->fetch_assoc();
}

// -------------------------------------------------------
// BUSCA COM FILTRO POR NOME
// -------------------------------------------------------
$busca    = trim($_GET['busca'] ?? '');
$like     = '%' . $busca . '%';

$sql = "SELECT c.id_cliente, u.nome, u.email, u.telefone
        FROM cliente c
        JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE u.nome LIKE ? OR u.email LIKE ?
        ORDER BY u.nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$clientes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Clientes</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Gerenciar Clientes</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="mensagem-erro" style="margin-bottom: 20px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO DE EDIÇÃO -->
        <?php if ($acao == 'editar' && $cliente_editar): ?>
        <div class="form-padrao" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">Editar Cliente</h3>
            <form action="clientes.php" method="POST">
                <input type="hidden" name="id_cliente" value="<?php echo $cliente_editar['id_cliente']; ?>">

                <div class="campo-form">
                    <label>Nome completo *</label>
                    <input type="text" name="nome" required
                           value="<?php echo htmlspecialchars($cliente_editar['nome']); ?>">
                </div>
                <div class="campo-form">
                    <label>Email *</label>
                    <input type="email" name="email" required
                           value="<?php echo htmlspecialchars($cliente_editar['email']); ?>">
                </div>
                <div class="campo-form">
                    <label>Telefone *</label>
                    <input type="text" name="telefone" required
                           value="<?php echo htmlspecialchars($cliente_editar['telefone']); ?>">
                </div>

                <div class="acoes-form">
                    <button type="submit" class="btn-primario" style="width: auto; padding: 10px 24px;">
                        Salvar Alterações
                    </button>
                    <a href="clientes.php" class="btn-secundario">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- BARRA DE BUSCA -->
        <form action="clientes.php" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="busca" placeholder="Buscar por nome ou email..."
                   value="<?php echo htmlspecialchars($busca); ?>"
                   style="padding: 9px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; width: 300px;">
            <button type="submit" class="btn-secundario">Buscar</button>
            <?php if ($busca): ?>
                <a href="clientes.php" class="btn-secundario">Limpar</a>
            <?php endif; ?>
        </form>

        <!-- TABELA DE CLIENTES -->
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes->num_rows > 0): ?>
                        <?php while ($c = $clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nome']); ?></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td><?php echo htmlspecialchars($c['telefone']); ?></td>
                            <td style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="historico_cliente.php?id=<?php echo $c['id_cliente']; ?>"
                                   class="btn-secundario" style="font-size: 13px;">
                                    Histórico
                                </a>
                                <a href="clientes.php?acao=editar&id=<?php echo $c['id_cliente']; ?>"
                                   class="btn-secundario" style="font-size: 13px;">
                                    Editar
                                </a>
                                <a href="clientes.php?acao=excluir&id=<?php echo $c['id_cliente']; ?>"
                                   class="btn-perigo"
                                   onclick="return pedirConfirmacao(this, 'Excluir este cliente?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum cliente encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
