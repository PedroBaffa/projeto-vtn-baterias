<?php

/**
 * @file
 * Processa todas as ações de back-end relacionadas aos produtos.
 * Inclui adicionar, editar, deletar, importar via CSV, alterar estoque e mais.
 * Este script não gera HTML, apenas processa dados e redireciona o usuário.
 */

session_start();
// Medida de segurança: Garante que apenas usuários logados possam executar estas ações.
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

// Inclui o arquivo de configuração para conexão com o banco de dados.
require_once 'config.php';

// --- FUNÇÕES DE AJUDA (HELPERS) ---

/**
 * Limpa e padroniza uma string de SKU.
 * Remove espaços, caracteres especiais e substitui separadores por hífens.
 *
 * @param string $sku O SKU original.
 * @return string O SKU sanitizado.
 */
function sanitize_sku($sku)
{
    // Remove espaços em branco no início e no fim.
    $sku = trim($sku);
    // Substitui espaços, barras e contrabarras por um hífen.
    $sku = preg_replace('/[\s\/\\\]+/', '-', $sku);
    // Remove quaisquer caracteres que não sejam letras, números, hífens ou underscores.
    $sku = preg_replace('/[^A-Za-z0-9\-_]/', '', $sku);
    return $sku;
}

/**
 * Comprime e redimensiona uma imagem antes de salvá-la.
 * Melhora a performance do site ao otimizar as imagens.
 * ATENÇÃO: Esta função não está sendo usada no fluxo atual, mas é uma excelente
 * prática para otimização e pode ser integrada no futuro.
 *
 * @param string $ficheiro_temporario Caminho do arquivo de imagem temporário.
 * @param string $caminho_destino Onde salvar a nova imagem.
 * @param int $largura_maxima A largura máxima da imagem.
 * @param int $qualidade A qualidade do JPEG (0-100).
 * @return string|false O caminho do novo arquivo ou false em caso de erro.
 */
function comprimir_e_salvar_imagem($ficheiro_temporario, $caminho_destino, $largura_maxima = 1024, $qualidade = 85)
{
    $info_imagem = @getimagesize($ficheiro_temporario);
    if (!$info_imagem) return false;

    $mime_type = $info_imagem['mime'];

    switch ($mime_type) {
        case 'image/jpeg':
            $imagem_original = @imagecreatefromjpeg($ficheiro_temporario);
            break;
        case 'image/png':
            $imagem_original = @imagecreatefrompng($ficheiro_temporario);
            break;
        case 'image/gif':
            $imagem_original = @imagecreatefromgif($ficheiro_temporario);
            break;
        default:
            return false;
    }

    if (!$imagem_original) return false;

    $largura_original = imagesx($imagem_original);
    $altura_original = imagesy($imagem_original);
    $ratio = $largura_original / $altura_original;

    if ($largura_original <= $largura_maxima) {
        $largura_nova = $largura_original;
        $altura_nova = $altura_original;
    } else {
        $largura_nova = $largura_maxima;
        $altura_nova = $largura_maxima / $ratio;
    }

    $imagem_redimensionada = imagecreatetruecolor($largura_nova, $altura_nova);

    if ($mime_type == 'image/png') {
        imagealphablending($imagem_redimensionada, false);
        imagesavealpha($imagem_redimensionada, true);
        $transparent = imagecolorallocatealpha($imagem_redimensionada, 255, 255, 255, 127);
        imagefilledrectangle($imagem_redimensionada, 0, 0, $largura_nova, $altura_nova, $transparent);
    }

    imagecopyresampled($imagem_redimensionada, $imagem_original, 0, 0, 0, 0, $largura_nova, $altura_nova, $largura_original, $altura_original);

    $caminho_destino_jpg = preg_replace('/\.[^.]+$/', '.jpg', $caminho_destino);
    $sucesso = imagejpeg($imagem_redimensionada, $caminho_destino_jpg, $qualidade);

    imagedestroy($imagem_original);
    imagedestroy($imagem_redimensionada);

    return $sucesso ? $caminho_destino_jpg : false;
}

/**
 * Roteamento de Ações
 * Determina qual operação deve ser executada com base no parâmetro 'acao'.
 */
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// --- AÇÃO: ADICIONAR PRODUTO ---
if ($acao == 'adicionar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação inicial dos campos obrigatórios.
    if (empty($_POST['title']) || empty($_POST['sku'])) {
        header("Location: form_produto.php?err=Título e SKU são obrigatórios.");
        exit();
    }

    // Inicia uma transação: ou todas as queries funcionam, ou nenhuma é executada.
    // Isso garante a integridade dos dados, evitando um produto sem imagem ou vice-versa.
    $conn->beginTransaction();
    try {
        $sku_sanitizado = sanitize_sku($_POST['sku']);

        // 1. Insere os dados básicos do produto.
        $stmt_produto = $conn->prepare("INSERT INTO produtos (brand, title, sku, price, capacity, condicao, descricao) VALUES (:brand, :title, :sku, :price, :capacity, :condicao, :descricao)");
        $stmt_produto->execute([
            ':brand' => $_POST['brand'],
            ':title' => $_POST['title'],
            ':sku' => $sku_sanitizado,
            ':price' => str_replace(',', '.', str_replace('.', '', $_POST['price'])), // Formata o preço para o padrão do DB.
            ':capacity' => empty($_POST['capacity']) ? 0 : (int)$_POST['capacity'],
            ':condicao' => $_POST['condicao'],
            ':descricao' => $_POST['descricao'],
        ]);
        $produto_id = $conn->lastInsertId(); // Pega o ID do produto recém-criado.

        // 2. Processa e salva as novas imagens, se houver.
        $novas_imagens_ids = [];
        if (isset($_FILES['novas_imagens']) && !empty($_FILES['novas_imagens']['name'][0])) {
            $target_dir = "../assets/img/products/";
            foreach ($_FILES['novas_imagens']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['novas_imagens']['error'][$key] == UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES['novas_imagens']['name'][$key]);
                    // Cria um nome de arquivo único para evitar sobreposições.
                    $file_name = uniqid('prod_' . $produto_id . '_') . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $original_name);
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $image_path = "assets/img/products/" . $file_name;
                        // Insere o caminho da imagem na tabela de imagens, associando ao produto.
                        $stmt_insert = $conn->prepare("INSERT INTO produto_imagens (produto_id, image_path) VALUES (?, ?)");
                        $stmt_insert->execute([$produto_id, $image_path]);
                        $novas_imagens_ids[] = $conn->lastInsertId();
                    }
                }
            }
        }

        // 3. Atualiza a ordem das imagens com base no que foi organizado no front-end.
        if (!empty($_POST['ordem_imagens']) && !empty($novas_imagens_ids)) {
            $ordem_array = json_decode($_POST['ordem_imagens']);
            $stmt_update_order = $conn->prepare("UPDATE produto_imagens SET ordem = :ordem WHERE id = :id");
            $novas_imagens_count = 0;
            foreach ($ordem_array as $index => $item_id) {
                // 'new' é um placeholder para imagens recém-adicionadas.
                if ($item_id === 'new' && isset($novas_imagens_ids[$novas_imagens_count])) {
                    $stmt_update_order->execute([':ordem' => $index, ':id' => $novas_imagens_ids[$novas_imagens_count]]);
                    $novas_imagens_count++;
                }
            }
        }
        // Se tudo deu certo, confirma as alterações no banco de dados.
        $conn->commit();
        header("Location: dashboard.php?msg=Produto adicionado com sucesso!");
        exit();
    } catch (Exception $e) {
        // Se algo deu errado, desfaz todas as alterações.
        $conn->rollBack();
        header("Location: form_produto.php?err=Erro ao adicionar produto: " . $e->getMessage());
        exit();
    }
}

// --- AÇÃO: EDITAR PRODUTO ---
if ($acao == 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id']) || empty($_POST['title'])) {
        header("Location: form_produto.php?id={$_POST['id']}&err=Dados insuficientes.");
        exit();
    }
    $produto_id = (int) $_POST['id'];

    $conn->beginTransaction();
    try {
        // 1. Atualiza os dados textuais do produto.
        $sku_sanitizado = sanitize_sku($_POST['sku']);
        $sql = "UPDATE produtos SET brand = :brand, title = :title, sku = :sku, capacity = :capacity, condicao = :condicao, descricao = :descricao";
        $params = [
            ':brand' => $_POST['brand'],
            ':title' => $_POST['title'],
            ':sku' => $sku_sanitizado,
            ':capacity' => empty($_POST['capacity']) ? 0 : (int)$_POST['capacity'],
            ':condicao' => $_POST['condicao'],
            ':descricao' => $_POST['descricao'],
            ':id' => $produto_id
        ];

        // O preço só é atualizado se não estiver em promoção (lógica no form_produto.php).
        if (isset($_POST['price'])) {
            $sql .= ", price = :price";
            $params[':price'] = str_replace(',', '.', str_replace('.', '', $_POST['price']));
        }

        $sql .= " WHERE id = :id";
        $stmt_produto = $conn->prepare($sql);
        $stmt_produto->execute($params);

        // 2. Remove imagens marcadas para exclusão.
        if (!empty($_POST['imagens_removidas'])) {
            $imagens_para_apagar = json_decode($_POST['imagens_removidas']);
            if (is_array($imagens_para_apagar) && !empty($imagens_para_apagar)) {
                $placeholders = implode(',', array_fill(0, count($imagens_para_apagar), '?'));
                // Pega os caminhos dos arquivos para apagar do servidor.
                $stmt_get_paths = $conn->prepare("SELECT image_path FROM produto_imagens WHERE id IN ($placeholders)");
                $stmt_get_paths->execute($imagens_para_apagar);
                $paths_to_delete = $stmt_get_paths->fetchAll(PDO::FETCH_COLUMN, 0);
                foreach ($paths_to_delete as $path) {
                    if (file_exists('../' . $path)) {
                        unlink('../' . $path); // Apaga o arquivo físico.
                    }
                }
                // Apaga os registros do banco de dados.
                $stmt_delete = $conn->prepare("DELETE FROM produto_imagens WHERE id IN ($placeholders)");
                $stmt_delete->execute($imagens_para_apagar);
            }
        }

        // 3. Adiciona novas imagens (mesma lógica da ação 'adicionar').
        $novas_imagens_ids = [];
        if (isset($_FILES['novas_imagens']) && !empty($_FILES['novas_imagens']['name'][0])) {
            $target_dir = "../assets/img/products/";
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

        // 4. Atualiza a ordem de todas as imagens (novas e existentes).
        if (!empty($_POST['ordem_imagens'])) {
            $ordem_array = json_decode($_POST['ordem_imagens']);
            $stmt_update_order = $conn->prepare("UPDATE produto_imagens SET ordem = :ordem WHERE id = :id");
            $novas_imagens_count = 0;
            foreach ($ordem_array as $index => $item_id) {
                if (is_numeric($item_id)) { // Imagem existente.
                    $stmt_update_order->execute([':ordem' => $index, ':id' => (int) $item_id]);
                } else if ($item_id === 'new' && isset($novas_imagens_ids[$novas_imagens_count])) { // Imagem nova.
                    $stmt_update_order->execute([':ordem' => $index, ':id' => $novas_imagens_ids[$novas_imagens_count]]);
                    $novas_imagens_count++;
                }
            }
        }

        $conn->commit();
        header("Location: dashboard.php?msg=Produto atualizado com sucesso!");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: form_produto.php?acao=editar&id={$produto_id}&err=Erro ao atualizar: " . $e->getMessage());
        exit();
    }
}

// --- AÇÃO: EDITAR APENAS O PREÇO (EDIÇÃO RÁPIDA NO DASHBOARD) ---
if ($acao == 'editar_preco' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id']) || !isset($_POST['price'])) {
        header("Location: dashboard.php?err=Dados insuficientes.");
        exit();
    }
    $id = (int) $_POST['id'];
    // Formata o preço vindo do formulário para o formato do banco de dados (ex: 1234.56).
    $price = str_replace('.', '', $_POST['price']);
    $price = str_replace(',', '.', $price);

    if (!is_numeric($price)) {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php') . "&err=Preço inválido.");
        exit();
    }
    try {
        $stmt = $conn->prepare("UPDATE produtos SET price = :price WHERE id = :id");
        $stmt->execute([':price' => $price, ':id' => $id]);
        // Redireciona de volta para a página de onde veio a requisição (preserva os filtros).
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
        exit();
    } catch (PDOException $e) {
        header("Location: dashboard.php?err=Erro ao atualizar o preço.");
        exit();
    }
}

// --- AÇÃO: IMPORTAR PRODUTOS VIA CSV ---
if ($acao == 'importar_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        // Pula a primeira linha (cabeçalho).
        fgetcsv($handle, 1000, ",");

        $conn->beginTransaction();
        try {
            // A query usa "ON DUPLICATE KEY UPDATE" para uma funcionalidade poderosa:
            // Se um produto com o mesmo SKU já existir, ele será atualizado.
            // Se não existir, será criado um novo.
            $stmt = $conn->prepare(
                "INSERT INTO produtos (brand, title, sku, price, capacity, condicao, descricao)
                 VALUES (:brand, :title, :sku, :price, :capacity, :condicao, :descricao)
                 ON DUPLICATE KEY UPDATE
                 title=:title, brand=:brand, price=:price, capacity=:capacity, condicao=:condicao, descricao=:descricao"
            );

            // Lê o arquivo CSV linha por linha.
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Sanitiza e formata cada dado da linha.
                $brand = strtolower(trim($data[0] ?? ''));
                $title = trim($data[1] ?? '');
                $sku = sanitize_sku(trim($data[2] ?? ''));
                $price = str_replace(',', '.', trim($data[3] ?? '0'));
                $capacity = (int) trim($data[4] ?? '0');
                $condicao = strtolower(trim($data[5] ?? 'novo'));
                if ($condicao !== 'novo' && $condicao !== 'retirado') {
                    $condicao = 'novo';
                }
                $descricao = trim($data[6] ?? null);

                // Pula linhas que não tenham os dados essenciais.
                if (empty($brand) || empty($title) || empty($sku)) {
                    continue;
                }
                $stmt->execute([
                    ':brand' => $brand,
                    ':title' => $title,
                    ':sku' => $sku,
                    ':price' => $price,
                    ':capacity' => $capacity,
                    ':condicao' => $condicao,
                    ':descricao' => $descricao
                ]);
            }
            $conn->commit();
            header("Location: dashboard.php?msg=Importação concluída com sucesso!");
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: importar.php?err=Erro durante a importação: " . $e->getMessage());
        }
        fclose($handle);
    } else {
        header("Location: importar.php?err=Nenhum ficheiro enviado ou erro no upload.");
    }
    exit();
}

// --- AÇÃO: ALTERNAR STATUS DE ESTOQUE ---
if ($acao == 'toggle_stock') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        try {
            // A query "NOT in_stock" inverte o valor booleano (0 para 1, 1 para 0).
            $stmt = $conn->prepare("UPDATE produtos SET in_stock = NOT in_stock WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
            exit();
        } catch (PDOException $e) {
            header("Location: dashboard.php?err=Erro ao alterar status de estoque.");
            exit();
        }
    }
}

// --- AÇÃO: DELETAR UM PRODUTO ---
if ($acao == 'deletar') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        try {
            // A exclusão em cascata configurada no banco de dados garante que as
            // imagens associadas a este produto também sejam removidas da tabela `produto_imagens`.
            $stmt = $conn->prepare("DELETE FROM produtos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
            exit();
        } catch (PDOException $e) {
            header("Location: dashboard.php?err=Erro ao remover o produto.");
            exit();
        }
    }
}

// --- AÇÃO: DELETAR MÚLTIPLOS PRODUTOS (EM MASSA) ---
if ($acao == 'deletar_massa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ids']) && is_array($_POST['ids'])) {
        // Filtra o array para garantir que contenha apenas IDs numéricos.
        $ids_para_deletar = array_filter($_POST['ids'], 'is_numeric');
        if (!empty($ids_para_deletar)) {
            try {
                // Cria placeholders (?) para cada ID, tornando a query segura (prevenção de SQL Injection).
                $placeholders = implode(',', array_fill(0, count($ids_para_deletar), '?'));
                $stmt = $conn->prepare("DELETE FROM produtos WHERE id IN ($placeholders)");
                $stmt->execute($ids_para_deletar);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
                exit();
            } catch (PDOException $e) {
                header("Location: dashboard.php?err=Erro ao remover os produtos selecionados.");
                exit();
            }
        }
    }
    header("Location: dashboard.php?err=Nenhum produto foi selecionado para apagar.");
    exit();
}

// Redirecionamento padrão final.
header("Location: dashboard.php");
exit();
