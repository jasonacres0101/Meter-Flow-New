<x-layouts.app :title="$product->name">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">{{ $types[$product->type] ?? $product->type }}</div>
            <h1 class="app-page-title mt-1">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $product->supplier ?: 'No supplier set' }} / {{ $product->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('stock.edit', $product) }}" class="app-button-secondary">Edit product</a>
            <a href="{{ route('stock.index') }}" class="app-button-secondary">Back to stock</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <x-stat label="Company stock" :value="$companyQuantity" tone="teal" />
        <x-stat label="Stock at sites" :value="$siteBalances->sum('quantity')" tone="blue" />
        <x-stat label="Compatible models" :value="$product->machineModels->count() ?: 'Any'" tone="slate" />
        <x-stat label="Compatible machines" :value="$compatibleMachines->count()" tone="amber" />
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-[1fr_0.85fr]">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Where This Stock Is Held</h2>
            <div class="mt-4 app-table-wrap">
                <table class="app-table">
                    <thead><tr><th>Location</th><th>Client</th><th>Quantity</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="font-bold text-slate-950">Company stock</td>
                            <td>Central store</td>
                            <td class="font-black text-teal-700">{{ $companyQuantity }}</td>
                        </tr>
                        @foreach($siteBalances as $balance)
                            <tr>
                                <td class="font-bold text-slate-950">{{ $balance->site?->name }}</td>
                                <td>{{ $balance->site?->client?->name }}</td>
                                <td class="font-black text-blue-700">{{ $balance->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-5">
            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Add Company Stock</h2>
                <form method="post" action="{{ route('stock.add') }}" class="mt-4 grid gap-3">
                    @csrf
                    <input type="hidden" name="stock_product_id" value="{{ $product->id }}">
                    <label class="app-field">Quantity
                        <input name="quantity" type="number" min="1" step="1" class="app-field-control" required>
                    </label>
                    <label class="app-field">Notes
                        <input name="notes" class="app-field-control" placeholder="Purchase order, supplier delivery, etc.">
                    </label>
                    <button class="app-button-secondary">Add stock</button>
                </form>
            </section>

            <section class="app-panel rounded-xl p-5">
                <h2 class="text-lg font-black text-slate-950">Move Stock To Site</h2>
                <form method="post" action="{{ route('stock.transfer') }}" class="mt-4 grid gap-3">
                    @csrf
                    <input type="hidden" name="stock_product_id" value="{{ $product->id }}">
                    <label class="app-field">Client site
                        <select name="site_id" class="app-field-control" required>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->client->name }} / {{ $site->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="app-field">Quantity
                        <input name="quantity" type="number" min="1" max="{{ $companyQuantity }}" step="1" class="app-field-control" required>
                    </label>
                    <label class="app-field">Notes
                        <input name="notes" class="app-field-control" placeholder="Moved for install, repair visit, etc.">
                    </label>
                    <button class="app-button">Move to site</button>
                </form>
            </section>
        </div>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Compatible Models</h2>
            <div class="mt-4 grid gap-2">
                @forelse($product->machineModels as $model)
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="font-black text-slate-950">{{ $model->manufacturer }} {{ $model->model_name }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $model->machines->count() }} machines using this model</div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No specific model links. Treat this as generic stock.</p>
                @endforelse
            </div>
        </section>

        <section class="app-panel rounded-xl p-5">
            <h2 class="text-lg font-black text-slate-950">Machines Using Compatible Models</h2>
            <div class="mt-4 app-table-wrap">
                <table class="app-table">
                    <thead><tr><th>Machine</th><th>Client</th><th>Site</th></tr></thead>
                    <tbody>
                        @forelse($compatibleMachines as $machine)
                            <tr>
                                <td><a href="{{ route('machines.show', $machine) }}" class="font-bold text-slate-950 hover:text-teal-700">{{ $machine->machine_name ?: $machine->serial_number }}</a><div class="text-xs text-slate-500">{{ $machine->manufacturer }} {{ $machine->model }}</div></td>
                                <td>{{ $machine->client?->name }}</td>
                                <td>{{ $machine->site?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-500">No machines are using the linked models yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="app-panel mt-6 rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Movement History</h2>
        <div class="mt-4 app-table-wrap">
            <table class="app-table">
                <thead><tr><th>Date</th><th>Movement</th><th>Qty</th><th>Destination</th><th>User</th><th>Notes</th></tr></thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                            <td>{{ str_replace('_', ' ', $movement->movement_type) }}</td>
                            <td class="font-black">{{ $movement->quantity }}</td>
                            <td>{{ $movement->toSite ? $movement->toSite->client->name.' / '.$movement->toSite->name : 'Company stock' }}</td>
                            <td>{{ $movement->createdBy?->name ?? '-' }}</td>
                            <td>{{ $movement->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-sm text-slate-500">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
