<x-layouts.app title="AI Settings">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="app-page-title">AI Settings</h1>
            <p class="mt-1 text-sm text-slate-500">OpenAI settings used by the SaaS admin parser queue to suggest report template mappings.</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_0.75fr]">
        <form method="post" action="{{ route('platform-ai-settings.update') }}" class="app-panel rounded-xl p-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <label class="app-field md:col-span-2">OpenAI API key
                    <input name="api_key" type="password" class="app-field-control" placeholder="{{ $setting?->api_key ? 'Leave blank to keep existing key' : 'sk-...' }}">
                </label>

                <label class="app-field">Parser model
                    <input name="model" value="{{ old('model', $setting->model ?? config('services.openai.model', 'gpt-4.1-mini')) }}" class="app-field-control">
                </label>

                <label class="app-field">API base URL
                    <input name="base_url" value="{{ old('base_url', $setting->base_url ?? config('services.openai.base_url', 'https://api.openai.com/v1')) }}" class="app-field-control">
                </label>

                <label class="app-field">Timeout seconds
                    <input name="timeout" type="number" min="5" max="120" value="{{ old('timeout', $setting->timeout ?? config('services.openai.timeout', 30)) }}" class="app-field-control">
                </label>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $setting->is_active ?? true))>
                Active
            </label>

            @if ($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>@endif
            <button class="app-button mt-5">Save AI settings</button>
        </form>

        <aside class="space-y-5">
            <div class="app-panel rounded-xl p-5">
                <h2 class="text-base font-black">Test AI mapping</h2>
                <p class="mt-1 text-sm text-slate-500">Runs a small parser suggestion against a sample toner report and stores the latest result status.</p>
                <form method="post" action="{{ route('platform-ai-settings.test') }}" class="mt-4">
                    @csrf
                    <button class="app-button-secondary">Run AI test</button>
                </form>
            </div>

            <div class="app-panel rounded-xl p-5">
                <h2 class="text-base font-black">Status</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-500">Source</dt><dd>{{ $setting?->isReady() ? 'Saved platform setting' : ($envConfigured ? '.env fallback' : 'Not configured') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Model</dt><dd>{{ $setting?->model ?? config('services.openai.model', 'gpt-4.1-mini') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Last tested</dt><dd>{{ $setting?->last_tested_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Last success</dt><dd>{{ $setting?->last_success_at?->format('d M Y H:i') ?? 'Never' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">State</dt><dd>{{ $setting?->is_active ? 'Active' : 'Inactive' }}</dd></div>
                </dl>
                @if($setting?->last_error)<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $setting->last_error }}</div>@endif
            </div>

            <div class="app-panel rounded-xl p-5">
                <h2 class="text-base font-black">Help</h2>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <p>Create an API key in your OpenAI account, paste it here, and keep the model as <span class="font-mono text-slate-900">gpt-4.1-mini</span> unless you want to use a different parser model.</p>
                    <p>The key is encrypted in the database. AI suggestions are only used in the SaaS admin parser queue, and an admin still approves the mapping before it becomes active.</p>
                    <p>If the saved setting is inactive or incomplete, the app falls back to <span class="font-mono text-slate-900">OPENAI_API_KEY</span> from the server environment.</p>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
