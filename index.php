<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="login-box">

    <img src="imagens/tesoura.png" 
         alt="Ícone de barbearia" 
         class="logo">

    <h1>BARBERTECH</h1>

        
    
    <title>BarberTech - Login</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="login-container">

        <div class="login-box">

            <h1>BARBERTECH</h1>

            <p class="subtitle">Sistema de Gestão para Barbearias</p>

            <form action="#" method="POST">

                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Digite seu e-mail"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="senha">Senha</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <button type="submit">ENTRAR</button>

            </form>

            <a href="#" class="forgot-password">
                Esqueceu sua senha?
            </a>

        </div>

    </div>

</body>
</html>