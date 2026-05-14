<x-layouts.app title="Login">
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[1fr_0.9fr]">
        <section class="bg-gradient-to-br from-slate-950 via-slate-900 to-teal-950 p-8 text-white sm:p-10">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-teal-300 to-cyan-400 text-lg font-black text-slate-950 shadow-lg shadow-teal-950/30">CM</span>
                <div><div class="text-lg font-black">Copier Monitor</div><div class="text-sm font-semibold text-slate-400">Managed print intelligence</div></div>
            </div>
            <h1 class="mt-10 text-4xl font-black tracking-normal">Welcome back to your operations console.</h1>
            <p class="mt-4 text-sm leading-6 text-slate-300">Track device reports, usage trends, parser failures and consumables across every company from a single secure SaaS workspace.</p>
            <div class="mt-8 grid gap-3 text-sm sm:grid-cols-3 lg:grid-cols-1">
                <div class="rounded-lg border border-white/10 bg-white/10 p-3"><span class="block text-xs font-black uppercase tracking-wide text-teal-200">Monitor</span><span class="mt-1 block font-semibold text-white">Daily copier reports</span></div>
                <div class="rounded-lg border border-white/10 bg-white/10 p-3"><span class="block text-xs font-black uppercase tracking-wide text-teal-200">Support</span><span class="mt-1 block font-semibold text-white">Engineer ticket workflows</span></div>
                <div class="rounded-lg border border-white/10 bg-white/10 p-3"><span class="block text-xs font-black uppercase tracking-wide text-teal-200">Billing</span><span class="mt-1 block font-semibold text-white">Usage and service revenue</span></div>
            </div>
        </section>
        <section class="p-8 sm:p-10">
            <div class="mb-8">
                <div class="text-sm font-bold uppercase tracking-wide text-teal-700">Secure login</div>
                <h2 class="mt-2 text-3xl font-black text-slate-950">Sign in</h2>
            </div>
            <form method="post" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <label class="app-field">Email<input name="email" value="{{ old('email') }}" autofocus class="app-field-control"></label>
                <label class="app-field">Password<input name="password" type="password" class="app-field-control"></label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-zinc-300"> Remember me</label>
                @if ($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>@endif
                <button class="app-button w-full">Login</button>
            </form>
        </section>
    </div>
</x-layouts.app>
