<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrientacaoTecnica;

class OrientacoesTecnicasSeeder extends Seeder
{
    /**
     * Seed das 28 Orientações Técnicas
     * Conteúdo completo fornecido pelo cliente
     */
    public function run(): void
    {
        $orientacoes = [
            [
                'numero' => 'OT-001',
                'titulo' => 'A Instrução Normativa SEGES nº 65/2021 se aplica aos municípios?',
                'conteudo' => '<p><strong>Resposta:</strong> A IN nº 65/2021 aplica-se aos órgãos da Administração Pública federal direta, autárquica e fundacional.</p>
<p>Entretanto, muitos municípios têm adotado as orientações da IN nº 65/2021 como referência de boas práticas na elaboração de orçamentos estimativos, mesmo não sendo juridicamente obrigados.</p>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 1º.</li>
    <li>Lei nº 14.133/21 (Nova Lei de Licitações) - aplicável a todos os entes da Federação.</li>
</ul>',
                'ordem' => 1
            ],
            [
                'numero' => 'OT-002',
                'titulo' => 'Quem pode e quem não pode elaborar o orçamento estimativo?',
                'conteudo' => '<p><strong>Pode elaborar:</strong></p>
<ul>
    <li>Servidor designado pelo órgão/entidade;</li>
    <li>Comissão de contratação;</li>
    <li>Setor de compras/licitações;</li>
    <li>Agente de contratação (para entidades que adotaram essa figura).</li>
</ul>
<p><strong>Não deve elaborar:</strong></p>
<ul>
    <li>Fornecedores interessados na contratação;</li>
    <li>Pessoas com conflito de interesses;</li>
    <li>Terceiros sem vínculo formal com a Administração.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, arts. 6º, XXIII, e 18, § 1º;</li>
    <li>IN SEGES nº 65/2021, art. 3º.</li>
</ul>',
                'ordem' => 2
            ],
            [
                'numero' => 'OT-003',
                'titulo' => 'Qual o momento adequado para a elaboração do orçamento estimativo?',
                'conteudo' => '<p><strong>Resposta:</strong> O orçamento estimativo deve ser elaborado durante a <strong>fase de planejamento da contratação</strong>, antes da publicação do edital ou convite.</p>
<p><strong>Momentos-chave:</strong></p>
<ul>
    <li><strong>Antes do Estudo Técnico Preliminar (ETP):</strong> para subsidiar análise de viabilidade;</li>
    <li><strong>Durante a elaboração do Termo de Referência/Projeto Básico:</strong> para validar se há dotação orçamentária suficiente;</li>
    <li><strong>Nunca após a licitação:</strong> o orçamento deve orientar o certame, não ser adaptado aos resultados.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 18, § 1º;</li>
    <li>IN SEGES nº 65/2021, art. 4º.</li>
</ul>',
                'ordem' => 3
            ],
            [
                'numero' => 'OT-004',
                'titulo' => 'É possível elaborar orçamento estimativo utilizando apenas uma ou duas amostras?',
                'conteudo' => '<p><strong>Resposta:</strong> Embora não seja ideal, é possível em casos excepcionais. O ideal é utilizar <strong>no mínimo 3 (três) amostras de preços</strong>.</p>
<p><strong>Quando menos de 3 amostras:</strong></p>
<ul>
    <li>Documente as tentativas de busca em diferentes fontes;</li>
    <li>Justifique formalmente a limitação;</li>
    <li>Considere realizar Cotação Direta com Fornecedores (CDF);</li>
    <li>Avalie se a contratação pode ser adiada até obter mais amostras.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 7º, § 5º;</li>
    <li>Princípio da competitividade e economicidade.</li>
</ul>',
                'ordem' => 4
            ],
            [
                'numero' => 'OT-005',
                'titulo' => 'O que fazer quando a pesquisa no Cesta de Preços não retornar resultados?',
                'conteudo' => '<p><strong>Resposta:</strong> Utilize fontes alternativas complementares:</p>
<p><strong>Fontes recomendadas:</strong></p>
<ol>
    <li><strong>Painel de Preços (Compras.gov):</strong> contratos de outros órgãos;</li>
    <li><strong>PNCP:</strong> contratações similares recentes;</li>
    <li><strong>Tabelas oficiais:</strong> SINAPI, SICRO, CMED (conforme aplicável);</li>
    <li><strong>Cotação Direta com Fornecedores (CDF):</strong> solicite orçamentos formais;</li>
    <li><strong>E-commerce:</strong> sites confiáveis (com prints e URLs);</li>
    <li><strong>Contratos anteriores:</strong> do próprio órgão (atualizados).</li>
</ol>
<p><strong>Importante:</strong> Documente TODAS as tentativas de pesquisa, mesmo as sem resultado.</p>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, arts. 5º e 6º.</li>
</ul>',
                'ordem' => 5
            ],
            [
                'numero' => 'OT-006',
                'titulo' => 'O que é amostra expurgada? Posso utilizá-la em minha cotação ou devo exclui-la?',
                'conteudo' => '<p><strong>Resposta:</strong> Amostra expurgada é aquela que foi <strong>excluída do cálculo</strong> por meio de análise estatística (saneamento), por estar muito distante da média ou mediana.</p>
<p><strong>Deve ser excluída quando:</strong></p>
<ul>
    <li>Estiver fora do intervalo do desvio-padrão (μ ± σ);</li>
    <li>Estiver acima/abaixo do percentual definido no saneamento percentual;</li>
    <li>For tecnicamente justificável a exclusão.</li>
</ul>
<p><strong>Não deve ser excluída arbitrariamente:</strong></p>
<ul>
    <li>A exclusão deve ser baseada em critérios estatísticos ou técnicos documentados;</li>
    <li>Nunca excluir apenas porque o preço é "inconveniente".</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 8º;</li>
    <li>Princípios da razoabilidade e motivação.</li>
</ul>',
                'ordem' => 6
            ],
            [
                'numero' => 'OT-007',
                'titulo' => 'É preciso elaborar orçamento estimativo antes de prorrogar contrato para prestação de serviços contínuos?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim</strong>, especialmente se houver reajuste de preços ou alteração quantitativa/qualitativa do contrato.</p>
<p><strong>Quando é obrigatório:</strong></p>
<ul>
    <li>Prorrogação com reajuste de preços;</li>
    <li>Aumento de quantitativo (acréscimo);</li>
    <li>Alteração nas especificações do serviço;</li>
    <li>Prorrogação após 60 meses (novo ciclo).</li>
</ul>
<p><strong>Quando pode ser dispensado:</strong></p>
<ul>
    <li>Prorrogação "pura e simples" (mesmos valores, sem reajuste);</li>
    <li>Fundamentar expressamente a dispensa no processo.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 107;</li>
    <li>IN SEGES nº 65/2021, art. 2º.</li>
</ul>',
                'ordem' => 7
            ],
            [
                'numero' => 'OT-008',
                'titulo' => 'É preciso elaborar orçamento estimativo antes de aderir a uma ata de registro de preços?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim</strong>, mesmo na adesão ("carona"), é fundamental validar se os preços registrados estão compatíveis com o mercado atual.</p>
<p><strong>Por quê?</strong></p>
<ul>
    <li>A ata pode estar desatualizada;</li>
    <li>Variações regionais podem tornar os preços incompatíveis;</li>
    <li>É necessário demonstrar vantajosidade;</li>
    <li>Fundamenta a decisão administrativa de aderir.</li>
</ul>
<p><strong>Como fazer:</strong></p>
<ol>
    <li>Elabore orçamento estimativo próprio;</li>
    <li>Compare com os preços da ata;</li>
    <li>Se os preços da ata forem superiores aos de mercado, negocie ou não adira;</li>
    <li>Documente a análise comparativa.</li>
</ol>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 86;</li>
    <li>Decreto nº 11.462/2023, art. 31 (adesão a ata).</li>
</ul>',
                'ordem' => 8
            ],
            [
                'numero' => 'OT-009',
                'titulo' => 'O orçamentista pode questionar a especificação do objeto ou demais condições de fornecimento?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim, deve questionar!</strong> O orçamentista tem o dever de alertar sobre especificações excessivas, restritivas ou incompatíveis com o mercado.</p>
<p><strong>Quando questionar:</strong></p>
<ul>
    <li>Especificação excessivamente restritiva (direcionamento);</li>
    <li>Marca/modelo específico sem justificativa técnica;</li>
    <li>Quantidades incompatíveis com a realidade;</li>
    <li>Condições de fornecimento inexequíveis;</li>
    <li>Prazos incompatíveis com o mercado.</li>
</ul>
<p><strong>Como proceder:</strong></p>
<ol>
    <li>Documente a incompatibilidade identificada;</li>
    <li>Notifique formalmente o setor requisitante;</li>
    <li>Solicite revisão ou justificativa técnica;</li>
    <li>Se persistir, registre a ressalva no processo.</li>
</ol>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 14 (vedação ao direcionamento);</li>
    <li>Princípios da competitividade e isonomia.</li>
</ul>',
                'ordem' => 9
            ],
            [
                'numero' => 'OT-010',
                'titulo' => 'É preciso elaborar orçamento estimativo antes de uma contratação direta por dispensa de licitação?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim</strong>, mesmo nas dispensas de licitação, o orçamento estimativo é obrigatório.</p>
<p><strong>Por quê?</strong></p>
<ul>
    <li>Demonstrar que o preço está compatível com o mercado;</li>
    <li>Fundamentar a contratação direta;</li>
    <li>Evitar superfaturamento;</li>
    <li>Cumprir princípio da economicidade.</li>
</ul>
<p><strong>Exceções (dispensável):</strong></p>
<ul>
    <li>Dispensa por emergência/calamidade (art. 75, VIII) - quando a urgência impedir;</li>
    <li>Dispensa por valor (art. 75, I e II) - mas recomenda-se fazer;</li>
    <li>Contratação com preços tabelados oficialmente.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 23, § 1º;</li>
    <li>IN SEGES nº 65/2021, art. 2º.</li>
</ul>',
                'ordem' => 10
            ],
            [
                'numero' => 'OT-011',
                'titulo' => 'Como elaborar orçamento estimativo no caso de contratação direta por inexigibilidade?',
                'conteudo' => '<p><strong>Resposta:</strong> Na inexigibilidade, o orçamento estimativo é mais desafiador, mas permanece necessário.</p>
<p><strong>Como proceder:</strong></p>
<ol>
    <li><strong>Fornecedor exclusivo:</strong> Pesquise contratos de outros órgãos que adquiriram do mesmo fornecedor;</li>
    <li><strong>Serviços técnicos especializados:</strong> Pesquise tabelas de honorários de conselhos profissionais (OAB, CREA, CRC);</li>
    <li><strong>Artista consagrado:</strong> Pesquise cachês de apresentações similares;</li>
    <li><strong>Não havendo parâmetros:</strong> Negocie com o contratado e justifique a razoabilidade dos valores.</li>
</ol>
<p><strong>Importante:</strong> Mesmo sem concorrência, o preço deve ser razoável e justificado.</p>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 74;</li>
    <li>Princípio da economicidade.</li>
</ul>',
                'ordem' => 11
            ],
            [
                'numero' => 'OT-012',
                'titulo' => 'É possível utilizar preços de sites da Internet nos orçamentos estimativos?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim</strong>, desde que observadas algumas condições de segurança e rastreabilidade.</p>
<p><strong>Boas práticas:</strong></p>
<ul>
    <li>Utilize sites confiáveis e conhecidos;</li>
    <li>Salve prints de tela mostrando: produto, especificações, preço, frete, data e hora;</li>
    <li>Registre a URL completa da página;</li>
    <li>Prefira sites de fornecedores diretos ou grandes varejistas;</li>
    <li>Confirme se o preço inclui ou não tributos/frete.</li>
</ul>
<p><strong>Atenção:</strong></p>
<ul>
    <li>Preços "promocionais" ou "relâmpago" devem ser usados com cautela;</li>
    <li>Verifique se o produto está realmente disponível (não "esgotado");</li>
    <li>Combine com outras fontes para validação.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 5º, VI;</li>
    <li>Princípio da razoabilidade.</li>
</ul>',
                'ordem' => 12
            ],
            [
                'numero' => 'OT-013',
                'titulo' => 'Como realizar Cotação Direta com Fornecedores (CDF) de forma segura?',
                'conteudo' => '<p><strong>Resposta:</strong> A CDF deve seguir procedimento formal e documentado para evitar questionamentos.</p>
<p><strong>Passo a passo seguro:</strong></p>
<ol>
    <li><strong>Formalize o pedido:</strong> Envie e-mail ou ofício com especificações completas do objeto;</li>
    <li><strong>Consulte múltiplos fornecedores:</strong> Mínimo de 3 (três) empresas do ramo;</li>
    <li><strong>Estabeleça prazo:</strong> Defina data-limite para resposta;</li>
    <li><strong>Exija documento formal:</strong> Orçamento em papel timbrado, com CNPJ, validade, assinatura;</li>
    <li><strong>Documente tudo:</strong> Salve e-mails, comprovantes de envio, respostas ou ausência delas;</li>
    <li><strong>Evite direcionamento:</strong> Não indique marca ou fornecedor preferencial.</li>
</ol>
<p><strong>Modelo de solicitação:</strong></p>
<pre>Assunto: Solicitação de Orçamento - [Objeto]

Prezados,

Solicitamos orçamento para o seguinte objeto:
- Descrição: [...]
- Quantidade: [...]
- Especificações: [...]
- Condições de fornecimento: [...]

Prazo para resposta: [data]
Validade mínima do orçamento: 60 dias

Att,
[Setor de Compras]</pre>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 5º, V;</li>
    <li>Princípio da impessoalidade.</li>
</ul>',
                'ordem' => 13
            ],
            [
                'numero' => 'OT-014',
                'titulo' => 'O que é Curva ABC? Para que serve?',
                'conteudo' => '<p><strong>Resposta:</strong> A Curva ABC é uma ferramenta de gestão que classifica itens por importância financeira.</p>
<p><strong>Classificação:</strong></p>
<ul>
    <li><strong>Classe A:</strong> Itens de maior valor agregado (~20% dos itens representam ~80% do valor total);</li>
    <li><strong>Classe B:</strong> Itens de valor intermediário (~30% dos itens representam ~15% do valor);</li>
    <li><strong>Classe C:</strong> Itens de menor valor (~50% dos itens representam ~5% do valor).</li>
</ul>
<p><strong>Para que serve no orçamento estimativo?</strong></p>
<ul>
    <li>Priorizar esforço de pesquisa nos itens de maior impacto financeiro (Classe A);</li>
    <li>Definir nível de detalhamento da pesquisa (mais amostras para itens A);</li>
    <li>Otimizar tempo e recursos da equipe de orçamentação;</li>
    <li>Fundamentar estratégias de contratação (agrupar itens C em lotes).</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 7º, § 4º (proporcionalidade);</li>
    <li>Princípio da eficiência.</li>
</ul>',
                'ordem' => 14
            ],
            [
                'numero' => 'OT-015',
                'titulo' => 'Qual o prazo de validade das pesquisas?',
                'conteudo' => '<p><strong>Resposta:</strong> Não há prazo único. Depende da natureza do objeto e da volatilidade dos preços.</p>
<p><strong>Recomendações:</strong></p>
<ul>
    <li><strong>Produtos com preços voláteis:</strong> Máximo 30 dias (ex: combustíveis, commodities);</li>
    <li><strong>Produtos com preços estáveis:</strong> Até 180 dias (ex: materiais de escritório, mobiliário);</li>
    <li><strong>Obras e serviços de engenharia:</strong> Até 12 meses (se houver atualização por índices);</li>
    <li><strong>Regra geral:</strong> 90 a 120 dias.</li>
</ul>
<p><strong>Atenção:</strong></p>
<ul>
    <li>Se o preço mudou significativamente, refazer a pesquisa;</li>
    <li>Documentar a data de cada pesquisa;</li>
    <li>Considerar sazonalidade (ex: material escolar em janeiro vs julho).</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 7º, § 6º;</li>
    <li>Princípio da atualidade dos preços.</li>
</ul>',
                'ordem' => 15
            ],
            [
                'numero' => 'OT-016',
                'titulo' => 'Os preços referenciais do orçamento estimativo são os preços máximos?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Não necessariamente</strong>. Há nuances importantes.</p>
<p><strong>Entendimento correto:</strong></p>
<ul>
    <li><strong>Em licitações:</strong> O orçamento estimativo <strong>pode ser usado como preço máximo</strong>, mas não é obrigatório. Pode-se aceitar proposta acima, desde que haja justificativa (ex: necessidade urgente, único fornecedor);</li>
    <li><strong>Em contratações diretas:</strong> O orçamento funciona como <strong>referência de razoabilidade</strong>, não como teto absoluto;</li>
    <li><strong>Regra de ouro:</strong> Qualquer valor acima do orçamento deve ser <strong>excepcionalmente justificado</strong>.</li>
</ul>
<p><strong>Quando é preço máximo:</strong></p>
<ul>
    <li>Quando o edital/termo de referência expressamente estabelecer;</li>
    <li>Em pregões eletrônicos (limite de lance inicial);</li>
    <li>Em contratações com dotação orçamentária exata.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 23, § 2º;</li>
    <li>IN SEGES nº 65/2021, art. 3º, § 1º.</li>
</ul>',
                'ordem' => 16
            ],
            [
                'numero' => 'OT-017',
                'titulo' => 'Como fazer orçamento de cursos e capacitações?',
                'conteudo' => '<p><strong>Resposta:</strong> Cursos e capacitações têm particularidades.</p>
<p><strong>Metodologia recomendada:</strong></p>
<ol>
    <li><strong>Pesquise instituições especializadas:</strong> Escolas de governo, universidades, empresas de treinamento;</li>
    <li><strong>Solicite propostas formais (CDF):</strong> Com programa detalhado, carga horária, corpo docente;</li>
    <li><strong>Compare itens equivalentes:</strong>
        <ul>
            <li>Valor por hora/aula;</li>
            <li>Valor por aluno;</li>
            <li>Inclui material didático?</li>
            <li>Inclui certificado?</li>
            <li>Modalidade (presencial, EAD, híbrido);</li>
        </ul>
    </li>
    <li><strong>Verifique se há tabelas oficiais:</strong> Algumas escolas de governo têm valores tabelados.</li>
</ol>
<p><strong>Cuidados:</strong></p>
<ul>
    <li>Não direcionar para instituição específica sem justificativa técnica;</li>
    <li>Avaliar a qualificação do corpo docente;</li>
    <li>Verificar se há custos adicionais (hospedagem, transporte, alimentação).</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 5º;</li>
    <li>Lei nº 14.133/21, art. 12, VII (contratação de capacitação).</li>
</ul>',
                'ordem' => 17
            ],
            [
                'numero' => 'OT-018',
                'titulo' => 'É permitido utilizar amostras de contratações similares de outros entes públicos com validade expirada?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim, desde que atualizadas</strong> por índices oficiais.</p>
<p><strong>Como proceder:</strong></p>
<ol>
    <li><strong>Identifique o contrato similar:</strong> Mesmo objeto, especificações compatíveis;</li>
    <li><strong>Verifique a data do contrato:</strong> Quanto mais recente, melhor;</li>
    <li><strong>Atualize o valor:</strong> Utilize índices oficiais (INPC, IPCA, IGP-M) ou índices setoriais;</li>
    <li><strong>Documente o cálculo:</strong>
        <ul>
            <li>Valor original: R$ X,XX</li>
            <li>Data original: dd/mm/aaaa</li>
            <li>Índice aplicado: [nome do índice]</li>
            <li>Variação do índice: X%</li>
            <li>Valor atualizado: R$ Y,YY</li>
        </ul>
    </li>
</ol>
<p><strong>Exemplo de cálculo:</strong></p>
<pre>Valor original (jan/2023): R$ 100,00
Índice: IPCA
Variação jan/2023 a out/2024: 8,5%
Valor atualizado: R$ 100,00 × 1,085 = R$ 108,50</pre>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 5º, II;</li>
    <li>Lei nº 14.133/21, art. 23, § 1º.</li>
</ul>',
                'ordem' => 18
            ],
            [
                'numero' => 'OT-019',
                'titulo' => 'É possível ao orçamentista aumentar ou diminuir o valor do orçamento estimado?',
                'conteudo' => '<p><strong>Resposta:</strong> O orçamentista <strong>não deve arbitrariamente</strong> aumentar ou diminuir valores. Qualquer alteração deve ser <strong>tecnicamente justificada</strong>.</p>
<p><strong>Quando é permitido ajustar:</strong></p>
<ul>
    <li><strong>Após saneamento estatístico:</strong> Exclusão de outliers (amostras muito distantes);</li>
    <li><strong>Após detecção de erro:</strong> Erro de conversão de unidades, erro de digitação;</li>
    <li><strong>Mudança de especificação:</strong> Setor requisitante alterou o objeto;</li>
    <li><strong>Atualização temporal:</strong> Pesquisa ficou desatualizada, novos valores de mercado.</li>
</ul>
<p><strong>Quando NÃO é permitido:</strong></p>
<ul>
    <li>Para "ajustar" ao orçamento disponível;</li>
    <li>Para favorecer ou prejudicar fornecedores;</li>
    <li>Por pressão externa (gestores, fornecedores);</li>
    <li>Sem justificativa técnica documentada.</li>
</ul>
<p><strong>Como proceder:</strong></p>
<ol>
    <li>Documente o motivo da alteração;</li>
    <li>Justifique tecnicamente;</li>
    <li>Mantenha histórico das versões (v1, v2, etc.);</li>
    <li>Submeta à aprovação da autoridade competente.</li>
</ol>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Princípios da impessoalidade, moralidade e motivação dos atos;</li>
    <li>IN SEGES nº 65/2021, art. 3º.</li>
</ul>',
                'ordem' => 19
            ],
            [
                'numero' => 'OT-020',
                'titulo' => 'O orçamento estimativo pode ser sigiloso?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim, em casos específicos</strong>.</p>
<p><strong>Quando pode ser sigiloso:</strong></p>
<ul>
    <li><strong>Licitações do tipo "menor preço" ou "maior desconto":</strong> Recomenda-se sigilo até a abertura das propostas para evitar direcionamento;</li>
    <li><strong>Contratações estratégicas:</strong> Segurança pública, defesa nacional;</li>
    <li><strong>Quando previsto no edital:</strong> Fundamentar a decisão de sigilo.</li>
</ul>
<p><strong>Quando NÃO pode ser sigiloso:</strong></p>
<ul>
    <li><strong>Técnica e Preço:</strong> Deve estar disponível para análise técnica;</li>
    <li><strong>Após a licitação:</strong> Torna-se público;</li>
    <li><strong>Quando houver pedido de acesso à informação:</strong> Salvo exceções legais (LAI).</li>
</ul>
<p><strong>Atenção:</strong></p>
<ul>
    <li>Mesmo sigiloso <strong>durante</strong> a licitação, o orçamento deve constar no processo;</li>
    <li>Sigilo não significa ausência de controle - órgãos de controle têm acesso.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 24, § 2º;</li>
    <li>IN SEGES nº 65/2021, art. 3º, § 2º;</li>
    <li>Lei nº 12.527/2011 (LAI), art. 23 (exceções ao acesso).</li>
</ul>',
                'ordem' => 20
            ],
            [
                'numero' => 'OT-021',
                'titulo' => 'Como se elabora o orçamento de tecnologia da informação (TI)?',
                'conteudo' => '<p><strong>Resposta:</strong> TI possui metodologia específica devido à complexidade e rápida evolução tecnológica.</p>
<p><strong>Etapas recomendadas:</strong></p>
<ol>
    <li><strong>Classifique o tipo de contratação:</strong>
        <ul>
            <li>Hardware (equipamentos);</li>
            <li>Software (licenças, desenvolvimento);</li>
            <li>Serviços (manutenção, suporte, desenvolvimento);</li>
            <li>Solução completa (integrada).</li>
        </ul>
    </li>
    <li><strong>Hardware:</strong>
        <ul>
            <li>Pesquise especificações técnicas mínimas (não marca);</li>
            <li>Utilize e-commerce, distribuidores autorizados, Painel de Preços;</li>
            <li>Atenção para garantia, suporte técnico, obsolescência.</li>
        </ul>
    </li>
    <li><strong>Software:</strong>
        <ul>
            <li>Licenças perpétuas vs assinatura (SaaS);</li>
            <li>Número de usuários/dispositivos;</li>
            <li>Consulte tabelas de fabricantes (Microsoft, Adobe, etc.);</li>
            <li>Atenção para custos recorrentes (renovação anual).</li>
        </ul>
    </li>
    <li><strong>Desenvolvimento de software:</strong>
        <ul>
            <li>Utilize métrica de Pontos de Função (quando aplicável);</li>
            <li>Pesquise valores de hora técnica (desenvolvedor, analista);</li>
            <li>Consulte contratos similares de desenvolvimento;</li>
            <li>Considere metodologia ágil vs tradicional.</li>
        </ul>
    </li>
    <li><strong>Serviços técnicos:</strong>
        <ul>
            <li>Hora técnica de profissionais (nível júnior, pleno, sênior);</li>
            <li>Certificações exigidas (impactam no preço);</li>
            <li>SLA (acordo de nível de serviço) - 24x7 custa mais.</li>
        </ul>
    </li>
</ol>
<p><strong>Fontes recomendadas:</strong></p>
<ul>
    <li>Painel de Preços TIC (Compras.gov);</li>
    <li>Contratos de TI no PNCP;</li>
    <li>Tabelas de fabricantes;</li>
    <li>Cotação com integradores/distribuidores;</li>
    <li>Referências de mercado (Gartner, IDC).</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SGD/ME nº 1/2019 (Contratação de TIC);</li>
    <li>IN SEGES nº 65/2021, arts. 5º e 6º.</li>
</ul>',
                'ordem' => 21
            ],
            [
                'numero' => 'OT-022',
                'titulo' => 'Como se elabora o orçamento de prestação de serviços com dedicação de mão de obra exclusiva?',
                'conteudo' => '<p><strong>Resposta:</strong> Este tipo de contratação (ex: vigilância, limpeza, recepção) exige cálculo detalhado dos custos de pessoal.</p>
<p><strong>Componentes do preço:</strong></p>
<ol>
    <li><strong>Remuneração (Módulo 1):</strong>
        <ul>
            <li>Salário-base da categoria (convenção coletiva);</li>
            <li>Adicional de periculosidade/insalubridade (se aplicável);</li>
            <li>Adicional noturno;</li>
            <li>Hora extra (se prevista);</li>
            <li>Outros adicionais.</li>
        </ul>
    </li>
    <li><strong>Encargos Sociais e Trabalhistas (Módulo 2):</strong>
        <ul>
            <li>INSS, FGTS, FGTS rescisório;</li>
            <li>Férias + 1/3 constitucional;</li>
            <li>13º salário;</li>
            <li>Aviso prévio;</li>
            <li>Outros (SAT, Sistema S, etc.).</li>
        </ul>
    </li>
    <li><strong>Insumos (Módulo 3):</strong>
        <ul>
            <li>Uniformes;</li>
            <li>Equipamentos (rádios, lanternas, etc.);</li>
            <li>Materiais de consumo;</li>
            <li>Treinamento.</li>
        </ul>
    </li>
    <li><strong>Custos Administrativos e Lucro (Módulo 4):</strong>
        <ul>
            <li>Taxa de administração;</li>
            <li>Lucro (razoável, compatível com o mercado);</li>
            <li>Tributos (ISS, PIS, COFINS, IR, CS).</li>
        </ul>
    </li>
</ol>
<p><strong>Fontes de pesquisa:</strong></p>
<ul>
    <li><strong>Convenção Coletiva da categoria:</strong> Para salário-base;</li>
    <li><strong>Painel de Preços:</strong> Filtrar por serviços similares;</li>
    <li><strong>Calculadora SEGES:</strong> Disponível no portal gov.br;</li>
    <li><strong>Contratos similares:</strong> PNCP, outros órgãos.</li>
</ul>
<p><strong>Atenção:</strong></p>
<ul>
    <li>Encargos variam conforme o regime de contratação (CLT, intermitente);</li>
    <li>Taxa de administração típica: 5% a 15%;</li>
    <li>Lucro típico: 5% a 10%;</li>
    <li>Revisar anualmente (dissídio da categoria).</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 5/2017 (revogada, mas ainda referência);</li>
    <li>IN SEGES nº 65/2021, arts. 5º e 9º;</li>
    <li>Cadernos SEGES (disponíveis no portal gov.br).</li>
</ul>',
                'ordem' => 22
            ],
            [
                'numero' => 'OT-023',
                'titulo' => 'Como se elabora o orçamento de obras e serviços de engenharia?',
                'conteudo' => '<p><strong>Resposta:</strong> Obras de engenharia possuem metodologia própria e detalhada.</p>
<p><strong>Etapas obrigatórias:</strong></p>
<ol>
    <li><strong>Levantamento de quantidades:</strong>
        <ul>
            <li>Deve ser baseado em projeto executivo (ou básico, minimamente);</li>
            <li>Memória de cálculo detalhada;</li>
            <li>Planilha de quantitativos (SINAPI/SICRO).</li>
        </ul>
    </li>
    <li><strong>Composição de custos:</strong>
        <ul>
            <li>Utilize tabelas oficiais: <strong>SINAPI</strong> (edificações) ou <strong>SICRO</strong> (rodovias/infraestrutura);</li>
            <li>Se o item não constar nas tabelas: crie composição própria justificada;</li>
            <li>BDI (Bonificação e Despesas Indiretas): percentual compatível com acórdãos TCU.</li>
        </ul>
    </li>
    <li><strong>Desoneração:</strong>
        <ul>
            <li>Verificar se aplica desoneração da folha de pagamento;</li>
            <li>Usar referências SINAPI desonerado ou não-desonerado conforme o caso.</li>
        </ul>
    </li>
    <li><strong>Cronograma físico-financeiro:</strong>
        <ul>
            <li>Distribuição dos custos ao longo do tempo;</li>
            <li>Fundamental para planejamento orçamentário.</li>
        </ul>
    </li>
</ol>
<p><strong>Fontes obrigatórias/preferenciais:</strong></p>
<ul>
    <li><strong>SINAPI:</strong> Sistema Nacional de Pesquisa de Custos e Índices da Construção Civil (Caixa);</li>
    <li><strong>SICRO:</strong> Sistema de Custos Rodoviários (DNIT);</li>
    <li><strong>Tabelas estaduais:</strong> Alguns estados têm tabelas próprias (ex: EMOP-RJ);</li>
    <li><strong>Composições próprias:</strong> Quando não houver nas tabelas (justificar).</li>
</ul>
<p><strong>BDI - Bonificação e Despesas Indiretas:</strong></p>
<ul>
    <li>Percentual que cobre: administração central, lucro, tributos, seguros, garantias;</li>
    <li><strong>Referência TCU:</strong> Acórdão 2.622/2013 - Plenário (fórmula de cálculo);</li>
    <li><strong>Valores típicos:</strong> 20% a 30% (depende da complexidade da obra).</li>
</ul>
<p><strong>Atenção:</strong></p>
<ul>
    <li>Projeto deficiente = orçamento impreciso = problemas na execução;</li>
    <li>Reajuste de preços: Usar INCC ou SINAPI (índice mensal);</li>
    <li>Obras complexas: Consultar engenheiro responsável.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 6º, XXIV (projeto básico/executivo);</li>
    <li>IN SEGES nº 65/2021, art. 10;</li>
    <li>Acórdãos TCU sobre BDI e orçamento de obras.</li>
</ul>',
                'ordem' => 23
            ],
            [
                'numero' => 'OT-024',
                'titulo' => 'É possível adotar metodologia de orçamentação diferente da fixada na IN SEGES nº 65/21?',
                'conteudo' => '<p><strong>Resposta:</strong> <strong>Sim</strong>, especialmente para entes não obrigados (estados, municípios) ou em casos específicos justificados.</p>
<p><strong>Quando é permitido:</strong></p>
<ul>
    <li><strong>Entes municipais/estaduais:</strong> Não estão obrigados à IN federal, podem adotar normas próprias;</li>
    <li><strong>Objetos específicos:</strong> Quando a natureza do objeto exigir metodologia diferenciada (ex: obras de arte, restauro);</li>
    <li><strong>Metodologia mais rigorosa:</strong> Sempre permitido adotar critérios mais exigentes que a IN;</li>
    <li><strong>Adaptações justificadas:</strong> Desde que fundamentadas tecnicamente.</li>
</ul>
<p><strong>Quando NÃO é permitido (órgãos federais):</strong></p>
<ul>
    <li>Adotar metodologia menos rigorosa sem justificativa;</li>
    <li>Ignorar completamente a IN 65/2021 (é vinculante para União).</li>
</ul>
<p><strong>Boas práticas:</strong></p>
<ol>
    <li>Documente a metodologia adotada;</li>
    <li>Justifique tecnicamente as diferenças em relação à IN;</li>
    <li>Mantenha coerência (use a mesma metodologia em casos similares);</li>
    <li>Submeta à aprovação da autoridade competente.</li>
</ol>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 1º (âmbito de aplicação - União);</li>
    <li>Princípio da autonomia federativa (art. 18 da CF/88);</li>
    <li>Princípio da razoabilidade e motivação.</li>
</ul>',
                'ordem' => 24
            ],
            [
                'numero' => 'OT-025',
                'titulo' => 'Existe uma ordem de preferência entre as fontes (parâmetros) de pesquisa?',
                'conteudo' => '<p><strong>Resposta:</strong> Não há hierarquia rígida, mas há fontes <strong>mais recomendadas</strong> conforme o objeto.</p>
<p><strong>Ordem sugerida (ordem decrescente de confiabilidade):</strong></p>
<ol>
    <li><strong>Tabelas oficiais de preços:</strong>
        <ul>
            <li>SINAPI, SICRO (engenharia);</li>
            <li>CMED (medicamentos);</li>
            <li>Tabelas de órgãos reguladores.</li>
        </ul>
    </li>
    <li><strong>Painel de Preços (Compras.gov):</strong>
        <ul>
            <li>Compras recentes da Administração Pública;</li>
            <li>Filtrar por período, localidade, órgão.</li>
        </ul>
    </li>
    <li><strong>Contratos públicos (PNCP):</strong>
        <ul>
            <li>Contratos similares de outros órgãos;</li>
            <li>Preferir contratos recentes e de entes próximos.</li>
        </ul>
    </li>
    <li><strong>Cotação Direta com Fornecedores (CDF):</strong>
        <ul>
            <li>Orçamentos formais de empresas do ramo;</li>
            <li>Mínimo de 3 fornecedores.</li>
        </ul>
    </li>
    <li><strong>Pesquisa em mídia especializada:</strong>
        <ul>
            <li>Revistas técnicas, catálogos de fabricantes;</li>
            <li>Sites especializados.</li>
        </ul>
    </li>
    <li><strong>Sites de e-commerce:</strong>
        <ul>
            <li>Uso complementar;</li>
            <li>Atenção para promoções atípicas.</li>
        </ul>
    </li>
    <li><strong>Contratos anteriores do próprio órgão:</strong>
        <ul>
            <li>Usar como base, mas validar atualidade;</li>
            <li>Atualizar por índices se necessário.</li>
        </ul>
    </li>
</ol>
<p><strong>Princípio:</strong></p>
<ul>
    <li>Quanto mais <strong>oficial, pública e rastreável</strong> a fonte, maior a confiabilidade;</li>
    <li>Sempre combinar <strong>múltiplas fontes</strong> para validação cruzada.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, arts. 5º e 6º;</li>
    <li>Princípio da publicidade e rastreabilidade.</li>
</ul>',
                'ordem' => 25
            ],
            [
                'numero' => 'OT-026',
                'titulo' => 'O que é "cesta de preços aceitável"?',
                'conteudo' => '<p><strong>Resposta:</strong> "Cesta de preços aceitável" é o conjunto de amostras de preços que, após tratamento estatístico (saneamento), representa valores de mercado confiáveis e compatíveis.</p>
<p><strong>Características de uma cesta aceitável:</strong></p>
<ul>
    <li><strong>Quantidade suficiente:</strong> Mínimo de 3 amostras (idealmente 5 ou mais);</li>
    <li><strong>Dispersão controlada:</strong> Baixo coeficiente de variação (CV < 25% idealmente);</li>
    <li><strong>Amostras recentes:</strong> Dentro do prazo de validade (90-180 dias);</li>
    <li><strong>Fontes confiáveis:</strong> Preferencialmente oficiais ou rastreáveis;</li>
    <li><strong>Comparabilidade:</strong> Mesma especificação, unidade, condições de fornecimento.</li>
</ul>
<p><strong>Quando a cesta NÃO é aceitável:</strong></p>
<ul>
    <li>Amostras insuficientes (menos de 3);</li>
    <li>Dispersão excessiva (CV > 50%) sem saneamento;</li>
    <li>Amostras desatualizadas ou incomparáveis;</li>
    <li>Fontes não rastreáveis ou duvidosas.</li>
</ul>
<p><strong>Como formar uma cesta aceitável:</strong></p>
<ol>
    <li>Colete amostras de múltiplas fontes;</li>
    <li>Verifique comparabilidade (especificação, unidade);</li>
    <li>Aplique saneamento estatístico (excluir outliers);</li>
    <li>Calcule média, mediana ou menor preço conforme metodologia;</li>
    <li>Documente todo o processo.</li>
</ol>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 7º;</li>
    <li>Princípios da razoabilidade e economicidade.</li>
</ul>',
                'ordem' => 26
            ],
            [
                'numero' => 'OT-027',
                'titulo' => 'O que é análise crítica do orçamento?',
                'conteudo' => '<p><strong>Resposta:</strong> Análise crítica é a revisão técnica e fundamentada do orçamento estimativo antes da sua aprovação, visando garantir consistência, razoabilidade e aderência às normas.</p>
<p><strong>O que deve ser verificado:</strong></p>
<ol>
    <li><strong>Metodologia aplicada:</strong>
        <ul>
            <li>Foi seguida a metodologia prevista na IN 65/2021 (ou norma local)?</li>
            <li>A metodologia está justificada?</li>
        </ul>
    </li>
    <li><strong>Fontes de pesquisa:</strong>
        <ul>
            <li>As fontes são confiáveis e rastreáveis?</li>
            <li>Há diversidade de fontes?</li>
            <li>As pesquisas estão atualizadas?</li>
        </ul>
    </li>
    <li><strong>Quantidade e qualidade das amostras:</strong>
        <ul>
            <li>Há amostras suficientes (mínimo 3)?</li>
            <li>As amostras são comparáveis (mesma especificação)?</li>
            <li>Foi aplicado saneamento estatístico adequado?</li>
        </ul>
    </li>
    <li><strong>Cálculos estatísticos:</strong>
        <ul>
            <li>Média, mediana, desvio-padrão estão corretos?</li>
            <li>Coeficiente de variação (CV) está adequado?</li>
            <li>Método de saneamento (DP ou percentual) foi aplicado corretamente?</li>
        </ul>
    </li>
    <li><strong>Razoabilidade dos preços:</strong>
        <ul>
            <li>Os valores finais são compatíveis com a realidade de mercado?</li>
            <li>Não há preços absurdamente altos ou baixos sem justificativa?</li>
        </ul>
    </li>
    <li><strong>Documentação:</strong>
        <ul>
            <li>Todas as pesquisas estão documentadas (prints, URLs, e-mails)?</li>
            <li>As exclusões de amostras estão justificadas?</li>
            <li>O memorial de cálculo está claro?</li>
        </ul>
    </li>
    <li><strong>Aspectos formais:</strong>
        <ul>
            <li>Há assinatura do orçamentista?</li>
            <li>Há data da elaboração?</li>
            <li>Há aprovação da autoridade competente?</li>
        </ul>
    </li>
</ol>
<p><strong>Quem deve fazer a análise crítica:</strong></p>
<ul>
    <li>Idealmente, pessoa diferente do orçamentista (segregação de funções);</li>
    <li>Pode ser: superior hierárquico, comissão de licitação, setor jurídico, controle interno.</li>
</ul>
<p><strong>Resultado da análise:</strong></p>
<ul>
    <li><strong>Aprovado:</strong> Orçamento está adequado, pode prosseguir;</li>
    <li><strong>Aprovado com ressalvas:</strong> Pequenos ajustes necessários;</li>
    <li><strong>Reprovado:</strong> Necessário refazer o orçamento.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>IN SEGES nº 65/2021, art. 8º (Análise estatística);</li>
    <li>Princípios da eficiência e controle interno.</li>
</ul>',
                'ordem' => 27
            ],
            [
                'numero' => 'OT-028',
                'titulo' => 'Indícios de Fraude nas Cotações Diretas',
                'conteudo' => '<p><strong>Resposta:</strong> Ao realizar Cotação Direta com Fornecedores (CDF), fique atento a possíveis indícios de fraude ou conluio.</p>
<p><strong>Sinais de alerta (Red Flags):</strong></p>
<ol>
    <li><strong>Orçamentos idênticos ou muito similares:</strong>
        <ul>
            <li>Valores exatamente iguais de fornecedores diferentes;</li>
            <li>Mesma formatação, mesmos erros de digitação;</li>
            <li>Mesma estrutura de texto.</li>
        </ul>
    </li>
    <li><strong>Empresas com vínculos:</strong>
        <ul>
            <li>Mesmo endereço, mesmo telefone;</li>
            <li>Sócios em comum (cruzamento de CNPJs);</li>
            <li>Empresas criadas recentemente (poucos meses).</li>
        </ul>
    </li>
    <li><strong>Respostas simultâneas:</strong>
        <ul>
            <li>Todos os fornecedores respondem no mesmo horário;</li>
            <li>E-mails enviados de redes IP próximas ou idênticas.</li>
        </ul>
    </li>
    <li><strong>Valores muito próximos da estimativa interna:</strong>
        <ul>
            <li>Se você divulgou valores aproximados, e todas cotações vieram próximas (possível "ancoragem").</li>
        </ul>
    </li>
    <li><strong>Fornecedores que não são do ramo:</strong>
        <ul>
            <li>CNAE não compatível com o objeto;</li>
            <li>Empresa sem histórico de fornecimento daquele produto/serviço.</li>
        </ul>
    </li>
    <li><strong>Recusa em fornecer informações:</strong>
        <ul>
            <li>Fornecedor não quer detalhar a composição de preços;</li>
            <li>Orçamento genérico, sem detalhamento.</li>
        </ul>
    </li>
</ol>
<p><strong>Como proceder ao identificar indícios:</strong></p>
<ol>
    <li><strong>Documente:</strong> Registre os indícios encontrados;</li>
    <li><strong>Investigue:</strong>
        <ul>
            <li>Consulte CNPJ na Receita Federal (quadro societário, endereço);</li>
            <li>Verifique histórico de contratações (PNCP);</li>
            <li>Pesquise se há processos administrativos contra essas empresas.</li>
        </ul>
    </li>
    <li><strong>Desconsidere cotações suspeitas:</strong> Fundamente a exclusão;</li>
    <li><strong>Busque novos fornecedores:</strong> Amplie a pesquisa;</li>
    <li><strong>Comunique superiores:</strong> Informe o gestor/autoridade sobre os indícios;</li>
    <li><strong>Considere comunicar órgãos de controle:</strong> Se houver fortes indícios de fraude, comunique CGU, TCU, TCE, Ministério Público.</li>
</ol>
<p><strong>Prevenção:</strong></p>
<ul>
    <li>Não divulgue valores estimados antes das cotações;</li>
    <li>Solicite orçamentos detalhados (composição de custos);</li>
    <li>Exija documentação comprobatória (papel timbrado, CNPJ, assinatura);</li>
    <li>Diversifique fontes de pesquisa (não apenas CDF);</li>
    <li>Cruze informações entre diferentes pesquisas.</li>
</ul>
<p><strong>Fundamento:</strong></p>
<ul>
    <li>Lei nº 14.133/21, art. 3º, I (princípio da legalidade e moralidade);</li>
    <li>Lei nº 12.846/2013 (Lei Anticorrupção);</li>
    <li>Código Penal, art. 90 (fraude em licitação);</li>
    <li>Resoluções e jurisprudência dos Tribunais de Contas.</li>
</ul>',
                'ordem' => 28
            ],
        ];

        foreach ($orientacoes as $orientacao) {
            OrientacaoTecnica::updateOrCreate(
                ['numero' => $orientacao['numero']],
                [
                    'titulo' => $orientacao['titulo'],
                    'conteudo' => $orientacao['conteudo'],
                    'ordem' => $orientacao['ordem'],
                    'ativo' => true
                ]
            );
        }

        if ($this->command) {
            $this->command->info('✅ 28 Orientações Técnicas importadas com sucesso!');
        }
    }
}
