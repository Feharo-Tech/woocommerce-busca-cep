jQuery(document).ready(function($) {
    // Textos padrão
    const defaultTexts = {
        dont_know_cep: 'Não sei meu CEP',
        fill_all_fields: 'Preencha todos os campos corretamente.',
        cep_found: 'CEP encontrado',
        cep_not_found: 'CEP não encontrado para esse endereço.',
        cep_error: 'Erro ao consultar CEP. Tente novamente.',
        select_cep: 'Selecione seu CEP:',
        loading: 'Buscando CEP...'
    };
    
    // Mescla textos padrão com localizados
    const texts = {
        ...defaultTexts,
        ...(window.wc_busca_cep_params?.i18n || {})
    };

    // Normaliza termos comuns de endereço
    function normalizarTermos(texto) {
        if (!texto) return '';
    
        const substituicoes = {
            'av.': 'avenida',
            'av': 'avenida',
            'r.': 'rua',
            'r': 'rua',
            'al.': 'alameda',
            'al': 'alameda',
            'dr.': 'doutor',
            'dr': 'doutor',
            'prof.': 'professor',
            'prof': 'professor',
            'estr.': 'estrada',
            'estr': 'estrada',
            'rod.': 'rodovia',
            'rod': 'rodovia',
            'trav.': 'travessa',
            'trav': 'travessa',
            'vl.': 'vila',
            'vl': 'vila',
            'lgo.': 'largo',
            'lgo': 'largo',
            'pq.': 'parque',
            'pq': 'parque',
            'jd.': 'jardim',
            'jd': 'jardim',
            'pto.': 'ponto',
            'pto': 'ponto',
            'sta.': 'santa',
            'sta': 'santa',
            'sto.': 'santo',
            'sto': 'santo',
            'snr.': 'senhor',
            'snr': 'senhor',
            'snra.': 'senhora',
            'snra': 'senhora'
        };
    
        return texto
            .toLowerCase()
            .split(' ')
            .map(palavra => substituicoes[palavra] || palavra)
            .join(' ');
    }

    // Posiciona o link conforme configuração
    function posicionarLink() {
        const container = $('#wc-busca-cep-container');
        const position = wc_busca_cep_params.link_position || 'after';
    
        container.detach();
    
        const $postcode = $('#billing_postcode');
        const $postcodeField = $('#billing_postcode_field');
    
        if ($postcode.length || $postcodeField.length) {
            // Estamos no checkout e o campo existe
            switch(position) {
                case 'before':
                    $postcode.before(container);
                    break;
                case 'after_field':
                    $postcodeField.after(container);
                    break;
                case 'after':
                default:
                    $postcode.after(container);
                    break;
            }
        } else {
            // Fora do checkout, anexa no lugar onde o shortcode foi renderizado
            const shortcodePlaceholder = $('#wc-busca-cep-shortcode');
    
            if (shortcodePlaceholder.length) {
                shortcodePlaceholder.append(container);
            } else {
                // Fallback: adiciona no fim do body (evita sumir)
                $('body').append(container);
            }
        }
    
        container.show();
    }

    // Chama a função de posicionamento
    posicionarLink();

    // Manipulação do modal
    $('#wc-busca-cep-container').on('click', '#wc-abrir-busca-cep', function(e) {
        e.preventDefault();
        $('#wc-resultado-cep').html('');
        $('#wc-modal-busca-cep').show();
    });

    $(document).on('click', '#wc-fechar-modal-cep', function(e) {
        e.preventDefault();
        $('#wc-modal-busca-cep').hide();
    });

    // Função para exibir os CEPs encontrados
    function exibirCepsEncontrados(ceps) {
        let html = `<div class="wc-ceps-list">
                        <h4>${texts.select_cep}</h4>
                        <ul class="wc-ceps-list-items">`;
        
        ceps.forEach(endereco => {
            const enderecoCompleto = `${endereco.logradouro}, ${endereco.complemento || ''} - ${endereco.bairro}`;
            html += `
                <li class="wc-cep-item" data-endereco='${JSON.stringify(endereco)}'>
                    <strong>${endereco.cep}</strong> - ${enderecoCompleto}
                </li>
            `;
        });
        
        html += `</ul></div>`;
        
        $('#wc-resultado-cep').html(html);
    }

    // Função para preencher os campos do checkout
    function preencherCamposCheckout(endereco) {
        $('#billing_postcode').val(endereco.cep).trigger('change');
        $('#billing_address_1').val(endereco.logradouro).trigger('change');
        $('#billing_neighborhood').val(endereco.bairro).trigger('change');
        $('#billing_city').val(endereco.localidade).trigger('change');
        $('select[name="billing_state"]').val(endereco.uf).trigger('change');
        setTimeout(() => $('body').trigger('update_checkout'), 300);
        $('#wc-busca-logradouro').val('');
        $('#wc-busca-cidade').val('');
        $('#wc-busca-uf').val('');
    }

    // Seleção de CEP na lista
    $(document).on('click', '.wc-cep-item', function() {
        const enderecoSelecionado = JSON.parse($(this).attr('data-endereco'));
        $('#wc-modal-busca-cep').hide();
        $('#wc-resultado-cep').html(`<p class="wc-busca-cep-success">CEP ${enderecoSelecionado.cep} selecionado</p>`);
        preencherCamposCheckout(enderecoSelecionado)
    });

    // Busca de CEP
    $(document).on('click', '#wc-buscar-cep', function() {
        let rua = $('#wc-busca-logradouro').val().trim();
        let cidade = $('#wc-busca-cidade').val().trim();
        const uf = $('#wc-busca-uf').val().trim().toUpperCase();
        const resultado = $('#wc-resultado-cep');

        resultado.html('');

        // Validação dos campos
        if (!rua || !cidade || !uf) {
            resultado.html(`<p class="wc-busca-cep-error">${texts.fill_all_fields}</p>`);
            return;
        }

        // Normaliza os termos de endereço
        rua = normalizarTermos(rua);
        cidade = normalizarTermos(cidade);

        // Codifica os parâmetros para URL
        const cidadeCodificada = encodeURIComponent(cidade);
        const ruaCodificada = encodeURIComponent(rua);
        
        // Monta a URL com os parâmetros codificados
        const url = `https://viacep.com.br/ws/${uf}/${cidadeCodificada}/${ruaCodificada}/json/`;

        // Mostra loading
        resultado.html(`<p class="wc-busca-cep-loading">${texts.loading}</p>`);

        // Faz a requisição
        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Erro na resposta da API');
                return res.json();
            })
            .then(data => {
                if (Array.isArray(data) && data.length > 0) {
                    if (data[0].cep) {
                        exibirCepsEncontrados(data);
                    } else {
                        resultado.html(`<p class="wc-busca-cep-error">${texts.cep_not_found}</p>`);
                    }
                } else {
                    resultado.html(`<p class="wc-busca-cep-error">${texts.cep_not_found}</p>`);
                }
            })
            .catch((erro) => {
                console.error('Erro ao consultar o CEP:', erro);
                resultado.html(`<p class="wc-busca-cep-error">${texts.cep_error}</p>`);
            });
    });
});