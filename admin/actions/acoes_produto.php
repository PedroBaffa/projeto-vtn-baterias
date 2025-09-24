<?php
// Arquivo: admin/actions/acoes_produto.php (Versão Final Completa com Todas as Ações)

// --- CONFIGURAÇÃO INICIAL E DE ERROS ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => 'Erro fatal no servidor: ' . $error['message']]);
        exit();
    }
});

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Sessão expirada.']);
    exit();
}

require_once '../config.php';

// --- ROTAS DE AÇÕES ---
$input_data = json_decode(file_get_contents('php://input'), true);
$acao = $input_data['acao'] ?? $_POST['acao'] ?? $_GET['acao'] ?? '';

// Define o cabeçalho como JSON para ações que retornam JSON
if (in_array($acao, ['analisar_csv', 'importar_linha_csv', 'salvar_galeria_produto'])) {
    header('Content-Type: application/json; charset=utf-8');
}

switch ($acao) {
    case 'analisar_csv':
        analisar_csv($conn);
        break;
    case 'importar_linha_csv':
        importar_linha_csv($conn, $input_data);
        break;
    case 'adicionar':
    case 'editar':
        salvar_produto($conn, $acao); // Chamada para a função que salva/edita
        break;
    case 'salvar_galeria_produto':
        salvar_galeria_produto($conn);
        break;
    case 'deletar':
        deletar_produto($conn, (int)($_GET['id'] ?? 0));
        break;
    case 'deletar_massa':
        deletar_massa($conn, $_POST['ids'] ?? []);
        break;
    case 'toggle_stock':
        toggle_stock($conn, (int)($_GET['id'] ?? 0));
        break;
    case 'editar_preco':
        editar_preco($conn, (int)($_POST['id'] ?? 0), $_POST['price'] ?? '0');
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida fornecida: ' . htmlspecialchars($acao)]);
        break;
}

// --- FUNÇÕES DE LÓGICA ---

/**
 * Adiciona ou Edita um produto vindo do form_produto.php.
 */
function salvar_produto($conn, $acao)
{
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price_str = trim($_POST['price'] ?? '0');
    $price = (float)str_replace(['.', ','], ['', '.'], $price_str);
    $capacity = (int)($_POST['capacity'] ?? 0);
    $condicao = trim($_POST['condicao'] ?? 'novo');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($title) || empty($sku) || empty($brand)) {
        header("Location: ../forms/form_produto.php?id=$id&err=Título, SKU e Marca são obrigatórios.");
        exit();
    }

    $conn->beginTransaction();
    try {
        if ($acao == 'adicionar') {
            $sql = "INSERT INTO produtos (title, sku, brand, price, capacity, condicao, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$title, $sku, $brand, $price, $capacity, $condicao, $descricao]);
            $produto_id = $conn->lastInsertId();
        } else { // editar
            $sql = "UPDATE produtos SET title=?, sku=?, brand=?, price=?, capacity=?, condicao=?, descricao=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$title, $sku, $brand, $price, $capacity, $condicao, $descricao, $id]);
            $produto_id = $id;
        }

        // A lógica de imagens é idêntica à da galeria, então a reutilizamos aqui
        salvar_imagens_do_produto($conn, $produto_id);

        $conn->commit();
        header("Location: ../dashboard.php?msg=Produto salvo com sucesso!");
    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->errorInfo[1] == 1062) { // Erro de SKU duplicado
            header("Location: ../forms/form_produto.php?id=$id&err=O SKU '$sku' já está em uso por outro produto.");
        } else {
            header("Location: ../forms/form_produto.php?id=$id&err=Erro no banco de dados: " . urlencode($e->getMessage()));
        }
    }
    exit();
}


/**
 * Função auxiliar para salvar imagens (usada por salvar_produto e salvar_galeria_produto).
 */
function salvar_imagens_do_produto($conn, $produto_id)
{
    // 1. Apagar imagens marcadas para remoção
    if (!empty($_POST['imagens_removidas'])) {
        $imagens_para_apagar = json_decode($_POST['imagens_removidas']);
        if (is_array($imagens_para_apagar) && !empty($imagens_para_apagar)) {
            $placeholders = implode(',', array_fill(0, count($imagens_para_apagar), '?'));
            $stmt_delete = $conn->prepare("DELETE FROM produto_imagens WHERE id IN ($placeholders) AND produto_id = ?");
            $params = array_merge($imagens_para_apagar, [$produto_id]);
            $stmt_delete->execute($params);
        }
    }

    // 2. Fazer upload de novas imagens do computador
    $novas_imagens_upload_ids = [];
    if (isset($_FILES['novas_imagens']) && !empty($_FILES['novas_imagens']['name'][0])) {
        $target_dir = "../../assets/img/products/";
        foreach ($_FILES['novas_imagens']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['novas_imagens']['error'][$key] == UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['novas_imagens']['name'][$key]);
                $file_name = uniqid('prod_' . $produto_id . '_') . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $original_name);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_path = "assets/img/products/" . $file_name;
                    $stmt_insert = $conn->prepare("INSERT INTO produto_imagens (produto_id, image_path) VALUES (?, ?)");
                    $stmt_insert->execute([$produto_id, $image_path]);
                    $novas_imagens_upload_ids[] = $conn->lastInsertId();
                }
            }
        }
    }

    // 3. Atualizar a ordem e inserir imagens da galeria
    if (!empty($_POST['ordem_imagens'])) {
        $ordem_array = json_decode($_POST['ordem_imagens']);
        $stmt_update_order = $conn->prepare("UPDATE produto_imagens SET ordem = :ordem WHERE id = :id AND produto_id = :pid");
        $stmt_insert_url = $conn->prepare("INSERT INTO produto_imagens (produto_id, image_path, ordem) VALUES (?, ?, ?)");

        $upload_count = 0;
        foreach ($ordem_array as $index => $item_id) {
            if (is_numeric($item_id)) {
                $stmt_update_order->execute([':ordem' => $index, ':id' => (int)$item_id, ':pid' => $produto_id]);
            } else if ($item_id === 'new-upload' && isset($novas_imagens_upload_ids[$upload_count])) {
                $stmt_update_order->execute([':ordem' => $index, ':id' => $novas_imagens_upload_ids[$upload_count], ':pid' => $produto_id]);
                $upload_count++;
            } else if (strpos($item_id, 'url:') === 0) {
                $path = substr($item_id, 4);
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM produto_imagens WHERE produto_id = ? AND image_path = ?");
                $stmt_check->execute([$produto_id, $path]);
                if ($stmt_check->fetchColumn() == 0) {
                    $stmt_insert_url->execute([$produto_id, $path, $index]);
                }
            }
        }
    }
}


function salvar_galeria_produto($conn)
{
    $produto_id = (int)($_POST['produto_id'] ?? 0);
    if (!$produto_id) {
        echo json_encode(['success' => false, 'message' => 'ID do produto inválido.']);
        exit();
    }
    $conn->beginTransaction();
    try {
        salvar_imagens_do_produto($conn, $produto_id); // Reutiliza a função principal
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Galeria atualizada com sucesso!']);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
    exit();
}

function analisar_csv($conn)
{
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload. Código: ' . ($_FILES['csv_file']['error'] ?? 'N/A')]);
        exit();
    }
    $file_path = $_FILES['csv_file']['tmp_name'];
    try {
        $file = fopen($file_path, 'r');
        if ($file === false) throw new Exception("Não foi possível abrir o arquivo CSV.");
        $header = fgetcsv($file);
        $expected_header = ['brand', 'title', 'sku', 'price', 'capacity', 'condicao', 'descricao', 'images'];
        if ($header !== $expected_header) {
            fclose($file);
            throw new Exception("Cabeçalho do CSV incorreto! A ordem exata deve ser: " . implode(',', $expected_header));
        }
        $stmt_skus = $conn->query("SELECT sku FROM produtos");
        $existing_skus = array_flip($stmt_skus->fetchAll(PDO::FETCH_COLUMN));
        $rows_data = [];
        $create_count = 0;
        $update_count = 0;
        while (($data = fgetcsv($file, 2000, ",")) !== FALSE) {
            if (count($data) < 8 || empty(trim($data[2]))) continue;
            $sku = trim($data[2]);
            $rows_data[] = [
                'brand' => strtolower(trim($data[0] ?? 'sem marca')),
                'title' => trim($data[1] ?? 'Sem Título'),
                'sku' => $sku,
                'price' => str_replace(',', '.', trim($data[3] ?? '0')),
                'capacity' => (int) trim($data[4] ?? '0'),
                'condicao' => in_array(strtolower(trim($data[5] ?? 'novo')), ['novo', 'retirado']) ? strtolower(trim($data[5])) : 'novo',
                'descricao' => trim($data[6] ?? ''),
                'images' => trim($data[7] ?? ''),
                'status' => isset($existing_skus[$sku]) ? 'update' : 'create'
            ];
            if (isset($existing_skus[$sku])) $update_count++;
            else $create_count++;
        }
        fclose($file);
        echo json_encode(['success' => true, 'summary' => ['create_count' => $create_count, 'update_count' => $update_count, 'total_count' => count($rows_data)], 'rows_data' => $rows_data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao analisar o arquivo: ' . $e->getMessage()]);
    }
    exit();
}

function importar_linha_csv($conn, $data)
{
    if (empty($data) || empty($data['sku'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados da linha inválidos.']);
        exit();
    }
    try {
        $conn->beginTransaction();
        $sql_produto = "
            INSERT INTO produtos (brand, title, sku, price, capacity, condicao, descricao, data_modificacao)
            VALUES (:brand, :title, :sku, :price, :capacity, :condicao, :descricao, NOW())
            ON DUPLICATE KEY UPDATE
                brand = VALUES(brand), title = VALUES(title), price = VALUES(price),
                capacity = VALUES(capacity), condicao = VALUES(condicao),
                descricao = VALUES(descricao), data_modificacao = NOW()";
        $stmt_produto = $conn->prepare($sql_produto);
        $stmt_produto->execute([':brand' => $data['brand'], ':title' => $data['title'], ':sku' => $data['sku'], ':price' => $data['price'], ':capacity' => $data['capacity'], ':condicao' => $data['condicao'], ':descricao' => $data['descricao']]);
        $stmt_get_id = $conn->prepare("SELECT id FROM produtos WHERE sku = :sku");
        $stmt_get_id->execute([':sku' => $data['sku']]);
        $produto_id = $stmt_get_id->fetchColumn();
        if ($produto_id) {
            $stmt_delete_images = $conn->prepare("DELETE FROM produto_imagens WHERE produto_id = :pid");
            $stmt_delete_images->execute([':pid' => $produto_id]);
            if (!empty($data['images'])) {
                $image_paths = array_map('trim', explode(',', $data['images']));
                $stmt_insert_image = $conn->prepare("INSERT INTO produto_imagens (produto_id, image_path, ordem) VALUES (?, ?, ?)");
                foreach ($image_paths as $index => $path) {
                    if (!empty($path)) $stmt_insert_image->execute([$produto_id, $path, $index]);
                }
            }
        }
        $conn->commit();
        echo json_encode(['success' => true, 'sku' => $data['sku']]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'sku' => $data['sku']]);
    }
    exit();
}

function deletar_produto($conn, $id)
{
    if ($id <= 0) {
        header("Location: ../dashboard.php?err=ID de produto inválido.");
        exit();
    }
    try {
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: ../dashboard.php?msg=Produto removido com sucesso!");
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?err=Erro ao remover produto: " . $e->getMessage());
    }
    exit();
}

function deletar_massa($conn, $ids)
{
    if (empty($ids) || !is_array($ids)) {
        header("Location: ../dashboard.php?err=Nenhum produto selecionado.");
        exit();
    }
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        header("Location: ../dashboard.php?msg=" . count($ids) . " produtos removidos com sucesso!");
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?err=Erro ao remover produtos em massa.");
    }
    exit();
}

function toggle_stock($conn, $id)
{
    if ($id <= 0) {
        header("Location: ../dashboard.php?err=ID de produto inválido.");
        exit();
    }
    try {
        $stmt = $conn->prepare("UPDATE produtos SET in_stock = NOT in_stock WHERE id = :id");
        $stmt->execute([':id' => $id]);
        unset($_GET['acao'], $_GET['id']);
        $query_string = http_build_query($_GET);
        header("Location: ../dashboard.php?" . $query_string);
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?err=Erro ao alterar status de estoque.");
    }
    exit();
}

function editar_preco($conn, $id, $price_str)
{
    if ($id <= 0) {
        header("Location: ../dashboard.php?err=ID de produto inválido.");
        exit();
    }
    $price = (float)str_replace(['.', ','], ['', '.'], $price_str);
    try {
        $stmt = $conn->prepare("UPDATE produtos SET price = :price WHERE id = :id");
        $stmt->execute([':price' => $price, ':id' => $id]);
        header("Location: ../dashboard.php?msg=Preço atualizado!");
    } catch (PDOException $e) {
        header("Location: ../dashboard.php?err=Erro ao atualizar preço.");
    }
    exit();
}
