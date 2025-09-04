<?php

/**
 * @file
 * API para gerenciar as imagens dos produtos na galeria do painel de administração.
 * Lida com as ações de listar, fazer upload e apagar imagens.
 * Responde a todas as solicitações em formato JSON.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Medida de segurança: Apenas utilizadores autenticados podem aceder a esta API.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // HTTP 403: Proibido
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

// Inclui a conexão com o banco de dados.
require_once 'config.php';

// Define a ação a ser executada com base no parâmetro GET.
$action = $_GET['acao'] ?? '';
// Define o diretório onde as imagens dos produtos são armazenadas.
$products_img_dir = '../assets/img/products/';

// Garante que o diretório de upload exista. Se não existir, tenta criá-lo.
if (!is_dir($products_img_dir)) {
    mkdir($products_img_dir, 0755, true);
}

// --- AÇÃO: LISTAR IMAGENS ---
// Retorna uma lista de todas as imagens no diretório e indica se estão em uso.
if ($action === 'listar') {
    // 1. Busca no banco de dados todas as imagens que estão atualmente associadas a algum produto.
    $stmt = $conn->query("SELECT DISTINCT image_path FROM produto_imagens WHERE image_path IS NOT NULL AND image_path != ''");
    $used_images_db = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    // 2. Otimização: Transforma o array para que os caminhos das imagens sejam as chaves.
    // Isso torna a verificação 'isset($used_images[$filePath])' muito mais rápida do que usar 'in_array()'.
    $used_images = array_flip($used_images_db);

    // 3. Lê todos os arquivos do diretório de imagens.
    $files = scandir($products_img_dir);
    $images = [];
    foreach ($files as $file) {
        $filePath = 'assets/img/products/' . $file;
        // Garante que é um arquivo válido antes de adicionar à lista.
        if (is_file($products_img_dir . $file)) {
            $images[] = [
                'path' => $filePath,
                'in_use' => isset($used_images[$filePath]) // Verifica se a imagem está na lista de imagens em uso.
            ];
        }
    }
    // Retorna a lista de imagens em JSON, revertendo a ordem para mostrar as mais recentes primeiro.
    echo json_encode(['success' => true, 'images' => array_reverse($images)]);
    exit();
}

// --- AÇÃO: UPLOAD DE IMAGENS ---
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['images'])) {
        $uploaded_files = [];
        // Itera sobre cada imagem enviada no formulário.
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $file_name = basename($_FILES['images']['name'][$i]);
            $target_file = $products_img_dir . $file_name;
            // Move o arquivo temporário para o diretório de destino.
            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
                $uploaded_files[] = 'assets/img/products/' . $file_name;
            }
        }
        echo json_encode(['success' => true, 'message' => 'Imagens enviadas com sucesso!', 'files' => $uploaded_files]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum ficheiro recebido.']);
    }
    exit();
}

// --- AÇÃO: APAGAR UMA IMAGEM ---
if ($action === 'apagar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe os dados em formato JSON enviados pelo JavaScript.
    $data = json_decode(file_get_contents('php://input'), true);
    $image_path = $data['imagem'] ?? '';

    if (empty($image_path)) {
        echo json_encode(['success' => false, 'message' => 'Caminho da imagem não fornecido.']);
        exit();
    }

    // Medida de segurança: Previne "Directory Traversal".
    // Garante que o arquivo a ser apagado está realmente dentro do diretório de imagens permitido.
    $real_base_path = realpath($products_img_dir);
    $real_image_path = realpath('../' . $image_path);

    if ($real_image_path && strpos($real_image_path, $real_base_path) === 0) {
        // Se o caminho for seguro, tenta apagar o arquivo.
        if (unlink($real_image_path)) {
            echo json_encode(['success' => true, 'message' => 'Imagem apagada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Não foi possível apagar a imagem.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Operação inválida ou ficheiro não encontrado.']);
    }
    exit();
}

// --- AÇÃO: APAGAR MÚLTIPLAS IMAGENS ---
if ($action === 'apagar_massa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $images_to_delete = $data['imagens'] ?? [];

    if (empty($images_to_delete)) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma imagem selecionada.']);
        exit();
    }

    $deleted_count = 0;
    $errors = [];
    $real_base_path = realpath($products_img_dir);

    // Itera sobre cada imagem a ser apagada, aplicando a mesma verificação de segurança.
    foreach ($images_to_delete as $image_path) {
        $real_image_path = realpath('../' . $image_path);
        if ($real_image_path && strpos($real_image_path, $real_base_path) === 0) {
            if (unlink($real_image_path)) {
                $deleted_count++;
            } else {
                $errors[] = $image_path; // Armazena os caminhos que falharam.
            }
        }
    }

    if ($deleted_count > 0 && empty($errors)) {
        echo json_encode(['success' => true, 'message' => "$deleted_count imagens apagadas com sucesso!"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Falha ao apagar algumas imagens.", 'failed_files' => $errors]);
    }
    exit();
}

// Resposta padrão se nenhuma ação válida for encontrada.
echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
