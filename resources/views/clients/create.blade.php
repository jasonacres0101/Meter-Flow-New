<x-layouts.app title="Add Client">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Add Client</h1>
            <p class="mt-1 text-sm text-slate-500">Create a customer record before adding sites and machines.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="app-button-secondary">Back to clients</a>
    </div>

    <form method="post" action="{{ route('clients.store') }}">
        @include('clients._form')
    </form>
</x-layouts.app>
