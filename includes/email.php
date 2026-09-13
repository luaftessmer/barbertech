<?php
// =====================================================
// BARBERTECH - Helper de Envio de Email
// Centraliza o envio de emails do sistema.
//
// CONFIGURAÇÃO:
// Para funcionar, edite as variáveis abaixo com os
// dados da sua conta de email (recomendado: Gmail).
//
// No Gmail, você precisa de uma "Senha de App":
// myaccount.google.com → Segurança → Senhas de App
// =====================================================

// -------------------------------------------------------
// CONFIGURAÇÕES DE EMAIL — edite aqui
// -------------------------------------------------------
define('EMAIL_SMTP_HOST',  'smtp.gmail.com');
define('EMAIL_SMTP_PORT',  587);
define('EMAIL_USUARIO',    'seuemail@gmail.com');   // ← seu email
define('EMAIL_SENHA',      'sua_senha_de_app');     // ← sua senha de app
define('EMAIL_NOME_ORIG',  'BarberTech');            // nome exibido no remetente

// -------------------------------------------------------
// Envia um email via SMTP usando sockets PHP puros.
// Não requer PHPMailer nem extensões extras.
//
// Parâmetros:
//   $para     - email do destinatário
//   $nome_para - nome do destinatário
//   $assunto  - assunto do email
//   $corpo    - corpo em HTML
//
// Retorna true em caso de sucesso, false se falhar.
// -------------------------------------------------------
function enviar_email($para, $nome_para, $assunto, $corpo) {

    $host     = EMAIL_SMTP_HOST;
    $port     = EMAIL_SMTP_PORT;
    $usuario  = EMAIL_USUARIO;
    $senha    = EMAIL_SENHA;
    $de       = EMAIL_USUARIO;
    $nome_de  = EMAIL_NOME_ORIG;

    // Conecta ao servidor SMTP via socket
    $socket = fsockopen("tls://$host", $port, $errno, $errstr, 10);
    if (!$socket) {
        error_log("BarberTech Email: não foi possível conectar ao SMTP ($errno: $errstr)");
        return false;
    }

    $respostas = [];

    // Função para enviar um comando SMTP e ler a resposta
    $cmd = function($comando) use ($socket, &$respostas) {
        if ($comando) {
            fwrite($socket, $comando . "\r\n");
        }
        $resposta = '';
        while ($linha = fgets($socket, 512)) {
            $resposta .= $linha;
            // Última linha da resposta não tem traço após o código
            if (substr($linha, 3, 1) == ' ') {
                break;
            }
        }
        $respostas[] = trim($resposta);
        return (int) substr($resposta, 0, 3); // retorna o código numérico
    };

    $cmd(null);                                          // 220 - boas-vindas
    $cmd("EHLO barbertech");                             // 250 - identifica
    $cmd("AUTH LOGIN");                                  // 334 - inicia auth
    $cmd(base64_encode($usuario));                       // 334 - envia usuário
    $cmd(base64_encode($senha));                         // 235 - envia senha

    $cmd("MAIL FROM: <$de>");                            // 250 - remetente
    $cmd("RCPT TO: <$para>");                            // 250 - destinatário
    $cmd("DATA");                                        // 354 - início do corpo

    // Cabeçalhos do email
    $cabecalho  = "From: =?UTF-8?B?" . base64_encode($nome_de) . "?= <$de>\r\n";
    $cabecalho .= "To: =?UTF-8?B?" . base64_encode($nome_para) . "?= <$para>\r\n";
    $cabecalho .= "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=\r\n";
    $cabecalho .= "MIME-Version: 1.0\r\n";
    $cabecalho .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Envia cabeçalhos + corpo + fim do DATA
    fwrite($socket, $cabecalho . "\r\n" . $corpo . "\r\n.\r\n");
    $cmd(null);                                          // 250 - mensagem aceita

    $cmd("QUIT");                                        // 221 - encerra
    fclose($socket);

    return true;
}

// -------------------------------------------------------
// Monta e envia o email de LEMBRETE de agendamento.
// Chamado pelo script de cron (cron/lembrete_email.php).
// -------------------------------------------------------
function email_lembrete($email_cliente, $nome_cliente, $data_hora, $nome_barbeiro, $servicos) {
    $data_formatada = date('d/m/Y', strtotime($data_hora));
    $hora_formatada = date('H:i',   strtotime($data_hora));

    $assunto = "Lembrete: seu agendamento é hoje às $hora_formatada";

    $corpo = "
    <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; color: #333;'>
        <h2 style='color: #222;'>Olá, $nome_cliente!</h2>
        <p>Este é um lembrete do seu agendamento na barbearia.</p>
        <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Data</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$data_formatada</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Horário</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$hora_formatada</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Barbeiro</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$nome_barbeiro</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Serviços</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$servicos</td>
            </tr>
        </table>
        <p style='color: #888; font-size: 13px;'>Te esperamos! — BarberTech</p>
    </div>";

    return enviar_email($email_cliente, $nome_cliente, $assunto, $corpo);
}

// -------------------------------------------------------
// Monta e envia o email de CANCELAMENTO automático.
// Chamado quando o admin desativa um serviço para um barbeiro.
// -------------------------------------------------------
function email_cancelamento($email_cliente, $nome_cliente, $data_hora, $nome_barbeiro, $nome_servico) {
    $data_formatada = date('d/m/Y', strtotime($data_hora));
    $hora_formatada = date('H:i',   strtotime($data_hora));

    $assunto = "Seu agendamento em $data_formatada foi cancelado";

    $corpo = "
    <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; color: #333;'>
        <h2 style='color: #222;'>Olá, $nome_cliente!</h2>
        <p>Infelizmente seu agendamento precisou ser cancelado.</p>
        <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Data</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$data_formatada</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Horário</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$hora_formatada</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Barbeiro</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>$nome_barbeiro</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #eee; background: #f9f9f9;'><strong>Motivo</strong></td>
                <td style='padding: 8px; border: 1px solid #eee;'>O serviço <strong>$nome_servico</strong> não está mais disponível com este barbeiro.</td>
            </tr>
        </table>
        <p>Pedimos desculpas pelo inconveniente. Você pode fazer um novo agendamento quando quiser.</p>
        <p style='color: #888; font-size: 13px;'>— BarberTech</p>
    </div>";

    return enviar_email($email_cliente, $nome_cliente, $assunto, $corpo);
}
?>
