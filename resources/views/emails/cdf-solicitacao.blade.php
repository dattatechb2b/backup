<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação de Cotação - CDF</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 20px 0;
            line-height: 1.6;
        }

        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Header Corporativo */
        .header {
            background: #2c3e50;
            color: #ffffff;
            padding: 40px 50px;
            border-bottom: 4px solid #34495e;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 8px 0;
            letter-spacing: 0.5px;
        }

        .header p {
            font-size: 14px;
            color: #bdc3c7;
            margin: 0;
            font-weight: 400;
        }

        /* Conteúdo */
        .content {
            padding: 50px;
            color: #2c3e50;
        }

        .greeting {
            font-size: 15px;
            margin-bottom: 10px;
            color: #34495e;
        }

        .fornecedor-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        .intro-text {
            font-size: 15px;
            line-height: 1.8;
            color: #4a5568;
            text-align: justify;
            margin-bottom: 30px;
        }

        /* Caixa de destaque formal */
        .notice-box {
            background: #f8f9fa;
            border-left: 4px solid #34495e;
            padding: 20px 25px;
            margin: 30px 0;
        }

        .notice-box-title {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .notice-box-text {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.7;
        }

        /* Seção de título */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        /* Tabela de informações */
        .info-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background: #ffffff;
        }

        .info-table tr {
            border-bottom: 1px solid #e9ecef;
        }

        .info-table tr:last-child {
            border-bottom: none;
        }

        .info-table td {
            padding: 14px 0;
            font-size: 14px;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #5a6c7d;
            width: 45%;
        }

        .info-table td:last-child {
            color: #2c3e50;
            font-weight: 500;
        }

        .info-highlight {
            color: #c0392b !important;
            font-weight: 700 !important;
        }

        /* Caixa de aviso (prazo) */
        .deadline-box {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-left: 4px solid #c0392b;
            padding: 18px 25px;
            margin: 30px 0;
        }

        .deadline-box strong {
            color: #c0392b;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .deadline-box p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #4a5568;
        }

        /* Botão corporativo */
        .button-container {
            text-align: center;
            margin: 40px 0;
        }

        .btn-primary {
            display: inline-block;
            background: #2c3e50;
            color: #ffffff !important;
            padding: 16px 50px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 4px;
            text-transform: uppercase;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #34495e;
        }

        .link-alternative {
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
            margin-top: 15px;
        }

        .link-alternative a {
            color: #3498db;
            word-break: break-all;
            text-decoration: none;
        }

        /* Lista de instruções */
        .instructions-list {
            margin: 20px 0;
            padding-left: 0;
            list-style: none;
            counter-reset: instruction-counter;
        }

        .instructions-list li {
            counter-increment: instruction-counter;
            position: relative;
            padding-left: 45px;
            margin-bottom: 16px;
            font-size: 14px;
            color: #4a5568;
            line-height: 1.7;
        }

        .instructions-list li::before {
            content: counter(instruction-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 28px;
            height: 28px;
            background: #34495e;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 35px 50px;
            border-top: 1px solid #e9ecef;
        }

        .footer-orgao {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .footer-sistema {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        .footer-info {
            font-size: 12px;
            color: #95a5a6;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .footer-info a {
            color: #3498db;
            text-decoration: none;
        }

        .footer-copyright {
            font-size: 11px;
            color: #bdc3c7;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        /* Responsividade */
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 25px;
            }

            .header {
                padding: 30px 25px;
            }

            .footer {
                padding: 25px 25px;
            }

            .info-table td:first-child {
                width: 100%;
                display: block;
                padding-bottom: 5px;
            }

            .info-table td:last-child {
                width: 100%;
                display: block;
                padding-top: 5px;
                padding-bottom: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">

        <!-- Header Corporativo -->
        <div class="header">
            <h1>Solicitação Oficial de Cotação de Preços</h1>
            <p>Cotação Direta com Fornecedor - CDF</p>
        </div>

        <!-- Conteúdo -->
        <div class="content">

            <p class="greeting">Prezado(a) Senhor(a),</p>
            <p class="fornecedor-name">{{ $fornecedor['razao_social'] }}</p>

            <p class="intro-text">
                {{ $orgao['nome'] }} vem, por meio deste instrumento, solicitar formalmente cotação de preços
                para eventual aquisição de materiais e/ou contratação de serviços, em conformidade com as
                especificações técnicas descritas no Sistema Cesta de Preços, plataforma oficial de gestão
                orçamentária desta municipalidade.
            </p>

            <div class="notice-box">
                <div class="notice-box-title">Importante</div>
                <div class="notice-box-text">
                    Esta é uma solicitação oficial de cotação de preços. Solicitamos que V.Sa. apresente
                    proposta comercial detalhada através do sistema disponibilizado neste e-mail, informando
                    valores unitários e totais, condições de pagamento, prazos de entrega e demais especificações
                    técnicas pertinentes.
                </div>
            </div>

            <!-- Dados da Solicitação -->
            <h2 class="section-title">Dados da Solicitação</h2>

            <table class="info-table">
                <tr>
                    <td>Número da Solicitação</td>
                    <td><strong>#{{ $solicitacao->id }}</strong></td>
                </tr>
                <tr>
                    <td>Órgão Solicitante</td>
                    <td><strong>{{ $orgao['nome'] }}</strong></td>
                </tr>
                <tr>
                    <td>Responsável pela Solicitação</td>
                    <td>{{ $orgao['usuario'] }}</td>
                </tr>
                <tr>
                    <td>CNPJ do Fornecedor</td>
                    <td>{{ $fornecedor['cnpj'] }}</td>
                </tr>
                <tr>
                    <td>Razão Social</td>
                    <td>{{ $fornecedor['razao_social'] }}</td>
                </tr>
                <tr>
                    <td>Prazo para Apresentação da Proposta</td>
                    <td><strong>{{ $solicitacao->prazo_resposta_dias }} {{ $solicitacao->prazo_resposta_dias == 1 ? 'dia útil' : 'dias úteis' }}</strong></td>
                </tr>
                <tr>
                    <td>Data Limite para Resposta</td>
                    <td class="info-highlight">{{ $validoAte->format('d/m/Y') }} às {{ $validoAte->format('H:i') }}</td>
                </tr>
            </table>

            <!-- Aviso de Prazo -->
            <div class="deadline-box">
                <strong>Atenção ao Prazo</strong>
                <p>
                    O link de acesso ao sistema expirará automaticamente em <strong>{{ $validoAte->format('d/m/Y') }} às {{ $validoAte->format('H:i') }}</strong>.
                    Após este horário, não será mais possível o envio de propostas comerciais através deste canal.
                </p>
            </div>

            <!-- Botão de Acesso -->
            <div class="button-container">
                <a href="{{ $linkResposta }}" class="btn-primary">
                    Acessar Sistema de Cotação
                </a>
            </div>

            <div class="link-alternative">
                Ou copie e cole o seguinte endereço em seu navegador:<br>
                <a href="{{ $linkResposta }}">{{ $linkResposta }}</a>
            </div>

            <!-- Instruções -->
            <h2 class="section-title">Instruções para Apresentação da Proposta</h2>

            <ol class="instructions-list">
                <li>Acesse o sistema através do link fornecido acima</li>
                <li>Preencha os dados cadastrais de sua empresa (o CNPJ será preenchido automaticamente)</li>
                <li>Informe os valores unitários e totais, marcas/fabricantes e prazos de entrega para cada item solicitado</li>
                <li>Anexe documentação complementar, caso necessário (catálogos técnicos, certificados, fichas técnicas, declarações, etc.)</li>
                <li>Assine digitalmente no campo específico para validação da proposta comercial</li>
                <li>Revise atentamente todos os dados informados e envie sua proposta através do sistema</li>
            </ol>

            <div class="notice-box">
                <div class="notice-box-title">Observação</div>
                <div class="notice-box-text">
                    Recomenda-se que V.Sa. prepare previamente todas as informações comerciais e documentação
                    técnica necessárias antes de acessar o sistema, visando agilizar o processo de cotação e
                    garantir a tempestividade da proposta.
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-orgao">{{ $orgao['nome'] }}</div>
            <div class="footer-sistema">Sistema Cesta de Preços</div>

            <div class="footer-info">
                Esta é uma mensagem automática gerada pelo sistema.
                Em caso de dúvidas ou necessidade de esclarecimentos adicionais, favor entrar em contato através do
                e-mail <a href="mailto:suporte@dattatech.com.br">suporte@dattatech.com.br</a>
            </div>

            <div class="footer-copyright">
                © {{ date('Y') }} DattaTech - Sistema Cesta de Preços · Todos os direitos reservados
            </div>
        </div>

    </div>
</body>
</html>
