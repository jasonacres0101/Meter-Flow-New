<?php

namespace App\Repositories;

use App\Models\Machine;

class MachineRepository
{
    public function findBySerialNumber(?string $serialNumber): ?Machine
    {
        if (! $serialNumber) {
            return null;
        }

        return Machine::query()
            ->whereRaw('lower(serial_number) = ?', [mb_strtolower(trim($serialNumber))])
            ->first();
    }
}
