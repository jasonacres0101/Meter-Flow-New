<?php

namespace App\Models;

use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Manufacturer extends Model
{
    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function machineModels(): HasMany
    {
        return $this->hasMany(MachineModel::class);
    }

    public static function findOrCreateByName(string $name): self
    {
        $cleanName = trim($name);

        return self::firstOrCreate(
            ['slug' => Str::slug($cleanName)],
            ['name' => $cleanName, 'is_active' => true],
        );
    }
}
