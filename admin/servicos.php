<?php
// =====================================================
// BARBERTECH - Gerenciar Serviços (Admin)
// Lista, cadastra, edita e exclui serviços.
// Quando um novo serviço é criado, é vinculado
// automaticamente a todos os barbeiros.
// =====================================================

require_once '../includes/autenticacao.php';
verificar_acesso('admin');

require_once '../conexao.php';

$mensagem = '';
$erro     = '';
$acao     = $_GET['acao'] ?? 'listar';

// -------------------------------------------------------
// AÇÃO: EXCLUIR SERVIÇO
// -------------------------------------------------------
if ($acao == 'excluir' && isset($_GET['id'])) {
    $id_servico = (int) $_GET['id'];

    $sql  = "DELETE FROM servico WHERE id_servico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_servico);
    $stmt->execute();

    $mensagem = 'Serviço excluído com sucesso.';
    $acao = 'listar';
}

// -------------------------------------------------------
// AÇÃO: SALVAR (novo ou editar)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome      = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco     = (float) str_replace(',', '.', $_POST['preco']); // aceita vírgula ou ponto
    $duracao   = (int) $_POST['duracao'];
    $id_editar = (int) ($_POST['id_servico'] ?? 0);

    if (empty($nome) || $preco <= 0 || $duracao <= 0) {
        $erro = 'Preencha nome, preço e duração corretamente.';
        $acao = ($id_editar > 0) ? 'editar' : 'novo';
    } else {

        if ($id_editar > 0) {
            // ---- EDITAR ----
            $sql  = "UPDATE servico SET nome = ?, descricao = ?, preco = ?, duracao = ? WHERE id_servico = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdii', $nome, $descricao, $preco, $duracao, $id_editar);
            $stmt->execute();
            $mensagem = 'Serviço atualizado com sucesso.';

        } else {
            // ---- NOVO ----
            $sql  = "INSERT INTO servico (nome, descricao, preco, duracao) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdi', $nome, $descricao, $preco, $duracao);
            $stmt->execute();
            $id_servico_novo = $conn->insert_id;

            // Vincula o novo serviço a todos os barbeiros existentes (ativo = 1)
            $barbeiros = $conn->query("SELECT id_barbeiro FROM barbeiro");
            while ($b = $barbeiros->fetch_assoc()) {
                $sql2  = "INSERT INTO barbeiro_servico (id_barbeiro, id_servico, ativo) VALUES (?, ?, 1)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param('ii', $b['id_barbeiro'], $id_servico_novo);
                $stmt2->execute();
            }

            $mensagem = 'Serviço cadastrado e vinculado a todos os barbeiros.';
        }

        $acao = 'listar';
    }
}

// -------------------------------------------------------
// BUSCAR DADOS PARA EDIÇÃO
// -------------------------------------------------------
$servico_editar = null;
if ($acao == 'editar' && isset($_GET['id'])) {
    $id_servico = (int) $_GET['id'];
    $sql  = "SELECT * FROM servico WHERE id_servico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_servico);
    $stmt->execute();
    $servico_editar = $stmt->get_result()->fetch_assoc();
}

// -------------------------------------------------------
// LISTA TODOS OS SERVIÇOS
// -------------------------------------------------------
$servicos = $conn->query("SELECT * FROM servico ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Serviços</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>

    <?php require_once '../includes/menu_admin.php'; ?>

    <div class="conteudo-principal">

        <h2 class="titulo-pagina">Gerenciar Serviços</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem-sucesso" style="margin-bottom: 20px;"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="mensagem-erro" style="margin-bottom: 20px;"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             FORMULÁRIO: NOVO ou EDITAR SERVIÇO
             ----------------------------------------------- -->
        <?php if ($acao == 'novo' || $acao == 'editar'): ?>

        <div class="form-padrao" style="margin-bottom: 30px;">
            <h3 style="margin-bottom: 20px;">
                <?php echo ($acao == 'editar') ? 'Editar Serviço' : 'Novo Serviço'; ?>
            </h3>

            <form action="servicos.php" method="POST">

                <?php if ($acao == 'editar'): ?>
                    <input type="hidden" name="id_servico" value="<?php echo $servico_editar['id_servico']; ?>">
                <?php endif; ?>

                <div class="campo-form">
                    <label>Nome do serviço *</label>
                    <input type="text" name="nome" placeholder="Ex: Corte de cabelo"
                           value="<?php echo htmlspecialchars($servico_editar['nome'] ?? ''); ?>">
                </div>

                <div class="campo-form">
                    <label>Descrição</label>
                    <input type="text" name="descricao" placeholder="Descrição opcional"
                           value="<?php echo htmlspecialchars($servico_editar['descricao'] ?? ''); ?>">
                </div>

                <div class="linha-dupla">
                    <div class="campo-form">
                        <label>Preço (R$) *</label>
                        <input type="text" name="preco" placeholder="Ex: 35.00"
                               value="<?php echo $servico_editar ? number_format($servico_editar['preco'], 2, '.', '') : ''; ?>">
                    </div>

                    <div class="campo-form">
                        <label>Duração (minutos) *</label>
                        <input type="number" name="duracao" placeholder="Ex: 30" min="5"
                               value="<?php echo htmlspecialchars($servico_editar['duracao'] ?? ''); ?>">
                    </div>
                </div>

                <div class="acoes-form">
                    <button type="submit" class="btn-primario" style="width: auto; padding: 10px 24px;">
                        <?php echo ($acao == 'editar') ? 'Salvar Alterações' : 'Cadastrar Serviço'; ?>
                    </button>
                    <a href="servicos.php" class="btn-secundario">Cancelar</a>
                </div>

            </form>
        </div>

        <?php else: ?>
            <div style="margin-bottom: 20px;">
                <a href="servicos.php?acao=novo" class="btn-primario" style="width: auto; padding: 10px 20px; text-decoration: none;">
                    + Novo Serviço
                </a>
            </div>
        <?php endif; ?>

        <!-- -----------------------------------------------
             TABELA DE SERVIÇOS
             ----------------------------------------------- -->
        <div class="container-tabela">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Duração</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($servicos->num_rows > 0): ?>
                        <?php while ($s = $servicos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nome']); ?></td>
                            <td><?php echo htmlspecialchars($s['descricao'] ?? '—'); ?></td>
                            <td>R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?></td>
                            <td><?php echo $s['duracao']; ?> min</td>
                            <td style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="servicos.php?acao=editar&id=<?php echo $s['id_servico']; ?>"
                                   class="btn-secundario" style="font-size: 13px;">
                                    Editar
                                </a>
                                <a href="servicos.php?acao=excluir&id=<?php echo $s['id_servico']; ?>"
                                   class="btn-perigo"
                                   onclick="return pedirConfirmacao(this, 'Excluir este serviço?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #aaa; padding: 30px;">
                                Nenhum serviço cadastrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
