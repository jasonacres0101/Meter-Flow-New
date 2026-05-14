<x-layouts.app :title="$parserDefinition->name">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="app-page-title">{{ $parserDefinition->name }}</h1>
            <p class="mt-1 font-mono text-sm text-slate-500">{{ $parserDefinition->parser_key }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('parser-definitions.edit', $parserDefinition) }}" class="app-button-secondary">Edit</a>
            @unless($parserDefinition->is_system)
                <form method="post" action="{{ route('parser-definitions.destroy', $parserDefinition) }}">
                    @csrf
                    @method('DELETE')
                    <button class="app-button-secondary">Delete</button>
                </form>
            @endunless
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <x-stat label="Engine" :value="\App\Models\ParserDefinition::engines()[$parserDefinition->engine_type] ?? $parserDefinition->engine_type" />
        <x-stat label="Status" :value="$parserDefinition->is_active ? 'Active' : 'Inactive'" />
        <x-stat label="Type" :value="$parserDefinition->is_system ? 'System' : 'Custom'" />
    </div>

    <section class="app-panel mt-6 rounded-xl p-5">
        <h2 class="text-base font-black">Default configuration</h2>
        <pre class="mt-4 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-white">{{ json_encode($parserDefinition->default_configuration ?? [], JSON_PRETTY_PRINT) }}</pre>
        @if($parserDefinition->notes)
            <div class="mt-4 text-sm text-slate-600">{{ $parserDefinition->notes }}</div>
        @endif
    </section>
</x-layouts.app>
