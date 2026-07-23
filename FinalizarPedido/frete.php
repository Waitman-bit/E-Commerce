<?php
/**
 * frete.php
 *
 * Regras de cálculo de frete SIMULADO (sem integração com Correios/API externa).
 * A loja está localizada em São Paulo (região Sudeste).
 * A região do cliente é identificada pelos dois primeiros dígitos do CEP.
 *
 * Usado por: calcular_frete.php (AJAX) e processar_pedido.php (revalidação no servidor).
 */

/**
 * Identifica a região do Brasil a partir do CEP.
 *
 * @param string $cep CEP com ou sem máscara.
 * @return string|null Região ('sudeste', 'sul', 'centro-oeste', 'nordeste', 'norte') ou null se não identificado.
 */
function titan_regiaoPorCep(string $cep): ?string
{
    $cep = preg_replace('/\D/', '', $cep);

    if (strlen($cep) < 2) {
        return null;
    }

    $prefixo = (int) substr($cep, 0, 2);

    if ($prefixo >= 1 && $prefixo <= 19) return 'sudeste';
    if ($prefixo >= 20 && $prefixo <= 28) return 'sudeste';
    if ($prefixo >= 30 && $prefixo <= 39) return 'sudeste';
    if ($prefixo >= 40 && $prefixo <= 48) return 'nordeste';
    if ($prefixo === 49) return 'nordeste';
    if ($prefixo >= 50 && $prefixo <= 56) return 'nordeste';
    if ($prefixo === 57) return 'nordeste';
    if ($prefixo === 58) return 'nordeste';
    if ($prefixo === 59) return 'nordeste';
    if ($prefixo >= 60 && $prefixo <= 63) return 'nordeste';
    if ($prefixo === 64) return 'nordeste';
    if ($prefixo === 65) return 'nordeste';
    if ($prefixo >= 66 && $prefixo <= 68) return 'norte';
    if ($prefixo === 69) return 'norte';
    if ($prefixo === 76) return 'norte';       // RO
    if ($prefixo === 77) return 'norte';       // TO
    if ($prefixo === 78) return 'centro-oeste'; // MT
    if ($prefixo === 79) return 'centro-oeste'; // MS
    if ($prefixo >= 70 && $prefixo <= 73) return 'centro-oeste'; // DF
    if ($prefixo >= 74 && $prefixo <= 75) return 'centro-oeste'; // GO
    if ($prefixo >= 80 && $prefixo <= 87) return 'sul';
    if ($prefixo === 88) return 'sul';
    if ($prefixo >= 90 && $prefixo <= 99) return 'sul';

    return null;
}

/**
 * Tabela de fretes simulada por região e tipo de entrega.
 */
function titan_tabelaFrete(): array
{
    return [
        'sudeste' => [
            'padrao'   => ['valor' => 14.90, 'prazo' => '2 a 4 dias úteis'],
            'expressa' => ['valor' => 24.90, 'prazo' => '1 a 2 dias úteis'],
        ],
        'sul' => [
            'padrao'   => ['valor' => 19.90, 'prazo' => '3 a 5 dias úteis'],
            'expressa' => ['valor' => 29.90, 'prazo' => '2 a 3 dias úteis'],
        ],
        'centro-oeste' => [
            'padrao'   => ['valor' => 22.90, 'prazo' => '4 a 6 dias úteis'],
            'expressa' => ['valor' => 34.90, 'prazo' => '2 a 4 dias úteis'],
        ],
        'nordeste' => [
            'padrao'   => ['valor' => 29.90, 'prazo' => '5 a 8 dias úteis'],
            'expressa' => ['valor' => 44.90, 'prazo' => '3 a 5 dias úteis'],
        ],
        'norte' => [
            'padrao'   => ['valor' => 39.90, 'prazo' => '7 a 10 dias úteis'],
            'expressa' => ['valor' => 59.90, 'prazo' => '4 a 6 dias úteis'],
        ],
    ];
}

/**
 * Calcula o frete para um CEP e tipo de entrega.
 *
 * @param string $cep
 * @param string $tipoEntrega 'padrao' | 'expressa' | 'retirada'
 * @return array{valor: float|null, prazo: string|null, regiao: string|null, erro: string|null}
 */
function titan_calcularFrete(string $cep, string $tipoEntrega): array
{
    if ($tipoEntrega === 'retirada') {
        return [
            'valor'  => 0.0,
            'prazo'  => 'Disponível em até 24 horas úteis',
            'regiao' => 'retirada',
            'erro'   => null,
        ];
    }

    $regiao = titan_regiaoPorCep($cep);

    if ($regiao === null) {
        return ['valor' => null, 'prazo' => null, 'regiao' => null, 'erro' => 'CEP inválido ou não identificado.'];
    }

    $tabela = titan_tabelaFrete();

    if (!isset($tabela[$regiao][$tipoEntrega])) {
        return ['valor' => null, 'prazo' => null, 'regiao' => $regiao, 'erro' => 'Tipo de entrega inválido.'];
    }

    return [
        'valor'  => $tabela[$regiao][$tipoEntrega]['valor'],
        'prazo'  => $tabela[$regiao][$tipoEntrega]['prazo'],
        'regiao' => $regiao,
        'erro'   => null,
    ];
}
