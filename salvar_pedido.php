<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);

    if ($dados) {
        $arquivo = 'pedidos.json';

        // Carrega os pedidos existentes
        $pedidos = [];
        if (file_exists($arquivo)) {
            $pedidos = json_decode(file_get_contents($arquivo), true) ?? [];
        }

        // Formata a data atual no padrão DD/MM/YYYY HH:MM
        date_default_timezone_set('America/Sao_Paulo');
        
        $novoPedido = [
            'nome'     => $dados['nome'] ?? '',
            'telefone' => $dados['telefone'] ?? '',
            'servico'  => $dados['servico'] ?? '',
            'endereco' => $dados['endereco'] ?? '',
            'data'     => date('d/m/Y H:i'),
            'status'   => 'pendente',
            'valor'    => '0.00'
        ];

        // Adiciona e salva no JSON
        $pedidos[] = $novoPedido;
        file_put_contents($arquivo, json_encode($pedidos, JSON_PRETTY_PRINT));

        echo json_encode(['sucesso' => true]);
        exit;
    }
}

echo json_encode(['sucesso' => false]);
exit;

// Exemplo de envio via cURL para a Evolution API
function enviarBotoesAlan($ultimoPedido) {
    $idPedidoSeguro = preg_replace('/[^0-9]/', '', $ultimoPedido['data']);
    
    $payload = [
        "number" => "5564999999999", // WhatsApp do Alan (DDD + Número)
        "options" => [
            "delay" => 1200,
            "presence" => "composing"
        ],
        "buttonMessage" => [
            "title" => "🛠️ NOVO PEDIDO RECEBIDO",
            "description" => "Cliente: " . $ultimoPedido['nome'] . "\n" .
                             "Telefone: " . $ultimoPedido['telefone'] . "\n" .
                             "Serviço: " . $ultimoPedido['servico'] . "\n" .
                             "Endereço: " . $ultimoPedido['endereco'] . "\n" .
                             "Data: " . $ultimoPedido['data'],
            "footer" => "TecnoAr - Confirmação do Técnico",
            "buttons" => [
                [
                    "type" => "reply",
                    "reply" => [
                        "id" => "CONFIRMAR_" . $idPedidoSeguro,
                        "title" => "✅ Confirmar"
                    ]
                ],
                [
                    "type" => "reply",
                    "reply" => [
                        "id" => "CANCELAR_" . $idPedidoSeguro,
                        "title" => "❌ Cancelar"
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init('https://sua-api-evolution.com/message/sendButtons/NOME_DA_INSTANCIA');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: SUA_CHAVE_API_AQUI'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $resposta = curl_exec($ch);
    curl_close($ch);
}
?>