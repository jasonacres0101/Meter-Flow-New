<?php

namespace App\Http\Controllers;

use App\Models\ParserDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParserDefinitionController extends Controller
{
    public function index(): View
    {
        return view('parser-definitions.index', [
            'parserDefinitions' => ParserDefinition::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('parser-definitions.create', [
            'engines' => ParserDefinition::engines(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ParserDefinition::create($this->validated($request));

        return redirect()->route('parser-definitions.index')->with('status', 'Parser profile created.');
    }

    public function show(ParserDefinition $parserDefinition): View
    {
        return view('parser-definitions.show', ['parserDefinition' => $parserDefinition]);
    }

    public function edit(ParserDefinition $parserDefinition): View
    {
        return view('parser-definitions.edit', [
            'parserDefinition' => $parserDefinition,
            'engines' => ParserDefinition::engines(),
        ]);
    }

    public function update(Request $request, ParserDefinition $parserDefinition): RedirectResponse
    {
        $parserDefinition->update($this->validated($request, $parserDefinition));

        return redirect()->route('parser-definitions.show', $parserDefinition)->with('status', 'Parser profile updated.');
    }

    public function destroy(ParserDefinition $parserDefinition): RedirectResponse
    {
        abort_if($parserDefinition->is_system, 422, 'System parser profiles cannot be deleted.');

        $parserDefinition->delete();

        return redirect()->route('parser-definitions.index')->with('status', 'Parser profile deleted.');
    }

    private function validated(Request $request, ?ParserDefinition $parserDefinition = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parser_key' => ['required', 'string', 'max:255', Rule::unique('parser_definitions', 'parser_key')->ignore($parserDefinition)],
            'engine_type' => ['required', Rule::in(array_keys(ParserDefinition::engines()))],
            'default_configuration' => ['nullable', 'json'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['parser_key'] = ParserDefinition::normaliseKey($data['parser_key']);
        $data['default_configuration'] = filled($data['default_configuration'] ?? null) ? json_decode($data['default_configuration'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_system'] = (bool) ($parserDefinition?->is_system ?? false);

        return $data;
    }
}
