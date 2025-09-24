<?php
header('Content-Type: application/json; charset=utf-8');

// --- CONFIGURAÇÕES DA API MELHOR ENVIO ---

// COLE AQUI O SEU TOKEN GERADO NO PAINEL DO MELHOR ENVIO
$token_melhor_envio = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYjMzYmIwMmZhYTFmOWRmMDg5Y2E1NjhiNmNjMmIxMjRmNGYxYmZkMjc3OGIzZmRkOWY4MWU0NjVmYjM4ZDg3OWQ5MjNkMTNjOTQ4YTE4M2EiLCJpYXQiOjE3NTU3MDQ2NjkuMzE5OTQ2LCJuYmYiOjE3NTU3MDQ2NjkuMzE5OTQ4LCJleHAiOjE3ODcyNDA2NjkuMzA2NDkxLCJzdWIiOiI5ZmFlMmEwNC0wMWU3LTRiZDgtYjBiZS02NmQzMzg1M2ZmNGMiLCJzY29wZXMiOlsiY2FydC1yZWFkIiwiY2FydC13cml0ZSIsImNvbXBhbmllcy1yZWFkIiwiY29tcGFuaWVzLXdyaXRlIiwiY291cG9ucy1yZWFkIiwiY291cG9ucy13cml0ZSIsIm5vdGlmaWNhdGlvbnMtcmVhZCIsIm9yZGVycy1yZWFkIiwicHJvZHVjdHMtcmVhZCIsInByb2R1Y3RzLWRlc3Ryb3kiLCJwcm9kdWN0cy13cml0ZSIsInB1cmNoYXNlcy1yZWFkIiwic2hpcHBpbmctY2FsY3VsYXRlIiwic2hpcHBpbmctY2FuY2VsIiwic2hpcHBpbmctY2hlY2tvdXQiLCJzaGlwcGluZy1jb21wYW5pZXMiLCJzaGlwcGluZy1nZW5lcmF0ZSIsInNoaXBwaW5nLXByZXZpZXciLCJzaGlwcGluZy1wcmludCIsInNoaXBwaW5nLXNoYXJlIiwic2hpcHBpbmctdHJhY2tpbmciLCJlY29tbWVyY2Utc2hpcHBpbmciLCJ0cmFuc2FjdGlvbnMtcmVhZCIsInVzZXJzLXJlYWQiLCJ1c2Vycy13cml0ZSIsIndlYmhvb2tzLXJlYWQiLCJ3ZWJob29rcy13cml0ZSIsIndlYmhvb2tzLWRlbGV0ZSIsInRkZWFsZXItd2ViaG9vayJdfQ.d-EDGV9AyXmNCNKfLYuXDS0w195ar22K0UyqnA2ZtF0eczTbONJzRvzE38hDtgC0P2wrOPZuGhtH-zOK3yFePxSHIcT5VZn43uv3SSVgkJWC7ZmN9GrRuMVqrRsNeKHEFsUDI9BSSwFyieko56yJgI4XQ4JRfOARcFMG9OTlLXa6aJpHEKuRg9cnLxPp3G5yzRVwjybqeRi--33JGGYRoUF7fJbH0eLJNh77223WYxSpDeL6rtXkqjKXpCF-kWOZyXSIE240SL9C0grP8vTGFIQdl_xQTEanfVTlCubwmL9AwAxn8nxsjFMM3QzFA1SVtxP8VB-t5ngwRDUy6FiAOmZMN-Tp488VWxFOC5wOcKu9rp0g2MsR7O_QGX0_vknBP0REqOHNXZiGEO7TMpmkHvHYhcaDaI3Hbgnnri58qDGZbRuaUGOhM6Vnw-M-VAvPmJYUyn3mMCwQKreoZ0TVY6oAPjjpLfNKhExpoYNUF0OzVw5_4G043_gEStHm4LpsJIzA3gYb928sPM8aozrU5iQ4ug2SqqfTj4a7G2N5e2BA5BXl3AaaNNaozZNJA_O1P3qYWci4qzT1ynWtqSWIYTNy7Q_oFgqIIBJqFBthth63hp_LkCiseWegaHXH0aRLMmUdKUZ3ZJr0XA0WWE5UiAUCYqVupPP-y8xSIeUCatY';

// DADOS DO SEU PACOTE E ORIGEM (CONFORME VOCÊ INFORMOU)
$cep_origem = '01022070';
$peso_padrao_kg = 1;      // 1 kg
$altura_padrao_cm = 5;
$largura_padrao_cm = 28;
$comprimento_padrao_cm = 20;

// --- LÓGICA DA API ---

$cep_destino = preg_replace('/[^0-9]/', '', $_GET['cep'] ?? '');

if (strlen($cep_destino) !== 8) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'CEP de destino inválido.']);
    exit();
}

// Prepara os dados no formato que a API do Melhor Envio espera (JSON)
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

// A API do Melhor Envio usa o ambiente de "sandbox" para testes e o de produção para cotações reais.
// Usaremos o de produção diretamente.
$url_api = 'https://www.melhorenvio.com.br/api/v2/me/shipment/calculate';

// Inicia a chamada cURL (padrão para chamadas de API complexas em PHP)
$ch = curl_init($url_api);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token_melhor_envio,
    'User-Agent: ' . 'VTN Baterias (seu-email-de-contato@dominio.com.br)' // É uma boa prática se identificar
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Trata a resposta da API
if ($http_code !== 200 || $response === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível se conectar à API de fretes.']);
    exit();
}

$data = json_decode($response, true);

if (isset($data['error']) || isset($data['errors'])) {
    $error_message = $data['error'] ?? $data['errors'][0]['error'] ?? 'Erro desconhecido na cotação.';
    echo json_encode(['sucesso' => false, 'mensagem' => $error_message]);
    exit();
}

// Formata os resultados para o nosso frontend
$resultados = [];
foreach ($data as $opcao) {
    // A API pode retornar opções inválidas, então verificamos se elas têm um preço.
    if (isset($opcao['price'])) {
        $resultados[] = [
            'tipo' => $opcao['name'], // Ex: "SEDEX", "PAC", "Jadlog .Package"
            'prazo' => $opcao['delivery_time'],
            'valor' => number_format($opcao['price'], 2, ',', '.'),
        ];
    }
}

echo json_encode(['sucesso' => true, 'opcoes' => $resultados]);
?>