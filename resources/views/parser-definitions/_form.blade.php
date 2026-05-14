@csrf
<div class="grid gap-4 md:grid-cols-2">
    <label class="app-field">Parser name
        <input name="name" value="{{ old('name', $parserDefinition->name ?? '') }}" class="app-field-control" placeholder="Ricoh status email">
    </label>
    <label class="app-field">Parser key
        <input name="parser_key" value="{{ old('parser_key', $parserDefinition->parser_key ?? '') }}" class="app-field-control" placeholder="ricoh_status_email">
    </label>
    <label class="app-field">Built-in engine
        <select name="engine_type" class="app-field-control">
            @foreach($engines as $engine => $label)
                <option value="{{ $engine }}" @selected(old('engine_type', $parserDefinition->engine_type ?? '') === $engine)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 md:self-end">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $parserDefinition->is_active ?? true))>
        Active
    </label>
    <label class="app-field md:col-span-2">Default parser configuration JSON
        <textarea name="default_configuration" class="app-field-control h-32">{{ old('default_configuration', isset($parserDefinition) ? json_encode($parserDefinition->default_configuration, JSON_PRETTY_PRINT) : '{}') }}</textarea>
    </label>
    <label class="app-field md:col-span-2">Notes
        <textarea name="notes" class="app-field-control h-24">{{ old('notes', $parserDefinition->notes ?? '') }}</textarea>
    </label>
</div>
@if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<button class="app-button mt-6">Save parser profile</button>
