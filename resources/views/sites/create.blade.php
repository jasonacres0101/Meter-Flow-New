<x-layouts.app title="Add Site">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Add Site</h1>
            <p class="mt-1 text-sm text-slate-500">Create a customer location before adding machines.</p>
        </div>
        <a href="{{ route('sites.index') }}" class="app-button-secondary">Back to sites</a>
    </div>

    <form method="post" action="{{ route('sites.store') }}">
        @include('sites._form')
    </form>
</x-layouts.app>
