-- =====================================================
-- BARBERTECH - Banco de Dados
-- Criado para ser importado no phpMyAdmin (XAMPP)
-- =====================================================

-- Cria e seleciona o banco de dados
CREATE DATABASE IF NOT EXISTS barbertech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barbertech;

-- =====================================================
-- TABELA: usuario
-- Armazena todos os usuários do sistema.
-- O campo "tipo" define se é admin, barbeiro ou cliente.
-- =====================================================
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    senha      VARCHAR(255) NOT NULL,  -- senha armazenada como hash (password_hash)
    telefone   VARCHAR(20)  NOT NULL,
    tipo       ENUM('admin', 'barbeiro', 'cliente') NOT NULL
);

-- =====================================================
-- TABELA: barbeiro
-- Cada barbeiro está vinculado a um usuário do tipo "barbeiro".
-- =====================================================
CREATE TABLE barbeiro (
    id_barbeiro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT NOT NULL UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- TABELA: cliente
-- Cada cliente está vinculado a um usuário do tipo "cliente".
-- =====================================================
CREATE TABLE cliente (
    id_cliente      INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NOT NULL UNIQUE,
    data_nascimento DATE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- TABELA: servico
-- Serviços oferecidos pela barbearia.
-- Cadastrado e gerenciado apenas pelo admin.
-- =====================================================
CREATE TABLE servico (
    id_servico  INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100)   NOT NULL,
    descricao   TEXT,
    preco       DECIMAL(10, 2) NOT NULL,  -- preço em reais, ex: 25.00
    duracao     INT            NOT NULL   -- duração em minutos, ex: 30
);

-- =====================================================
-- TABELA: barbeiro_servico
-- Define quais serviços cada barbeiro pode realizar.
-- Por padrão, todos os barbeiros fazem todos os serviços.
-- O admin pode desativar um serviço específico para um barbeiro.
-- =====================================================
CREATE TABLE barbeiro_servico (
    id_barbeiro INT NOT NULL,
    id_servico  INT NOT NULL,
    ativo       TINYINT(1) NOT NULL DEFAULT 1,  -- 1 = ativo, 0 = desativado
    PRIMARY KEY (id_barbeiro, id_servico),
    FOREIGN KEY (id_barbeiro) REFERENCES barbeiro(id_barbeiro) ON DELETE CASCADE,
    FOREIGN KEY (id_servico)  REFERENCES servico(id_servico)  ON DELETE CASCADE
);

-- =====================================================
-- TABELA: horario_funcionamento
-- Define os dias e horários de funcionamento da barbearia.
-- Um registro por dia da semana.
-- =====================================================
CREATE TABLE horario_funcionamento (
    id_horario        INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana        TINYINT NOT NULL,  -- 0=Domingo, 1=Segunda, ..., 6=Sábado
    hora_inicio       TIME NOT NULL,     -- ex: 09:00:00
    hora_fim          TIME NOT NULL,     -- ex: 18:00:00
    intervalo_inicio  TIME,              -- início do intervalo de almoço
    intervalo_fim     TIME,              -- fim do intervalo de almoço
    aberto            TINYINT(1) NOT NULL DEFAULT 1  -- 1 = aberto, 0 = fechado
);

-- =====================================================
-- TABELA: atendimento
-- Registra cada agendamento/atendimento do sistema.
-- Status: pendente, concluido ou cancelado.
-- =====================================================
CREATE TABLE atendimento (
    id_atendimento INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente     INT            NOT NULL,
    id_barbeiro    INT            NOT NULL,
    data_hora      DATETIME       NOT NULL,
    status         ENUM('pendente', 'concluido', 'cancelado') NOT NULL DEFAULT 'pendente',
    valor_total    DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    observacao     TEXT,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    FOREIGN KEY (id_barbeiro) REFERENCES barbeiro(id_barbeiro)
);

-- =====================================================
-- TABELA: atendimento_servico
-- Um atendimento pode ter mais de um serviço.
-- Esta tabela faz a ligação entre atendimento e serviço.
-- =====================================================
CREATE TABLE atendimento_servico (
    id_atendimento INT NOT NULL,
    id_servico     INT NOT NULL,
    PRIMARY KEY (id_atendimento, id_servico),
    FOREIGN KEY (id_atendimento) REFERENCES atendimento(id_atendimento) ON DELETE CASCADE,
    FOREIGN KEY (id_servico)     REFERENCES servico(id_servico)
);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Horário de funcionamento padrão da barbearia
-- Segunda a Sábado: 09:00 às 18:00, intervalo 12:00 às 13:00
-- Domingo: fechado
INSERT INTO horario_funcionamento (dia_semana, hora_inicio, hora_fim, intervalo_inicio, intervalo_fim, aberto) VALUES
(0, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 0),  -- Domingo: fechado
(1, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1),  -- Segunda
(2, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1),  -- Terça
(3, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1),  -- Quarta
(4, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1),  -- Quinta
(5, '09:00:00', '18:00:00', '12:00:00', '13:00:00', 1),  -- Sexta
(6, '09:00:00', '17:00:00', '12:00:00', '13:00:00', 1);  -- Sábado: fecha mais cedo

-- Administrador padrão do sistema
-- Email: admin@barbertech.com | Senha: admin123
-- IMPORTANTE: Após a instalação, troque a senha pelo painel!
INSERT INTO usuario (nome, email, senha, telefone, tipo) VALUES
('Administrador', 'admin@barbertech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(00) 00000-0000', 'admin');
-- Obs: a senha acima é o hash de "password" gerado pelo PHP password_hash()
-- Para gerar o hash da sua própria senha, use: password_hash('sua_senha', PASSWORD_DEFAULT)
