<?php
// =====================================================
// BARBERTECH - Gerenciar Barbeiros (Admin)
// Lista, cadastra, edita e exclui barbeiros.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

$mensagem = '';
$erro     = '';
$acao     = $_GET['acao'] ?? 'listar'; // ação padrão é listar

// -------------------------------------------------------
// AÇÃO: EXCLUIR BARBEIRO
// -------------------------------------------------------
if ($acao == 'excluir' && isset($_GET['id'])) {
    $id_barbeiro = (int) $_GET['id'];

    // Busca o id_usuario vinculado ao barbeiro para excluir o usuário também
    $sql  = "SELECT id_usuario FROM barbeiro WHERE id_barbeiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_barbeiro);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado) {
        $id_usuario = $resultado['id_usuario'];

        // Remove os serviços do atendimento vinculados a este barbeiro
        $sql  = "DELETE ats FROM atendimento_servico ats
                 INNER JOIN atendimento a ON ats.id_atendimento = a.id_atendimento
                 WHERE a.id_barbeiro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_barbeiro);
        $stmt->execute();

        // Remove os atendimentos do barbeiro
        $sql  = "DELETE FROM atendimento WHERE id_barbeiro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_barbeiro);
        $stmt->execute();

        // Excluir o usuário apaga o barbeiro e barbeiro_servico automaticamente (ON DELETE CASCADE)
        $sql  = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_usuario);
        $stmt->execute();

        $mensagem = 'Barbeiro excluído com sucesso.';
    }
    $acao = 'listar';
}

// -------------------------------------------------------
// AÇÃO: SALVAR NOVO BARBEIRO ou EDITAR
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome     = trim($_POST['nome']);
    $email    = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $id_editar = (int) ($_POST['id_barbeiro'] ?? 0); // 0 = novo cadastro

    if (empty($nome) || empty($email) || empty($telefone)) {
        $erro = 'Preencha todos os campos obrigatórios.';
        $acao = ($id_editar > 0) ? 'editar' : 'novo';

    } else {

        if ($id_editar > 0) {
            // ---- EDITAR barbeiro existente ----
            // Atualiza os dados do usuário vinculado ao barbeiro
            $sql  = "UPDATE usuario u
                     JOIN barbeiro b ON b.id_usuario = u.id_usuario
                     SET u.nome = ?, u.email = ?, u.telefone = ?
                     WHERE b.id_barbeiro = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssi', $nome, $email, $telefone, $id_editar);
            $stmt->execute();
            $mensagem = 'Barbeiro atualizado com sucesso.';

        } else {
            // ---- NOVO barbeiro ----
            $senha = trim($_POST['senha']);

            if (empty($senha) || strlen($senha) < 6) {
                $erro = 'A senha deve ter pelo menos 6 caracteres.';
                $acao = 'novo';
            } else {
                // Verifica se o email já existe
                $sql  = "SELECT id_usuario FROM usuario WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $erro = 'Este email já está cadastrado.';
                    $acao = 'novo';
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                    // Insere na tabela usuario
                    $sql  = "INSERT INTO usuario (nome, email, senha, telefone, tipo) VALUES (?, ?, ?, ?, 'barbeiro')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssss', $nome, $email, $senha_hash, $telefone);
                    $stmt->execute();
                    $id_usuario = $conn->insert_id;

                    // Insere na tabela barbeiro
                    $sql  = "INSERT INTO barbeiro (id_usuario) VALUES (?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $id_usuario);
                    $stmt->execute();
                    $id_barbeiro_novo = $conn->insert_id;

                    // Vincula todos os serviços existentes ao novo barbeiro
                    $servicos = $conn->query("SELECT id_servico FROM servico");
                    while ($s = $servicos->fetch_assoc()) {
                        $sql2  = "INSERT INTO barbeiro_servico (id_barbeiro, id_servico, ativo) VALUES (?, ?, 1)";
                        $stmt2 = $conn->prepare($sql2);
                        $stmt2->bind_param('ii', $id_barbeiro_novo, $s['id_servico']);
                        $stmt2->execute();
                    }

                    $mensagem = 'Barbeiro cadastrado com sucesso.';
                }
            }
        }

        if (empty($erro)) {
            $acao = 'listar';
        }
    }
}

// -------------------------------------------------------
// AÇÃO: BUSCAR DADOS PARA EDIÇÃO
// -------------------------------------------------------
$barbeiro_editar = null;
if ($acao == 'editar' && isset($_GET['id'])) {
    $id_barbeiro = (int) $_GET['id'];
    $sql  = "SELECT b.id_barbeiro, u.nome, u.email, u.telefone
             FROM barbeiro b
             JOIN usuario u ON b.id_usuario = u.id_usuario
             WHERE b.id_barbeiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_barbeiro);
    $stmt->execute();
    $barbeiro_editar = $stmt->get_result()->fetch_assoc();
}

// -------------------------------------------------------
// LISTA TODOS OS BARBEIROS
// -------------------------------------------------------
$sql      = "SELECT b.id_barbeiro, u.nome, u.email, u.telefone
             FROM barbeiro b
             JOIN usuario u ON b.id_usuario = u.id_usuario
             ORDER BY u.nome ASC";
$barbeiros = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Barbeiros</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Gerenciar Barbeiros</h2>

        <!-- Mensagens de sucesso ou erro -->
        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="mensagem-erro" style="margin-bottom: 20px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             FORMULÁRIO: NOVO ou EDITAR BARBEIRO
             ----------------------------------------------- -->
        <?php if ($acao == 'novo' || $acao == 'editar'): ?>

        <div class="form-padrao" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">
                <?php echo ($acao == 'editar') ? 'Editar Barbeiro' : 'Novo Barbeiro'; ?>
            </h3>

            <form action="barbeiros.php" method="POST">

                <!-- Campo oculto com o ID quando estiver editando -->
                <?php if ($acao == 'editar'): ?>
                    <input type="hidden" name="id_barbeiro" value="<?php echo $barbeiro_editar['id_barbeiro']; ?>">
                <?php endif; ?>

                <div class="campo-form">
                    <label>Nome completo *</label>
                    <input type="text" name="nome" placeholder="Nome do barbeiro" required
                           value="<?php echo htmlspecialchars($barbeiro_editar['nome'] ?? ''); ?>">
                </div>

                <div class="campo-form">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="email@exemplo.com" required
                           value="<?php echo htmlspecialchars($barbeiro_editar['email'] ?? ''); ?>">
                </div>

                <div class="campo-form">
                    <label>Telefone *</label>
                    <input type="text" name="telefone" placeholder="(00) 00000-0000" required
                           value="<?php echo htmlspecialchars($barbeiro_editar['telefone'] ?? ''); ?>">
                </div>

                <!-- Senha só é necessária no cadastro, não na edição -->
                <?php if ($acao == 'novo'): ?>
                <div class="campo-form">
                    <label>Senha * (mínimo 6 caracteres)</label>
                    <input type="password" name="senha" placeholder="Senha de acesso" required>
                </div>
                <?php endif; ?>

                <div class="acoes-form">
                    <button type="submit" class="btn-primario" style="width: auto; padding: 10px 24px;">
                        <?php echo ($acao == 'editar') ? 'Salvar Alterações' : 'Cadastrar Barbeiro'; ?>
                    </button>
                    <a href="barbeiros.php" class="btn-secundario">Cancelar</a>
                </div>

            </form>
        </div>

        <?php else: ?>
            <!-- Botão para abrir formulário de novo barbeiro -->
            <div style="margin-bottom: 20px;">
                <a href="barbeiros.php?acao=novo" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none;">
                    + Novo Barbeiro
                </a>
            </div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             TABELA DE BARBEIROS
             ----------------------------------------------- -->
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Serviços</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($barbeiros->num_rows > 0): ?>
                        <?php while ($b = $barbeiros->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['nome']); ?></td>
                            <td><?php echo htmlspecialchars($b['email']); ?></td>
                            <td><?php echo htmlspecialchars($b['telefone']); ?></td>
                            <td>
                                <!-- Link para gerenciar quais serviços o barbeiro faz -->
                                <a href="barbeiro_servicos.php?id=<?php echo $b['id_barbeiro']; ?>"
                                   style="color: #333; font-size: 13px;">
                                    Gerenciar serviços
                                </a>
                            </td>
                            <td style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="barbeiros.php?acao=editar&id=<?php echo $b['id_barbeiro']; ?>"
                                   class="btn-secundario" style="font-size: 13px;">
                                    Editar
                                </a>
                                <a href="barbeiros.php?acao=excluir&id=<?php echo $b['id_barbeiro']; ?>"
                                   class="btn-perigo"
                                   onclick="return pedirConfirmacao(this, 'Excluir este barbeiro?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum barbeiro cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
