@csrf
<div class="grid gap-5 lg:grid-cols-[1fr_0.75fr]">
    <section class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Product Details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="app-field md:col-span-2">Product
                <input name="name" value="{{ old('name', $product->name ?? '') }}" class="app-field-control" placeholder="Sharp MX-61GTBA black toner" required>
            </label>
            <label class="app-field">Type
                <select name="type" class="app-field-control">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $product->type ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @unless(isset($product->id))
                <label class="app-field">Initial company qty
                    <input name="quantity" type="number" min="0" step="1" value="{{ old('quantity', 0) }}" class="app-field-control">
                </label>
            @endunless
            <label class="app-field md:col-span-2">Supplier
                <input name="supplier" value="{{ old('supplier', $product->supplier ?? '') }}" class="app-field-control" placeholder="Supplier name">
            </label>
            <label class="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
                Active product
            </label>
            <label class="app-field md:col-span-2">Notes
                <textarea name="notes" class="app-field-control h-28">{{ old('notes', $product->notes ?? '') }}</textarea>
            </label>
        </div>
    </section>

    <aside class="app-panel rounded-xl p-5">
        <h2 class="text-lg font-black text-slate-950">Compatible Models</h2>
        <p class="mt-1 text-sm text-slate-500">Select every copier/printer model this product can be used with. Leave empty for generic stock such as paper.</p>
        <label class="app-field mt-4">Machine models
            @php($selectedModels = collect(old('machine_model_ids', $product->machineModels?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all())
            <select name="machine_model_ids[]" multiple class="app-field-control h-72">
                @foreach($machineModels as $model)
                    <option value="{{ $model->id }}" @selected(in_array($model->id, $selectedModels, true))>
                        {{ $model->manufacturer }} {{ $model->model_name }}{{ is_null($model->company_id) ? ' (prebuilt)' : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif
        <button class="app-button mt-5 w-full">{{ isset($product->id) ? 'Save product' : 'Create product' }}</button>
    </aside>
</div>
