<?php

namespace App\Models;

use Database\Factories\MachineCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineCredential extends Model
{
    /** @use HasFactory<MachineCredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'created_by_user_id',
        'label',
        'username',
        'password',
        'url',
        'notes',
        'last_rotated_at',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'notes' => 'encrypted',
            'last_rotated_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
