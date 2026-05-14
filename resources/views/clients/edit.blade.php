<x-layouts.app :title="'Edit '.$client->name">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Edit Client</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $client->name }}</p>
        </div>
        <a href="{{ route('clients.show', $client) }}" class="app-button-secondary">View client</a>
    </div>

    <form method="post" action="{{ route('clients.update', $client) }}">
        @method('PUT')
        @include('clients._form')
    </form>
</x-layouts.app>
