<x-layouts.app title="Add Service Agreement">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Commercial agreements</div>
            <h1 class="mt-1 text-2xl font-black text-slate-950">Add Service Agreement</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Set the agreement terms, then choose every machine covered by this agreement.</p>
        </div>
        <a href="{{ route('service-agreements.index') }}" class="app-button-secondary">Back to agreements</a>
    </div>

    @include('service-agreements.partials.form', [
        'action' => route('service-agreements.store'),
        'method' => 'POST',
        'agreement' => null,
        'selectedMachineIds' => collect(old('machine_ids', []))->map(fn ($id) => (int) $id),
    ])
</x-layouts.app>
