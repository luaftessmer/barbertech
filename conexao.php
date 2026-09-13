<?php
// =====================================================
// BARBERTECH - Arquivo de Conexão com o Banco de Dados
// Este arquivo é incluído em todas as páginas que
// precisam acessar o banco de dados.
// =====================================================

// Configurações do banco de dados
$host   = 'localhost';   // endereço do servidor (padrão no XAMPP)
$banco  = 'barbertech';  // nome do banco de dados
$usuario = 'root';       // usuário do MySQL (padrão no XAMPP)
$senha  = '';            // senha do MySQL (padrão no XAMPP é vazia)

// Tenta conectar ao banco de dados
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    // Se falhar, para a execução e mostra o erro
    die('Erro ao conectar com o banco de dados: ' . $conn->connect_error);
}

// Define o charset para aceitar acentos e caracteres especiais
$conn->set_charset('utf8mb4');
?>
