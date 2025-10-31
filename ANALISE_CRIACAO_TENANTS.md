# CRIAÇÃO DE TENANTS NO MINHADATTATECH - ANÁLISE COMPLETA E DETALHADA

## SUMÁRIO EXECUTIVO

A funcionalidade de **criação de tenants** no painel técnico do MinhaDattaTech implementa um sistema de multi-tenancy onde cada cliente (tenant) recebe um subdomínio único com acesso a módulos independentes. O processo envolve:

1. **Interface Livewire** no Technical Panel
2. **Backend em Laravel** com validações e lógica de negócio
3. **Integrações de infraestrutura** (DNS, Caddy, MinhaDattaTech)
4. **Banco de dados multi-tenant** para armazenar configurações

---

## 1. ARQUITETURA GERAL

### Diagrama de Fluxo Completo

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TECHNICAL PANEL (UI)                             │
│              Livewire Component: Manager.php                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  USER CLICKS "NOVO TENANT"                                          │
│         ↓                                                             │
│  Modal Form Opens (manager.blade.php)                               │
│         ↓                                                             │
│  User Fills Form (customer_name, subdomain, email, phone)          │
│         ↓                                                             │
│  Form Validation (Frontend HTML5 + Backend Laravel)                 │
│         ↓                                                             │
│  saveTenant() Method Triggers                                       │
│         ↓                                                             │
│  Database Transaction Begins (DB::beginTransaction)                 │
│         ↓                                                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ CREATE ModuleTenant Record (module_tenants table)           │   │
│  │ - Store: crm_customer_uuid, customer_name, subdomain        │   │
│  │ - Store: primary_email, primary_phone, status, metadata     │   │
│  │ - Store: created_by (auth user ID)                          │   │
│  └─────────────────────────────────────────────────────────────┘   │
│         ↓                                                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ CALL ModuleTenantService::createTenant()                    │   │
│  │ (SYNCHRONOUS - NOT QUEUED)                                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│         ↓                                                             │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                  ModuleTenantService (PHP)                          │
│              /home/dattapro/technical/app/Services                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  STEP 1: Configure DNS                                              │
│  ├─ Call SimpleDnsService::addSubdomain()                          │
│  ├─ Add A record: {subdomain}.dattapro.online → 179.108.221.51    │
│  ├─ Add A record: www.{subdomain}.dattapro.online → 179.108.221.51│
│  └─ Via BIND9 Zone File Management                                 │
│         ↓                                                             │
│  STEP 2: Configure Caddy (Reverse Proxy)                           │
│  ├─ Generate Caddy config file                                     │
│  ├─ Proxy: {subdomain}.dattapro.online → minha.dattatech.com.br   │
│  ├─ SSL: Auto-generate via Let's Encrypt                           │
│  ├─ Headers: X-Tenant-Domain, X-Real-IP, etc                       │
│  ├─ Redirect: www → non-www                                        │
│  └─ Save to: /home/hosting/config/caddy/{subdomain}.conf           │
│         ↓                                                             │
│  STEP 3: Create Tenant in MinhaDattaTech                           │
│  ├─ Execute: artisan tenant:create {subdomain} {customer_name}    │
│  ├─ Pass ENV: APP_KEY, DB_*, DB_USERNAME, DB_PASSWORD             │
│  ├─ Creates database for this tenant (separate DB)                │
│  ├─ Runs seeding (create initial data)                            │
│  └─ Returns success/failure                                        │
│         ↓                                                             │
│  STEP 4: Reload Services                                           │
│  ├─ sudo systemctl reload named (DNS)                              │
│  ├─ sudo systemctl restart caddy (Web Server)                      │
│  ├─ Wait 5 seconds for SSL generation                              │
│  ├─ Verify: systemctl is-active caddy                              │
│  └─ Check status before returning                                  │
│         ↓                                                             │
│  SUCCESS: Return portal_url                                         │
│  (or ERROR: Return exception, trigger rollback)                    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                   ROLLBACK (if error occurs)                        │
│  ├─ Remove Caddy config file (backup saved)                        │
│  ├─ Remove DNS entries from BIND9 zone                             │
│  ├─ Remove tenant from MinhaDattaTech (if created)                 │
│  └─ DB::rollBack() (ModuleTenant record not created)               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
                   Return to UI with Status
```

---

## 2. INTERFACE DO USUÁRIO (FRONTEND)

### 2.1 Localização
**Arquivo:** `/home/dattapro/technical/resources/views/livewire/module-tenants/manager.blade.php`
**Componente:** Manager.php (Livewire)
**Rota:** Geralmente `/technical/module-tenants` ou similar

### 2.2 Elementos de UI

#### 2.2.1 Botão de "Novo Tenant"
```html
<button wire:click="openCreateModal"
        class="bg-white text-dt-primary px-6 py-3 rounded-lg font-semibold">
    <svg class="w-5 h-5">+</svg>
    <span>Novo Tenant</span>
</button>
```
- **Ação:** Dispara `openCreateModal()` (Manager.php:170)
- **Resultado:** Abre modal de criação com formulário

#### 2.2.2 Formulário de Criação/Edição (Modal)

**Campos Obrigatórios:**
```
1. Cliente CRM (SELECT)
   - Source: crmClients array (carregado via CrmIntegrationService)
   - OnChange: Popula automaticamente customer_name, primary_email, primary_phone
   - Disabled: Se editando (não permite mudar cliente)

2. Nome do Cliente (TEXT)
   - Placeholder: "Ex: Prefeitura Municipal de São Paulo"
   - Autocomplete: Preenchido ao selecionar cliente CRM
   - Validation: required|string|max:255
   - Error Display: Via @error('customer_name')

3. Subdomínio (TEXT)
   - Pattern: [a-z0-9\-]+
   - UI: Prefix "https://" + Suffix ".dattapro.online"
   - Auto-generated: Se não preenchido manualmente
   - Validation: required|regex:/^[a-z0-9-]+$/|unique:module_tenants
   - Rules for Edit: unique:module_tenants,subdomain,{id}
   - Hint: "Use apenas letras minúsculas, números e hífen"

4. Email Principal (EMAIL)
   - Placeholder: "contato@exemplo.com.br"
   - Validation: required|email|max:255
   - Auto-filled: Pelo cliente CRM selecionado

5. Telefone (TEXT) - Opcional
   - Placeholder: "(00) 0000-0000"
   - Validation: nullable|string|max:20
   - Auto-filled: Pelo cliente CRM selecionado

6. Observações (TEXTAREA) - Opcional
   - Rows: 3
   - Storage: Salvo em metadata JSON
   - Purpose: Notas internas sobre o cliente
```

**Validações Frontend:**
- HTML5 required, email, pattern
- wire:model para two-way binding
- @error() para mostrar mensagens de erro
- Desabilita campo de CRM se editando (não permite mudar cliente)

**Info Alert (Apenas em Criação):**
```
ℹ️ Após criar o tenant:
   ✓ O DNS será configurado automaticamente
   ✓ O servidor web será configurado
   ✓ O portal ficará disponível em alguns minutos
   ✓ Você poderá ativar módulos específicos
```

#### 2.2.3 Botões do Modal
- **Cancelar:** `wire:click="closeModals"` → Fecha modal sem salvar
- **Criar/Atualizar:** `type="submit"` → Executa `saveTenant()`

### 2.3 Lista de Tenants

#### 2.3.1 Tabela Principal
**Colunas:**
1. ID (tenant->id)
2. Cliente (customer_name + email + telefone com ícone)
3. Subdomínio (link externo para portal)
4. Módulos (badges com status)
5. Status (active/suspended/terminated com dot indicator)
6. Criado em (data + hora)
7. Ações (botões)

#### 2.3.2 Filtros e Busca
```
Search: wire:model.live="search"
  - Busca por: customer_name, subdomain, crm_customer_uuid, primary_email
  - Scope: ModuleTenant::search($search)

Filter Status:
  - Todos os Status
  - Ativo (active)
  - Suspenso (suspended)
  - Terminado (terminated)

Items Per Page:
  - 10 (default)
  - 25
  - 50

Refresh Button: wire:click="loadCrmClients"
  - Recarrega lista de clientes do CRM
```

#### 2.3.3 Stats Cards
```
┌─────────────┬──────────────┬─────────────┬──────────────┐
│ Total       │ Ativos       │ Suspensos   │ Total        │
│ Tenants     │ (verde)      │ (amarelo)   │ Módulos      │
│             │              │             │              │
│ n tenants   │ count(active)│ count(susp.)│ sum(modules) │
└─────────────┴──────────────┴─────────────┴──────────────┘
```

#### 2.3.4 Ações por Tenant
```
┌───────────────────────────────────────────────────────────┐
│ VIEW (ℹ️)          → viewDetails($tenant->id)              │
│ EDIT (✎️)          → editTenant($tenant->id)               │
│ MODULES (📦)      → openModulesModal($tenant->id)         │
│ USERS (👥)        → openUsersModal($tenant->id)           │
│ SUSPEND (⏸️)      → suspendTenant($tenant->id)            │
│ OR REACTIVATE (▶️)│ → reactivateTenant($tenant->id)       │
│ TERMINATE (✕)     → terminateTenant($tenant->id)          │
│ DELETE (🗑️)       → deleteTenant($tenant->id)             │
└───────────────────────────────────────────────────────────┘
```

### 2.4 Modal de Detalhes
**Conteúdo:**
- ID, Cliente, UUID CRM, Subdomínio, Status
- Email, Telefone, Criado por, Criado em, Última atualização
- Tabela de módulos com status, datas, limits
- Read-only (informativo)

### 2.5 Modal de Módulos

**Lifecycle Diagram (4 Passos):**
```
1. INSTALAR (🔧) → criar tabelas no banco (invisible no desktop)
   ↓
2. ATIVAR (✓) → mostrar no desktop do tenant
   ↓
3. DESATIVAR (⏸️) → ocultar do desktop (dados OK)
   ↓
4. DESINSTALAR (🗑️) → remover dados completamente
```

**Módulos Ativos:**
- Listagem com status (installed/active/suspended)
- Botões de ação (ativar, desativar, reativar, desinstalar)
- Max users, storage, data de expiração
- Sincronização com desktop do tenant

**Instalar Novo Módulo:**
- Select: escolher módulo disponível
- Input: Max usuários
- Input: Storage (GB)
- Input: Data de expiração (opcional)
- Checkboxes: Features (se aplicável)
- Submit: "Instalar Módulo"

### 2.6 Modal de Usuários

**Configurações de Usuários:**
- Checkbox: Permitir auto-registro
- Checkbox: Exigir verificação de email
- Checkbox: Permitir redefinição de senha
- Input: Limite de usuários (com slider)
- Buttons: 5 (pequena), 25 (média), 100 (grande), 500 (enterprise), ∞ (ilimitado)

**Adicionar Novo Usuário:**
```
Nome Completo:        (required|string|max:255)
Usuário (username):   (required|regex:/^[a-z0-9._-]+$/)
Email Recuperação:    (nullable|email) - externo
Perfil (role):        (admin, manager, user, viewer)
Botão: "Adicionar"
```

**Lista de Usuários:**
- Tabela com Nome, Usuário, Email, Perfil, Status, Último Acesso
- Botões: Editar, Redefinir Senha, Reenviar Verificação, Ativar Manualmente, Remover

---

## 3. BACKEND / PROCESSAMENTO

### 3.1 Componente Livewire
**Arquivo:** `/home/dattapro/technical/app/Livewire/ModuleTenants/Manager.php`
**Namespace:** `App\Livewire\ModuleTenants`
**Classe:** `Manager extends Component`
**Traits:** `WithPagination`

### 3.2 Propriedades Públicas

```php
// Controle de modais
public $showCreateModal = false;
public $showModulesModal = false;
public $showDetailsModal = false;
public $showUsersModal = false;
public $editingTenantId = null;

// Filtros
public $search = '';
public $filterStatus = '';
public $perPage = 10;

// Formulário Tenant
public $crm_customer_uuid = '';
public $crm_customer_id = '';
public $customer_name = '';
public $subdomain = '';
public $primary_email = '';
public $primary_phone = '';
public $notes = '';

// Módulos
public $selectedTenant = null;
public $selectedTenantModules = [];
public $moduleToActivate = '';
public $moduleMaxUsers = 5;
public $moduleMaxStorage = 1;
public $moduleExpiresAt = '';
public $selectedFeatures = [];

// Usuários
public $tenantUsers = [];
public $newUser = ['name' => '', 'username' => '', 'recovery_email' => '', 'role' => 'user'];
public $tenantUserSettings = ['max_users' => 5, ...];

// Mensagens
public $successMessage = '';
public $errorMessage = '';
```

### 3.3 Rules de Validação

```php
protected $rules = [
    'crm_customer_uuid' => 'required|string|max:36',
    'customer_name' => 'required|string|max:255',
    'subdomain' => 'required|string|max:100|regex:/^[a-z0-9-]+$/',
    'primary_email' => 'required|email|max:255',
    'primary_phone' => 'nullable|string|max:20'
];

protected $messages = [
    'crm_customer_uuid.required' => 'Selecione um cliente do CRM',
    'customer_name.required' => 'Nome do cliente é obrigatório',
    'subdomain.required' => 'Subdomínio é obrigatório',
    'subdomain.regex' => 'Subdomínio deve conter apenas letras minúsculas, números e hífen',
    'primary_email.required' => 'Email é obrigatório',
    'primary_email.email' => 'Email inválido'
];
```

**Validação Especial (na edição):**
```php
if ($this->editingTenantId) {
    // Permite reutilizar o próprio subdomínio
    $this->rules['subdomain'] = 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:module_tenants,subdomain,' . $this->editingTenantId;
}
```

### 3.4 Métodos Principais

#### 3.4.1 mount()
```php
public function mount()
{
    $this->loadCrmClients();
}
```
- **Quando:** Componente é inicializado
- **O que faz:** Carrega lista de clientes do CRM
- **Chamada:** CrmIntegrationService::getAvailableClients()

#### 3.4.2 loadCrmClients()
```php
public function loadCrmClients()
{
    $crmService = new CrmIntegrationService();
    $this->crmClients = $crmService->getAvailableClients();
    // Em caso de erro, define como array vazio
}
```
- **Propósito:** Carregar clientes disponíveis do sistema CRM
- **Integração:** `App\Services\CrmIntegrationService`
- **Retorno:** Array de clientes com `['id', 'name', 'email', 'phone', 'document']`

#### 3.4.3 saveTenant()
```php
public function saveTenant()
{
    // 1. Validação (com rules customizadas para edit)
    $this->validate();
    
    // 2. Inicia transação
    DB::beginTransaction();
    
    try {
        // 3. Prepara dados
        $data = [
            'crm_customer_uuid' => $this->crm_customer_uuid,
            'crm_customer_id' => $this->crm_customer_id,
            'customer_name' => $this->customer_name,
            'subdomain' => $this->subdomain,
            'primary_email' => $this->primary_email,
            'primary_phone' => $this->primary_phone,
            'metadata' => [
                'notes' => $this->notes,
                'created_from' => 'technical_panel'
            ]
        ];
        
        if ($this->editingTenantId) {
            // 4a. EDIÇÃO: Apenas atualiza no Technical Panel
            $tenant = ModuleTenant::find($this->editingTenantId);
            $data['updated_by'] = auth()->id();
            $tenant->update($data);
            $this->successMessage = 'Tenant atualizado com sucesso!';
        } else {
            // 4b. CRIAÇÃO: Cria no Technical Panel + infraestrutura
            $data['status'] = 'active';
            $data['created_by'] = auth()->id();
            $tenant = ModuleTenant::create($data);
            
            // 5. SINCRONIZA COM INFRAESTRUTURA (SÍNCRONO!)
            $service = new ModuleTenantService();
            $result = $service->createTenant($tenant);
            
            if ($result['success']) {
                $this->successMessage = 'Tenant criado com sucesso! Portal: ' . $result['portal_url'];
            } else {
                throw new Exception($result['message']);
            }
        }
        
        // 6. Commit transação
        DB::commit();
        
        // 7. Fecha modal e reseta
        $this->showCreateModal = false;
        $this->resetFormFields();
        $this->resetPage();
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to save tenant', ['error' => $e->getMessage()]);
        $this->errorMessage = 'Erro ao salvar tenant: ' . $e->getMessage();
    }
}
```

**Fluxo de Validação:**
1. Frontend: HTML5 validation (browser)
2. Livewire: `$this->validate()` (server-side)
3. Mensagens de erro exibidas no modal
4. Se há erro, modal permanece aberto

**Transação:**
- Se criação e ModuleTenantService falhar → DB::rollBack()
- ModuleTenant não é criado no banco
- DNS, Caddy e MinhaDattaTech são removidos (rollback)

#### 3.4.4 generateSubdomain()
```php
public function generateSubdomain()
{
    if ($this->customer_name) {
        $base = Str::slug($this->customer_name);  // "Prefeitura SP" → "prefeitura-sp"
        $subdomain = $base;
        $counter = 1;
        
        // Garante subdomínio único
        while (ModuleTenant::where('subdomain', $subdomain)->exists()) {
            $subdomain = $base . '-' . $counter;  // "prefeitura-sp-1"
            $counter++;
        }
        
        $this->subdomain = $subdomain;
    }
}
```
- **Acionado:** `updatedCrmCustomerUuid()` quando cliente CRM é selecionado
- **Algoritmo:** Base (slugified) + contador se existir duplicata

#### 3.4.5 installModule()
```php
public function installModule()
{
    // 1. Valida módulo selecionado
    if (!$this->moduleToActivate) {
        $this->errorMessage = 'Selecione um módulo para instalar';
        return;
    }
    
    // 2. Verifica se já instalado
    if ($this->selectedTenant->hasModule($this->moduleToActivate)) {
        $this->errorMessage = 'Este módulo já está instalado';
        return;
    }
    
    DB::beginTransaction();
    
    try {
        // 3. Cria registro com status = 'installed' (NÃO 'active' ainda)
        $module = TenantModule::create([
            'module_tenant_id' => $this->selectedTenant->id,
            'module_key' => $this->moduleToActivate,
            'module_name' => $this->getModuleName($this->moduleToActivate),
            'max_users' => $this->moduleMaxUsers,
            'max_storage_gb' => $this->moduleMaxStorage,
            'enabled_features' => $this->selectedFeatures,
            'status' => 'installed',  // ← PASSO 1: INSTALADO MAS OCULTO
            'activated_at' => now(),
            'activated_by' => auth()->id(),
            'expires_at' => $this->moduleExpiresAt ?: null
        ]);
        
        // 4. Dispara instalação (cria tabelas no banco)
        $this->dispatchModuleInstallationJob($module);
        
        DB::commit();
        
        $this->successMessage = 'Módulo instalado! Agora você pode ATIVAR para aparecer no desktop.';
        
        // 5. Recarrega módulos
        $this->selectedTenant->load('modules');
        $this->selectedTenantModules = $this->selectedTenant->modules->toArray();
        $this->resetModuleForm();
        
    } catch (\Exception $e) {
        DB::rollBack();
        $this->errorMessage = 'Erro ao instalar: ' . $e->getMessage();
    }
}
```

**Arquitetura 4-Passos:**
1. **Install** (installer) → status='installed' → banco criado, desktop OCULTO
2. **Activate** (ativar) → status='active' → desktop VISÍVEL
3. **Deactivate** (desativar) → status='suspended' → desktop OCULTO, dados OK
4. **Uninstall** (desinstalar) → DELETE → tabelas removidas

#### 3.4.6 activateInstalledModule($moduleId)
```php
public function activateInstalledModule($moduleId)
{
    $module = TenantModule::find($moduleId);
    
    if ($module->status === 'active') {
        $this->errorMessage = 'Módulo já está ativo';
        return;
    }
    
    try {
        // PASSO 2: Marca como ativo
        $module->update(['status' => 'active']);
        
        // Sincroniza com MinhaDattaTech (enabled = true)
        $this->syncModuleActivation($module);
        
        $this->successMessage = 'Módulo ativado! Agora está no desktop.';
        
        $this->selectedTenant->load('modules');
        $this->selectedTenantModules = $this->selectedTenant->modules->toArray();
        
    } catch (\Exception $e) {
        $this->errorMessage = 'Erro ao ativar: ' . $e->getMessage();
    }
}
```

#### 3.4.7 dispatchModuleInstallationJob($module)
```php
private function dispatchModuleInstallationJob($module)
{
    $tenant = ModuleTenant::find($module->module_tenant_id);
    
    // Sincroniza INSTALAÇÃO com MinhaDattaTech via HTTP
    $response = Http::withHeaders([
        'X-Technical-Api-Key' => config('app.technical_api_key'),
        'Accept' => 'application/json'
    ])->post(config('app.minhadattatech_url') . '/api/technical/modules/install', [
        'subdomain' => $tenant->subdomain,
        'module_key' => $module->module_key,
        'technical_client_id' => $tenant->id
    ]);
    
    if (!$response->successful()) {
        throw new Exception('Falha na API: ' . $response->body());
    }
    
    Log::info('Module installation synchronized with MinhaDattaTech', [...]);
}
```

**Integração HTTP:**
- **URL:** `http://minha.dattatech.com.br/api/technical/modules/install`
- **Headers:** X-Technical-Api-Key
- **Payload:** { subdomain, module_key, technical_client_id }
- **Efeito:** Cria tabelas do módulo no banco de dados do tenant

#### 3.4.8 syncModuleActivation($module)
```php
private function syncModuleActivation($module)
{
    $tenant = ModuleTenant::find($module->module_tenant_id);
    
    // Sincroniza ATIVAÇÃO com MinhaDattaTech
    $response = Http::withHeaders([
        'X-Technical-Api-Key' => config('app.technical_api_key'),
        'Accept' => 'application/json'
    ])->post(config('app.minhadattatech_url') . '/api/technical/modules/activate', [
        'subdomain' => $tenant->subdomain,
        'module_key' => $module->module_key,
        'technical_client_id' => $tenant->id
    ]);
    
    if (!$response->successful()) {
        throw new Exception('Falha na API: ' . $response->body());
    }
}
```

**Integração HTTP:**
- **URL:** `http://minha.dattatech.com.br/api/technical/modules/activate`
- **Efeito:** Define enabled=true, módulo visível no desktop

#### 3.4.9 uninstallModule($moduleId)
```php
public function uninstallModule($moduleId)
{
    // AÇÃO DESTRUTIVA - requer wire:confirm()
    
    $module = TenantModule::find($moduleId);
    $tenant = ModuleTenant::find($module->module_tenant_id);
    
    DB::beginTransaction();
    
    try {
        // Sincroniza desinstalação com MinhaDattaTech
        $response = Http::withHeaders([
            'X-Technical-Api-Key' => config('app.technical_api_key'),
            'Accept' => 'application/json'
        ])->post(config('app.minhadattatech_url') . '/api/technical/modules/deactivate', [
            'subdomain' => $tenant->subdomain,
            'module_key' => $module->module_key,
            'technical_client_id' => $tenant->id,
            'uninstall' => true  // ← PASSO 4: REMOVE DADOS
        ]);
        
        if (!$response->successful()) {
            throw new Exception('Falha na API: ' . $response->json('message'));
        }
        
        // Remove registro do Technical Panel
        $module->delete();
        
        DB::commit();
        
        $this->successMessage = 'Módulo desinstalado! Tabelas removidas.';
        
        $this->selectedTenant->load('modules');
        $this->selectedTenantModules = $this->selectedTenant->modules->toArray();
        
    } catch (\Exception $e) {
        DB::rollBack();
        $this->errorMessage = 'Erro ao desinstalar: ' . $e->getMessage();
    }
}
```

**⚠️ CONFIRMAÇÃO OBRIGATÓRIA:**
```
⚠️ ATENÇÃO: AÇÃO IRREVERSÍVEL!

Desinstalar o módulo {name} irá:
• Remover TODAS as tabelas do banco de dados
• APAGAR PERMANENTEMENTE todos os dados do módulo
• Esta ação NÃO pode ser desfeita

Tem ABSOLUTA CERTEZA que deseja prosseguir?
```

#### 3.4.10 suspendTenant($tenantId)
```php
public function suspendTenant($tenantId)
{
    $tenant = ModuleTenant::find($tenantId);
    
    try {
        // Chama método do Model que:
        // - update status = 'suspended'
        // - suspende todos os módulos também
        $tenant->suspend('Suspenso via painel técnico');
        
        $this->successMessage = 'Tenant suspenso com sucesso!';
        $this->resetPage();
    } catch (\Exception $e) {
        $this->errorMessage = 'Erro ao suspender tenant';
    }
}
```

#### 3.4.11 deleteTenant($tenantId) - MUITO IMPORTANTE
```php
public function deleteTenant($tenantId)
{
    // ⚠️ AÇÃO COMPLETAMENTE DESTRUTIVA
    
    $tenant = ModuleTenant::find($tenantId);
    
    try {
        // Chama ModuleTenantService para remover TUDO
        $service = new ModuleTenantService();
        $result = $service->removeTenant($tenant);
        
        if ($result['success']) {
            $this->successMessage = 'Tenant removido completamente!';
            $this->resetPage();
        } else {
            throw new Exception($result['message']);
        }
        
    } catch (\Exception $e) {
        $this->errorMessage = 'Erro ao deletar: ' . $e->getMessage();
    }
}
```

**Confirmação:**
```
⚠️ ATENÇÃO: Esta ação REMOVERÁ COMPLETAMENTE o tenant, 
incluindo:
• Todos os arquivos
• DNS (dattapro.online)
• Configurações (Caddy)

Esta ação é IRREVERSÍVEL! Tem certeza absoluta?
```

#### 3.4.12 addUser(), editUser(), updateUser(), removeUser()
```php
public function addUser()
{
    // Validação
    $this->validate([
        'newUser.name' => 'required|string|max:255',
        'newUser.username' => 'required|string|max:50|regex:/^[a-z0-9._-]+$/',
        'newUser.recovery_email' => 'nullable|email|max:255',
        'newUser.role' => 'required|in:admin,manager,user,viewer'
    ]);
    
    try {
        // Requisição ao MinhaDattaTech
        $response = Http::withHeaders([
            'X-Technical-Api-Key' => config('app.technical_api_key'),
            'Accept' => 'application/json',
        ])->post("http://minha.dattatech.com.br/api/tenants/{$this->selectedTenant->subdomain}/users", [
            'name' => $this->newUser['name'],
            'username' => $this->newUser['username'],
            'email' => $this->newUser['username'] . '@' . $this->selectedTenant->subdomain . '.dattapro.online',
            'recovery_email' => $this->newUser['recovery_email'],  // Email externo
            'role' => $this->newUser['role'],
            'send_invitation' => true
        ]);
        
        if ($response->successful()) {
            $this->successMessage = 'Usuário criado! Email de convite enviado.';
            $this->loadTenantUsers();  // Recarrega lista
            $this->selectedTenant->increment('current_users');
        } else {
            throw new Exception($response->json('message'));
        }
    } catch (\Exception $e) {
        $this->errorMessage = 'Erro ao criar usuário: ' . $e->getMessage();
    }
}
```

**Email do Usuário:**
- **Sistema:** `{username}@{subdomain}.dattapro.online`
- **Recuperação:** Email externo (opcional, para reset de senha)
- **Roles:** admin, manager, user, viewer

---

## 4. BANCO DE DADOS / SCHEMA

### 4.1 Tabela: module_tenants

```sql
CREATE TABLE module_tenants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    
    -- Dados do Cliente
    crm_customer_uuid VARCHAR(36) UNIQUE NOT NULL,
    crm_customer_id BIGINT NULLABLE,
    customer_name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    custom_domain VARCHAR(255) NULLABLE,
    primary_email VARCHAR(255) NOT NULL,
    primary_phone VARCHAR(20) NULLABLE,
    
    -- Status
    status ENUM('active', 'suspended', 'terminated') DEFAULT 'active',
    
    -- Limites de Usuários
    max_users INT DEFAULT 5,
    current_users INT DEFAULT 0,
    
    -- Configurações de Usuários
    allow_user_registration BOOLEAN DEFAULT false,
    require_email_verification BOOLEAN DEFAULT true,
    allow_password_reset BOOLEAN DEFAULT true,
    last_user_activity TIMESTAMP NULLABLE,
    
    -- Dados Adicionais
    metadata JSON NULLABLE,  -- { "notes": "...", "created_from": "technical_panel" }
    
    -- Auditoria
    created_by BIGINT NULLABLE FOREIGN KEY -> users(id),
    updated_by BIGINT NULLABLE FOREIGN KEY -> users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    INDEX(status),
    INDEX(subdomain),
    INDEX(customer_name)
);
```

**Campos Importantes:**
- `subdomain`: Único, usado para URL do tenant (prefeitura-sp.dattapro.online)
- `status`: active (operacional), suspended (pausado), terminated (encerrado)
- `max_users`: Limite de usuários para este tenant
- `current_users`: Contador de usuários ativos
- `metadata`: JSON com notas internas, origem da criação, etc

### 4.2 Tabela: tenant_modules

```sql
CREATE TABLE tenant_modules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    module_tenant_id BIGINT NOT NULL FOREIGN KEY -> module_tenants(id),
    
    module_key VARCHAR(100) NOT NULL,  -- 'price_basket', 'bidding', etc
    module_name VARCHAR(255) NOT NULL, -- 'Cesta de Preços'
    
    -- Configurações
    configuration JSON NULLABLE,
    max_users INT DEFAULT 5,
    max_storage_gb INT DEFAULT 1,
    enabled_features JSON NULLABLE,
    
    -- Status Lifecycle
    status ENUM('installed', 'active', 'suspended', 'expired') DEFAULT 'active',
    activated_at TIMESTAMP NULLABLE,
    expires_at TIMESTAMP NULLABLE,
    
    -- Auditoria
    activated_by BIGINT NULLABLE FOREIGN KEY -> users(id),
    notes TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Constraints
    UNIQUE(module_tenant_id, module_key),
    
    -- Indexes
    INDEX(module_key, status),
    INDEX(expires_at)
);
```

**Status Lifecycle (4 Passos):**
1. `installed` → Tabelas criadas no banco, NÃO visível no desktop
2. `active` → Visível e funcional no desktop
3. `suspended` → Oculto do desktop, dados preservados
4. `expired` → Expirou por data de fim

### 4.3 Migrations

**2025_09_26_create_module_tenants_table.php:**
- Cria tabela principal de tenants

**2025_09_26_create_tenant_modules_table.php:**
- Cria tabela de módulos por tenant

**2025_09_28_add_user_limits_to_module_tenants.php:**
- Adiciona colunas de limites de usuários

---

## 5. INTEGRAÇÕES EXTERNAS

### 5.1 CRM Integration Service

**Arquivo:** `App\Services\CrmIntegrationService`
**Método:** `getAvailableClients()`
**Retorno:**
```php
[
    [
        'id' => 'uuid-string',
        'name' => 'Prefeitura Municipal de São Paulo',
        'email' => 'contato@prefeitura.sp.gov.br',
        'phone' => '(11) 3313-3000',
        'document' => '12.345.678/0001-90',
        'crm_id' => 123
    ],
    // ... mais clientes
]
```

**Integração:** Lê dados de cliente no formulário de criação
**Propósito:** Pré-preencher dados para novo tenant

### 5.2 DNS Configuration (SimpleDnsService)

**Arquivo:** `App\Services\SimpleDnsService`
**Método:** `addSubdomain($subdomain, $ipAddress)`
**O que faz:**
- Adiciona registro A no BIND9: `{subdomain}.dattapro.online → 179.108.221.51`
- Adiciona registro A: `www.{subdomain}.dattapro.online → 179.108.221.51`
- Valida zona com `named-checkzone`

**Arquivo de Zona:** `/etc/bind/zones/db.dattapro.online`

**Exemplo de Entrada:**
```
prefeitura-sp    IN  A  179.108.221.51
www.prefeitura-sp IN  A  179.108.221.51
```

### 5.3 Web Server Configuration (Caddy)

**Arquivo Gerado:** `/home/hosting/config/caddy/{subdomain}.conf`
**Exemplo de Configuração:**
```caddyfile
prefeitura-sp.dattapro.online, www.prefeitura-sp.dattapro.online {
    # SSL automático via Let's Encrypt
    tls {
        protocols tls1.2 tls1.3
    }
    
    # Proxy reverso para MinhaDattaTech
    reverse_proxy https://minha.dattatech.com.br {
        header_up Host minha.dattatech.com.br
        header_up X-Original-Host {host}
        header_up X-Tenant-Domain prefeitura-sp.dattapro.online
        header_up X-Real-IP {remote_host}
    }
    
    # Headers de segurança
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        X-XSS-Protection "1; mode=block"
        Referrer-Policy strict-origin-when-cross-origin
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
    }
    
    # Redireciona www para non-www
    @www host www.prefeitura-sp.dattapro.online
    redir @www https://prefeitura-sp.dattapro.online{uri} permanent
}
```

**Procedimento:**
1. Gera arquivo temporário em `/tmp/`
2. Copia para `/home/hosting/config/caddy/` com `sudo`
3. Remove arquivo temporário
4. Restart do Caddy para gerar certificados SSL

### 5.4 MinhaDattaTech Integration

#### 5.4.1 Criar Tenant
**Comando:** `php artisan tenant:create {subdomain} {customer_name}`
**Arquivo:** `/home/dattapro/minhadattatech/artisan`
**Variáveis de Ambiente:**
```
APP_KEY=base64:wicqkfWquBvYK6ClrBYle0GNWpCcUp5ONhcZ3obexGg=
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minhadattatech_db
DB_USERNAME=minhadattatech_user
DB_PASSWORD=MinhaDataTech2024SecureDB
```

**Efeito:**
- Cria database nova para o tenant
- Roda migrations (cria tabelas)
- Roda seeders (insere dados iniciais)
- Registra tenant no banco central

#### 5.4.2 Instalar Módulo (API)
**Endpoint:** `POST /api/technical/modules/install`
**Headers:** `X-Technical-Api-Key`
**Payload:**
```json
{
    "subdomain": "prefeitura-sp",
    "module_key": "price_basket",
    "technical_client_id": 1
}
```
**Efeito:** Cria tabelas do módulo no banco do tenant

#### 5.4.3 Ativar Módulo (API)
**Endpoint:** `POST /api/technical/modules/activate`
**Payload:**
```json
{
    "subdomain": "prefeitura-sp",
    "module_key": "price_basket",
    "technical_client_id": 1
}
```
**Efeito:** `enabled = true`, módulo visível no desktop

#### 5.4.4 Desativar Módulo (API)
**Endpoint:** `POST /api/technical/modules/deactivate`
**Payload:**
```json
{
    "subdomain": "prefeitura-sp",
    "module_key": "price_basket",
    "technical_client_id": 1,
    "uninstall": false  // desativar apenas
}
```
**Efeito:** `enabled = false`, módulo oculto

#### 5.4.5 Remover Tenant (API + Artisan)
**Comando:** `php artisan tenant:remove {subdomain} --force`
**Efeito:**
- Remove todas as tabelas do banco do tenant
- Remove registro do banco central
- Delete database

#### 5.4.6 Usuários (API)
**GET** `/api/tenants/{subdomain}/users`
- Lista usuários do tenant

**POST** `/api/tenants/{subdomain}/users`
- Cria novo usuário
- Envia email de convite

**PUT** `/api/tenants/{subdomain}/users/{userId}`
- Atualiza dados do usuário

**DELETE** `/api/tenants/{subdomain}/users/{userId}`
- Remove usuário

---

## 6. FLUXO COMPLETO PASSO-A-PASSO

### PASSO 1: USUÁRIO CLICA "NOVO TENANT"
```
Arquivo: manager.blade.php (linhas 70-76)
Elemento: <button wire:click="openCreateModal">Novo Tenant</button>
Ação: Dispara openCreateModal() no Manager.php (linha 170)
Resultado: $showCreateModal = true; Modal abre
```

### PASSO 2: MODAL ABRE COM FORMULÁRIO
```
Arquivo: manager.blade.php (linhas 448-607)
Exibição: 
- Select com clientes CRM (wire:model.live="crm_customer_uuid")
- Input nome cliente (wire:model="customer_name")
- Input subdomínio (wire:model="subdomain")
- Input email (wire:model="primary_email")
- Input telefone (wire:model="primary_phone")
- Textarea observações (wire:model="notes")
```

### PASSO 3: USUÁRIO SELECIONA CLIENTE CRM
```
Arquivo: Manager.php (linhas 126-145)
Método: updatedCrmCustomerUuid($value)
Efeito:
1. Encontra cliente no array $crmClients
2. Auto-preenche: customer_name, primary_email, primary_phone
3. Chama generateSubdomain() para auto-gerar URL
4. Se já tem subdomínio, NÃO sobrescreve

Resultado: customer_name = "Prefeitura de São Paulo"
            subdomain = "prefeitura-sao-paulo"
            primary_email = "contato@prefeitura.sp.gov.br"
```

### PASSO 4: USUÁRIO PREENCHE DADOS E ENVIA
```
Arquivo: manager.blade.php (linhas 600-603)
Elemento: <button type="submit">Criar Tenant</button>
Ação: Form submit → saveTenant() em Manager.php
```

### PASSO 5: VALIDAÇÃO (FRONTEND)
```
HTML5 Validations (browser):
- required (todos os campos obrigatórios)
- email (primary_email)
- pattern (subdomain: [a-z0-9\-]+)

Se houver erro → Form NÃO submete
```

### PASSO 6: VALIDAÇÃO (BACKEND)
```
Arquivo: Manager.php (linhas 204-213)
Método: saveTenant()
Validação: $this->validate()

Rules:
- crm_customer_uuid: required|string|max:36
- customer_name: required|string|max:255
- subdomain: required|regex:/^[a-z0-9-]+$/|unique:module_tenants
- primary_email: required|email|max:255
- primary_phone: nullable|string|max:20

Se falhar: Exibe mensagem @error() no modal
Se passar: Continua
```

### PASSO 7: INICIA TRANSAÇÃO DB
```
Código: DB::beginTransaction()
Propósito: Garante atomicidade
Se houver erro depois → Todos os dados são desfeitos
```

### PASSO 8: CRIA REGISTRO NO TECHNICAL PANEL
```
Arquivo: Manager.php (linhas 238-243)
Modelo: ModuleTenant::create([
    'crm_customer_uuid' => 'uuid-123',
    'customer_name' => 'Prefeitura de São Paulo',
    'subdomain' => 'prefeitura-sao-paulo',
    'primary_email' => 'contato@prefeitura.sp.gov.br',
    'primary_phone' => '(11) 3313-3000',
    'status' => 'active',
    'created_by' => auth()->id(),
    'metadata' => ['notes' => '...', 'created_from' => 'technical_panel']
])

Resultado: ModuleTenant ID #1 criado no banco
```

### PASSO 9: CHAMA INFRAESTRUTURA (SINCRONOUSLY!)
```
Arquivo: Manager.php (linhas 246-247)
Método: ModuleTenantService::createTenant($tenant)

IMPORTANTE: NÃO é fila (queue), é síncrono!
Pode levar 30-60 segundos
```

### PASSO 9.1: CONFIGURA DNS
```
Arquivo: ModuleTenantService.php (linhas 137-159)
Método: configureDns($subdomain)

1. Chama SimpleDnsService::addSubdomain()
   - Adiciona: prefeitura-sao-paulo.dattapro.online → 179.108.221.51
   - Adiciona: www.prefeitura-sao-paulo.dattapro.online → 179.108.221.51
   
2. Valida zona com named-checkzone
3. Se falhar, lança Exception (rollback)
```

### PASSO 9.2: CONFIGURA CADDY (REVERSE PROXY)
```
Arquivo: ModuleTenantService.php (linhas 164-212)
Método: configureCaddy($tenant)

1. Gera arquivo de configuração:
   /tmp/caddy_prefeitura-sao-paulo.conf
   
2. Conteúdo:
   - Domain: prefeitura-sao-paulo.dattapro.online
   - Proxy para: https://minha.dattatech.com.br
   - Headers: X-Tenant-Domain, X-Real-IP, etc
   - SSL: auto via Let's Encrypt
   - Redirect: www → non-www
   
3. Copia para: /home/hosting/config/caddy/
4. Remove arquivo temporário
```

### PASSO 9.3: CRIA TENANT NO MINHADATTATECH
```
Arquivo: ModuleTenantService.php (linhas 218-268)
Método: createInMinhaDattaTech($tenant)

Comando:
php /usr/bin/php /home/dattapro/minhadattatech/artisan \
  tenant:create "prefeitura-sao-paulo" "Prefeitura de São Paulo" \
  --technical_client_id=1 --crm_customer_id=0

Variáveis de Ambiente:
- APP_KEY (obrigatório para Laravel)
- DB_* (configuração PostgreSQL)
- DB_USERNAME, DB_PASSWORD (credenciais)

Efeito:
1. Cria database: "prefeitura_sao_paulo" (ou similar)
2. Roda migrations (cria tabelas padrão)
3. Roda seeders (insere dados iniciais)
4. Registra no tenant central

Timeout: 30 segundos
```

### PASSO 9.4: RECARREGA SERVIÇOS
```
Arquivo: ModuleTenantService.php (linhas 374-420)
Método: reloadServices()

1. DNS:
   sudo /usr/bin/systemctl reload named
   Tempo: ~5 segundos
   
2. Caddy (IMPORTANTE: RESTART não reload):
   sudo /usr/bin/systemctl restart caddy
   Motivo: Precisa gerar certificado SSL para novo domínio
   
3. Aguarda: sleep(5) para SSL ser gerado
   
4. Verifica: systemctl is-active caddy
   Status deve ser "active"
   
Se restart falhar → tenta reload como fallback
```

### PASSO 10: COMMIT TRANSAÇÃO
```
Código: DB::commit()
Resultado: ModuleTenant é salvo permanentemente no banco
```

### PASSO 11: EXIBE SUCESSO
```
Arquivo: Manager.php (linhas 250-251)
Mensagem: "Tenant criado com sucesso! Portal: https://prefeitura-sao-paulo.dattapro.online"

Ações:
- $showCreateModal = false (fecha modal)
- $this->resetFormFields() (limpa formulário)
- $this->resetPage() (retorna página 1 da lista)
```

### PASSO 12: USUÁRIO VÊ NOVO TENANT NA LISTA
```
Arquivo: manager.blade.php (linhas 226-398)
Tabela: Mostra novo tenant com:
- ID
- Nome cliente
- Subdomínio (link clicável)
- 0 módulos
- Status: "Ativo" (verde)
- Data criação
- Botões de ação
```

---

## 7. PROBLEMAS IDENTIFICADOS

### CRÍTICOS

#### 7.1 SENHA DO BANCO DE DADOS EM CÓDIGO-FONTE
**Arquivo:** `ModuleTenantService.php` (linhas 233-239)
**Problema:**
```php
Process::env([
    'APP_KEY' => 'base64:wicqkfWquBvYK6ClrBYle0GNWpCcUp5ONhcZ3obexGg=',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'minhadattatech_db',
    'DB_USERNAME' => 'minhadattatech_user',
    'DB_PASSWORD' => 'MinhaDataTech2024SecureDB'  // ← HARDCODED!
])->timeout(30)->run($command);
```
**Risco:** Credenciais visíveis no código-fonte
**Solução:**
- Mover para `.env` do sistema
- Usar `env('DB_PASSWORD')` ao invés de hardcoded
- Usar variáveis de ambiente do servidor

#### 7.2 IP DO SERVIDOR HARDCODED
**Arquivo:** `ModuleTenantService.php` (linhas 142, 152)
**Problema:**
```php
$dnsResult = $this->dnsService->addSubdomain(
    $subdomain,
    '179.108.221.51'  // ← HARDCODED!
);
```
**Risco:** Se IP mudar, precisa atualizar código
**Solução:**
```php
'server_ip' => env('SERVER_IP', '179.108.221.51')
```

#### 7.3 SINCRONIZAÇÃO DE USUÁRIOS NÃO BIDIRECIONAL
**Problema:** Usuários são criados via API no MinhaDattaTech, mas:
- Technical Panel lê via HTTP GET
- Não há webhook para sincronização automática
- Se usuário é criado no MinhaDattaTech diretamente, não aparece no Technical Panel

**Falta:** 
- Event listeners em MinhaDattaTech
- Webhook para notificar Technical Panel
- Fila de sincronização periódica

#### 7.4 ROLLBACK INCOMPLETO
**Arquivo:** `ModuleTenantService.php` (linhas 425-435)
**Problema:**
```php
protected function rollbackCreation(ModuleTenant $tenant): void
{
    try {
        $this->removeCaddyConfiguration($tenant->subdomain);
        $this->removeDnsEntries($tenant->subdomain);
        $this->removeFromMinhaDattaTech($tenant->subdomain);
    } catch (Exception $e) {
        Log::error("Rollback failed", ['error' => $e->getMessage()]);
        // ← SEM RAISE! Silencia o erro
    }
}
```
**Problema:** Se rollback falha, o erro é apenas logado
**Consequência:** Tenant fica em estado inconsistente (banco criado, DNS não removido, etc)
**Solução:** Alertar admin sobre tenant inconsistente

#### 7.5 NENHUMA VALIDAÇÃO DE UNICIDADE DE SUBDOMÍNIO EM TEMPO REAL
**Problema:** Geração de subdomínio é baseada em while loop, mas:
- Race condition: dois tenants podem tentar simultaneamente
- Sem lock pessimista no banco de dados

**Solução:**
```php
while (true) {
    try {
        // Tenta inserir com unique constraint
        ModuleTenant::create(['subdomain' => $subdomain, ...]);
        break;
    } catch (UniqueConstraintViolation) {
        $counter++;
        $subdomain = $base . '-' . $counter;
    }
}
```

#### 7.6 TIMEOUT MUITO CURTO PARA CADDY RESTART
**Arquivo:** `ModuleTenantService.php` (linhas 389)
**Problema:**
```php
$result = Process::timeout(30)->run('sudo /usr/bin/systemctl restart caddy');
```
**Risco:** Se certificado SSL leva >30s, timeout ocorre
**Solução:** Aumentar timeout ou validar certificado em background

#### 7.7 MODELTENANT NÃO TEM FILLABLE PARA COLUNAS ADICIONADAS
**Problema:** Migrations adicionam colunas (max_users, allow_user_registration, etc) mas Model pode não ter fillable atualizado
**Arquivo:** `ModuleTenant.php` (linhas 15-27)
**Fillable:**
```php
protected $fillable = [
    'crm_customer_uuid',
    'crm_customer_id',
    'customer_name',
    'subdomain',
    'custom_domain',
    'primary_email',
    'primary_phone',
    'status',
    'metadata',
    'created_by',
    'updated_by'
    // ← FALTAM: max_users, current_users, allow_user_registration, etc
];
```

#### 7.8 NENHUMA VALIDAÇÃO QUE TENANT EXISTE ANTES DE REMOVER
**Arquivo:** `Manager.php` (linha 878)
**Problema:**
```php
public function deleteTenant($tenantId)
{
    $tenant = ModuleTenant::find($tenantId);  // Pode ser NULL
    
    if (!$tenant) {  // Verifica
        $this->errorMessage = 'Tenant não encontrado';
        return;
    }
    
    // Mas aqui pode ter race condition!
    // Outro usuário deletou entre find() e removeTenant()
}
```

#### 7.9 SEM AUDIT LOG DE CRIAÇÃO/DELEÇÃO DE TENANTS
**Problema:** Nenhum registro de quem criou/deletou tenant
**Buscado em código:** Apenas `created_by` e `updated_by` são salvos
**Falta:** Verdadeiro audit trail com detalhes da ação

#### 7.10 SEM VALIDAÇÃO DE CAPACIDADE DO SERVIDOR
**Problema:** Cria quantos tenants quiser sem validar:
- Espaço em disco
- Limite de conexões PostgreSQL
- Limite de processos Caddy

### IMPORTANTES MAS NÃO CRÍTICOS

#### 7.11 MÓDULOS SÓ SUPORTAM "price_basket"
**Arquivo:** `Manager.php` (linhas 954-962)
**Problema:**
```php
public function getAvailableModules()
{
    return [
        'price_basket' => [
            'name' => 'Cesta de Preços',
            // ...
        ]
        // ← COMENTADO/REMOVIDO: bidding, transparency, etc
    ];
}
```
**Consequência:** Não pode instalar outros módulos
**Solução:** Implementar módulos adicionais

#### 7.12 FEATURES SÃO VAZIAS
**Arquivo:** `Manager.php` (linhas 972-974)
**Problema:**
```php
public function getModuleFeatures($moduleKey)
{
    $features = [
        'price_basket' => []  // Sem features
    ];
}
```
**Consequência:** Nenhuma feature pode ser selecionada

#### 7.13 NENHUMA PROTEÇÃO CONTRA DELEÇÃO ACIDENTAL
**Problema:** wire:confirm é apenas JavaScript, pode ser bypassado
**Solução:** Implementar código de confirmação (tipo "DELETAR TENANT_ID")

#### 7.14 SINCRONIZAÇÃO DE USUÁRIOS NÃO TRATA 404
**Arquivo:** `Manager.php` (linhas 1002-1015)
**Problema:**
```php
$response = Http::get("http://minha.dattatech.com.br/api/tenants/{$subdomain}/users");

if ($response->successful()) {
    $this->tenantUsers = $response->json('users', []);
} else {
    $this->tenantUsers = [];  // ← Silencia erro
}
```
**Problema:** Se API retorna 404 ou 500, usuário não sabe

#### 7.15 URL DO MINHADATTATECH HARDCODED
**Arquivo:** Múltiplas linhas (1005, 1113, 1200, etc)
**Problema:**
```php
"http://minha.dattatech.com.br/api/..."  // Hardcoded!
```
**Solução:** Usar `config('app.minhadattatech_url')`

#### 7.16 EMAIL DE USUÁRIO NÃO PODE SER ALTERADO
**Problema:** Email do usuário é sempre `{username}@{subdomain}.dattapro.online`
**Arquivo:** `Manager.php` (linhas 1116, 1195)
**Problema:** Não permite customização

#### 7.17 SEM NOTIFICAÇÃO POR EMAIL
**Problema:** Quando tenant é criado, ninguém é notificado
**Falta:** Email para admin/customer

#### 7.18 SEM LOGGING ESTRUTURADO
**Problema:** Logs usam Log::info/error mas sem estrutura consistente
**Falta:** Logging com stack traces, context completo

### MENORES

#### 7.19 PLACEHOLDER TEXTO NA LISTA DE TENANTS VAZIOS
**Arquivo:** `manager.blade.php` (linhas 407-415)
**UI:** Exibe "Nenhum tenant encontrado" 
**Problema:** Não diferencia entre "nenhum criado" e "nenhum na página atual"

#### 7.20 PAGINAÇÃO NÃO RESET AO FILTRAR
**Problema:** Se está na página 3 e filtra por status, fica na página 3 (pode estar vazia)
**Solução:** `$this->resetPage()` quando filtro muda

#### 7.21 SUBDOMÍNIO NÃO PODE SER ALTERADO DEPOIS
**Arquivo:** `manager.blade.php` (linhas 476-477)
**UI:** `@if($editingTenantId) disabled @endif`
**Problema:** Subdomínio é disable ao editar, pode confundir usuário

#### 7.22 SEM LOADING STATE NA CRIAÇÃO
**Problema:** Criação pode levar 30-60s, sem feedback visual
**Falta:** Spinner/progress bar

---

## 8. CÓDIGO-FONTE RELEVANTE

### 8.1 Componente Livewire - Manager.php

**Método Principal: saveTenant() (Linhas 204-269)**
```php
public function saveTenant()
{
    // Validação com rules customizadas para edição
    if ($this->editingTenantId) {
        $this->rules['subdomain'] = 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:module_tenants,subdomain,' . $this->editingTenantId;
    } else {
        $this->rules['subdomain'] = 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:module_tenants';
    }

    $this->validate();

    DB::beginTransaction();

    try {
        $data = [
            'crm_customer_uuid' => $this->crm_customer_uuid,
            'crm_customer_id' => $this->crm_customer_id,
            'customer_name' => $this->customer_name,
            'subdomain' => $this->subdomain,
            'primary_email' => $this->primary_email,
            'primary_phone' => $this->primary_phone,
            'metadata' => [
                'notes' => $this->notes,
                'created_from' => 'technical_panel'
            ]
        ];

        if ($this->editingTenantId) {
            // Atualizar tenant existente
            $tenant = ModuleTenant::find($this->editingTenantId);
            $data['updated_by'] = auth()->id();
            $tenant->update($data);

            $this->successMessage = 'Tenant atualizado com sucesso!';
        } else {
            // Criar novo tenant
            $data['status'] = 'active';
            $data['created_by'] = auth()->id();

            $tenant = ModuleTenant::create($data);

            // Criar infraestrutura de forma SÍNCRONA
            $service = new ModuleTenantService();
            $result = $service->createTenant($tenant);

            if ($result['success']) {
                $this->successMessage = 'Tenant criado com sucesso! Portal disponível em: ' . $result['portal_url'];
            } else {
                throw new \Exception($result['message']);
            }
        }

        DB::commit();

        $this->showCreateModal = false;
        $this->resetFormFields();
        $this->resetPage();

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to save tenant', ['error' => $e->getMessage()]);
        $this->errorMessage = 'Erro ao salvar tenant: ' . $e->getMessage();
    }
}
```

### 8.2 Service - ModuleTenantService.php

**Método Principal: createTenant() (Linhas 30-77)**
```php
public function createTenant(ModuleTenant $tenant): array
{
    try {
        Log::info("Starting module tenant creation", [
            'tenant_id' => $tenant->id,
            'subdomain' => $tenant->subdomain,
            'customer' => $tenant->customer_name
        ]);

        // 1. Configurar DNS para o subdomínio
        $this->configureDns($tenant->subdomain);

        // 2. Configurar Caddy para proxy reverso ao MinhaDattaTech
        $this->configureCaddy($tenant);

        // 3. Criar tenant no MinhaDattaTech
        $this->createInMinhaDattaTech($tenant);

        // 4. Recarregar serviços
        $this->reloadServices();

        Log::info("Module tenant creation completed successfully", [
            'tenant_id' => $tenant->id,
            'subdomain' => $tenant->subdomain,
            'portal_url' => $tenant->getPortalUrl()
        ]);

        return [
            'success' => true,
            'message' => 'Tenant criado com sucesso',
            'portal_url' => $tenant->getPortalUrl()
        ];

    } catch (Exception $e) {
        Log::error("Module tenant creation failed", [
            'tenant_id' => $tenant->id,
            'error' => $e->getMessage()
        ]);

        // Tentar fazer rollback
        $this->rollbackCreation($tenant);

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
```

### 8.3 Model - ModuleTenant.php

**Boot Method com Auto-generation de Subdomínio (Linhas 36-56)**
```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($tenant) {
        // Auto-gerar subdomínio se não fornecido
        if (!$tenant->subdomain && $tenant->customer_name) {
            $base = Str::slug($tenant->customer_name);
            $subdomain = $base;
            $counter = 1;

            // Garantir subdomínio único
            while (self::where('subdomain', $subdomain)->exists()) {
                $subdomain = $base . $counter;
                $counter++;
            }

            $tenant->subdomain = $subdomain;
        }
    });
}
```

### 8.4 Blade Template - manager.blade.php

**Botão Novo Tenant (Linhas 70-76)**
```blade
<button wire:click="openCreateModal"
        class="bg-white text-dt-primary px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center space-x-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Novo Tenant</span>
</button>
```

**Modal Form (Linhas 448-607)**
```blade
@if($showCreateModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mt-20 mb-8">
            <form wire:submit="saveTenant">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-dt-primary to-dt-primary-light rounded-t-xl">
                    <h3 class="text-xl font-semibold text-white">
                        {{ $editingTenantId ? 'Editar' : 'Criar Novo' }} Tenant
                    </h3>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- Cliente CRM -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cliente CRM <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="crm_customer_uuid"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dt-accent focus:border-dt-accent transition-colors duration-200 @error('crm_customer_uuid') border-red-500 @enderror"
                                @if($editingTenantId) disabled @endif>
                            <option value="">-- Selecione um cliente --</option>
                            @foreach($crmClients as $client)
                                <option value="{{ $client['id'] }}">
                                    {{ $client['name'] }}
                                    @if(isset($client['document']))
                                        - {{ $client['document'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('crm_customer_uuid')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- More fields... -->
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex justify-end space-x-3">
                    <button type="button" wire:click="closeModals"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-dt-primary text-white rounded-lg hover:bg-dt-primary-dark transition-colors duration-200">
                        {{ $editingTenantId ? 'Atualizar' : 'Criar' }} Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
```

---

## 9. RECOMENDAÇÕES

### CRÍTICOS (FAZER IMEDIATAMENTE)

1. **Remover hardcoded credentials**
   - Mover DATABASE PASSWORD para `.env`
   - Usar `env('DB_PASSWORD')` em ModuleTenantService
   - Mesma coisa para APP_KEY e SERVER_IP

2. **Implementar verificação real de rollback**
   - Se rollback falha, alertar admin
   - Criar tabela de "tenants inconsistentes"
   - Dashboard para limpar estados inconsistentes

3. **Adicionar race condition protection**
   - Usar lock pessimista no banco
   - Ou tratar UniqueConstraintViolation em while loop

4. **Aumentar timeout de Caddy**
   - De 30s para 60s
   - Ou validar certificado em background

5. **Atualizar Model fillable**
   - Adicionar colunas novas: max_users, allow_user_registration, etc
   - Validar dados antes de atualizar

6. **Implementar audit log real**
   - Tabela: `audit_logs` com action, actor, details, timestamp
   - Log toda criação/deleção de tenant

### IMPORTANTES (FAZER LOGO)

7. **Adicionar loading state na criação**
   - Progress bar com "Configurando DNS...", "Criando portal...", etc
   - Evitar double-click

8. **Validação de capacidade de servidor**
   - Verificar espaço em disco
   - Verificar limite de conexões PostgreSQL
   - Alertar se próximo dos limites

9. **Webhook ou sincronização bidirecionai de usuários**
   - Implementar event listeners em MinhaDattaTech
   - Ou fila de sincronização periódica

10. **Proteção contra deleção acidental**
    - Código de confirmação (digitar "DELETAR ID")
    - Ou 2FA

11. **Logging estruturado**
    - Usar Monolog ou similar
    - Stack traces, context, request ID

12. **Implementar módulos adicionais**
    - Desativar/comentar apenas temporariamente
    - Implementar bidding, transparency, etc

### MELHORIAS UX

13. **Diferenciação de estados do subdomínio**
   - Verde se único
   - Amarelo se colisão detectada
   - Real-time validation

14. **Previsão de tempo de criação**
   - "Você será redirecio em ~2 minutos"
   - Status atualizado em tempo real (Livewire polling)

15. **Integração com CRM mais forte**
   - Sincronizar automaticamente quando novo cliente é criado no CRM
   - Webhook do CRM para Technical Panel

16. **Template de portas padrão**
   - Não exigir que usuário preencha max_users, storage, etc
   - Usar presets por tipo de cliente (pequena, média, grande, enterprise)

### SEGURANÇA

17. **HTTPS obrigatório em chamadas para MinhaDattaTech**
    - Atualmente é `http://` em alguns lugares
    - Deve ser `https://`

18. **API Key validation mais forte**
    - Usar Bearer token ao invés de header customizado
    - Implementar rotating keys

19. **Rate limiting**
    - Limitar criações de tenant por usuário/IP
    - Evitar abuso

20. **Validação de domínio**
    - Verificar se subdomínio não conflita com serviços existentes
    - Blocklist de palavras-chave (admin, api, test, etc)

---

## CONCLUSÃO

A funcionalidade de criação de tenants está **estruturalmente sólida** com boa separação de responsabilidades (Livewire → Service → Infraestrutura), mas tem **problemas críticos de segurança e robustez** que precisam ser endereçados, especialmente:

1. Credenciais hardcoded
2. Rollback incompleto
3. Race conditions
4. Falta de audit log

Depois de corrigir os itens críticos, a sistema será muito mais confiável e seguro para produção.

