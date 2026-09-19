<?php
session_start();

$arquivoPedidos = 'pedidos.json';
$arquivoSenhaAlan = 'senha_alan.txt';

$SENHA_MESTRA = "49744705$";
$SENHA_RESETE = "50744705$";

// 1. Inicializa a senha do Alan no servidor se o arquivo não existir
if (!file_exists($arquivoSenhaAlan)) {
    file_put_contents($arquivoSenhaAlan, $SENHA_RESETE);
}
$senhaSalvaAlan = trim(file_get_contents($arquivoSenhaAlan));

// 2. Processa Encerrar Sessão (Logout)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// 3. Processa Tentativa de Login e Redefinição Automática via Form/POST
if (isset($_POST['acao_login'])) {
    $senhaDigitada = trim((string)($_POST['senha'] ?? ''));

    // A. Login Mestre (Mateus)
    if ($senhaDigitada === $SENHA_MESTRA) {
        $_SESSION['logado'] = true;
        $_SESSION['perfil'] = 'Mestre';
    } 
    // B. Redefinição da Senha do Alan (Se digitar o código de resete, atualiza o arquivo)
    elseif ($senhaDigitada === $SENHA_RESETE) {
        file_put_contents($arquivoSenhaAlan, $SENHA_RESETE);
        $_SESSION['logado'] = true;
        $_SESSION['perfil'] = 'Alan';
    }
    // C. Login Normal (Senha gravada no arquivo para o Alan)
    elseif ($senhaDigitada === $senhaSalvaAlan) {
        $_SESSION['logado'] = true;
        $_SESSION['perfil'] = 'Alan';
    } 
    // D. Senha Incorreta
    else {
        $erro_login = "Senha incorreta!";
    }
}

$usuario_logado = $_SESSION['logado'] ?? false;

// 4. Manipulação AJAX (Apenas para Usuários Autenticados)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['acao_login'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if (!$usuario_logado) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado']);
        exit;
    }

    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    if (file_exists($arquivoPedidos)) {
        $pedidos = json_decode(file_get_contents($arquivoPedidos), true) ?? [];
        
        // Atualizar Status ou Valor
        if (isset($dados['acao']) && $dados['acao'] === 'atualizar') {
            $index = $dados['index'];
            if (isset($pedidos[$index])) {
                if (isset($dados['status'])) $pedidos[$index]['status'] = $dados['status'];
                if (isset($dados['valor'])) $pedidos[$index]['valor'] = (string)$dados['valor'];
                file_put_contents($arquivoPedidos, json_encode($pedidos, JSON_PRETTY_PRINT));
                echo json_encode(['sucesso' => true]);
                exit;
            }
        }
        
        // Excluir Registros Selecionados
        if (isset($dados['acao']) && $dados['acao'] === 'excluir') {
            $indices = $dados['indices'];
            rsort($indices);
            foreach ($indices as $i) {
                if (isset($pedidos[$i])) {
                    array_splice($pedidos, $i, 1);
                }
            }
            file_put_contents($arquivoPedidos, json_encode($pedidos, JSON_PRETTY_PRINT));
            echo json_encode(['sucesso' => true]);
            exit;
        }
    }
    echo json_encode(['sucesso' => false]);
    exit;
}

// 5. Carrega pedidos apenas se estiver autenticado no PHP
$pedidos = [];
if ($usuario_logado && file_exists($arquivoPedidos)) {
    $pedidos = json_decode(file_get_contents($arquivoPedidos), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel ADM & Balanço - TecnoAr</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #334155; padding: 20px; }
        
        .topo-adm { text-align: center; margin-bottom: 25px; }
        .topo-adm h1 { color: #0284c7; font-size: 2rem; margin-bottom: 5px; }
        
        .card-painel { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px; }
        .titulo-secao { color: #0284c7; font-size: 1.25rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }

        .grid-metricas { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .card-metrica { padding: 15px; border-radius: 10px; border-left: 5px solid #0284c7; background: #f0f9ff; }
        .card-metrica.verde { border-left-color: #22c55e; background: #f0fdf4; }
        .card-metrica.laranja { border-left-color: #f97316; background: #fff7ed; }
        .card-metrica h4 { font-size: 0.85rem; color: #64748b; margin-bottom: 5px; text-transform: uppercase; }
        .card-metrica .valor-m { font-size: 1.5rem; font-weight: bold; color: #0f172a; }

        .grafico-container { display: flex; align-items: flex-end; gap: 15px; height: 180px; padding: 20px 10px 10px 10px; border-bottom: 2px solid #cbd5e1; overflow-x: auto; }
        .coluna-grafico { flex: 1; min-width: 50px; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; }
        .barra-grafico { width: 100%; max-width: 40px; background-color: #0284c7; border-radius: 4px 4px 0 0; transition: height 0.4s ease; min-height: 4px; }
        .label-mes { font-size: 0.75rem; font-weight: bold; margin-top: 6px; color: #475569; }
        .val-grafico { font-size: 0.7rem; font-weight: bold; color: #0284c7; margin-bottom: 4px; }

        .container-busca { margin-bottom: 15px; }
        .input-busca { width: 100%; padding: 12px 15px; border: 2px solid #bae6fd; border-radius: 8px; font-size: 0.95rem; outline: none; }
        .input-busca:focus { border-color: #0284c7; }

        .tabela-wrapper { overflow-x: auto; border-radius: 8px; border: 1px solid #cbd5e1; }
        table { width: 100%; border-collapse: collapse; text-align: left; background: #fff; }
        th { background-color: #0284c7; color: #ffffff; padding: 12px; font-size: 0.9rem; }
        td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        tr:hover { background-color: #f0f9ff; }

        .select-status { padding: 6px 10px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; outline: none; }
        .status-pendente { background-color: #ffedd5; color: #c2410c; }
        .status-concluido { background-color: #dcfce7; color: #15803d; }
        .status-cancelado { background-color: #fee2e2; color: #b91c1c; }

        .header-cal { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .header-cal h2 { color: #0369a1; font-size: 1.3rem; }
        .btn-cal { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 6px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .legenda { display: flex; gap: 15px; margin-bottom: 15px; font-size: 0.85rem; font-weight: bold; }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .dias-semana { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; color: #0284c7; background: #e0f2fe; padding: 8px 0; border-radius: 6px; margin-bottom: 5px; }
        .grid-cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
        .dia-card { background: #fff; border: 1px solid #e2e8f0; min-height: 70px; border-radius: 6px; padding: 6px; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between; }
        .num-dia { font-weight: bold; font-size: 0.85rem; }
        .barras-status { display: flex; flex-direction: column; gap: 2px; }
        .barra { height: 4px; border-radius: 2px; width: 100%; }
        .bg-pendente { background-color: #f97316; }
        .bg-concluido { background-color: #22c55e; }
        .bg-cancelado { background-color: #ef4444; }

        .painel-edicao { border-top: 4px solid #0284c7; background: #ffffff; }
        .painel-acoes { display: flex; gap: 10px; margin-bottom: 15px; background: #e0f2fe; padding: 12px; border-radius: 8px; align-items: center; }
        .btn-acao-adm { padding: 9px 18px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; color: #fff; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; }
        .btn-pdf { background-color: #0284c7; }
        .btn-excluir { background-color: #ef4444; }
        .btn-sair { background-color: #64748b; text-decoration: none; padding: 8px 14px; border-radius: 6px; color: #fff; font-weight: bold; font-size: 0.85rem; }
        .input-valor { width: 100px; padding: 6px; border: 2px solid #bae6fd; border-radius: 6px; font-weight: bold; color: #0f172a; outline: none; }

        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); }
        .modal-body { background: #fff; margin: 8% auto; padding: 25px; border-radius: 12px; width: 90%; max-width: 400px; border-top: 5px solid #0284c7; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .fechar-modal { float: right; font-size: 24px; cursor: pointer; color: #64748b; }
        .item-pedido { background: #f8fafc; border-left: 4px solid #0284c7; padding: 10px; margin-top: 10px; border-radius: 4px; }

        #modalConteudo .select-status {
            padding: 2px 6px !important;
            font-size: 0.7rem !important;
            display: inline-block !important;
            margin-left: 5px !important;
        }
    </style>
</head>
<body>

<?php if (!$usuario_logado): ?>
    <!-- FORMULÁRIO DE AUTENTICAÇÃO DIRETO -->
    <div id="modalLogin" class="modal" style="display: block;">
        <div class="modal-body" style="text-align: center;">
            <h2 style="color: #0284c7; margin-bottom: 10px;">🔒 Área Restrita ADM</h2>
            <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 20px;">Digite a senha de acesso:</p>

            <form method="POST">
                <input type="hidden" name="acao_login" value="1">
                <input type="password" name="senha" class="input-busca" placeholder="Digite a senha..." style="text-align: center; margin-bottom: 15px;" required autofocus>
                <button type="submit" class="btn-acao-adm btn-pdf" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                    Acessar Painel
                </button>
            </form>

            <?php if (isset($erro_login)): ?>
                <p style="color: #ef4444; font-size: 0.85rem; font-weight: bold; margin-top: 12px;"><?= htmlspecialchars($erro_login) ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- PAINEL ADMINISTRATIVO -->
    <div id="conteudoPainel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <span style="font-size: 0.85rem; color: #64748b; font-weight: bold;">
                Perfil Ativo: <span style="color: #0284c7;"><?= htmlspecialchars($_SESSION['perfil'] ?? 'Usuário') ?></span>
            </span>
            <a href="?logout=1" class="btn-sair">🚪 Sair do Sistema</a>
        </div>

        <div class="topo-adm">
            <h1>TecnoAr - Gestão & Balanço Financeiro</h1>
            <p>Painel Administrativo de Serviços</p>
        </div>

        <!-- 1. DASHBOARD FINANCIAL -->
        <div class="card-painel">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <h2 class="titulo-secao" style="margin-bottom: 0;">📊 Balanço Financeiro</h2>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="filtroPeriodo" style="font-weight: bold; font-size: 0.85rem; color: #0369a1;">Período:</label>
                    <select id="filtroPeriodo" onchange="atualizarBalanco()" style="padding: 6px 12px; border-radius: 6px; border: 2px solid #bae6fd; font-weight: bold; color: #0369a1; outline: none; cursor: pointer; background: #fff;">
                        <option value="atual">Mês Atual</option>
                        <option value="anterior">Mês Anterior</option>
                        <option value="todos">Todo o Período (Geral)</option>
                    </select>
                </div>
            </div>
            
            <div class="grid-metricas">
                <div class="card-metrica verde">
                    <h4>Ganhos Totais (Concluídos)</h4>
                    <div class="valor-m" id="totalGanhos">R$ 0,00</div>
                </div>
                <div class="card-metrica laranja">
                    <h4>A Receber (Pedidos Pendentes)</h4>
                    <div class="valor-m" id="totalAFAzer">R$ 0,00</div>
                </div>
                <div class="card-metrica">
                    <h4>Serviços Concluídos</h4>
                    <div class="valor-m" id="qtdConcluidos">0</div>
                </div>
            </div>

            <h3 style="font-size:1rem; color:#0369a1; margin-top:10px;">Faturamento por Mês (R$)</h3>
            <div class="grafico-container" id="graficoMeses"></div>
        </div>

        <!-- 2. TABELA DE PEDIDOS RECEBIDOS -->
        <div class="card-painel">
            <h2 class="titulo-secao">📋 Painel de Pedidos Recebidos</h2>
            <div class="container-busca">
                <input type="text" id="inputBusca" class="input-busca" placeholder="🔍 Buscar por nome, serviço ou telefone..." onkeyup="filtrarTabela('inputBusca', '.linha-pedido')">
            </div>

            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Serviço</th>
                            <th>Endereço</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaPedidos">
                        <?php if (empty($pedidos)): ?>
                            <tr><td colspan="6" style="text-align:center;">Nenhum pedido cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $index => $p): 
                                $st = $p['status'] ?? 'pendente'; 
                            ?>
                                <tr class="linha-pedido">
                                    <td><?= htmlspecialchars($p['data'] ?? '-') ?></td>
                                    <td><strong><?= htmlspecialchars($p['nome'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars($p['telefone'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['servico'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['endereco'] ?? '-') ?></td>
                                    <td>
                                        <select id="select-status-<?= $index ?>" class="select-status status-<?= $st ?>" onchange="tentarAlterarStatus(<?= $index ?>, this.value, this)">
                                            <option value="pendente" <?= $st === 'pendente' ? 'selected' : '' ?>>🟠 Pendente</option>
                                            <option value="concluido" <?= $st === 'concluido' ? 'selected' : '' ?>>🟢 Concluído</option>
                                            <option value="cancelado" <?= $st === 'cancelado' ? 'selected' : '' ?>>🔴 Cancelado</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. CALENDÁRIO INTERATIVO -->
        <div class="card-painel">
            <div class="header-cal">
                <button class="btn-cal" onclick="mudarMes(-1)">&lt; Anterior</button>
                <h2 id="tituloMesAno"></h2>
                <button class="btn-cal" onclick="mudarMes(1)">Próximo &gt;</button>
            </div>

            <div class="legenda">
                <span><span class="dot" style="background:#f97316;"></span> Laranja: Pendente</span>
                <span><span class="dot" style="background:#22c55e;"></span> Verde: Concluído</span>
                <span><span class="dot" style="background:#ef4444;"></span> Vermelho: Cancelado</span>
            </div>

            <div class="dias-semana">
                <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
            </div>
            <div id="gridCalendario" class="grid-cal"></div>
        </div>


        <!-- BLOCO DE CRIAÇÃO DE ORDEM DE SERVIÇO (OS) MANUAL -->
<div class="card-os">
    <h2 class="titulo-os">📝 Criar Nova Ordem de Serviço (OS Manual)</h2>
    
    <form id="formCriarOS" onsubmit="salvarNovaOS(event)">
        <!-- Dados do Cliente em Grid estilo Formulário -->
        <div class="grupo-cliente">
            <input type="text" id="osNomeCliente" class="campo-texto" placeholder="Nome do Cliente" required>
            <input type="text" id="osTelefoneCliente" class="campo-texto" placeholder="Telefone / WhatsApp" required>
            <input type="text" id="osEnderecoCliente" class="campo-texto" placeholder="Endereço Completo" required>
        </div>

        <h4 class="subtitulo-itens">Serviços e Peças:</h4>

        <!-- Cabeçalho estilo Planilha -->
        <div class="cabecalho-itens-os">
            <div>Tipo de Serviço</div>
            <div>Descrição Detalhada</div>
            <div>Qtd</div>
            <div>Valor Unit. (R$)</div>
            <div>Subtotal</div>
            <div></div>
        </div>
        
        <!-- Container dos Itens Dinâmicos -->
        <div id="containerItensOS">
            <div class="linha-item-os">
                <input type="text" class="campo-texto item-tipo" placeholder="Ex: Higienização" required>
                <input type="text" class="campo-texto item-desc" placeholder="Detalhes adicionais do serviço...">
                <input type="number" min="1" value="1" class="campo-texto item-qtd" oninput="calcularTotaisOS()" required>
                <input type="number" step="0.01" min="0" placeholder="0.00" class="campo-texto item-valor" oninput="calcularTotaisOS()" required>
                <div class="caixa-subtotal">R$ <span class="valor-subtotal">0.00</span></div>
                <button type="button" class="btn-remover-item" onclick="removerLinhaItem(this)" title="Remover item">🗑️</button>
            </div>
        </div>

        <!-- Rodapé do Formulário -->
        <div class="rodape-formulario">
            <button type="button" class="btn-add-item" onclick="adicionarLinhaItem()">
                ➕ Adicionar Mais Serviços/Itens
            </button>
            
            <div class="caixa-total">
                <span>Total da OS:</span>
                <strong id="displayTotalOS">R$ 0,00</strong>
            </div>
        </div>

        <button type="submit" class="btn-salvar-os">💾 Gerar e Salvar Ordem de Serviço</button>
    </form>
</div>
        <!-- 4. GERENCIAMENTO DE VALORES, EXCLUSÃO E PDF -->
        <div class="card-painel painel-edicao">
            <h2 class="titulo-secao">🛠️ Gerenciamento de Valores, PDF e Exclusão</h2>
            
            <div class="container-busca">
                <input type="text" id="inputBuscaEdicao" class="input-busca" placeholder="🔍 Pesquisar pedidos para editar valores, gerar PDF ou excluir..." onkeyup="filtrarTabela('inputBuscaEdicao', '.linha-edicao')">
            </div>

            <div class="painel-acoes">
                <span style="font-weight: bold; color: #0369a1;">Ações para Selecionados:</span>
                <button class="btn-acao-adm btn-pdf" onclick="gerarPDF()">📄 Gerar PDF com Valores</button>
                <button class="btn-acao-adm btn-excluir" onclick="excluirSelecionados()">🗑️ Excluir Selecionados</button>
            </div>

            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="checkTodos" onchange="selecionarTodos(this)"></th>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th>Serviço</th>
                            <th>Data</th>
                            <th>Valor do Serviço (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr><td colspan="6" style="text-align:center;">Nenhum pedido cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $index => $p): 
                                $val = $p['valor'] ?? '0.00';
                            ?>
                                <tr class="linha-edicao">
                                    <td style="text-align: center;"><input type="checkbox" class="check-item" value="<?= $index ?>"></td>
                                    <td><strong><?= htmlspecialchars($p['nome'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars($p['telefone'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['servico'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['data'] ?? '-') ?></td>
                                    <td>
                                        R$ <input type="number" step="0.01" id="input-val-<?= $index ?>" class="input-valor" value="<?= htmlspecialchars($val) ?>" onchange="salvarValor(<?= $index ?>, this.value)">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALHES DO CALENDÁRIO -->
    <div id="modalDia" class="modal">
        <div class="modal-body">
            <span class="fechar-modal" onclick="fecharModal()">&times;</span>
            <h3 id="modalTitulo" style="color:#0284c7;"></h3>
            <div id="modalConteudo" style="margin-top:15px;"></div>
        </div>
    </div>

    <script>
        let listaPedidos = <?= json_encode($pedidos) ?>;
        let dataAtual = new Date();

        document.addEventListener("DOMContentLoaded", () => {
            renderizarCalendario();
            atualizarBalanco();
        });

        function filtrarTabela(idInput, classeLinhas) {
            const termo = document.getElementById(idInput).value.toLowerCase();
            document.querySelectorAll(classeLinhas).forEach(linha => {
                linha.style.display = linha.innerText.toLowerCase().includes(termo) ? '' : 'none';
            });
        }

        function selecionarTodos(master) {
            document.querySelectorAll('.check-item').forEach(c => c.checked = master.checked);
        }

        function tentarAlterarStatus(index, novoStatus, elementoSelect) {
            const inputVal = document.getElementById(`input-val-${index}`);
            if (inputVal) {
                listaPedidos[index].valor = inputVal.value;
            }

            const valorAtual = parseFloat(listaPedidos[index].valor || 0);

            if (novoStatus === 'concluido' && valorAtual <= 0) {
                alert('⚠️ SEM VALOR DEFINIDO!\n\nInforme o valor do serviço (R$) na tabela de gerenciamento abaixo antes de marcar como Concluído.');
                elementoSelect.value = listaPedidos[index].status || 'pendente';
                elementoSelect.className = `select-status status-${elementoSelect.value}`;
                return;
            }

            elementoSelect.className = `select-status status-${novoStatus}`;
            listaPedidos[index].status = novoStatus;

            atualizarBalanco();
            renderizarCalendario();

            enviarServidor({ acao: 'atualizar', index: index, status: novoStatus, valor: listaPedidos[index].valor });
        }

        function salvarValor(index, novoValor) {
            listaPedidos[index].valor = novoValor;
            atualizarBalanco();
            enviarServidor({ acao: 'atualizar', index: index, valor: novoValor });
        }

        function enviarServidor(dados) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
        }

        function atualizarBalanco() {
            let totalGanhos = 0;
            let totalAFazer = 0;
            let qtdConcluidos = 0;
            let faturamentoPorMes = {};

            const seletor = document.getElementById('filtroPeriodo');
            const filtro = seletor ? seletor.value : 'atual';

            const agora = new Date();
            const mesAtual = agora.getMonth() + 1;
            const anoAtual = agora.getFullYear();

            let mesAnterior = mesAtual - 1;
            let anoAnterior = anoAtual;
            if (mesAnterior === 0) {
                mesAnterior = 12;
                anoAnterior = anoAtual - 1;
            }

            listaPedidos.forEach(p => {
                const val = parseFloat(p.valor || 0);
                const status = p.status || 'pendente';

                let mPedido = 0, aPedido = 0;
                if (p.data && p.data.length >= 10) {
                    const partes = p.data.split(' ')[0].split('/');
                    if (partes.length === 3) {
                        mPedido = parseInt(partes[1], 10);
                        aPedido = parseInt(partes[2], 10);
                    }
                }

                let pertenceAoFiltro = false;
                if (filtro === 'todos') {
                    pertenceAoFiltro = true;
                } else if (filtro === 'atual' && mPedido === mesAtual && aPedido === anoAtual) {
                    pertenceAoFiltro = true;
                } else if (filtro === 'anterior' && mPedido === mesAnterior && aPedido === anoAnterior) {
                    pertenceAoFiltro = true;
                }

                if (pertenceAoFiltro) {
                    if (status === 'concluido') {
                        totalGanhos += val;
                        qtdConcluidos++;
                    } else if (status === 'pendente') {
                        totalAFazer += val;
                    }
                }

                if (status === 'concluido' && mPedido > 0) {
                    const chaveMes = `${String(mPedido).padStart(2, '0')}/${aPedido}`;
                    faturamentoPorMes[chaveMes] = (faturamentoPorMes[chaveMes] || 0) + val;
                }
            });

            document.getElementById('totalGanhos').innerText = `R$ ${totalGanhos.toFixed(2)}`;
            document.getElementById('totalAFAzer').innerText = `R$ ${totalAFazer.toFixed(2)}`;
            document.getElementById('qtdConcluidos').innerText = qtdConcluidos;

            const containerGrafico = document.getElementById('graficoMeses');
            containerGrafico.innerHTML = '';

            const chaves = Object.keys(faturamentoPorMes);
            if (chaves.length === 0) {
                containerGrafico.innerHTML = '<p style="color:#94a3b8; font-size:0.85rem; width:100%; text-align:center;">Nenhum faturamento registrado até o momento.</p>';
                return;
            }

            const maiorValor = Math.max(...Object.values(faturamentoPorMes), 1);

            chaves.forEach(mes => {
                const valorMes = faturamentoPorMes[mes];
                const porcentagem = (valorMes / maiorValor) * 100;

                const col = document.createElement('div');
                col.className = 'coluna-grafico';
                col.innerHTML = `
                    <span class="val-grafico">R$ ${valorMes.toFixed(0)}</span>
                    <div class="barra-grafico" style="height: ${Math.max(porcentagem, 5)}%;"></div>
                    <span class="label-mes">${mes}</span>
                `;
                containerGrafico.appendChild(col);
            });
        }

        function excluirSelecionados() {
            const selecionados = Array.from(document.querySelectorAll('.check-item:checked')).map(cb => parseInt(cb.value));
            if (selecionados.length === 0) return alert("Selecione pelo menos um pedido na lista.");

            if (confirm(`Tem certeza que deseja excluir ${selecionados.length} pedido(s)?`)) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ acao: 'excluir', indices: selecionados })
                }).then(() => window.location.reload());
            }
        }

        function gerarPDF() {
            const selecionados = Array.from(document.querySelectorAll('.check-item:checked')).map(cb => parseInt(cb.value));
            if (selecionados.length === 0) return alert("Selecione os pedidos que deseja incluir no PDF.");

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(18);
            doc.setTextColor(2, 132, 199);
            doc.text("TecnoAr - Relatório / Orçamento de Serviços", 14, 20);

            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Data de Emissão: ${new Date().toLocaleDateString('pt-BR')}`, 14, 28);

            const linhas = [];
            let valorTotal = 0;

            selecionados.forEach(i => {
                const p = listaPedidos[i];
                const v = parseFloat(p.valor || 0);
                valorTotal += v;
                linhas.push([
                    p.data || '-',
                    p.nome || '-',
                    p.telefone || '-',
                    p.servico || '-',
                    p.endereco || '-',
                    `R$ ${v.toFixed(2)}`
                ]);
            });

            doc.autoTable({
                startY: 35,
                head: [['Data', 'Cliente', 'Telefone', 'Serviço', 'Endereço', 'Valor']],
                body: linhas,
                headStyles: { fillColor: [2, 132, 199] }
            });

            const finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(`Valor Total Geral: R$ ${valorTotal.toFixed(2)}`, 14, finalY);

            const nomeArquivo = `TecnoAr_Relatorio_${Date.now()}.pdf`;
            
            try {
                doc.save(nomeArquivo);
            } catch (e) {
                const blob = doc.output('blob');
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = nomeArquivo;
                link.click();
            }
        }

        function renderizarCalendario() {
            const grid = document.getElementById('gridCalendario');
            const titulo = document.getElementById('tituloMesAno');
            grid.innerHTML = '';

            const ano = dataAtual.getFullYear();
            const mes = dataAtual.getMonth();
            const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
            titulo.innerText = `${meses[mes]} ${ano}`;

            const primeiroDia = new Date(ano, mes, 1).getDay();
            const totalDias = new Date(ano, mes + 1, 0).getDate();

            for (let i = 0; i < primeiroDia; i++) grid.appendChild(document.createElement('div'));

            for (let dia = 1; dia <= totalDias; dia++) {
                const diaCard = document.createElement('div');
                diaCard.className = 'dia-card';
                const diaF = String(dia).padStart(2, '0');
                const mesF = String(mes + 1).padStart(2, '0');
                const dataStr = `${diaF}/${mesF}/${ano}`;

                diaCard.innerHTML = `<span class="num-dia">${dia}</span>`;
                const pedidosDia = listaPedidos.filter(p => p.data && p.data.includes(dataStr));

                if (pedidosDia.length > 0) {
                    const conteiner = document.createElement('div');
                    conteiner.className = 'barras-status';
                    pedidosDia.forEach(p => {
                        const b = document.createElement('div');
                        b.className = `barra bg-${p.status || 'pendente'}`;
                        conteiner.appendChild(b);
                    });
                    diaCard.appendChild(conteiner);
                }

                diaCard.onclick = () => abrirDetalhesDia(dataStr, pedidosDia);
                grid.appendChild(diaCard);
            }
        }

        function mudarMes(d) { dataAtual.setMonth(dataAtual.getMonth() + d); renderizarCalendario(); }

        function abrirDetalhesDia(data, pedidos) {
            document.getElementById('modalTitulo').innerText = `Pedidos de ${data}`;
            const c = document.getElementById('modalConteudo');
            c.innerHTML = pedidos.length === 0 ? '<p>Nenhum pedido neste dia.</p>' : '';
            pedidos.forEach(p => {
                const st = p.status || 'pendente';
                c.innerHTML += `
                    <div class="item-pedido">
                        <strong>Cliente:</strong> ${p.nome}<br>
                        <strong>Serviço:</strong> ${p.servico}<br>
                        <strong>Valor:</strong> R$ ${parseFloat(p.valor || 0).toFixed(2)}<br>
                        <strong>Endereço:</strong> ${p.endereco}<br>
                        <strong>Status:</strong> <span class="select-status status-${st}">${st.toUpperCase()}</span>
                    </div>`;
            });
            document.getElementById('modalDia').style.display = 'block';
        }

        function fecharModal() { document.getElementById('modalDia').style.display = 'none'; }
    </script>
<?php endif; ?>
</body>
</html>