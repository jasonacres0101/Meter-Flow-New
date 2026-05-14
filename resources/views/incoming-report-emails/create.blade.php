<x-layouts.app title="Add Incoming Email">
    <h1 class="app-page-title mb-6">Add Incoming Email</h1>
    <form method="post" action="{{ route('incoming-report-emails.store') }}" class="app-panel rounded-xl p-5">
        @csrf
        <div class="grid gap-4">
            <label class="app-field">From<input name="from_email" value="{{ old('from_email') }}" class="app-field-control"></label>
            <label class="app-field">To<input name="to_email" value="{{ old('to_email') }}" class="app-field-control"></label>
            <label class="app-field">Subject<input name="subject" value="{{ old('subject', 'MX-2630N Status Message') }}" class="app-field-control"></label>
            <label class="app-field">Body<textarea name="body_text" class="app-field-control h-96">{{ old('body_text') }}</textarea></label>
        </div>
        @if ($errors->any())<div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <button class="app-button mt-6">Store and parse</button>
    </form>
</x-layouts.app>
