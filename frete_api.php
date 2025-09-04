<?php

/**
 * @file
 * frete_api.php
 * API para cálculo de frete.
 * Este script serve como um proxy para a API do Melhor Envio,
 * recebendo um CEP de destino e retornando as opções de frete disponíveis.
 * ATENÇÃO: Substitua os placeholders pelas suas chaves e informações reais.
 */

// Define o cabeçalho da resposta como JSON com codificação UTF-8.
header('Content-Type: application/json; charset=utf-8');

// --- CONFIGURAÇÕES DA API MELHOR ENVIO ---

// IMPORTANTE: Substitua pela sua chave de API (token) real do Melhor Envio.
$token_melhor_envio = 'SEU_TOKEN_DO_MELHOR_ENVIO_VAI_AQUI';

// DADOS DE ORIGEM E DIMENSÕES PADRÃO DO PACOTE
$cep_origem = '01001-001'; // CEP de onde os produtos são enviados.
$peso_padrao_kg = 0.3;     // Peso padrão do pacote em kg.
$altura_padrao_cm = 5;     // Altura padrão em cm.
$largura_padrao_cm = 15;    // Largura padrão em cm.
$comprimento_padrao_cm = 20; // Comprimento padrão em cm.

// --- LÓGICA DA API ---

// Limpa o CEP de destino, removendo qualquer caractere que não seja número.
$cep_destino = preg_replace('/[^0-9]/', '', $_GET['cep'] ?? '');

// Valida se o CEP tem 8 dígitos.
if (strlen($cep_destino) !== 8) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'CEP de destino inválido.']);
    exit();
}

// Monta o corpo (payload) da requisição no formato esperado pela API do Melhor Envio.
$payload = [
    'from' => [
        'postal_code' => $cep_origem,
    ],
    'to' => [
        'postal_code' => $cep_destino,
    ],
    'package' => [
        'weight' => $peso_padrao_kg,
        'width' => $largura_padrao_cm,
        'height' => $altura_padrao_cm,
        'length' => $comprimento_padrao_cm,
    ],
];

// Endpoint da API de produção do Melhor Envio para cálculo de frete.
$url_api = 'https://www.melhorenvio.com.br/api/v2/me/shipment/calculate';

// Inicia a chamada cURL para a API externa.
$ch = curl_init($url_api);

// Configura as opções do cURL.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string.
curl_setopt($ch, CURLOPT_POST, true); // Define o método como POST.
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); // Envia os dados em formato JSON.
curl_setopt($ch, CURLOPT_HTTPHEADER, [ // Define os cabeçalhos da requisição.
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token_melhor_envio,
    // É uma boa prática se identificar no User-Agent.
    'User-Agent: ' . 'Empresa_Nome (seu-email@vtnbaterias.com.br)'
]);

// Executa a requisição e obtém a resposta e o código HTTP.
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Trata a resposta da API.
if ($http_code !== 200 || $response === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível se conectar à API de fretes.']);
    exit();
}

$data = json_decode($response, true);

// Verifica se a API do Melhor Envio retornou algum erro.
if (isset($data['error']) || isset($data['errors'])) {
    $error_message = $data['error'] ?? $data['errors'][0]['error'] ?? 'Erro desconhecido na cotação.';
    echo json_encode(['sucesso' => false, 'mensagem' => $error_message]);
    exit();
}

// Formata os resultados para serem facilmente utilizados pelo front-end.
$resultados = [];
foreach ($data as $opcao) {
    // Garante que apenas opções válidas com preço sejam adicionadas.
    if (isset($opcao['price'])) {
        $resultados[] = [
            'tipo' => $opcao['name'], // Ex: "SEDEX", "PAC", "Jadlog .Package"
            'prazo' => $opcao['delivery_time'],
            'valor' => number_format($opcao['price'], 2, ',', '.'),
        ];
    }
}

// Retorna os resultados formatados em JSON.
echo json_encode(['sucesso' => true, 'opcoes' => $resultados]);
