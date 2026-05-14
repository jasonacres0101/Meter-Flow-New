<x-layouts.app title="Add Template">
    <div class="mb-6">
        <h1 class="app-page-title">{{ isset($sourceEmail) && $sourceEmail ? 'Create Template From Email' : 'Add Template' }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ isset($sourceEmail) && $sourceEmail ? 'Review the detected labels, confirm the parser configuration, then save this as the reusable template for the matched make and model.' : 'Create a reusable sample report format for a machine model.' }}</p>
    </div>

    <form method="post" action="{{ route('report-templates.store') }}">
        @include('report-templates._form')
    </form>
</x-layouts.app>
