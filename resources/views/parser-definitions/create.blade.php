<x-layouts.app title="Add Parser Profile">
    <div class="mb-6">
        <h1 class="app-page-title">Add Parser Profile</h1>
        <p class="mt-1 text-sm text-slate-500">Add a SaaS-admin parser option without writing code from the browser.</p>
    </div>
    <div class="grid gap-5 lg:grid-cols-[1fr_0.75fr]">
        <form method="post" action="{{ route('parser-definitions.store') }}" class="app-panel rounded-xl p-5">
            @include('parser-definitions._form')
        </form>
        @include('parser-definitions._help')
    </div>
</x-layouts.app>
