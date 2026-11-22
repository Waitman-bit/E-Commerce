<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método de requisição inválido.']);
    exit;
}

// 1. Busca a chave GEMINI_API_KEY nas variáveis de ambiente ou no arquivo .env
$apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';

if ($apiKey === '') {
    $caminhoEnv = __DIR__ . '/../.env';
    if (is_file($caminhoEnv) && is_readable($caminhoEnv)) {
        $linhas = file($caminhoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            if (trim($chave) === 'GEMINI_API_KEY') {
                $apiKey = trim($valor, " \t\n\r\0\x0B\"'");
                break;
            }
        }
    }
}

if ($apiKey === '') {
    echo json_encode(['erro' => 'Chave GEMINI_API_KEY não encontrada no arquivo .env.']);
    exit;
}

// 2. Recebe e normaliza os dados enviados pelo JavaScript
$dadosRecebidos = json_decode(file_get_contents('php://input'), true) ?? [];

$nome      = trim((string) ($dadosRecebidos['nome'] ?? ''));
$categoria = trim((string) ($dadosRecebidos['categoria'] ?? ''));
$genero    = trim((string) ($dadosRecebidos['genero'] ?? ''));
$tamanhos  = trim((string) ($dadosRecebidos['tamanhos'] ?? ''));
$preco     = trim((string) ($dadosRecebidos['preco'] ?? ''));
$detalhes  = trim((string) ($dadosRecebidos['detalhes'] ?? ''));

if ($nome === '') {
    echo json_encode(['erro' => 'Preencha o nome do produto antes de gerar com IA.']);
    exit;
}

const LIMITE_DESCRICAO = 150;

/**
 * Normaliza e corta o texto gerado garantindo o teto máximo de 150 caracteres.
 */
function limitarDescricao150(string $texto): string
{
    $limpo = trim($texto);
    $limpo = preg_replace('/\s+/', ' ', $limpo) ?? '';
    $limpo = trim($limpo, "\"'");

    if (mb_strlen($limpo, 'UTF-8') > LIMITE_DESCRICAO) {
        $limpo = rtrim(mb_substr($limpo, 0, LIMITE_DESCRICAO, 'UTF-8'));
    }

    return $limpo;
}

// 3. Verifica cache local simples (inclui o limite na chave para invalidar respostas antigas de 50 chars)
$chaveCache = hash('sha256', mb_strtolower(json_encode([
    'limite'    => LIMITE_DESCRICAO,
    'nome'      => $nome,
    'categoria' => $categoria,
    'genero'    => $genero,
    'tamanhos'  => $tamanhos,
    'preco'     => $preco,
    'detalhes'  => $detalhes,
], JSON_UNESCAPED_UNICODE), 'UTF-8'));

$pastaCache = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tcc_gemini_cache';
if (!is_dir($pastaCache)) {
    @mkdir($pastaCache, 0777, true);
}
$arquivoCache = $pastaCache . DIRECTORY_SEPARATOR . $chaveCache . '.json';
$ttlCacheSegundos = 86400; // 24 horas

if (is_file($arquivoCache) && (time() - filemtime($arquivoCache)) < $ttlCacheSegundos) {
    $conteudoCache = @file_get_contents($arquivoCache);
    $dadosCache = $conteudoCache ? json_decode($conteudoCache, true) : null;
    if (is_array($dadosCache) && !empty($dadosCache['descricao'])) {
        echo json_encode([
            'sucesso'   => true,
            'descricao' => limitarDescricao150((string) $dadosCache['descricao']),
            'cache'     => true
        ]);
        exit;
    }
}

// 4. Monta o prompt focado em valor comercial e benefícios dentro de 150 caracteres
$prompt = "Você é um redator de e-commerce. Escreva uma descrição comercial persuasiva, clara e atrativa para este produto em português, " .
          "com NO MÁXIMO 150 caracteres (incluindo espaços e pontuação), idealmente entre 90 e 140 caracteres. " .
          "Destaque benefícios, conforto, desempenho ou ocasião de uso. Não repita apenas o nome e o preço do produto. " .
          "Não use aspas, emojis, hashtags, quebras de linha nem markdown.\n\n" .
          "Destaque os tamanhos disponíveis para o produto." .
          "Nome: {$nome}\n" .
          "Categoria: " . ($categoria !== '' ? $categoria : 'Não informada') . "\n" .
          "Gênero: " . ($genero !== '' ? $genero : 'Unissex') . "\n" .
          "Tamanhos: " . ($tamanhos !== '' ? $tamanhos : 'Não informados') . "\n" .
          "Preço: " . ($preco !== '' ? "R$ {$preco}" : 'Não informado') . "\n" .
          "Detalhes: " . ($detalhes !== '' ? $detalhes : 'Nenhum');

$payloadJson = json_encode([
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
]);

$modelos = [
    'gemini-3.8-flash',
    'gemini-3.7-flash',
    'gemini-3.5-flash-lite',
];

$textoGerado = null;
$modeloUsado = null;
$ultimoErro = 'Não foi possível gerar a descrição no momento.';

// 5. Percorre os modelos em ordem, fazendo retry curto em erros temporários e avançando se necessário
foreach ($modelos as $modelo) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . urlencode($apiKey);

    $maxTentativas = 2;
    $response = false;
    $erroCurl = '';
    $httpCode = 0;

    for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $erroCurl = curl_errno($ch) ? curl_error($ch) : '';
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($erroCurl === '' && $httpCode >= 200 && $httpCode < 300) {
            break;
        }

        $ehTemporario = ($erroCurl !== '') || in_array($httpCode, [429, 500, 502, 503, 504], true);
        if ($tentativa < $maxTentativas && $ehTemporario) {
            usleep(800000);
            continue;
        }

        break;
    }

    if ($erroCurl !== '') {
        $ultimoErro = 'Falha de conexão com o serviço de IA: ' . $erroCurl;
        continue;
    }

    $resultado = json_decode((string) $response, true);
    $candidatoTexto = $resultado['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($httpCode >= 200 && $httpCode < 300 && is_string($candidatoTexto) && trim($candidatoTexto) !== '') {
        $textoGerado = limitarDescricao150($candidatoTexto);
        $modeloUsado = $modelo;
        break;
    }

    $mensagemApi = $resultado['error']['message'] ?? 'Não foi possível gerar a descrição no momento.';
    $ehPico = (stripos($mensagemApi, 'high demand') !== false) || in_array($httpCode, [429, 503], true);

    $ultimoErro = $ehPico
        ? 'A IA está com pico de acessos no momento. Tente novamente em instantes ou digite manualmente (até 150 caracteres).'
        : 'Erro na IA: ' . $mensagemApi;

    // Se o erro for de indisponibilidade/restrição do modelo (400/403/404) ou pico temporário, tenta o próximo modelo
    $podeTentarProximo = $ehPico || in_array($httpCode, [400, 403, 404, 500, 502, 504], true);
    if (!$podeTentarProximo) {
        break;
    }
}

if ($textoGerado === null || $textoGerado === '') {
    echo json_encode(['erro' => $ultimoErro]);
    exit;
}

// 6. Salva no cache local para próximas consultas iguais
@file_put_contents($arquivoCache, json_encode([
    'descricao' => $textoGerado,
    'modelo'    => $modeloUsado,
    'gerado_em' => date('c')
], JSON_UNESCAPED_UNICODE));

echo json_encode([
    'sucesso'   => true,
    'descricao' => $textoGerado,
    'modelo'    => $modeloUsado,
    'cache'     => false
]);