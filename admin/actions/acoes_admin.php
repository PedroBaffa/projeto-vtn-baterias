<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

// CAMINHO ATUALIZADO: Volta um nível para encontrar o config.php
require_once '../config.php';

try {
    // A conexão já é feita pelo config.php, esta parte pode ser removida se já estiver lá.
    // Se o seu config.php já instancia o $conn, pode apagar as 5 linhas abaixo.
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// --- LÓGICA PARA ADICIONAR ---
if ($acao == 'adicionar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['usuario']) || empty($_POST['senha'])) {
        // CAMINHO ATUALIZADO para o formulário
        header("Location: ../forms/form_admin.php?acao=adicionar&err=Todos os campos são obrigatórios.");
        exit();
    }
    $usuario = $_POST['usuario'];
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO administradores (usuario, senha) VALUES (:usuario, :senha)");
        $stmt->execute([':usuario' => $usuario, ':senha' => $senha_hash]);
        // CAMINHO ATUALIZADO para a lista de admins
        header("Location: ../admins.php?msg=Admin adicionado com sucesso!");
        exit();
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // Erro de duplicado
            header("Location: ../forms/form_admin.php?acao=adicionar&err=Este nome de usuário já existe.");
        } else {
            header("Location: ../forms/form_admin.php?acao=adicionar&err=Erro ao adicionar no banco de dados.");
        }
        exit();
    }
}

// --- LÓGICA PARA EDITAR ---
if ($acao == 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id']) || empty($_POST['usuario'])) {
        header("Location: ../admins.php?err=Dados insuficientes."); // CAMINHO ATUALIZADO
        exit();
    }

    $id = (int) $_POST['id'];
    $usuario = $_POST['usuario'];
    $nova_senha = $_POST['senha'];

    try {
        if (!empty($nova_senha)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE administradores SET usuario = :usuario, senha = :senha WHERE id = :id");
            $stmt->execute([':usuario' => $usuario, ':senha' => $senha_hash, ':id' => $id]);
        } else {
            $stmt = $conn->prepare("UPDATE administradores SET usuario = :usuario WHERE id = :id");
            $stmt->execute([':usuario' => $usuario, ':id' => $id]);
        }
        header("Location: ../admins.php?msg=Admin atualizado com sucesso!"); // CAMINHO ATUALIZADO
        exit();
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            header("Location: ../forms/form_admin.php?acao=editar&id={$id}&err=Este nome de usuário já pertence a outro admin."); // CAMINHO ATUALIZADO
        } else {
            header("Location: ../forms/form_admin.php?acao=editar&id={$id}&err=Erro ao atualizar no banco de dados."); // CAMINHO ATUALIZADO
        }
        exit();
    }
}

// --- LÓGICA PARA DELETAR ---
if ($acao == 'deletar') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $id_usuario_logado = $_SESSION['user_id'];

    if ($id > 0 && $id != $id_usuario_logado) {
        try {
            $stmt = $conn->prepare("DELETE FROM administradores WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: ../admins.php?msg=Admin removido!"); // CAMINHO ATUALIZADO
            exit();
        } catch (PDOException $e) {
            header("Location: ../admins.php?err=Erro ao remover."); // CAMINHO ATUALIZADO
            exit();
        }
    } else {
        header("Location: ../admins.php?err=Não é possível remover a própria conta."); // CAMINHO ATUALIZADO
        exit();
    }
}

header("Location: ../admins.php"); // CAMINHO ATUALIZADO
exit();
