<?php
// =====================================================
// BARBERTECH - Cadastro de Cliente
// Página pública onde novos clientes criam sua conta.
// =====================================================

session_start();

// Se já está logado, redireciona para o painel
if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$erro   = '';
$sucesso = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    require_once 'conexao.php';

    // Pega os dados do formulário e remove espaços extras
    $nome     = trim($_POST['nome']);
    $email    = trim($_POST['email']);
    $senha    = trim($_POST['senha']);
    $confirma = trim($_POST['confirma_senha']);
    $telefone = trim($_POST['telefone']);

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($telefone)) {
        $erro = 'Preencha todos os campos obrigatórios.';

    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';

    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';

    } else {
        // Verifica se o email já está cadastrado
        $sql = "SELECT id_usuario FROM usuario WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $erro = 'Este email já está cadastrado.';
        } else {
            // Gera o hash seguro da senha (nunca salvar senha pura no banco)
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o usuário na tabela "usuario"
            $sql2 = "INSERT INTO usuario (nome, email, senha, telefone, tipo) VALUES (?, ?, ?, ?, 'cliente')";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('ssss', $nome, $email, $senha_hash, $telefone);
            $stmt2->execute();

            // Pega o ID do usuário recém-criado
            $id_usuario = $conn->insert_id;

            // Insere também na tabela "cliente" vinculando ao usuário
            $sql3 = "INSERT INTO cliente (id_usuario) VALUES (?)";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param('i', $id_usuario);
            $stmt3->execute();

            $sucesso = 'Cadastro realizado com sucesso! Agora você pode fazer login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Cadastro</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="pagina-login">

    <div class="container-login">

        <div class="logo-login">
            <img src="IMAGENS/tesoura.png" alt="BarberTech" class="icone-logo">
            <h1>Criar conta</h1>
            <p>Preencha seus dados para começar</p>
        </div>

        <!-- Formulário de cadastro -->
        <form action="cadastro.php" method="POST" class="form-login">

            <!-- Exibe mensagens de erro ou sucesso -->
            <?php if ($erro): ?>
                <div class="mensagem-erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="mensagem-sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>

            <div class="campo-form">
                <label for="nome">Nome completo *</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
            </div>

            <div class="campo-form">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="campo-form">
                <label for="telefone">Telefone *</label>
                <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" required>
            </div>

            <div class="campo-form">
                <label for="senha">Senha * (mínimo 6 caracteres)</label>
                <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
            </div>

            <div class="campo-form">
                <label for="confirma_senha">Confirmar senha *</label>
                <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required>
            </div>

            <button type="submit" class="btn-primario">Criar conta</button>

        </form>

        <!-- Link para voltar ao login -->
        <div class="link-cadastro">
            <p>Já tem conta? <a href="index.php">Faça login aqui</a></p>
        </div>

    </div>

    <!-- Validação com mensagens bonitas (substitui o balão padrão do navegador) -->
    <script src="JS/validacao.js"></script>

</body>
</html>
