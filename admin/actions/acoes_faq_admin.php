<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}
require_once '../config.php';

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($acao == 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $resposta = trim($_POST['resposta'] ?? '');
    $status = $_POST['status'] ?? 'pendente';

    if ($id === 0 || empty($resposta)) {
        header("Location: ../forms/form_faq.php?id={$id}&err=A resposta não pode estar em branco.");
        exit();
    }

    // Valida o status
    $status_validos = ['pendente', 'aprovada', 'rejeitada'];
    if (!in_array($status, $status_validos)) {
        header("Location: ../forms/form_faq.php?id={$id}&err=Status inválido.");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE faq_perguntas SET resposta = ?, status = ?, data_resposta = NOW() WHERE id = ?");
        $stmt->execute([$resposta, $status, $id]);

        header("Location: ../faq.php?status={$status}&msg=Resposta salva com sucesso!");
        exit();
    } catch (PDOException $e) {
        header("Location: ../forms/form_faq.php?id={$id}&err=Erro ao salvar a resposta.");
        exit();
    }
}

// Redirecionamento padrão
header("Location: ../faq.php");
exit();
