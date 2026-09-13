<?php
// =====================================================
// BARBERTECH - Tela de Login
// Página inicial do sistema. Todos os usuários
// (admin, barbeiro e cliente) fazem login aqui.
// =====================================================

// Inicia a sessão para guardar informações do usuário logado
session_start();

// Se o usuário já está logado, redireciona para o painel correto
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['tipo'] == 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['tipo'] == 'barbeiro') {
        header('Location: barbeiro/dashboard.php');
    } else {
        header('Location: cliente/dashboard.php');
    }
    exit();
}

// Variável para guardar mensagem de erro
$erro = '';

// Verifica se o formulário foi enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Inclui a conexão com o banco de dados
    require_once 'conexao.php';

    // Pega os dados digitados pelo usuário e remove espaços extras
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Busca o usuário no banco de dados pelo email
    $sql = "SELECT id_usuario, nome, senha, tipo FROM usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);  // 's' significa string
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verifica se encontrou um usuário com esse email
    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();

        // Verifica se a senha digitada bate com o hash salvo no banco
        if (password_verify($senha, $usuario['senha'])) {

            // Login correto! Salva os dados na sessão
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome']       = $usuario['nome'];
            $_SESSION['tipo']       = $usuario['tipo'];

            // Se for barbeiro, também salva o id_barbeiro na sessão
            if ($usuario['tipo'] == 'barbeiro') {
                $sql2 = "SELECT id_barbeiro FROM barbeiro WHERE id_usuario = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param('i', $usuario['id_usuario']);
                $stmt2->execute();
                $res2 = $stmt2->get_result()->fetch_assoc();
                $_SESSION['id_barbeiro'] = $res2['id_barbeiro'];
            }

            // Se for cliente, também salva o id_cliente na sessão
            if ($usuario['tipo'] == 'cliente') {
                $sql3 = "SELECT id_cliente FROM cliente WHERE id_usuario = ?";
                $stmt3 = $conn->prepare($sql3);
                $stmt3->bind_param('i', $usuario['id_usuario']);
                $stmt3->execute();
                $res3 = $stmt3->get_result()->fetch_assoc();
                $_SESSION['id_cliente'] = $res3['id_cliente'];
            }

            // Redireciona para o painel correto conforme o tipo do usuário
            if ($usuario['tipo'] == 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($usuario['tipo'] == 'barbeiro') {
                header('Location: barbeiro/dashboard.php');
            } else {
                header('Location: cliente/dashboard.php');
            }
            exit();

        } else {
            // Senha incorreta
            $erro = 'Email ou senha incorretos.';
        }
    } else {
        // Email não encontrado
        $erro = 'Email ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberTech - Login</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="pagina-login">

    <!-- Painel esquerdo com texto decorativo (definido via CSS ::before) -->
    <!-- Conteúdo sobreposto ao painel esquerdo -->
    <div style="position: fixed; top: 0; left: 0; width: 45%; height: 100vh;
                display: flex; flex-direction: column; justify-content: center;
                align-items: center; padding: 48px; z-index: 1; text-align: center;">
        <div style="color: #fff;">
            <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.5px;">
            </h2>
            
            </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel direito: formulário -->
    <div class="container-login">

        <div class="logo-login">
            <img src="IMAGENS/tesoura.png" alt="BarberTech" class="icone-logo">
            <h1>Bem-vindo </h1>
            

        <form action="index.php" method="POST" class="form-login">

            <?php if ($erro): ?>
                <div class="mensagem-erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="campo-form">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="campo-form">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primario" style="margin-top: 4px;">Entrar</button>

        </form>

        <div class="link-cadastro">
            <p>Ainda não tem conta? <a href="cadastro.php">Cadastre-se </a></p>
        </div>

    </div>

    <!-- Validação com mensagens bonitas (substitui o balão padrão do navegador) -->
    <script src="JS/validacao.js"></script>

</body>
</html>
