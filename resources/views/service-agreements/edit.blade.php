<x-layouts.app title="Edit Service Agreement">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Commercial agreements</div>
            <h1 class="mt-1 text-2xl font-black text-slate-950">Edit {{ $agreement->agreement_number }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Update agreement terms or change which machines are covered.</p>
        </div>
        <a href="{{ route('service-agreements.show', $agreement) }}" class="app-button-secondary">Back to agreement</a>
    </div>

    @include('service-agreements.partials.form', [
        'action' => route('service-agreements.update', $agreement),
        'method' => 'PUT',
        'selectedMachineIds' => collect(old('machine_ids', $agreement->machines->pluck('id')->all()))->map(fn ($id) => (int) $id),
    ])
</x-layouts.app>
