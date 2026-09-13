<?php
// =====================================================
// BARBERTECH - Landing Page (site público da barbearia)
// Página de apresentação. O botão "AGENDAR" leva o
// visitante para o login/cadastro do sistema.
// =====================================================

require_once 'conexao.php';

// Busca os serviços ativos cadastrados no sistema
$servicos_db = $conn->query("SELECT nome, descricao, preco FROM servico ORDER BY preco ASC");

// Busca os barbeiros cadastrados
$barbeiros_db = $conn->query(
    "SELECT u.nome FROM barbeiro b
     JOIN usuario u ON b.id_usuario = u.id_usuario
     ORDER BY u.nome ASC"
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia BarberTech</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<!-- A classe "site" ativa a paleta e a fonte da landing (ver style.css) -->
<body class="site">

    <!-- ============ CABEÇALHO ============ -->
    <header class="site-header">
        <div class="marca">
            <span class="rotulo">Est. 2009</span>
            <a href="home.php" class="nome">BarberTech</a>
        </div>

        <nav class="site-nav">
            <a href="#inicio" class="ativo">Início</a>
            <a href="#servicos">Serviços</a>
            <a href="#equipe">Equipe</a>
            <a href="#agendar">Agendar</a>
        </nav>

        <a href="cadastro.php" class="btn-agendar">Agendar</a>
    </header>

    <!-- ============ HERO (seção principal) ============ -->
    <section class="hero" id="inicio">

        <!-- Coluna de texto -->
        <div class="hero-texto">
            <span class="rotulo hero-tag">Barbearia clássica</span>

            <h1>
                O ofício do
                <span class="destaque">cuidado</span>
                masculino.
            </h1>

            <p class="hero-sub">
                Mais de quinze anos dedicados à arte da barbearia tradicional.
                Agendamento online, sem complicações — escolha seu barbeiro,
                seu horário, e apareça.
            </p>

            <div class="hero-acoes">
                <a href="cadastro.php" class="btn-agendar">Reservar horário</a>
                <a href="#servicos" class="link-seta">Ver serviços &rarr;</a>
            </div>

            <!-- Linha de números -->
            <div class="hero-stats">
                <div>
                    <div class="stat-numero">4.9</div>
                    <div class="stat-texto">Avaliação média</div>
                </div>
                <div>
                    <div class="stat-numero">2.400+</div>
                    <div class="stat-texto">Clientes atendidos</div>
                </div>
                <div>
                    <div class="stat-numero">15+</div>
                    <div class="stat-texto">Anos de tradição</div>
                </div>
            </div>
        </div>

        <!-- Coluna da imagem (espaço reservado para foto) -->
        <div class="hero-imagem">
            <div class="hero-card">
                <span class="rotulo">Próximo horário disponível</span>
                <div class="horario">Hoje &middot; 14:45</div>
                <div class="barbeiro">com Antônio Meireles</div>
            </div>
        </div>

    </section>

    <!-- ============ SERVIÇOS ============ -->
    <section class="site-secao" id="servicos">
        <span class="rotulo">O que fazemos</span>
        <h2>Serviços</h2>

        <div class="grade-servicos">
            <?php if ($servicos_db && $servicos_db->num_rows > 0): ?>
                <?php while ($s = $servicos_db->fetch_assoc()): ?>
                <div class="servico-item">
                    <h3><?php echo htmlspecialchars($s['nome']); ?></h3>
                    <?php if (!empty($s['descricao'])): ?>
                        <p><?php echo htmlspecialchars($s['descricao']); ?></p>
                    <?php endif; ?>
                    <span class="preco">R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#aaa;">Nenhum serviço cadastrado ainda.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============ EQUIPE ============ -->
    <section class="site-secao" id="equipe">
        <span class="rotulo">Quem atende</span>
        <h2>Nossa equipe</h2>
        <p class="hero-sub">
            Barbeiros experientes, cada um com seu estilo. Na hora de agendar
            você escolhe com quem quer marcar.
        </p>

        <?php if ($barbeiros_db && $barbeiros_db->num_rows > 0): ?>
        <div class="grade-servicos" style="margin-top: 32px;">
            <?php while ($b = $barbeiros_db->fetch_assoc()): ?>
            <div class="servico-item" style="text-align: center;">
                <div style="font-size: 40px; margin-bottom: 12px;">✂</div>
                <h3><?php echo htmlspecialchars($b['nome']); ?></h3>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============ CHAMADA PARA AGENDAR ============ -->
    <section class="site-secao" id="agendar">
        <span class="rotulo">Bora marcar</span>
        <h2>Agende em menos de um minuto</h2>
        <div class="hero-acoes">
            <a href="cadastro.php" class="btn-agendar">Criar conta e agendar</a>
            <a href="index.php" class="link-seta">Já tenho conta &rarr;</a>
        </div>
    </section>

    <!-- ============ RODAPÉ ============ -->
    <footer class="site-footer">
        <span class="nome">BarberTech</span>
        <span>Rua Exemplo, 123 &middot; São Paulo &middot; (11) 90000-0000</span>
        <a href="index.php">Entrar no sistema</a>
    </footer>

</body>
</html>
