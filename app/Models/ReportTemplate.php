<?php

namespace App\Models;

use Database\Factories\ReportTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    /** @use HasFactory<ReportTemplateFactory> */
    use HasFactory;

    public const STATUS_COMPANY = 'company';

    public const STATUS_PENDING_GLOBAL_REVIEW = 'pending_global_review';

    public const STATUS_APPROVED_GLOBAL = 'approved_global';

    protected $fillable = [
        'machine_model_id',
        'company_id',
        'template_name',
        'family_key',
        'version',
        'sample_subject',
        'sample_body',
        'parser_type',
        'parser_configuration',
        'is_active',
        'approval_status',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'parser_configuration' => 'array',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function machineModel(): BelongsTo
    {
        return $this->belongsTo(MachineModel::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function displayName(): string
    {
        return $this->template_name.' v'.$this->version;
    }
}
