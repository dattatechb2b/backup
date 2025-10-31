<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogItem extends Model
{
    // NÃO especificar $connection - usar a conexão padrão do tenant configurada dinamicamente
    // (cada tenant tem seu próprio banco, ex: novaroma_db, materlandia_db, etc)
    protected $table = 'cp_audit_log_itens';

    protected $fillable = [
        'item_id',
        'event_type',
        'sample_number',
        'before_value',
        'after_value',
        'rule_applied',
        'justification',
        'usuario_id',
        'usuario_nome',
    ];

    protected $casts = [
        'sample_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes para tipos de eventos
    const EVENT_SNAPSHOT_CREATED = 'snapshot_created';            // ✅ NOVO: Snapshot automático
    const EVENT_APPLY_SANITIZATION_DP = 'APPLY_SANITIZATION_DP';
    const EVENT_APPLY_SANITIZATION_MEDIAN = 'APPLY_SANITIZATION_MEDIAN';
    const EVENT_PURGE_SAMPLE = 'PURGE_SAMPLE';
    const EVENT_REVALIDATE_SAMPLE = 'REVALIDATE_SAMPLE';
    const EVENT_CHANGE_METHODOLOGY = 'CHANGE_METHODOLOGY';
    const EVENT_ADJUST_CONVERSION = 'ADJUST_CONVERSION';
    const EVENT_EDIT_SAMPLE = 'EDIT_SAMPLE';
    const EVENT_ADD_ATTACHMENT = 'ADD_ATTACHMENT';
    const EVENT_REMOVE_ATTACHMENT = 'REMOVE_ATTACHMENT';
    const EVENT_UPDATE_LINK_QR = 'UPDATE_LINK_QR';
    const EVENT_FIX_SNAPSHOT = 'FIX_SNAPSHOT';
    const EVENT_GENERATE_PDF = 'GENERATE_PDF';

    /**
     * Relacionamento com Item do Orçamento
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemOrcamento::class, 'item_id');
    }

    /**
     * Relacionamento com Usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Ícone baseado no tipo de evento
     */
    public function getIconeAttribute(): string
    {
        return match($this->event_type) {
            self::EVENT_SNAPSHOT_CREATED => 'fa-camera',          // ✅ NOVO
            self::EVENT_APPLY_SANITIZATION_DP => 'fa-filter',
            self::EVENT_APPLY_SANITIZATION_MEDIAN => 'fa-filter',
            self::EVENT_PURGE_SAMPLE => 'fa-trash',
            self::EVENT_REVALIDATE_SAMPLE => 'fa-redo',
            self::EVENT_CHANGE_METHODOLOGY => 'fa-sliders-h',
            self::EVENT_ADJUST_CONVERSION => 'fa-exchange-alt',
            self::EVENT_EDIT_SAMPLE => 'fa-edit',
            self::EVENT_ADD_ATTACHMENT => 'fa-paperclip',
            self::EVENT_REMOVE_ATTACHMENT => 'fa-times',
            self::EVENT_UPDATE_LINK_QR => 'fa-link',
            self::EVENT_FIX_SNAPSHOT => 'fa-camera',
            self::EVENT_GENERATE_PDF => 'fa-file-pdf',
            default => 'fa-history',
        };
    }

    /**
     * Cor da badge baseada no evento
     */
    public function getCorAttribute(): string
    {
        return match($this->event_type) {
            self::EVENT_SNAPSHOT_CREATED => '#8b5cf6',            // ✅ NOVO: Roxo
            self::EVENT_APPLY_SANITIZATION_DP => '#10b981',
            self::EVENT_APPLY_SANITIZATION_MEDIAN => '#10b981',
            self::EVENT_PURGE_SAMPLE => '#ef4444',
            self::EVENT_REVALIDATE_SAMPLE => '#3b82f6',
            self::EVENT_CHANGE_METHODOLOGY => '#f59e0b',
            self::EVENT_ADJUST_CONVERSION => '#f59e0b',
            self::EVENT_EDIT_SAMPLE => '#f59e0b',
            self::EVENT_ADD_ATTACHMENT => '#6b7280',
            self::EVENT_REMOVE_ATTACHMENT => '#ef4444',
            self::EVENT_UPDATE_LINK_QR => '#3b82f6',
            self::EVENT_FIX_SNAPSHOT => '#8b5cf6',
            self::EVENT_GENERATE_PDF => '#06b6d4',
            default => '#6b7280',
        };
    }

    /**
     * Label amigável do evento
     */
    public function getLabelAttribute(): string
    {
        return match($this->event_type) {
            self::EVENT_SNAPSHOT_CREATED => 'Snapshot Criado',    // ✅ NOVO
            self::EVENT_APPLY_SANITIZATION_DP => 'Saneamento DP±MEAN',
            self::EVENT_APPLY_SANITIZATION_MEDIAN => 'Saneamento MEDIAN±PERC',
            self::EVENT_PURGE_SAMPLE => 'Expurgo Manual',
            self::EVENT_REVALIDATE_SAMPLE => 'Revalidar Amostra',
            self::EVENT_CHANGE_METHODOLOGY => 'Mudar Metodologia',
            self::EVENT_ADJUST_CONVERSION => 'Ajustar Conversão',
            self::EVENT_EDIT_SAMPLE => 'Editar Amostra',
            self::EVENT_ADD_ATTACHMENT => 'Adicionar Anexo',
            self::EVENT_REMOVE_ATTACHMENT => 'Remover Anexo',
            self::EVENT_UPDATE_LINK_QR => 'Atualizar Link/QR',
            self::EVENT_FIX_SNAPSHOT => 'Fixar Snapshot',
            self::EVENT_GENERATE_PDF => 'Gerar PDF',
            default => $this->event_type,
        };
    }

    /**
     * Registrar um log de auditoria (método estático helper)
     */
    public static function log(
        int $itemId,
        string $eventType,
        ?int $sampleNumber = null,
        ?string $beforeValue = null,
        ?string $afterValue = null,
        ?string $ruleApplied = null,
        ?string $justification = null
    ): self {
        $user = auth()->user();

        return self::create([
            'item_id' => $itemId,
            'event_type' => $eventType,
            'sample_number' => $sampleNumber,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'rule_applied' => $ruleApplied,
            'justification' => $justification,
            'usuario_id' => $user?->id,
            'usuario_nome' => $user?->name ?? 'Sistema',
        ]);
    }
}
