<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Inválido - Resposta CDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c5282 0%, #3b82c4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .icon-invalid { color: #e74c3c; }
        .icon-expired { color: #f39c12; }
        .icon-responded { color: #3498db; }
        .icon-error { color: #95a5a6; }
        h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        p {
            color: #7f8c8d;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: left;
        }
        .info-box strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="error-card">
        @if($motivo === 'token_invalido')
            <div class="error-icon icon-invalid">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Link Inválido</h1>
            <p>{{ $mensagem }}</p>
            <p>Este link pode ter sido digitado incorretamente ou não existe mais.</p>
        @elseif($motivo === 'ja_respondido')
            <div class="error-icon icon-responded">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Já Respondido</h1>
            <p>{{ $mensagem }}</p>
            @if(isset($data_resposta))
                <div class="info-box">
                    <strong>Data da resposta:</strong> {{ $data_resposta->format('d/m/Y \à\s H:i') }}
                </div>
            @endif
            <p class="mt-3"><small>Se você precisa fazer alguma alteração, entre em contato conosco.</small></p>
        @elseif($motivo === 'link_expirado')
            <div class="error-icon icon-expired">
                <i class="fas fa-clock"></i>
            </div>
            <h1>Prazo Expirado</h1>
            <p>{{ $mensagem }}</p>
            @if(isset($valido_ate))
                <div class="info-box">
                    <strong>Válido até:</strong> {{ $valido_ate->format('d/m/Y \à\s H:i') }}
                </div>
            @endif
            <p class="mt-3"><small>Entre em contato conosco se ainda deseja responder esta solicitação.</small></p>
        @else
            <div class="error-icon icon-error">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1>Erro ao Carregar</h1>
            <p>{{ $mensagem ?? 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.' }}</p>
        @endif

        <div class="mt-4">
            <p><strong>Precisa de ajuda?</strong></p>
            <p><small>Entre em contato conosco através do e-mail ou telefone fornecido na solicitação original.</small></p>
        </div>
    </div>
</body>
</html>
