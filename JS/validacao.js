// =====================================================
// BARBERTECH - Validação de formulário (mensagens bonitas)
//
// O navegador tem um balão cinza padrão ("Preencha este campo.")
// que aparece nos campos "required". Ele é feio e não dá pra
// estilizar. Este arquivo desliga esse balão e mostra uma
// mensagem nossa, embaixo do campo, com o estilo do site.
// =====================================================

// Espera a página carregar antes de mexer nos formulários
document.addEventListener('DOMContentLoaded', function () {

    // Pega todos os formulários que têm a classe "form-login"
    var formularios = document.querySelectorAll('.form-login');

    formularios.forEach(function (form) {

        // "novalidate" desliga o balão padrão do navegador,
        // mas ainda conseguimos checar os campos pelo JS
        form.setAttribute('novalidate', 'novalidate');

        // Pega todos os inputs do formulário
        var campos = form.querySelectorAll('input');

        // Quando o usuário tenta enviar o formulário
        form.addEventListener('submit', function (evento) {
            var temErro = false;

            campos.forEach(function (campo) {
                // Limpa erro anterior desse campo
                limparErro(campo);

                // checkValidity() retorna false se o campo está
                // inválido (vazio quando required, email errado, etc.)
                if (!campo.checkValidity()) {
                    mostrarErro(campo);
                    temErro = true;
                }
            });

            // Se algum campo está errado, cancela o envio
            if (temErro) {
                evento.preventDefault();
            }
        });

        // Enquanto o usuário digita, tira a mensagem de erro do campo
        campos.forEach(function (campo) {
            campo.addEventListener('input', function () {
                if (campo.checkValidity()) {
                    limparErro(campo);
                }
            });
        });
    });

    // -------------------------------------------------
    // Mostra a mensagem de erro embaixo do campo
    // -------------------------------------------------
    function mostrarErro(campo) {
        // Marca o campo como inválido (usado no CSS pra borda vermelha)
        campo.classList.add('campo-invalido');

        // Cria o parágrafo com a mensagem
        var aviso = document.createElement('span');
        aviso.className = 'erro-campo';
        aviso.textContent = pegarMensagem(campo);

        // Coloca a mensagem logo depois do input
        campo.insertAdjacentElement('afterend', aviso);
    }

    // -------------------------------------------------
    // Remove a mensagem de erro do campo
    // -------------------------------------------------
    function limparErro(campo) {
        campo.classList.remove('campo-invalido');

        var aviso = campo.parentElement.querySelector('.erro-campo');
        if (aviso) {
            aviso.remove();
        }
    }

    // -------------------------------------------------
    // Escolhe o texto do erro conforme o tipo do problema
    // -------------------------------------------------
    function pegarMensagem(campo) {
        // valueMissing = campo obrigatório vazio
        if (campo.validity.valueMissing) {
            return 'Preencha este campo.';
        }
        // typeMismatch = formato errado (ex: email sem "@")
        if (campo.validity.typeMismatch) {
            if (campo.type === 'email') {
                return 'Digite um email válido.';
            }
            return 'Valor inválido.';
        }
        // tooShort = texto menor que o mínimo (minlength)
        if (campo.validity.tooShort) {
            return 'Digite pelo menos ' + campo.minLength + ' caracteres.';
        }
        // Qualquer outro caso
        return 'Campo inválido.';
    }
});
