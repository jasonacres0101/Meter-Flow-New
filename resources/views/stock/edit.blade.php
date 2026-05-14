<x-layouts.app :title="'Edit '.$product->name">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="app-page-title">Edit Stock Product</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $product->name }}</p>
        </div>
        <a href="{{ route('stock.show', $product) }}" class="app-button-secondary">View product</a>
    </div>

    <form method="post" action="{{ route('stock.update', $product) }}">
        @method('PUT')
        @include('stock._form')
    </form>
</x-layouts.app>
