<x-layouts.app title="Add Stock Product">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Add Stock Product</h1>
            <p class="mt-1 text-sm text-slate-500">Create a stock item and link it to all compatible machine models.</p>
        </div>
        <a href="{{ route('stock.index') }}" class="app-button-secondary">Back to stock</a>
    </div>

    <form method="post" action="{{ route('stock.store') }}">
        @include('stock._form')
    </form>
</x-layouts.app>
