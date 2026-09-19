<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $novoPedido = json_decode($input, true);

    if ($novoPedido) {
        $novoPedido['data'] = date('d/m/Y H:i');
        $arquivo = 'pedidos.json';
        $pedidos = [];

        if (file_exists($arquivo)) {
            $conteudo = file_get_contents($arquivo);
            $pedidos = json_decode($conteudo, true) ?? [];
        }

        array_unshift($pedidos, $novoPedido);

        if (file_put_contents($arquivo, json_encode($pedidos, JSON_PRETTY_PRINT))) {
            echo json_encode(['sucesso' => true]);
            exit;
        }
    }
}

echo json_encode(['sucesso' => false]);