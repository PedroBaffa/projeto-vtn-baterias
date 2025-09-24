<?php
// Arquivo: vtn/cep_api.php

header('Content-Type: application/json; charset=utf-8');

$cep = preg_replace('/[^0-9]/', '', $_GET['cep'] ?? '');

if (strlen($cep) !== 8) {
    http_response_code(400);
    echo json_encode(['erro' => true, 'mensagem' => 'CEP inválido.']);
    exit();
}

$url = "https://viacep.com.br/ws/{$cep}/json/";

// Usa cURL para fazer a requisição, que é mais robusto
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || $response === false) {
    http_response_code(500);
    echo json_encode(['erro' => true, 'mensagem' => 'Não foi possível conectar ao serviço de CEP.']);
    exit();
}

$data = json_decode($response);

// Se o ViaCEP retornar um erro (ex: CEP não encontrado), repassa a mensagem
if (isset($data->erro) && $data->erro) {
    echo json_encode(['erro' => true, 'mensagem' => 'CEP não encontrado.']);
    exit();
}

echo json_encode($data);
