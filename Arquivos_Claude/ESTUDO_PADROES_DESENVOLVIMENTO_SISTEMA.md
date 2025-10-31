# ESTUDO ESPECIALIZADO - PADRÕES DE DESENVOLVIMENTO DO SISTEMA

**Data:** 31 de Outubro de 2025  
**Módulo:** Cesta de Preços  
**Nível de Detalhamento:** Very Thorough  
**Versão:** 1.0

---

## ÍNDICE

1. [Padrões de Migrations](#1-padrões-de-migrations)
2. [Padrões de Controllers](#2-padrões-de-controllers)
3. [Padrões de Models](#3-padrões-de-models)
4. [Padrões de Rotas](#4-padrões-de-rotas)
5. [Padrões JavaScript](#5-padrões-javascript)
6. [Padrões de Views](#6-padrões-de-views)
7. [Resumo Executivo](#7-resumo-executivo)

---

## 1. PADRÕES DE MIGRATIONS

### 1.1 Convenções de Nomenclatura

#### Estrutura do Nome
```
YYYY_MM_DD_HHMMSS_acao_tabela_descricao.php
```

**Exemplos Reais:**
- `2025_10_01_122007_create_cp_itens_orcamento_table.php`
- `2025_10_18_213955_add_tenant_id_to_all_tables.php`
- `2025_10_24_160533_corrigir_prefixo_tabelas_inconsistentes.php`
- `2025_10_27_150000_increase_telefone_length_all_tables.php`

#### Padrão de Ações
- **create_** - Criação de nova tabela
- **add_** - Adicionar colunas
- **drop_** - Remover colunas
- **update_** - Modificar colunas existentes
- **fix_** - Correções estruturais
- **corrigir_** - Correções (português aceito)

### 1.2 Prefixos Obrigatórios

**REGRA CRÍTICA:** Todas as tabelas DEVEM ter prefixo `cp_`

#### Justificativa
- **Isolamento:** Separar tabelas do módulo no banco compartilhado
- **Multitenancy:** Facilitar identificação de tabelas do módulo
- **Organização:** Evitar conflitos com outras aplicações

#### Exemplos Corretos
```php
'cp_orcamentos'
'cp_itens_orcamento'
'cp_fornecedores'
'cp_solicitacoes_cdf'
'cp_medicamentos_cmed'
'cp_catmat'
```

#### Migration de Correção de Prefixos
```php
<?php
// 2025_10_24_160533_corrigir_prefixo_tabelas_inconsistentes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrigir tabelas que foram criadas sem o prefixo cp_
     *
     * CONTEXTO:
     * - Todas as tabelas do módulo devem ter prefixo cp_
     * - 2 migrations criaram tabelas sem o prefixo
     * - Esta migration corrige renomeando as tabelas
     */
    public function up(): void
    {
        // Renomear checkpoint_importacao para cp_checkpoint_importacao
        if (Schema::hasTable('checkpoint_importacao') 
            && !Schema::hasTable('cp_checkpoint_importacao')) {
            Schema::rename('checkpoint_importacao', 'cp_checkpoint_importacao');
            
            DB::statement('COMMENT ON TABLE cp_checkpoint_importacao IS \'...\';');
        }

        // Renomear consultas_pncp_cache para cp_consultas_pncp_cache
        if (Schema::hasTable('consultas_pncp_cache') 
            && !Schema::hasTable('cp_consultas_pncp_cache')) {
            Schema::rename('consultas_pncp_cache', 'cp_consultas_pncp_cache');
            
            DB::statement('COMMENT ON TABLE cp_consultas_pncp_cache IS \'...\';');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cp_checkpoint_importacao')) {
            Schema::rename('cp_checkpoint_importacao', 'checkpoint_importacao');
        }

        if (Schema::hasTable('cp_consultas_pncp_cache')) {
            Schema::rename('cp_consultas_pncp_cache', 'consultas_pncp_cache');
        }
    }
};
```

### 1.3 Estrutura Padrão de Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cp_nome_tabela', function (Blueprint $table) {
            // PRIMARY KEY
            $table->id();
            
            // FOREIGN KEYS
            $table->foreignId('orcamento_id')
                  ->constrained('cp_orcamentos')
                  ->onDelete('cascade');
            
            // CAMPOS DE NEGÓCIO
            $table->string('nome', 255);
            $table->text('descricao');
            $table->decimal('preco_unitario', 15, 2)->nullable();
            $table->enum('status', ['pendente', 'realizado'])->default('pendente');
            
            // TIMESTAMPS PADRÃO
            $table->timestamps();
            $table->softDeletes();
            
            // ÍNDICES
            $table->index('orcamento_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_nome_tabela');
    }
};
```

### 1.4 Padrão de Tipos de Dados

#### Strings
```php
$table->string('nome', 255);              // Textos curtos com limite
$table->text('descricao');                // Textos longos sem limite
$table->enum('tipo', ['A', 'B', 'C']);   // Enumerações
```

#### Números
```php
$table->id();                             // BIGINT UNSIGNED AUTO_INCREMENT
$table->integer('quantidade');            // Inteiros
$table->decimal('preco', 15, 2);         // Decimais financeiros (15 dígitos, 2 casas)
$table->decimal('percentual', 5, 4);     // Percentuais (0.0001 a 9.9999)
```

#### Datas
```php
$table->date('data_publicacao');         // Apenas data
$table->datetime('data_conclusao');      // Data e hora
$table->timestamp('created_at');         // Timestamp
$table->timestamps();                    // created_at + updated_at
$table->softDeletes();                   // deleted_at
```

#### Booleanos
```php
$table->boolean('ativo')->default(true);
$table->boolean('eh_registro_precos')->default(false);
```

#### JSON
```php
$table->json('tags_segmento')->nullable();
$table->jsonb('metadados')->nullable();  // PostgreSQL
```

### 1.5 Foreign Keys e Relacionamentos

#### Padrão Completo
```php
$table->foreignId('orcamento_id')
      ->constrained('cp_orcamentos')    // Tabela referenciada
      ->onDelete('cascade');             // Ação ao deletar

$table->foreignId('lote_id')
      ->nullable()
      ->constrained('cp_lotes')
      ->onDelete('set null');            // Setar NULL ao deletar
```

#### Index nas Foreign Keys
```php
// SEMPRE criar índice em foreign keys
$table->index('orcamento_id');
$table->index('lote_id');
```

### 1.6 Índices

```php
// Índice simples
$table->index('status');
$table->index('created_at');

// Índice composto
$table->index(['orcamento_id', 'status']);

// Índice único
$table->unique('numero_documento');
$table->unique(['orcamento_id', 'numero_item']);

// Índice fulltext (PostgreSQL)
DB::statement('CREATE INDEX idx_titulo_fulltext 
               ON cp_catmat 
               USING GIN (to_tsvector(\'portuguese\', titulo))');
```

### 1.7 Reversibilidade (down())

**REGRA FUNDAMENTAL:** Toda migration DEVE ser reversível

#### Migration Simples
```php
public function down(): void
{
    Schema::dropIfExists('cp_nome_tabela');
}
```

#### Migration Complexa (com avisos)
```php
public function down(): void
{
    // ATENÇÃO: Rollback pode causar TRUNCAMENTO DE DADOS!
    Schema::table('cp_orgaos', function (Blueprint $table) {
        $table->string('telefone', 20)->nullable()->change();
    });
}
```

#### Migration que Altera Múltiplas Tabelas
```php
public function down(): void
{
    $tables = [
        'cp_orgaos',
        'cp_fornecedores',
        'cp_solicitacoes_cdf',
    ];

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->change();
        });
    }
}
```

### 1.8 Comentários e Documentação

#### Documentação Inline
```php
/**
 * Run the migrations.
 *
 * Expande campos telefone de VARCHAR(20) para VARCHAR(50)
 * para suportar telefones longos retornados pela Receita Federal.
 *
 * Afeta 3 tabelas (4 colunas):
 * - cp_orgaos.telefone
 * - cp_fornecedores.telefone
 * - cp_fornecedores.celular
 * - cp_solicitacoes_cdf.telefone
 */
public function up(): void
{
    // Implementação...
}
```

#### Comentários no Banco de Dados
```php
DB::statement('COMMENT ON TABLE cp_checkpoint_importacao IS 
               \'Rastreamento de progresso de importações\'');

DB::statement('COMMENT ON COLUMN cp_catmat.tem_preco_comprasgov IS 
               \'Flag indicando se o material tem preços na API\'');
```

### 1.9 Migrations Especiais

#### Migration de Tenant (DESABILITADA)
```php
/**
 * DESABILITADO: Esta migration é da arquitetura ANTIGA (banco compartilhado).
 * Na nova arquitetura cada tenant tem BANCO EXCLUSIVO, então tenant_id não é necessário.
 */
public function up(): void
{
    // Migration desabilitada - não é necessária com banco exclusivo por tenant
    return;
    
    // Código antigo comentado...
}
```

#### Migration de Correção com Validação
```php
public function up(): void
{
    // 1. Tabela cp_orgaos - telefone
    Schema::table('cp_orgaos', function (Blueprint $table) {
        $table->string('telefone', 50)->nullable()->change();
    });

    // 2. Tabela cp_fornecedores - telefone e celular
    Schema::table('cp_fornecedores', function (Blueprint $table) {
        $table->string('telefone', 50)->nullable()->change();
        $table->string('celular', 50)->nullable()->change();
    });

    // 3. Tabela cp_solicitacoes_cdf - telefone
    Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
        $table->string('telefone', 50)->nullable()->change();
    });
}
```

### 1.10 Exemplo Completo de Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cp_itens_orcamento', function (Blueprint $table) {
            // PRIMARY KEY
            $table->id();
            
            // FOREIGN KEYS
            $table->foreignId('orcamento_id')
                  ->constrained('cp_orcamentos')
                  ->onDelete('cascade');
            
            $table->foreignId('lote_id')
                  ->nullable()
                  ->constrained('cp_lotes')
                  ->onDelete('set null');
            
            // CAMPOS DE NEGÓCIO
            $table->text('descricao');
            $table->string('medida_fornecimento', 50);
            $table->decimal('quantidade', 15, 4);
            $table->string('indicacao_marca')->nullable();
            $table->enum('tipo', ['produto', 'servico'])->default('servico');
            $table->boolean('alterar_cdf')->default(false);
            
            // TIMESTAMPS
            $table->timestamps();
            $table->softDeletes();
            
            // ÍNDICES
            $table->index('orcamento_id');
            $table->index('lote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_itens_orcamento');
    }
};
```

---

## 2. PADRÕES DE CONTROLLERS

### 2.1 Estrutura de Classe

```php
<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrcamentoController extends Controller
{
    /**
     * Serviços de API em tempo real
     */
    private TceRsApiService $tceRsApi;
    private ComprasnetApiService $comprasnetApi;

    /**
     * Injeção de dependências via construtor
     */
    public function __construct(
        TceRsApiService $tceRsApi, 
        ComprasnetApiService $comprasnetApi
    ) {
        $this->tceRsApi = $tceRsApi;
        $this->comprasnetApi = $comprasnetApi;
    }
    
    // Métodos...
}
```

### 2.2 Injeção de Dependências

#### Via Construtor (Preferred)
```php
private TceRsApiService $tceRsApi;
private ComprasnetApiService $comprasnetApi;

public function __construct(
    TceRsApiService $tceRsApi, 
    ComprasnetApiService $comprasnetApi
) {
    $this->tceRsApi = $tceRsApi;
    $this->comprasnetApi = $comprasnetApi;
}
```

#### Via Method Injection
```php
public function buscar(Request $request, TceRsApiService $tceApi)
{
    $resultado = $tceApi->buscarItens($request->termo);
    // ...
}
```

### 2.3 Validação de Dados

#### Validação Inline
```php
public function store(Request $request)
{
    $rules = [
        'nome' => 'required|string|max:255',
        'referencia_externa' => 'nullable|string|max:255',
        'objeto' => 'required|string',
        'orgao_interessado' => 'nullable|string|max:255',
        'tipo_criacao' => 'required|in:do_zero,outro_orcamento,documento',
        'orcamento_origem_id' => 'nullable|exists:cp_orcamentos,id',
    ];

    // Validação adicional condicional
    if ($request->tipo_criacao === 'documento') {
        $rules['documento'] = 'required|file|mimes:pdf,xlsx,xls|max:10240';
    }

    try {
        $validated = $request->validate($rules, [
            'nome.required' => 'O campo Nome do Orçamento é obrigatório.',
            'nome.max' => 'O Nome do Orçamento não pode ter mais de 255 caracteres.',
            'objeto.required' => 'O campo Objeto é obrigatório.',
            'tipo_criacao.required' => 'Selecione como deseja criar o orçamento.',
            'tipo_criacao.in' => 'Tipo de criação inválido.',
            'orcamento_origem_id.exists' => 'Orçamento de origem não encontrado.',
            'documento.required' => 'O upload do documento é obrigatório.',
            'documento.mimes' => 'O documento deve ser do tipo PDF ou Excel.',
            'documento.max' => 'O documento não pode ter mais de 10MB.',
        ]);

        Log::info('[DIAGNÓSTICO] Validação passou', [
            'validated_keys' => array_keys($validated)
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('[DIAGNÓSTICO] Validação falhou', [
            'errors' => $e->errors()
        ]);
        throw $e;
    }
    
    // Continuar processamento...
}
```

### 2.4 Tratamento de Erros

#### Padrão Try-Catch com Logging
```php
public function buscar(Request $request)
{
    $termo = trim($request->get('termo', ''));

    // Validação
    if (strlen($termo) < 3) {
        return response()->json([
            'success' => false,
            'message' => 'Digite pelo menos 3 caracteres para buscar'
        ]);
    }

    Log::info('========== PESQUISA RAPIDA INICIADA ==========', ['termo' => $termo]);

    try {
        // 1. CMED - Medicamentos
        Log::info('PesquisaRapida: [1/7] Iniciando busca no CMED...');
        try {
            $resultadosCMED = $this->buscarNoCMED($termo);
            if (!empty($resultadosCMED)) {
                $resultados = array_merge($resultados, $resultadosCMED);
                Log::info('PesquisaRapida: [1/7] CMED retornou ' . count($resultadosCMED) . ' medicamentos');
            }
        } catch (\Exception $e) {
            Log::warning('PesquisaRapida: [1/7] Erro no CMED', ['erro' => $e->getMessage()]);
        }
        
        // Continuar...
        
    } catch (\Exception $e) {
        Log::error('PesquisaRapida: Erro geral', [
            'termo' => $termo,
            'erro' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erro ao realizar pesquisa: ' . $e->getMessage()
        ], 500);
    }
}
```

### 2.5 Respostas JSON

#### Sucesso
```php
return response()->json([
    'success' => true,
    'total' => count($resultados),
    'resultados' => $resultados
]);
```

#### Erro
```php
return response()->json([
    'success' => false,
    'message' => 'Erro ao processar requisição'
], 500);
```

#### Validação
```php
return response()->json([
    'success' => false,
    'message' => 'Digite pelo menos 3 caracteres',
    'resultados' => []
]);
```

### 2.6 Transações de Banco

```php
DB::beginTransaction();

try {
    // Criar orçamento
    $orcamento = Orcamento::create([
        'nome' => $validated['nome'],
        'objeto' => $validated['objeto'],
        'status' => 'pendente',
        'user_id' => Auth::id(),
    ]);

    Log::info('[DIAGNÓSTICO] Orçamento criado', [
        'orcamento_id' => $orcamento->id,
        'nome' => $orcamento->nome
    ]);

    // Criar itens
    foreach ($itensExtraidos as $itemData) {
        $item = OrcamentoItem::create([
            'orcamento_id' => $orcamento->id,
            'descricao' => $itemData['descricao'],
            'quantidade' => $itemData['quantidade'],
        ]);
    }

    DB::commit();
    
    return redirect()
        ->route('orcamentos.elaborar', $orcamento->id)
        ->with('success', 'Orçamento criado com sucesso!');

} catch (\Exception $e) {
    DB::rollBack();
    
    Log::error('[STORE] Erro ao criar orçamento', [
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return back()->withErrors([
        'error' => 'Erro ao criar orçamento: ' . $e->getMessage()
    ])->withInput();
}
```

### 2.7 Métodos RESTful

#### Index (Listagem)
```php
public function index()
{
    $orcamentos = Orcamento::pendentes()
        ->orderBy('created_at', 'desc')
        ->get();
    
    return view('orcamentos.pendentes', compact('orcamentos'));
}
```

#### Create (Formulário)
```php
public function create()
{
    $orcamentosRealizados = Orcamento::realizados()
        ->orderBy('created_at', 'desc')
        ->get();
    
    return view('orcamentos.create', compact('orcamentosRealizados'));
}
```

#### Store (Salvar)
```php
public function store(Request $request)
{
    // Validação
    $validated = $request->validate([...]);
    
    // Criar registro
    $orcamento = Orcamento::create($validated);
    
    // Redirecionar
    return redirect()
        ->route('orcamentos.show', $orcamento->id)
        ->with('success', 'Registro criado com sucesso!');
}
```

#### Show (Visualizar)
```php
public function show($id)
{
    $orcamento = Orcamento::with(['itens', 'lotes', 'user'])
        ->findOrFail($id);
    
    return view('orcamentos.show', compact('orcamento'));
}
```

#### Edit (Formulário de Edição)
```php
public function edit($id)
{
    $orcamento = Orcamento::findOrFail($id);
    
    return view('orcamentos.edit', compact('orcamento'));
}
```

#### Update (Atualizar)
```php
public function update(Request $request, $id)
{
    $validated = $request->validate([...]);
    
    $orcamento = Orcamento::findOrFail($id);
    $orcamento->update($validated);
    
    return redirect()
        ->route('orcamentos.show', $orcamento->id)
        ->with('success', 'Registro atualizado com sucesso!');
}
```

#### Destroy (Deletar)
```php
public function destroy($id)
{
    $orcamento = Orcamento::findOrFail($id);
    $orcamento->delete();
    
    return redirect()
        ->route('orcamentos.index')
        ->with('success', 'Registro deletado com sucesso!');
}
```

### 2.8 Logging Estruturado

```php
Log::info('========== PESQUISA RAPIDA INICIADA ==========', [
    'termo' => $termo
]);

Log::info('PesquisaRapida: [1/7] Iniciando busca no CMED...', [
    'termo' => $termo,
    'timestamp' => now()
]);

Log::warning('PesquisaRapida: [1/7] Erro no CMED', [
    'termo' => $termo,
    'erro' => $e->getMessage()
]);

Log::error('[STORE] Erro ao criar orçamento', [
    'user_id' => Auth::id(),
    'erro' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

---

## 3. PADRÕES DE MODELS

### 3.1 Estrutura Básica

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orcamento extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'cp_orcamentos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nome',
        'objeto',
        'status',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data_conclusao' => 'datetime',
        'aceitar_fontes_alternativas' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
```

### 3.2 Conexões de Banco

#### Conexão Padrão (Tenant)
```php
// NÃO especificar $connection - usa conexão padrão do tenant
protected $table = 'cp_orcamentos';
```

#### Conexão Compartilhada (CATMAT, CMED)
```php
// IMPORTANTE: Usar conexão 'pgsql_main' para dados compartilhados
// que estão no banco principal independente do tenant
protected $connection = 'pgsql_main';
protected $table = 'cp_catmat';
```

**Exemplo Completo:**
```php
class Catmat extends Model
{
    use HasFactory;

    // IMPORTANTE: Usar conexão 'pgsql_main' que SEMPRE aponta para o banco principal
    // onde estão os dados compartilhados (CATMAT, CMED) independente do tenant
    protected $connection = 'pgsql_main';
    
    protected $table = 'cp_catmat';
    
    protected $fillable = [
        'codigo',
        'titulo',
        'tipo',
        'unidade_padrao',
    ];
}
```

### 3.3 Relacionamentos

#### belongsTo (Pertence a Um)
```php
/**
 * Relacionamento: Item pertence a um orçamento
 */
public function orcamento()
{
    return $this->belongsTo(Orcamento::class, 'orcamento_id');
}

/**
 * Relacionamento: Item pode pertencer a um lote
 */
public function lote()
{
    return $this->belongsTo(Lote::class, 'lote_id');
}

/**
 * Relacionamento: Orçamento pertence a um usuário
 */
public function user()
{
    return $this->belongsTo(User::class);
}

/**
 * Relacionamento: Orçamento pertence a um órgão
 */
public function orgao()
{
    return $this->belongsTo(Orgao::class);
}
```

#### hasMany (Tem Muitos)
```php
/**
 * Relacionamento: Orçamento tem muitos itens
 */
public function itens()
{
    return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
}

/**
 * Relacionamento: Orçamento tem muitos lotes
 */
public function lotes()
{
    return $this->hasMany(Lote::class, 'orcamento_id');
}

/**
 * Relacionamento: Fornecedor tem muitos itens
 */
public function itens()
{
    return $this->hasMany(FornecedorItem::class, 'fornecedor_id');
}
```

#### Relacionamentos Auto-referenciais
```php
/**
 * Relacionamento: Orçamento pode ter sido criado a partir de outro
 */
public function orcamentoOrigem()
{
    return $this->belongsTo(Orcamento::class, 'orcamento_origem_id');
}

/**
 * Relacionamento: Orçamento pode ter gerado outros orçamentos
 */
public function orcamentosDerivados()
{
    return $this->hasMany(Orcamento::class, 'orcamento_origem_id');
}
```

### 3.4 Fillable e Guarded

#### Fillable (Campos Permitidos)
```php
protected $fillable = [
    'nome',
    'referencia_externa',
    'objeto',
    'orgao_interessado',
    'tipo_criacao',
    'status',
    'user_id',
    'metodo_juizo_critico',
    'metodo_obtencao_preco',
    'casas_decimais',
    'observacao_justificativa',
    // Dados do Orçamentista
    'orcamentista_nome',
    'orcamentista_cpf_cnpj',
    'orcamentista_matricula',
    'brasao_path',
    // Metodologia
    'metodologia_analise_critica',
    'medida_tendencia_central',
    'prazo_validade_amostras',
    'numero_minimo_amostras',
    'aceitar_fontes_alternativas',
    'orgao_id',
];
```

### 3.5 Casts (Type Casting)

```php
protected $casts = [
    // Datas
    'data_conclusao' => 'datetime',
    'data_publicacao' => 'date',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    
    // Números
    'quantidade' => 'decimal:4',
    'preco_unitario' => 'decimal:2',
    'prazo_validade_amostras' => 'integer',
    'numero_minimo_amostras' => 'integer',
    
    // Booleanos
    'aceitar_fontes_alternativas' => 'boolean',
    'usou_similares' => 'boolean',
    'usou_cdf' => 'boolean',
    'alterar_cdf' => 'boolean',
    'importado_de_planilha' => 'boolean',
    
    // JSON/Array
    'tags_segmento' => 'array',
];
```

### 3.6 Scopes

#### Query Scopes
```php
/**
 * Scope para filtrar apenas orçamentos pendentes
 */
public function scopePendentes($query)
{
    return $query->where('status', 'pendente');
}

/**
 * Scope para filtrar apenas orçamentos realizados
 */
public function scopeRealizados($query)
{
    return $query->where('status', 'realizado');
}

/**
 * Scope para filtrar por tipo de criação
 */
public function scopeTipoCriacao($query, $tipo)
{
    return $query->where('tipo_criacao', $tipo);
}

/**
 * Scope: apenas ativos
 */
public function scopeAtivo($query)
{
    return $query->where('ativo', true);
}

/**
 * Scope: busca por código
 */
public function scopePorCodigo($query, $codigo)
{
    return $query->where('codigo', $codigo);
}

/**
 * Scope: busca fulltext por título (PostgreSQL)
 */
public function scopeBuscarTitulo($query, $termo)
{
    return $query->whereRaw(
        "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)", 
        [$termo]
    );
}

/**
 * Scope: Busca por CNPJ/CPF
 */
public function scopeByDocumento($query, $numeroDocumento)
{
    $numeroLimpo = preg_replace('/\D/', '', $numeroDocumento);
    return $query->where('numero_documento', $numeroLimpo);
}

/**
 * Scope: Busca por nome (razão social ou fantasia)
 */
public function scopeByNome($query, $nome)
{
    return $query->where('razao_social', 'ILIKE', "%{$nome}%")
                 ->orWhere('nome_fantasia', 'ILIKE', "%{$nome}%");
}
```

**Uso:**
```php
// Simples
$pendentes = Orcamento::pendentes()->get();

// Encadeamento
$orcamentos = Orcamento::pendentes()
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Com parâmetros
$orcamentos = Orcamento::tipoCriacao('documento')->get();
```

### 3.7 Accessors/Mutators

#### Accessors (Getters)
```php
/**
 * Accessor: CNPJ/CPF formatado
 */
public function getNumeroDocumentoFormatadoAttribute()
{
    $numero = preg_replace('/\D/', '', $this->numero_documento);

    if ($this->tipo_documento === 'CNPJ' && strlen($numero) === 14) {
        return preg_replace(
            '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', 
            '$1.$2.$3/$4-$5', 
            $numero
        );
    }

    if ($this->tipo_documento === 'CPF' && strlen($numero) === 11) {
        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/', 
            '$1.$2.$3-$4', 
            $numero
        );
    }

    return $numero;
}

/**
 * Obter label do tipo de criação
 */
public function getTipoCriacaoLabelAttribute()
{
    $labels = [
        'do_zero' => 'Criado do Zero',
        'outro_orcamento' => 'Criado a partir de Outro Orçamento',
        'documento' => 'Criado a partir de Documento',
    ];

    return $labels[$this->tipo_criacao] ?? 'Desconhecido';
}

/**
 * Obter label do status
 */
public function getStatusLabelAttribute()
{
    $labels = [
        'pendente' => 'Pendente',
        'realizado' => 'Realizado',
    ];

    return $labels[$this->status] ?? 'Desconhecido';
}
```

**Uso:**
```php
echo $fornecedor->numero_documento_formatado; // "12.345.678/0001-90"
echo $orcamento->tipo_criacao_label;          // "Criado do Zero"
echo $orcamento->status_label;                // "Pendente"
```

### 3.8 Métodos Auxiliares

```php
/**
 * Marcar orçamento como realizado
 */
public function marcarComoRealizado()
{
    $this->update([
        'status' => 'realizado',
        'data_conclusao' => now(),
    ]);
}

/**
 * Marcar orçamento como pendente
 */
public function marcarComoPendente()
{
    $this->update([
        'status' => 'pendente',
        'data_conclusao' => null,
    ]);
}

/**
 * Verificar se orçamento está pendente
 */
public function isPendente()
{
    return $this->status === 'pendente';
}

/**
 * Verificar se orçamento está realizado
 */
public function isRealizado()
{
    return $this->status === 'realizado';
}

/**
 * Incrementa contador de ocorrências e atualiza última ocorrência
 */
public function registrarOcorrencia()
{
    $this->increment('contador_ocorrencias');
    $this->update(['ultima_ocorrencia_em' => now()]);

    if ($this->contador_ocorrencias === 1) {
        $this->update(['primeira_ocorrencia_em' => now()]);
    }
}
```

### 3.9 Boot Methods

```php
/**
 * Boot do model para gerar número automaticamente
 */
protected static function boot()
{
    parent::boot();

    static::creating(function ($orcamento) {
        // Se o número não foi fornecido, gerar automaticamente
        if (empty($orcamento->numero)) {
            // Buscar o próximo ID disponível
            $ultimoId = self::withTrashed()->max('id') ?? 0;
            $proximoId = $ultimoId + 1;

            // Gerar número no formato: 00001/2025
            $ano = date('Y');
            $orcamento->numero = str_pad($proximoId, 5, '0', STR_PAD_LEFT) . '/' . $ano;
        }
    });
}
```

---

## 4. PADRÕES DE ROTAS

### 4.1 Agrupamento por Middleware

#### Rotas Públicas (Sem Autenticação)
```php
// Rotas públicas de autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'online',
        'module' => 'cestadeprecos',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Preview público (sem autenticação)
Route::get('/orcamentos/{id}/preview', [OrcamentoController::class, 'preview'])
    ->name('orcamentos.preview.public');
```

#### Rotas Protegidas (Com Autenticação)
```php
Route::middleware(['ensure.authenticated'])->group(function () {

    // Dashboard principal
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // Configurações do Órgão
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])
        ->name('configuracoes.index');
    
    // Pesquisa Rápida
    Route::get('/pesquisa-rapida', function () {
        return view('pesquisa-rapida');
    })->name('pesquisa.rapida');
    
    // ... outras rotas protegidas
});
```

### 4.2 Nomenclatura de Rotas

#### Padrão RESTful
```php
Route::get('/orcamentos', [OrcamentoController::class, 'index'])
    ->name('orcamentos.index');

Route::get('/orcamentos/novo', [OrcamentoController::class, 'create'])
    ->name('orcamentos.create');

Route::post('/orcamentos/novo', [OrcamentoController::class, 'store'])
    ->name('orcamentos.store');

Route::get('/orcamentos/{id}', [OrcamentoController::class, 'show'])
    ->name('orcamentos.show');

Route::get('/orcamentos/{id}/editar', [OrcamentoController::class, 'edit'])
    ->name('orcamentos.edit');

Route::put('/orcamentos/{id}', [OrcamentoController::class, 'update'])
    ->name('orcamentos.update');

Route::delete('/orcamentos/{id}', [OrcamentoController::class, 'destroy'])
    ->name('orcamentos.destroy');
```

#### Rotas Aninhadas
```php
// Itens de orçamento
Route::post('/orcamentos/{id}/itens', [OrcamentoController::class, 'storeItem'])
    ->name('orcamentos.itens.store');

Route::patch('/orcamentos/{id}/itens/{item_id}', [OrcamentoController::class, 'updateItem'])
    ->name('orcamentos.itens.update');

Route::delete('/orcamentos/{id}/itens/{item_id}', [OrcamentoController::class, 'destroyItem'])
    ->name('orcamentos.itens.destroy');

// CDF
Route::get('/orcamentos/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'getCDF'])
    ->name('orcamentos.cdf.get');

Route::delete('/orcamentos/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'destroyCDF'])
    ->name('orcamentos.cdf.destroy');
```

### 4.3 Prefixos e Grupos

#### Resource Groups
```php
Route::prefix('orcamentos')->name('orcamentos.')->group(function () {
    Route::get('/novo', [OrcamentoController::class, 'create'])->name('create');
    Route::post('/novo', [OrcamentoController::class, 'store'])->name('store');
    Route::get('/pendentes', [OrcamentoController::class, 'pendentes'])->name('pendentes');
    Route::get('/realizados', [OrcamentoController::class, 'realizados'])->name('realizados');
    Route::get('/{id}/elaborar', [OrcamentoController::class, 'elaborar'])->name('elaborar');
    Route::get('/{id}/imprimir', [OrcamentoController::class, 'imprimir'])->name('imprimir');
});
```

#### API Routes
```php
Route::prefix('api')->group(function () {
    
    Route::get('/status', function () {
        return response()->json([
            'message' => 'API do módulo Cesta de Preços',
            'status' => 'ready',
            'tenant' => request()->attributes->get('tenant')['subdomain'] ?? 'unknown'
        ]);
    });

    // CATMAT
    Route::prefix('catmat')->name('api.catmat.')->group(function () {
        Route::get('/suggest', [CatmatController::class, 'suggest'])->name('suggest');
        Route::get('/{codigo}', [CatmatController::class, 'show'])->name('show');
        Route::get('/', [CatmatController::class, 'index'])->name('index');
    });
    
    // Fornecedores
    Route::prefix('fornecedores')->name('api.fornecedores.')->group(function () {
        Route::get('/sugerir', [FornecedorController::class, 'sugerir'])->name('sugerir');
        Route::post('/atualizar-pncp', [FornecedorController::class, 'atualizarPNCP'])
            ->name('atualizarPNCP');
    });
});
```

### 4.4 Rotas de Recursos (Resource Routes)

```php
// Fornecedores
Route::prefix('fornecedores')->name('fornecedores.')->group(function () {
    // Listagem
    Route::get('/', [FornecedorController::class, 'index'])->name('index');
    
    // Cadastro
    Route::post('/', [FornecedorController::class, 'store'])->name('store');
    
    // Consultar CNPJ na Receita Federal
    Route::get('/consultar-cnpj/{cnpj}', [FornecedorController::class, 'consultarCNPJ'])
        ->name('consultar-cnpj');
    
    // Download modelo planilha
    Route::get('/modelo-planilha', [FornecedorController::class, 'downloadModelo'])
        ->name('modelo-planilha');
    
    // Importar planilha
    Route::post('/importar', [FornecedorController::class, 'importarPlanilha'])
        ->name('importar');
    
    // Visualizar, editar e excluir
    Route::get('/{id}', [FornecedorController::class, 'show'])->name('show');
    Route::put('/{id}', [FornecedorController::class, 'update'])->name('update');
    Route::delete('/{id}', [FornecedorController::class, 'destroy'])->name('destroy');
});
```

### 4.5 Rotas Públicas vs Autenticadas

#### Padrão de Organização
```php
// ========== ROTAS PÚBLICAS ==========
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Preview público
Route::get('/orcamentos/{id}/preview', [OrcamentoController::class, 'preview'])
    ->name('orcamentos.preview.public');

// API pública (PNCP, ComprasGov, etc)
Route::get('/pncp/buscar', [OrcamentoController::class, 'buscarPNCP'])
    ->name('pncp.buscar.public');

Route::get('/compras-gov/buscar', function(\Illuminate\Http\Request $request) {
    // Implementação...
})->name('compras-gov.buscar.public');


// ========== ROTAS PROTEGIDAS ==========
Route::middleware(['ensure.authenticated'])->group(function () {
    
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    
    Route::prefix('orcamentos')->name('orcamentos.')->group(function () {
        // Rotas protegidas...
    });
});
```

### 4.6 Servindo Arquivos Estáticos

```php
// IMPORTANTE: Estas rotas devem estar NO FINAL para não capturar outras rotas

// Servir arquivos CSS
Route::get('/css/{filename}', function ($filename) {
    $path = public_path('css/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'text/css']);
})->where('filename', '.*');

// Servir arquivos JavaScript
Route::get('/js/{filename}', function ($filename) {
    $path = public_path('js/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'application/javascript']);
})->where('filename', '.*');

// Servir imagens
Route::get('/images/{filename}', function ($filename) {
    $path = public_path('images/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/' . $extension
    };
    return response()->file($path, ['Content-Type' => $mimeType]);
})->where('filename', '.*');
```

---

## 5. PADRÕES JAVASCRIPT

### 5.1 Estrutura de Arquivos

#### Pattern: IIFE (Immediately Invoked Function Expression)
```javascript
console.log('🚀🚀🚀 [MODAL-COTACAO.JS] ARQUIVO CARREGADO! Data: ' + new Date().toLocaleString());

/**
 * ================================================
 * MODAL DE COTAÇÃO DE PREÇOS - JAVASCRIPT COMPLETO
 * Baseado nos prints: MODAL1.png, MODAL2.png, MODAL3.png, MODAL4.png
 * Data: 09/10/2025
 * ================================================
 */

(function() {
    'use strict';

    const modal = document.getElementById('modalCotacaoPrecos');
    if (!modal) {
        console.warn('⚠️ Modal de Cotação não encontrado');
        return;
    }

    // ===== VARIÁVEIS GLOBAIS (dentro da IIFE) =====
    let resultadosCompletos = [];
    let resultadosFiltrados = [];

    // ===== FUNÇÕES =====
    function inicializar() {
        console.log('🚀 Inicializando Modal de Cotação...');
        vincularEventos();
    }
    
    function vincularEventos() {
        // Implementação...
    }
    
    // ===== INICIALIZAÇÃO =====
    inicializar();
    
})();
```

### 5.2 Nomenclatura de Funções

#### Padrão camelCase
```javascript
// BOM
function realizarPesquisa() { }
function vincularEventos() { }
function filtrarResultados() { }
function exibirMensagemErro() { }

// EVITAR
function RealizarPesquisa() { }  // PascalCase é para classes
function realizar_pesquisa() { }  // snake_case não é JavaScript idiomático
```

#### Funções Assíncronas
```javascript
async function realizarPesquisa(tipo) {
    console.log('🖱️ Botão PESQUISAR clicado, tipo:', tipo);
    
    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
        
    } catch (error) {
        console.error('❌ Erro na pesquisa:', error);
        throw error;
    }
}
```

### 5.3 Fetch API (AJAX)

#### Padrão Completo
```javascript
async function buscarDados(termo) {
    const url = `/pesquisa/buscar?termo=${encodeURIComponent(termo)}`;
    
    try {
        console.log('🌐 Fazendo requisição:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        console.log('✅ Resposta recebida:', {
            success: data.success,
            total: data.total
        });
        
        return data;

    } catch (error) {
        console.error('❌ Erro na requisição:', {
            url: url,
            error: error.message,
            stack: error.stack
        });
        
        throw error;
    }
}
```

#### POST com JSON
```javascript
async function salvarDados(dados) {
    const url = '/api/salvar';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(dados)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();

    } catch (error) {
        console.error('❌ Erro ao salvar:', error);
        throw error;
    }
}
```

#### POST com FormData
```javascript
async function uploadArquivo(arquivo) {
    const formData = new FormData();
    formData.append('arquivo', arquivo);
    formData.append('tipo', 'documento');
    
    try {
        const response = await fetch('/api/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
                // NÃO incluir Content-Type - deixar o browser definir
            },
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();

    } catch (error) {
        console.error('❌ Erro no upload:', error);
        throw error;
    }
}
```

### 5.4 Tratamento de Erros

#### Try-Catch Completo
```javascript
async function processarDados() {
    try {
        exibirLoader();
        
        const resultado = await buscarDados();
        
        if (!resultado.success) {
            throw new Error(resultado.message || 'Erro desconhecido');
        }
        
        exibirResultados(resultado.data);
        
    } catch (error) {
        console.error('❌ Erro ao processar:', {
            message: error.message,
            stack: error.stack
        });
        
        exibirMensagemErro('Erro ao processar dados: ' + error.message);
        
    } finally {
        ocultarLoader();
    }
}
```

#### Validação de Entrada
```javascript
function validarEntrada() {
    const termo = document.getElementById('input-termo').value.trim();
    
    if (termo.length < 3) {
        exibirMensagemErro('Digite pelo menos 3 caracteres');
        return false;
    }
    
    if (!/^[a-zA-Z0-9\s]+$/.test(termo)) {
        exibirMensagemErro('Apenas letras e números são permitidos');
        return false;
    }
    
    return true;
}
```

### 5.5 CSRF Tokens

#### Obter Token
```javascript
function getCsrfToken() {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!tokenMeta) {
        console.error('❌ CSRF token não encontrado!');
        return '';
    }
    return tokenMeta.content;
}
```

#### Uso em Requests
```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

### 5.6 Event Listeners

#### Padrão de Vinculação
```javascript
function vincularEventos() {
    console.log('🔗 Vinculando eventos...');

    // Botões
    const btnPesquisar = document.getElementById('btn-pesquisar');
    if (btnPesquisar) {
        btnPesquisar.addEventListener('click', async function() {
            console.log('🖱️ Botão PESQUISAR clicado');
            await realizarPesquisa();
        });
        console.log('  ✅ Evento vinculado: btn-pesquisar');
    } else {
        console.error('  ❌ btn-pesquisar não encontrado!');
    }

    // Enter key
    const inputTermo = document.getElementById('input-termo');
    if (inputTermo) {
        inputTermo.addEventListener('keydown', async function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                console.log('⌨️ Enter pressionado');
                await realizarPesquisa();
            }
        });
        console.log('  ✅ Evento vinculado: input-termo (Enter)');
    }
}
```

#### Remover Event Listeners
```javascript
// Definir função fora para permitir remoção
async function handlePesquisar() {
    await realizarPesquisa();
}

function vincularEventos() {
    const btn = document.getElementById('btn-pesquisar');
    
    // Remover listener antigo
    btn.removeEventListener('click', handlePesquisar);
    
    // Adicionar novo
    btn.addEventListener('click', handlePesquisar);
}
```

#### Event Delegation
```javascript
// Delegação para elementos dinâmicos
document.getElementById('tabela-resultados').addEventListener('click', function(e) {
    // Detectar clique em botão de seleção
    if (e.target.classList.contains('btn-selecionar')) {
        const itemId = e.target.dataset.itemId;
        selecionarItem(itemId);
    }
    
    // Detectar clique em linha da tabela
    if (e.target.closest('tr')) {
        const linha = e.target.closest('tr');
        const itemId = linha.dataset.id;
        exibirDetalhes(itemId);
    }
});
```

### 5.7 Manipulação de DOM

#### Seleção de Elementos
```javascript
// ID (mais rápido)
const modal = document.getElementById('modalCotacao');

// Classe (usar querySelector se precisar de apenas 1)
const primeiroItem = document.querySelector('.item-resultado');
const todosItens = document.querySelectorAll('.item-resultado');

// Atributos
const botoes = document.querySelectorAll('[data-action="selecionar"]');

// Hierarquia
const inputsNoFormulario = document.querySelectorAll('#formulario input[type="text"]');
```

#### Modificação de Elementos
```javascript
// Texto
elemento.textContent = 'Novo texto';
elemento.innerText = 'Novo texto (renderizado)';
elemento.innerHTML = '<strong>HTML</strong>'; // Cuidado com XSS!

// Atributos
elemento.setAttribute('data-id', '123');
elemento.getAttribute('data-id');
elemento.removeAttribute('disabled');

// Classes
elemento.classList.add('ativo');
elemento.classList.remove('inativo');
elemento.classList.toggle('expandido');
elemento.classList.contains('selecionado');

// Estilos
elemento.style.display = 'none';
elemento.style.backgroundColor = '#f0f0f0';

// Datasets
elemento.dataset.id = '123';           // data-id
elemento.dataset.nomeCompleto = 'João'; // data-nome-completo
```

#### Criação de Elementos
```javascript
function criarItemLista(dados) {
    const item = document.createElement('div');
    item.className = 'item-resultado';
    item.dataset.id = dados.id;
    
    item.innerHTML = `
        <div class="item-header">
            <h3>${escapeHtml(dados.nome)}</h3>
            <span class="badge">${escapeHtml(dados.tipo)}</span>
        </div>
        <div class="item-body">
            <p>${escapeHtml(dados.descricao)}</p>
            <p class="preco">R$ ${formatarPreco(dados.preco)}</p>
        </div>
        <div class="item-footer">
            <button class="btn btn-sm btn-primary" data-action="selecionar">
                Selecionar
            </button>
        </div>
    `;
    
    return item;
}

// Inserir no DOM
const container = document.getElementById('lista-resultados');
container.appendChild(item);
```

### 5.8 Utilitários Comuns

#### Formatação
```javascript
function formatarPreco(valor) {
    return Number(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarCNPJ(cnpj) {
    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}
```

#### Escape HTML (Prevenir XSS)
```javascript
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Ou usando replace
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
```

#### Debounce
```javascript
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Uso
const pesquisaDebounced = debounce(async function(termo) {
    await realizarPesquisa(termo);
}, 300);

inputTermo.addEventListener('input', (e) => {
    pesquisaDebounced(e.target.value);
});
```

### 5.9 Logging Estruturado

```javascript
// Início de operação
console.log('========== PESQUISA INICIADA ==========', {
    termo: termo,
    timestamp: new Date().toISOString()
});

// Progresso
console.log('PesquisaRapida: [1/5] Iniciando busca no CMED...');
console.log('  ✅ CMED retornou ' + count + ' resultados');

// Erro
console.error('❌ Erro na busca:', {
    fonte: 'PNCP',
    erro: error.message,
    stack: error.stack
});

// Warning
console.warn('⚠️ Modal não encontrado, abortando inicialização');

// Debugging
console.debug('🐛 Estado atual:', {
    resultadosCompletos: resultadosCompletos.length,
    resultadosFiltrados: resultadosFiltrados.length,
    filtrosAtivos: Object.keys(estadoFiltros)
});
```

---

## 6. PADRÕES DE VIEWS

### 6.1 Estrutura Blade

#### Layout Base
```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cesta de Preços')</title>

    <!-- Base path para requisições via proxy -->
    <script>
        window.APP_BASE_PATH = '/module-proxy/price_basket';
    </script>

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    @include('layouts.sidebar')
    
    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>
```

### 6.2 Seções e Yields

#### Definir Seções
```blade
@extends('layouts.app')

@section('title', 'Novo Orçamento - Cesta de Preços')

@section('styles')
<style>
    .custom-class {
        background: #f0f0f0;
    }
</style>
@endsection

@section('content')
<div class="container">
    <h1>Novo Orçamento</h1>
    <!-- Conteúdo... -->
</div>
@endsection

@section('scripts')
<script>
    console.log('Página carregada!');
</script>
@endsection
```

### 6.3 Includes

#### Include Simples
```blade
@include('layouts.sidebar')
@include('orcamentos._form')
@include('partials.alerts')
```

#### Include com Variáveis
```blade
@include('orcamentos._item', [
    'item' => $item,
    'index' => $loop->index,
    'showActions' => true
])
```

### 6.4 Components

#### Definir Component
```blade
{{-- resources/views/components/alert.blade.php --}}
<div class="alert alert-{{ $type }} {{ $class ?? '' }}" role="alert">
    {{ $slot }}
</div>
```

#### Usar Component
```blade
<x-alert type="success">
    Orçamento criado com sucesso!
</x-alert>

<x-alert type="danger" class="mb-3">
    Erro ao processar requisição.
</x-alert>
```

### 6.5 Diretivas de Controle

#### Condicionais
```blade
@if ($orcamento->isPendente())
    <span class="badge bg-warning">Pendente</span>
@elseif ($orcamento->isRealizado())
    <span class="badge bg-success">Realizado</span>
@else
    <span class="badge bg-secondary">Desconhecido</span>
@endif

@unless ($errors->isEmpty())
    <div class="alert alert-danger">
        Há erros no formulário!
    </div>
@endunless

@isset($usuario)
    <p>Bem-vindo, {{ $usuario->nome }}!</p>
@endisset

@empty($itens)
    <p>Nenhum item encontrado.</p>
@endempty
```

#### Loops
```blade
@foreach ($orcamentos as $orcamento)
    <div class="orcamento-item">
        <h3>{{ $orcamento->nome }}</h3>
        <p>{{ $orcamento->objeto }}</p>
        
        @if ($loop->first)
            <span class="badge">Primeiro</span>
        @endif
        
        @if ($loop->last)
            <span class="badge">Último</span>
        @endif
    </div>
@endforeach

@forelse ($itens as $item)
    <tr>
        <td>{{ $item->descricao }}</td>
        <td>{{ $item->quantidade }}</td>
        <td>R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="text-center">
            Nenhum item cadastrado.
        </td>
    </tr>
@endforelse

@while ($contador < 10)
    <p>Contador: {{ $contador++ }}</p>
@endwhile
```

### 6.6 Variável $loop

```blade
@foreach ($itens as $item)
    <div class="item">
        {{-- Propriedades do $loop --}}
        Índice: {{ $loop->index }}      {{-- 0, 1, 2, ... --}}
        Iteração: {{ $loop->iteration }} {{-- 1, 2, 3, ... --}}
        Restante: {{ $loop->remaining }} {{-- Quantos faltam --}}
        Total: {{ $loop->count }}        {{-- Total de itens --}}
        Profundidade: {{ $loop->depth }} {{-- Nível de aninhamento --}}
        
        @if ($loop->first)
            <span>Primeiro item</span>
        @endif
        
        @if ($loop->last)
            <span>Último item</span>
        @endif
        
        @if ($loop->even)
            <span>Par</span>
        @endif
        
        @if ($loop->odd)
            <span>Ímpar</span>
        @endif
    </div>
@endforeach
```

### 6.7 Blade Stacks

#### Definir Stack
```blade
{{-- Layout base --}}
<head>
    @stack('styles')
</head>
<body>
    @yield('content')
    
    @stack('scripts')
</body>
```

#### Empilhar (Push)
```blade
@push('styles')
<link rel="stylesheet" href="/css/custom.css">
@endpush

@push('scripts')
<script src="/js/custom.js"></script>
@endpush
```

#### Prepend (Adicionar no Início)
```blade
@prepend('scripts')
<script src="/js/primeiro.js"></script>
@endprepend
```

### 6.8 Formulários

#### Form Básico
```blade
<form method="POST" action="{{ route('orcamentos.store') }}">
    @csrf
    
    <div class="form-group">
        <label for="nome" class="form-label">
            Nome do Orçamento <span class="required">*</span>
        </label>
        <input 
            type="text" 
            id="nome" 
            name="nome" 
            class="form-input @error('nome') is-invalid @enderror" 
            value="{{ old('nome') }}"
            required
        >
        @error('nome')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="objeto" class="form-label">
            Objeto <span class="required">*</span>
        </label>
        <textarea 
            id="objeto" 
            name="objeto" 
            class="form-textarea @error('objeto') is-invalid @enderror"
            required
        >{{ old('objeto') }}</textarea>
        @error('objeto')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn-save">Salvar</button>
        <a href="{{ route('orcamentos.index') }}" class="btn-cancel">Cancelar</a>
    </div>
</form>
```

#### Form com Upload
```blade
<form method="POST" action="{{ route('orcamentos.store') }}" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label for="documento" class="form-label">
            Documento (PDF ou Excel) <span class="required">*</span>
        </label>
        <input 
            type="file" 
            id="documento" 
            name="documento" 
            class="form-input @error('documento') is-invalid @enderror"
            accept=".pdf,.xlsx,.xls"
            required
        >
        <p class="form-helper">Formatos aceitos: PDF, Excel (.xlsx, .xls). Tamanho máximo: 10MB</p>
        @error('documento')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>
    
    <button type="submit" class="btn-save">Upload e Processar</button>
</form>
```

#### Form de Edição (PUT)
```blade
<form method="POST" action="{{ route('orcamentos.update', $orcamento->id) }}">
    @csrf
    @method('PUT')
    
    <div class="form-group">
        <label for="nome" class="form-label">Nome</label>
        <input 
            type="text" 
            id="nome" 
            name="nome" 
            class="form-input"
            value="{{ old('nome', $orcamento->nome) }}"
        >
    </div>
    
    <button type="submit" class="btn-save">Atualizar</button>
</form>
```

#### Form de Exclusão (DELETE)
```blade
<form method="POST" action="{{ route('orcamentos.destroy', $orcamento->id') }}" 
      onsubmit="return confirm('Tem certeza que deseja excluir?')">
    @csrf
    @method('DELETE')
    
    <button type="submit" class="btn btn-danger">Excluir</button>
</form>
```

### 6.9 Exibição de Erros

#### Erros de Validação
```blade
@if ($errors->any())
    <div class="alert alert-danger">
        <h4>Há erros no formulário:</h4>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Ou erro específico --}}
@error('nome')
    <div class="form-error">{{ $message }}</div>
@enderror
```

#### Mensagens de Sucesso/Erro
```blade
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
```

### 6.10 Assets e URLs

#### Rotas
```blade
{{-- Rota nomeada --}}
<a href="{{ route('orcamentos.index') }}">Orçamentos</a>

{{-- Rota com parâmetros --}}
<a href="{{ route('orcamentos.show', $orcamento->id) }}">Ver Orçamento</a>

{{-- Rota com múltiplos parâmetros --}}
<a href="{{ route('orcamentos.itens.update', ['id' => $orcamento->id, 'item_id' => $item->id]) }}">
    Editar Item
</a>

{{-- URL absoluta --}}
<a href="{{ url('/orcamentos') }}">Orçamentos</a>

{{-- URL atual --}}
<p>URL atual: {{ url()->current() }}</p>
<p>URL completa: {{ url()->full() }}</p>
```

#### Assets
```blade
{{-- Arquivo público --}}
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
<script src="{{ asset('js/app.js') }}"></script>
<img src="{{ asset('images/logo.png') }}" alt="Logo">

{{-- Mix (compilado) --}}
<link rel="stylesheet" href="{{ mix('css/app.css') }}">
<script src="{{ mix('js/app.js') }}"></script>
```

### 6.11 Escape de HTML

```blade
{{-- Escapado (seguro) --}}
<p>{{ $usuario->nome }}</p>

{{-- NÃO escapado (CUIDADO - XSS!) --}}
<div>{!! $html_confiavel !!}</div>

{{-- Valor antigo do formulário --}}
<input type="text" value="{{ old('nome', $orcamento->nome) }}">
```

---

## 7. RESUMO EXECUTIVO

### 7.1 Estatísticas do Sistema

**Migrations:** 67 arquivos, 3.875 linhas de código
**Models:** 32 modelos
**Controllers:** 17 controllers
**Rotas:** 200+ rotas definidas
**JavaScript:** 4 arquivos principais

### 7.2 Convenções Críticas

1. **Prefixo de Tabelas:** SEMPRE usar `cp_`
2. **Reversibilidade:** Toda migration DEVE ter down()
3. **CSRF:** Sempre incluir tokens em requisições POST
4. **Logging:** Usar logging estruturado com contexto
5. **Validação:** Validar no backend, NUNCA confiar no frontend
6. **Transações:** Usar DB transactions para operações multi-tabela
7. **Scopes:** Preferir scopes a queries duplicadas
8. **Casts:** Declarar type casts em Models
9. **Escape HTML:** SEMPRE escapar output ({{ }} não {!! !!})
10. **Conexões:** Usar pgsql_main para dados compartilhados

### 7.3 Checklist de Desenvolvimento

#### Nova Feature
- [ ] Migration com prefixo cp_ e down() reversível
- [ ] Model com $table, $fillable, $casts definidos
- [ ] Controller com validação e error handling
- [ ] Rotas nomeadas com padrão RESTful
- [ ] View com Blade components e escape HTML
- [ ] JavaScript com CSRF token e error handling
- [ ] Testes manuais em desenvolvimento
- [ ] Logging estruturado
- [ ] Documentação inline

#### Código Seguro
- [ ] Validação de entrada
- [ ] Escape de output
- [ ] CSRF protection
- [ ] SQL injection prevention (usar Eloquent)
- [ ] XSS prevention (usar {{ }})
- [ ] Autorização verificada
- [ ] Logs sem dados sensíveis

### 7.4 Recursos de Referência

**Documentação Interna:**
- `/Arquivos_Claude/README.md` - Índice geral
- `/Arquivos_Claude/ESTUDO_COMPLETO_MODULO_CESTA_PRECOS.md` - Arquitetura
- `/Arquivos_Claude/ESTUDO_COMPLETO_SISTEMA_30-10-2025.md` - Sistema completo

**Padrões Externos:**
- Laravel Docs: https://laravel.com/docs
- PSR-12 Code Style: https://www.php-fig.org/psr/psr-12/
- MDN JavaScript: https://developer.mozilla.org/

---

**FIM DO DOCUMENTO**

Data: 31 de Outubro de 2025  
Versão: 1.0  
Autor: Claude (Anthropic)
