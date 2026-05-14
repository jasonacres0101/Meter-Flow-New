<x-layouts.app :title="'Edit '.$parserDefinition->name">
    <div class="mb-6">
        <h1 class="app-page-title">Edit Parser Profile</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $parserDefinition->parser_key }}</p>
    </div>
    <div class="grid gap-5 lg:grid-cols-[1fr_0.75fr]">
        <form method="post" action="{{ route('parser-definitions.update', $parserDefinition) }}" class="app-panel rounded-xl p-5">
            @method('PUT')
            @include('parser-definitions._form')
        </form>
        @include('parser-definitions._help')
    </div>
</x-layouts.app>
