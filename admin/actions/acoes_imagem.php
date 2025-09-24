<?php
// Arquivo: admin/actions/acoes_imagem.php (Caminhos Corrigidos)

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

require_once '../config.php'; // CAMINHO ATUALIZADO

$action = $_POST['acao'] ?? $_GET['acao'] ?? '';
$products_img_dir = '../../assets/img/products/'; // CAMINHO ATUALIZADO

if (!is_dir($products_img_dir)) {
    mkdir($products_img_dir, 0755, true);
}

// Ação para LISTAR todas as imagens
if ($action === 'listar') {
    $stmt = $conn->query("SELECT DISTINCT image_path FROM produto_imagens WHERE image_path IS NOT NULL AND image_path != ''");
    $used_images_db = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $used_images = array_flip($used_images_db);

    $files = scandir($products_img_dir);
    $images = [];
    foreach ($files as $file) {
        $filePath = 'assets/img/products/' . $file;
        if (is_file($products_img_dir . $file)) {
            $images[] = [
                'path' => $filePath,
                'in_use' => isset($used_images[$filePath])
            ];
        }
    }
    echo json_encode(['success' => true, 'images' => array_reverse($images)]);
    exit();
}

// Ação para FAZER UPLOAD de novas imagens
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['images'])) {
        $uploaded_files = [];
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            $file_name = basename($_FILES['images']['name'][$i]);
            $target_file = $products_img_dir . $file_name;
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

// Ação para APAGAR uma imagem
if ($action === 'apagar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $image_path = $data['imagem'] ?? '';

    if (empty($image_path)) {
        echo json_encode(['success' => false, 'message' => 'Caminho da imagem não fornecido.']);
        exit();
    }

    // CAMINHO ATUALIZADO para encontrar a imagem
    $real_image_path = realpath('../../' . $image_path);
    $real_base_path = realpath($products_img_dir);

    if ($real_image_path && strpos($real_image_path, $real_base_path) === 0) {
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

// Ação para APAGAR MÚLTIPLAS IMAGENS
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

    foreach ($images_to_delete as $image_path) {
        // CAMINHO ATUALIZADO para encontrar a imagem
        $real_image_path = realpath('../../' . $image_path);
        if ($real_image_path && strpos($real_image_path, $real_base_path) === 0) {
            if (unlink($real_image_path)) {
                $deleted_count++;
            } else {
                $errors[] = $image_path;
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

// AÇÃO PARA SALVAR/ATUALIZAR IMAGENS DE UM PRODUTO ESPECÍFICO
if ($_POST['acao'] ?? '' === 'salvar_imagens_produto') {
    if (empty($_POST['produto_id'])) {
        header("Location: ../galeria.php?err=ID do produto não fornecido."); // CAMINHO ATUALIZADO
        exit();
    }
    $produto_id = (int)$_POST['produto_id'];

    $conn->beginTransaction();
    try {
        if (!empty($_POST['imagens_removidas'])) {
            $imagens_para_apagar = json_decode($_POST['imagens_removidas']);
            if (is_array($imagens_para_apagar) && !empty($imagens_para_apagar)) {
                $placeholders = implode(',', array_fill(0, count($imagens_para_apagar), '?'));
                $stmt_get_paths = $conn->prepare("SELECT image_path FROM produto_imagens WHERE id IN ($placeholders)");
                $stmt_get_paths->execute($imagens_para_apagar);
                $paths_to_delete = $stmt_get_paths->fetchAll(PDO::FETCH_COLUMN, 0);
                foreach ($paths_to_delete as $path) {
                    if (file_exists('../../' . $path)) { // CAMINHO ATUALIZADO
                        unlink('../../' . $path);
                    }
                }
                $stmt_delete = $conn->prepare("DELETE FROM produto_imagens WHERE id IN ($placeholders)");
                $stmt_delete->execute($imagens_para_apagar);
            }
        }

        $novas_imagens_ids = [];
        if (isset($_FILES['novas_imagens']) && !empty($_FILES['novas_imagens']['name'][0])) {
            $target_dir = "../../assets/img/products/"; // CAMINHO ATUALIZADO
            foreach ($_FILES['novas_imagens']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['novas_imagens']['error'][$key] == UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES['novas_imagens']['name'][$key]);
                    $file_name = uniqid('prod_' . $produto_id . '_') . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $original_name);
                    $target_file = $target_dir . $file_name;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $image_path = "assets/img/products/" . $file_name;
                        $stmt_insert = $conn->prepare("INSERT INTO produto_imagens (produto_id, image_path) VALUES (?, ?)");
                        $stmt_insert->execute([$produto_id, $image_path]);
                        $novas_imagens_ids[] = $conn->lastInsertId();
                    }
                }
            }
        }

        if (!empty($_POST['ordem_imagens'])) {
            $ordem_array = json_decode($_POST['ordem_imagens']);
            $stmt_update_order = $conn->prepare("UPDATE produto_imagens SET ordem = :ordem WHERE id = :id");
            $novas_imagens_count = 0;
            foreach ($ordem_array as $index => $item_id) {
                if (is_numeric($item_id)) {
                    $stmt_update_order->execute([':ordem' => $index, ':id' => (int)$item_id]);
                } elseif ($item_id === 'new' && isset($novas_imagens_ids[$novas_imagens_count])) {
                    $stmt_update_order->execute([':ordem' => $index, ':id' => $novas_imagens_ids[$novas_imagens_count]]);
                    $novas_imagens_count++;
                }
            }
        }

        $conn->commit();
        header("Location: ../galeria.php?msg=Imagens atualizadas com sucesso!"); // CAMINHO ATUALIZADO
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../forms/form_imagem.php?id={$produto_id}&err=Erro ao salvar: " . urlencode($e->getMessage())); // CAMINHO ATUALIZADO
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
