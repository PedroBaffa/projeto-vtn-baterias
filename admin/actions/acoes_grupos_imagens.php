<?php
// Arquivo: admin/actions/acoes_grupos_imagens.php (Lógica de apagar corrigida)

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

require_once '../config.php';

$acao = $_POST['acao'] ?? '';

// --- AÇÃO: CRIAR NOVO GRUPO ---
if ($acao === 'criar_grupo') {
    $nome = trim($_POST['nome'] ?? 'Novo Grupo');
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'O nome do grupo não pode ser vazio.']);
        exit();
    }
    try {
        $stmt = $conn->prepare("INSERT INTO galeria_grupos (nome) VALUES (?)");
        $stmt->execute([$nome]);
        echo json_encode(['success' => true, 'message' => 'Grupo criado com sucesso!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar o grupo no banco de dados.']);
    }
    exit();
}

// --- AÇÃO: RENOMEAR GRUPO ---
if ($acao === 'renomear_grupo') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    if ($id > 0 && !empty($nome)) {
        $stmt = $conn->prepare("UPDATE galeria_grupos SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    }
    exit();
}

// --- AÇÃO: APAGAR GRUPO ---
if ($acao === 'apagar_grupo') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->beginTransaction();
        try {
            // 1. Busca todos os caminhos de imagem ANTES de apagar as referências
            $stmt_paths = $conn->prepare("SELECT image_path FROM galeria_imagens WHERE grupo_id = ?");
            $stmt_paths->execute([$id]);
            $paths_in_group = $stmt_paths->fetchAll(PDO::FETCH_COLUMN, 0);

            // 2. Apaga o grupo (e as referências em `galeria_imagens` via ON DELETE CASCADE)
            $stmt_delete_group = $conn->prepare("DELETE FROM galeria_grupos WHERE id = ?");
            $stmt_delete_group->execute([$id]);

            // 3. Verifica cada imagem para ver se ainda está em uso por algum produto
            $stmt_check_usage = $conn->prepare("SELECT COUNT(*) FROM produto_imagens WHERE image_path = ?");
            foreach ($paths_in_group as $path) {
                $stmt_check_usage->execute([$path]);
                $usage_count = $stmt_check_usage->fetchColumn();

                // Se não estiver em uso por NENHUM produto, apaga o ficheiro físico
                if ($usage_count == 0 && file_exists('../../' . $path)) {
                    unlink('../../' . $path);
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Grupo apagado com sucesso.']);
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao apagar o grupo.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de grupo inválido.']);
    }
    exit();
}

// --- AÇÃO: OBTER URLS DE UM GRUPO ---
if ($acao === 'obter_urls') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT image_path FROM galeria_imagens WHERE grupo_id = ? ORDER BY ordem ASC");
        $stmt->execute([$id]);
        $urls = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        echo json_encode(['success' => true, 'urls' => implode(', ', $urls)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de grupo inválido.']);
    }
    exit();
}

// --- AÇÃO: UPLOAD DE IMAGENS ---
if ($acao === 'upload_imagens') {
    $grupo_id = (int)($_POST['grupo_id'] ?? 0);
    if ($grupo_id > 0 && isset($_FILES['novas_imagens'])) {
        $target_dir = "../../assets/img/products/";
        $uploaded_images = [];
        $conn->beginTransaction();
        try {
            $stmt_max_order = $conn->prepare("SELECT MAX(ordem) FROM galeria_imagens WHERE grupo_id = ?");
            $stmt_max_order->execute([$grupo_id]);
            $ordem_atual = (int)$stmt_max_order->fetchColumn();
            foreach ($_FILES['novas_imagens']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['novas_imagens']['error'][$key] == UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES['novas_imagens']['name'][$key]);
                    $file_name = uniqid('grupofixo_' . $grupo_id . '_') . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $original_name);
                    $target_file = $target_dir . $file_name;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $image_path = "assets/img/products/" . $file_name;
                        $ordem_atual++;
                        $stmt_insert = $conn->prepare("INSERT INTO galeria_imagens (grupo_id, image_path, ordem) VALUES (?, ?, ?)");
                        $stmt_insert->execute([$grupo_id, $image_path, $ordem_atual]);
                        $new_id = $conn->lastInsertId();
                        $uploaded_images[] = ['id' => $new_id, 'path' => $image_path];
                    }
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'images' => $uploaded_images]);
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de banco de dados durante o upload.']);
        }
        exit();
    }
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo ou ID de grupo inválido.']);
    exit();
}

// --- AÇÃO: APAGAR IMAGEM INDIVIDUAL DE UM GRUPO ---
if ($acao === 'apagar_imagem_grupo') {
    $image_id = (int)($_POST['image_id'] ?? 0);
    if ($image_id > 0) {
        $conn->beginTransaction();
        try {
            // 1. Pega o caminho do ficheiro ANTES de apagar a referência
            $stmt_path = $conn->prepare("SELECT image_path FROM galeria_imagens WHERE id = ?");
            $stmt_path->execute([$image_id]);
            $path = $stmt_path->fetchColumn();

            // 2. Apaga a referência da imagem no grupo
            $stmt_delete_ref = $conn->prepare("DELETE FROM galeria_imagens WHERE id = ?");
            $stmt_delete_ref->execute([$image_id]);

            // 3. Verifica se o ficheiro ainda está a ser usado por algum PRODUTO
            if ($path) {
                $stmt_check_usage = $conn->prepare("SELECT COUNT(*) FROM produto_imagens WHERE image_path = ?");
                $stmt_check_usage->execute([$path]);
                $usage_count = $stmt_check_usage->fetchColumn();

                // Se não estiver em uso, apaga o ficheiro físico
                if ($usage_count == 0 && file_exists('../../' . $path)) {
                    unlink('../../' . $path);
                }
            }
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao apagar imagem do grupo.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de imagem inválido.']);
    }
    exit();
}

// --- AÇÃO: SALVAR ORDEM DAS IMAGENS ---
if ($acao === 'salvar_ordem') {
    $ordem_imagens = json_decode($_POST['ordem'] ?? '[]');
    if (empty($ordem_imagens) || !is_array($ordem_imagens)) {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ordem de imagens recebida.']);
        exit();
    }
    try {
        $conn->beginTransaction();
        $stmt_update = $conn->prepare("UPDATE galeria_imagens SET ordem = ? WHERE id = ?");
        foreach ($ordem_imagens as $index => $imagem_id) {
            $stmt_update->execute([$index, (int)$imagem_id]);
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Ordem das imagens salva com sucesso!']);
    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar a ordem no banco de dados.']);
    }
    exit();
}


http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Ação desconhecida ou dados inválidos.']);
    