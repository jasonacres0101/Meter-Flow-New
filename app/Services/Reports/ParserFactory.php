<?php

namespace App\Services\Reports;

use App\Models\ParserDefinition;
use App\Services\Reports\Parsers\GenericCounterEmailParser;
use App\Services\Reports\Parsers\MachineReportParser;
use App\Services\Reports\Parsers\SharpMxStatusEmailParser;
use InvalidArgumentException;

class ParserFactory
{
    public function make(string $parserType): MachineReportParser
    {
        $engineType = ParserDefinition::findActiveByKey($parserType)?->engine_type ?? $parserType;

        return match ($engineType) {
            'sharp_mx_status_email' => app(SharpMxStatusEmailParser::class),
            'generic_counter_email' => app(GenericCounterEmailParser::class),
            default => throw new InvalidArgumentException("Unsupported parser type [{$parserType}]."),
        };
    }
}
