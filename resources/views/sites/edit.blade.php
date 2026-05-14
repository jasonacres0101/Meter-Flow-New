<x-layouts.app :title="'Edit '.$site->name">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Edit Site</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $site->client->name }} / {{ $site->name }}</p>
        </div>
        <a href="{{ route('sites.show', $site) }}" class="app-button-secondary">View site</a>
    </div>

    <form method="post" action="{{ route('sites.update', $site) }}">
        @method('PUT')
        @include('sites._form')
    </form>
</x-layouts.app>
