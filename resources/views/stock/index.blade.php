<x-layouts.app title="Stock">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="app-page-title">Stock</h1>
            <p class="mt-1 text-sm text-slate-500">Browse company stock products and open a product to manage quantities, sites and compatible machines.</p>
        </div>
        <a href="{{ route('stock.create') }}" class="app-button">Add product</a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <x-stat label="Products" :value="$products->total()" tone="slate" />
        <x-stat label="Company stock" :value="number_format($products->getCollection()->sum(fn ($product) => (int) ($product->balances->firstWhere('site_id', null)?->quantity ?? 0)))" tone="teal" />
        <x-stat label="Stock at sites" :value="number_format($products->getCollection()->sum(fn ($product) => (int) $product->balances->whereNotNull('site_id')->sum('quantity')))" tone="blue" />
    </div>

    <div class="app-panel app-table-wrap mt-5">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Models</th>
                    <th>Supplier</th>
                    <th>Company qty</th>
                    <th>Site qty</th>
                    <th>Movements</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $companyQty = (int) ($product->balances->firstWhere('site_id', null)?->quantity ?? 0);
                        $siteQty = (int) $product->balances->whereNotNull('site_id')->sum('quantity');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('stock.show', $product) }}" class="font-bold text-slate-950 hover:text-teal-700">{{ $product->name }}</a>
                            @if($product->notes)<div class="text-xs text-slate-500">{{ $product->notes }}</div>@endif
                        </td>
                        <td><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $types[$product->type] ?? $product->type }}</span></td>
                        <td>
                            @if($product->machineModels->isNotEmpty())
                                <span class="text-sm font-semibold text-slate-700">{{ $product->machineModels->count() }} linked</span>
                            @else
                                <span class="text-sm text-slate-500">Any model</span>
                            @endif
                        </td>
                        <td>{{ $product->supplier ?: '-' }}</td>
                        <td class="font-black text-slate-950">{{ $companyQty }}</td>
                        <td class="font-black text-teal-700">{{ $siteQty }}</td>
                        <td>{{ $product->movements_count }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('stock.show', $product) }}" class="app-button-secondary">View</a>
                                <a href="{{ route('stock.edit', $product) }}" class="app-button-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-sm text-slate-500">No stock products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</x-layouts.app>
