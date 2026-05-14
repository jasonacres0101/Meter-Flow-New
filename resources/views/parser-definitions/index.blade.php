<x-layouts.app title="Parser Library">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="app-page-title">Parser Library</h1>
            <p class="mt-1 text-sm text-slate-500">Create reusable parser profiles for master makes, models and templates.</p>
        </div>
        <a href="{{ route('parser-definitions.create') }}" class="app-button">Add parser profile</a>
    </div>

    <div class="app-panel app-table-wrap">
        <table class="app-table">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr><th>Name</th><th>Key</th><th>Engine</th><th>Status</th></tr>
            </thead>
            <tbody>
            @foreach($parserDefinitions as $definition)
                <tr>
                    <td><a class="font-bold text-slate-950 hover:text-teal-700" href="{{ route('parser-definitions.show', $definition) }}">{{ $definition->name }}</a><div class="text-xs text-slate-500">{{ $definition->is_system ? 'System profile' : 'Custom profile' }}</div></td>
                    <td class="font-mono text-xs">{{ $definition->parser_key }}</td>
                    <td>{{ \App\Models\ParserDefinition::engines()[$definition->engine_type] ?? $definition->engine_type }}</td>
                    <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $definition->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $definition->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $parserDefinitions->links() }}</div>
</x-layouts.app>
