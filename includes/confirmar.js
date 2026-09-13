function pedirConfirmacao(link, mensagem) {
    var existente = document.getElementById('box-confirmar');
    if (existente) {
        // Se clicar de novo no mesmo link, fecha
        if (existente.dataset.alvo === link.href) {
            existente.remove();
            return false;
        }
        existente.remove();
    }

    var box = document.createElement('div');
    box.id = 'box-confirmar';
    box.dataset.alvo = link.href;
    box.innerHTML =
        '<span>' + mensagem + '</span>' +
        '<a href="' + link.href + '" class="confirmar-sim">Sim</a>' +
        '<button onclick="document.getElementById(\'box-confirmar\').remove()" class="confirmar-nao">Não</button>';

    link.parentNode.insertBefore(box, link.nextSibling);
    return false;
}
