<?php

namespace App\Support;

use App\Models\ParserDefinition;

class ParserRegistry
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = ParserDefinition::builtInDefinitions();

        if (self::tableExists()) {
            ParserDefinition::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->each(function (ParserDefinition $definition) use (&$options): void {
                    $options[$definition->parser_key] = $definition->name;
                });
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    private static function tableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('parser_definitions');
        } catch (\Throwable) {
            return false;
        }
    }
}
