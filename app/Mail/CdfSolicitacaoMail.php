<?php

namespace App\Mail;

use App\Models\SolicitacaoCDF;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class CdfSolicitacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitacao;

    /**
     * Create a new message instance.
     */
    public function __construct(SolicitacaoCDF $solicitacao)
    {
        $this->solicitacao = $solicitacao;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('suporte@dattatech.com.br', 'Cesta de Preços - DattaTech'),
            replyTo: [
                new Address('suporte@dattatech.com.br', 'Suporte DattaTech'),
            ],
            subject: 'Solicitação de Cotação - CDF #' . $this->solicitacao->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Obter dados do usuário e órgão através do orçamento
        $orcamento = $this->solicitacao->orcamento;
        $usuario = $orcamento ? $orcamento->user : null;

        // Tentar obter nome do órgão dos atributos da request ou usar fallback
        $nomeOrgao = request()->attributes->get('tenant')['name'] ?? 'DattaTech';
        $nomeUsuario = $usuario ? $usuario->name : 'Sistema';

        return new Content(
            view: 'emails.cdf-solicitacao',
            with: [
                'solicitacao' => $this->solicitacao,
                'linkResposta' => $this->solicitacao->url_resposta,
                'validoAte' => $this->solicitacao->valido_ate,
                'fornecedor' => [
                    'razao_social' => $this->solicitacao->razao_social,
                    'cnpj' => $this->solicitacao->cnpj,
                    'email' => $this->solicitacao->email,
                ],
                'orgao' => [
                    'nome' => $nomeOrgao,
                    'usuario' => $nomeUsuario,
                ]
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
