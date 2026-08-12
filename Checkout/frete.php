<?php
/**
 * frete.php
 *
 * Fonte única de verdade para cálculo de frete e consulta de endereço por CEP.
 * Incluído por: checkout.php, buscar_cep.php e pagamento.php.
 *
 * Não depende de nenhuma tabela nova no banco: a "região" é derivada do
 * estado (UF) em tempo de execução, usando o mapa abaixo.
 */

// ===== MAPA DE REGIÃO POR UF =====
const TITAN_MAPA_REGIAO_UF = [
    'AC' => 'Norte', 'AP' => 'Norte', 'AM' => 'Norte', 'PA' => 'Norte',
    'RO' => 'Norte', 'RR' => 'Norte', 'TO' => 'Norte',

    'AL' => 'Nordeste', 'BA' => 'Nordeste', 'CE' => 'Nordeste', 'MA' => 'Nordeste',
    'PB' => 'Nordeste', 'PE' => 'Nordeste', 'PI' => 'Nordeste', 'RN' => 'Nordeste',
    'SE' => 'Nordeste',

    'DF' => 'Centro-Oeste', 'GO' => 'Centro-Oeste', 'MT' => 'Centro-Oeste', 'MS' => 'Centro-Oeste',

    'ES' => 'Sudeste', 'MG' => 'Sudeste', 'RJ' => 'Sudeste', 'SP' => 'Sudeste',

    'PR' => 'Sul', 'RS' => 'Sul', 'SC' => 'Sul',
];

// ===== TABELA DE FRETE POR REGIÃO =====
// Valores de referência informados pelo cliente. Fácil de ajustar aqui,
// em um único lugar, sem precisar mexer no restante do sistema.
const TITAN_TABELA_FRETE = [
    'Sudeste'      => [
        'normal'   => ['valor' => 10.00, 'prazo' => '5 dias úteis',  'prazo_dias' => 5],
        'expressa' => ['valor' => 18.00, 'prazo' => '2 dias úteis',  'prazo_dias' => 2],
    ],
    'Sul'          => [
        'normal'   => ['valor' => 16.00, 'prazo' => '7 dias úteis',  'prazo_dias' => 7],
        'expressa' => ['valor' => 26.00, 'prazo' => '4 dias úteis',  'prazo_dias' => 4],
    ],
    'Centro-Oeste' => [
        'normal'   => ['valor' => 20.00, 'prazo' => '8 dias úteis',  'prazo_dias' => 8],
        'expressa' => ['valor' => 32.00, 'prazo' => '5 dias úteis',  'prazo_dias' => 5],
    ],
    'Nordeste'     => [
        'normal'   => ['valor' => 25.00, 'prazo' => '10 dias úteis', 'prazo_dias' => 10],
        'expressa' => ['valor' => 40.00, 'prazo' => '6 dias úteis',  'prazo_dias' => 6],
    ],
    'Norte'        => [
        'normal'   => ['valor' => 32.00, 'prazo' => '12 dias úteis', 'prazo_dias' => 12],
        'expressa' => ['valor' => 50.00, 'prazo' => '7 dias úteis',  'prazo_dias' => 7],
    ],
];

/**
 * Retorna a região (Sudeste, Sul, Centro-Oeste, Nordeste, Norte) a partir da UF.
 * Retorna null se a UF não for reconhecida.
 */
function titan_regiao_por_uf(string $uf): ?string
{
    $uf = strtoupper(trim($uf));
    return TITAN_MAPA_REGIAO_UF[$uf] ?? null;
}

/**
 * Retorna as 3 opções de entrega (normal, expressa, retirada) para uma UF.
 * Retorna false se a UF for inválida.
 */
function titan_opcoes_frete(string $uf)
{
    $regiao = titan_regiao_por_uf($uf);

    if ($regiao === null || !isset(TITAN_TABELA_FRETE[$regiao])) {
        return false;
    }

    $tabela = TITAN_TABELA_FRETE[$regiao];

    return [
        'regiao'   => $regiao,
        'normal'   => $tabela['normal'],
        'expressa' => $tabela['expressa'],
        'retirada' => ['valor' => 0.00, 'prazo' => 'Disponível para retirada em até 2 dias úteis', 'prazo_dias' => 2],
    ];
}

/**
 * Calcula o frete final (valor + prazo) para uma UF + método de entrega.
 * $metodo deve ser: 'normal', 'expressa' ou 'retirada'.
 * Retorna false se UF ou método forem inválidos. ESTA É A FUNÇÃO USADA
 * PARA VALIDAÇÃO FINAL NO SERVIDOR — nunca confie em valor de frete vindo do navegador.
 */
function titan_calcular_frete(string $uf, string $metodo)
{
    $opcoes = titan_opcoes_frete($uf);

    if ($opcoes === false) {
        return false;
    }

    if (!in_array($metodo, ['normal', 'expressa', 'retirada'], true)) {
        return false;
    }

    return [
        'valor'  => (float) $opcoes[$metodo]['valor'],
        'prazo'  => $opcoes[$metodo]['prazo'],
        'regiao' => $opcoes['regiao'],
    ];
}

/**
 * Consulta o endereço a partir de um CEP usando a API pública ViaCEP
 * (https://viacep.com.br) — gratuita, sem necessidade de chave/cadastro.
 *
 * Retorna um array associativo ['logradouro','bairro','cidade','uf'] em
 * caso de sucesso, ou false em caso de erro/CEP inexistente.
 *
 * Se o servidor não tiver acesso à internet ou a API estiver fora do ar,
 * a função falha graciosamente (retorna false) e o formulário de checkout
 * permite que o usuário preencha o endereço manualmente.
 */
function titan_buscar_cep(string $cep)
{
    $cep = preg_replace('/\D/', '', $cep);

    if (strlen($cep) !== 8) {
        return false;
    }

    $url = "https://viacep.com.br/ws/{$cep}/json/";

    $contexto = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 4,
        ],
    ]);

    $resposta = @file_get_contents($url, false, $contexto);

    if ($resposta === false) {
        return false;
    }

    $dados = json_decode($resposta, true);

    if (!is_array($dados) || isset($dados['erro'])) {
        return false;
    }

    return [
        'logradouro' => $dados['logradouro'] ?? '',
        'bairro'     => $dados['bairro'] ?? '',
        'cidade'     => $dados['localidade'] ?? '',
        'uf'         => $dados['uf'] ?? '',
    ];
}
