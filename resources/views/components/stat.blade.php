@props(['label', 'value', 'tone' => 'teal'])
@php
    $tones = [
        'teal' => 'from-teal-500 to-cyan-500 text-teal-700 bg-teal-50',
        'blue' => 'from-blue-500 to-indigo-500 text-blue-700 bg-blue-50',
        'amber' => 'from-amber-400 to-orange-500 text-amber-700 bg-amber-50',
        'rose' => 'from-rose-500 to-pink-500 text-rose-700 bg-rose-50',
        'slate' => 'from-slate-600 to-slate-900 text-slate-700 bg-slate-100',
    ];
    $toneClass = $tones[$tone] ?? $tones['teal'];
@endphp
<div class="service-card-white min-w-0 shadow-sm shadow-slate-100">
    <div class="flex items-center justify-between gap-3">
        <div class="service-label">{{ $label }}</div>
        <div class="h-2.5 w-10 shrink-0 rounded-full bg-gradient-to-r {{ explode(' ', $toneClass)[0] }} {{ explode(' ', $toneClass)[1] }}"></div>
    </div>
    <div class="mt-2 break-words text-2xl font-black tracking-normal text-slate-950">{{ $value }}</div>
    <div class="mt-3 h-1 rounded-full {{ explode(' ', $toneClass)[3] }}">
        <div class="h-1 w-2/3 rounded-full bg-gradient-to-r {{ explode(' ', $toneClass)[0] }} {{ explode(' ', $toneClass)[1] }}"></div>
    </div>
</div>
