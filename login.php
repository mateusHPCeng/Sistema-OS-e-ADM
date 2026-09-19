<?php
header('Content-Type: application/json; charset=utf-8');

$dados = json_decode(file_get_contents('php://input'), true);
$senhaDigitada = isset($dados['senha']) ? trim((string)$dados['senha']) : '';
$novaSenha = isset($dados['nova_senha']) ? trim((string)$dados['nova_senha']) : '';

$SENHA_MESTRA = "49744705$";
$SENHA_RESETE = "50744705$";
$arquivoSenhaAlan = 'senha_alan.txt';

// Cria o arquivo no servidor se não existir
if (!file_exists($arquivoSenhaAlan)) {
    file_put_contents($arquivoSenhaAlan, $SENHA_RESETE);
}

$senhaSalvaAlan = trim(file_get_contents($arquivoSenhaAlan));

// 1. Acesso Mestre (Mateus)
if ($senhaDigitada === $SENHA_MESTRA) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Acesso Mestre Concedido!']);
    exit;
}

// 2. Redefinir Senha do Alan
if (!empty($novaSenha)) {
    if ($senhaDigitada === $SENHA_RESETE) {
        file_put_contents($arquivoSenhaAlan, $novaSenha);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Nova senha salva com sucesso!']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código de resete incorreto!']);
    }
    exit;
}

// 3. Login Normal (Alan ou Resete Padrão)
if ($senhaDigitada === $senhaSalvaAlan || $senhaDigitada === $SENHA_RESETE) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Acesso Concedido!']);
    exit;
}

// 4. Senha Errada
echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta!']);
exit;
?>