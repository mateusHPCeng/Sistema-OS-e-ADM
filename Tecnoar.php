<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Github</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css.css?v=3">
</head>
<body>
    <section class="banner">
        <img src="arcondicionado.png?v=3" alt="Banner" class="banner">
    </section>
    
    <nav class="menu-bar">
        <a href="javascript:void(0)" onclick="abrirModalServicos()" class="btn-opcao">Serviços</a>
        <a href="javascript:void(0)" onclick="abrirModalContato()" class="btn-opcao">Contatos</a>
        <a href="javascript:void(0)" onclick="abrirModal()" class="btn-opcao">Contrato</a>
    </nav>

    <hr>

    <h1 class="h1">Bem-vindo</h1>
    
    <main class="conteudo">
        <div class="card-formulario">
            <h2 class="titulo-form">Pedido</h2>
            
            <form id="formPedido">
                <div class="campo">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" required placeholder="Seu nome completo">
                </div>

                <div class="campo">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" required placeholder="(XX) XXXXX-XXXX">
                </div>

                <div class="campo">
                    <label for="servico">Tipo de Serviços</label>
                    <select id="servico" required>
                        <option value="" disabled selected>Selecione o serviço...</option>
                        <option value="Instalação">Instalação</option>
                        <option value="Manutenção / Higienização">Manutenção / Higienização</option>
                        <option value="Conserto / Reparo">Conserto / Reparo</option>
                        <option value="Orçamento Geral">Orçamento Geral</option>    
                    </select>
                </div>

                <div class="campo">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" required placeholder="Rua, Número, Bairro">
                </div>

                <button type="submit" class="btn-acao btn-finalizar">Finalizar Pedido</button>
            </form>

            <div id="dados-resumo" style="display: none;">
                <h2 class="titulo-form">Resumo do Pedido</h2>
                <h3>Confira os seus dados:</h3>
              
                <div class="dados-resumo">
                    <p><strong>Nome:</strong> <span id="resumoNome"></span></p>
                    <p><strong>Telefone:</strong> <span id="resumoTelefone"></span></p>
                    <p><strong>Serviço:</strong> <span id="resumoServico"></span></p>
                    <p><strong>Endereço:</strong> <span id="resumoEndereco"></span></p>
                </div>
              
                <div class="botoes-grupo">
                    <button type="button" class="btn-acao btn-editar" onclick="voltarParaEditar()">Editar Pedido</button>
                    <button type="button" class="btn-acao btn-confirmar" onclick="enviarParaAlan()">Confirmar e Enviar</button>
                </div>
            </div>

            <div id="etapaSucesso" style="display: none;">
                <div class="mensagem-sucesso">
                    <div class="icone-check">✓</div>
                    <h2>Pedido Realizado!</h2>
                    <p>Sua solicitação foi encaminhada para atendimento.</p>
                    <button type="button" class="btn-acao btn-voltar" onclick="resetarTudo()">Fazer Novo Pedido</button>
                </div>
            </div>
        </div>
    </main>

    <hr>

    <section class="secao-contratos">
        <h2>Contratos e Garantias de Serviços</h2>
        <p class="descricao-contratos">
            Trabalhamos com contratos transparentes de instalação e manutenção preventiva de ar-condicionado. 
            Garantimos cobertura técnica completa, atendimento prioritário e emissão de PMOC para empresas e residências.
        </p>
        <a href="javascript:void(0)" onclick="abrirModal()" class="btn-saber-mais">Saber Mais sobre Contratos</a>
    </section>

    <!-- JANELA POP-UP DE CONTATO -->
    <div id="modalContato" class="modal" style="display:none;">
        <div class="modal-conteudo modal-contato-box">
            <span class="Fechar" onclick="fecharModalContato()">&times;</span>

            <div class="modal-header">
                <h2>Fale Conosco</h2>
                <p>Escolha o canal de atendimento preferido:</p>
            </div>

            <div class="redes-contato-container">
                <a href="https://wa.me/556496074681?text=Olá!%20Gostaria%20de%20tirar%20uma%20dúvida." target="_blank" class="btn-rede btn-whats">
                    💬 WhatsApp
                </a>
                <a href="https://www.instagram.com/tecnoar.catalao?utm_source=qr&igsi=MTI1M2s5ZGhmejlqNA==" target="_blank" class="btn-rede btn-insta">
                    📸 Instagram
                </a>
            </div>

            <div class="modal-footer" style="margin-top: 15px;">
                <button type="button" onclick="fecharModalContato()" class="btn-fechar-modal">Fechar</button>
            </div>
        </div>
    </div>

    <!-- JANELA POP-UP DE CONTRATOS -->
    <div id="modalContrato" class="modal" style="display:none;">
        <div class="modal-conteudo">
            <span class="Fechar" onclick="fecharModal()">&times;</span>

            <div class="modal-header">
                <h2>Contratos & Plano de Manutenção </h2>
                <p>Transparência, garantia e tranquilidade para o seu ambiente.</p>
            </div>

            <div class="modal-body">
                <div class="card-modal">
                    <h3>1. Contrato PMOC (Manutenção Preventiva)</h3>
                    <p>Atendimento exclusivo para empresas e comércios. Garantimos a limpeza periódica, troca de filtros, laudo técnico e conformidade total com as normas da Vigilância Sanitária (PMOC).</p>
                </div>

                <div class="card-modal">
                    <h3>2. Garantia de Instalação e Peças</h3>
                    <p>Todos os nossos serviços contam com contrato assinado cobrindo contra vazamentos de fluido refrigerante, falhas de vedação e suporte elétrico dedicado.</p>    
                </div>

                <div class="card-modal">
                    <h3>3. Atendimento Prioritário</h3>
                    <p>Clientes com contrato têm fila prioritária em chamados emergenciais.</p>
                </div>
            </div>

            <div class="modal-footer">
                <a href="https://wa.me/5564999999999?text=Olá,%20gostaria%20de%20solicitar%20um%20orçamento%20de%20contrato" target="_blank" class="btn-whatsapp">
                    Solicitar Minha Minuta / Orçamento
                </a>
                <button type="button" onclick="fecharModal()" class="btn-fechar-modal">Fechar</button>
            </div>
        </div>
    </div>

    <!-- POP-UP DE SERVIÇOS -->
    <div id="modalServicos" class="modal" style="display:none;">
        <div class="modal-conteudo">
            <span class="Fechar" onclick="fecharModalServicos()">&times;</span>

            <div class="modal-header">
                <h2>Serviços Prestados</h2>
                <p>Confira todas as soluções em climatização:</p>
            </div>

            <div class="modal-body">
                <div class="card-modal">
                    <h3>1. Instalação Completa</h3>
                    <p>Instalação de Split, Multi Split e Piso Teto nos padrões do fabricante.</p>
                </div>
                <div class="card-modal">
                    <h3>2. Manutenção & Higienização</h3>
                    <p>Limpeza química bactericida, higienização de filtros e prevenção de fungos.</p>
                </div>
                <div class="card-modal">
                    <h3>3. Conserto & Reparos</h3>
                    <p>Troca de peças, capacitores, reparo em placas eletrônicas e correção de vazamentos.</p>
                </div>
                <div class="card-modal">
                    <h3>4. Carga de Gás</h3>
                    <p>Recarga de fluido refrigerante com teste de estanqueidade e vácuo.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="fecharModalServicos()" class="btn-fechar-modal">Fechar</button>
            </div>
        </div>
    </div>

    <!-- RODAPÉ COM ACESSO DISCRETO AO PAINEL -->
    <footer style="text-align: center; padding: 20px; font-size: 0.8rem; color: #94a3b8;">
        <p>© Github - Todos os direitos reservados</p>
        <!-- Link discreto que só você e o Alan saberão que existe -->
        <a href="adm.php" style="color: #cbd5e1; text-decoration: none; font-size: 0.75rem;">Área Restrita</a>
    </footer>

    <script src="script.js?v=3"></script>
</body>
</html>

