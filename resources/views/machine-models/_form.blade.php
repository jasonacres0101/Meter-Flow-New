@csrf
<div class="grid gap-4 md:grid-cols-2">
    @if(auth()->user()->isPlatformAdmin())
        <div class="rounded-lg bg-teal-50 p-4 text-sm font-medium text-teal-800 md:col-span-2">This will be saved as a platform master model that every company can use when adding machines.</div>
    @endif
    <label class="app-field">Manufacturer
        <select name="manufacturer_id" class="app-field-control">
            <option value="">Add new manufacturer below</option>
            @foreach($manufacturers as $manufacturer)
                <option value="{{ $manufacturer->id }}" @selected(old('manufacturer_id', $machineModel->manufacturer_id ?? '') == $manufacturer->id)>{{ $manufacturer->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="app-field">New manufacturer
        <input name="manufacturer_name" value="{{ old('manufacturer_name') }}" class="app-field-control" placeholder="Only use if the manufacturer is not listed">
    </label>
    <label class="app-field">Model name<input name="model_name" value="{{ old('model_name', $machineModel->model_name ?? '') }}" class="app-field-control"></label>
    @if(auth()->user()->isPlatformAdmin())
        <label class="app-field">Parser type<select name="parser_type" class="app-field-control">@foreach($parserTypes as $parser => $label)<option value="{{ $parser }}" @selected(old('parser_type', $machineModel->parser_type ?? '') === $parser)>{{ $label }} / {{ $parser }}</option>@endforeach</select></label>
    @else
        <input type="hidden" name="parser_type" value="{{ old('parser_type', $machineModel->parser_type ?? 'generic_counter_email') }}">
    @endif
    <label class="app-field md:col-span-2">Notes<textarea name="notes" class="app-field-control">{{ old('notes', $machineModel->notes ?? '') }}</textarea></label>
</div>
@if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<button class="app-button mt-6">Save model</button>
