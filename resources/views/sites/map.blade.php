<x-layouts.app title="Site Map">
    @php
        $totalMachines = $sites->sum(fn ($site) => $site->machines->count());
        $totalClients = $sites->pluck('client_id')->unique()->count();
        $activeMachines = $sites->sum(fn ($site) => $site->machines->where('is_active', true)->count());
    @endphp

    <section class="service-panel mb-6">
        <div class="service-header-solid">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-teal-100">Estate overview</span>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200">{{ $sites->count() }} mapped</span>
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-normal">Site Map</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">View every client site on a map, then click a marker or site card to inspect the customer and machines installed there.</p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <a href="{{ route('sites.index') }}" class="inline-flex items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/15">Site list</a>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 md:grid-cols-4">
            <div class="service-metric"><div class="service-label">Mapped sites</div><div class="service-value">{{ $sites->count() }}</div></div>
            <div class="service-metric"><div class="service-label">Clients</div><div class="service-value">{{ $totalClients }}</div></div>
            <div class="service-metric"><div class="service-label">Machines</div><div class="service-value">{{ $totalMachines }}</div></div>
            <div class="service-metric"><div class="service-label">Active machines</div><div class="service-value">{{ $activeMachines }}</div></div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-teal-950 px-5 py-5 text-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-black uppercase tracking-wide text-teal-200">Geographic estate</div>
                    <h2 class="mt-1 text-xl font-black text-white">Mapped Sites</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">Select a location to see the client, address and installed machine list.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-200 xl:grid-cols-[1.35fr_0.65fr]">
            <div class="bg-white p-5">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm shadow-slate-100">
                    <div id="site-map" class="h-[34rem] w-full"></div>
                </div>
            </div>

            <aside class="bg-white p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">Site directory</div>
                            <h3 class="mt-1 text-base font-black text-slate-950">Sites</h3>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700 ring-1 ring-slate-200">{{ $sites->count() }} mapped</span>
                    </div>

                    <div class="mt-4 max-h-[18rem] space-y-2 overflow-y-auto pr-1">
                        @forelse($sites as $site)
                            <button type="button" data-site-id="{{ $site->id }}" class="site-map-list-item w-full rounded-xl border border-slate-200 bg-white p-3 text-left shadow-sm shadow-slate-100 transition hover:border-teal-300 hover:bg-teal-50">
                                <div class="font-black text-slate-950">{{ $site->name }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">{{ $site->client->name }} / {{ $site->machines->count() }} machines</div>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">No mapped sites yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-100">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-500">Selected site</div>
                    <div id="site-map-detail" class="mt-3 text-sm text-slate-600">Select a site marker or list item to view client and machine details.</div>
                </div>
            </aside>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sites = {{ Illuminate\Support\Js::from($mapSites) }};
            const mapElement = document.querySelector('#site-map');
            const detailElement = document.querySelector('#site-map-detail');

            if (!sites.length) {
                mapElement.innerHTML = '<div class="flex h-full items-center justify-center p-8 text-center text-sm font-semibold text-slate-500">No mapped sites yet. Add latitude and longitude to sites to show them here.</div>';
                return;
            }

            const map = L.map(mapElement, { scrollWheelZoom: false });
            const bounds = [];
            const markers = new Map();
            const markerIcon = L.divIcon({
                className: '',
                html: '<span class="block h-5 w-5 rounded-full border-4 border-white bg-teal-500 shadow-lg shadow-slate-900/30"></span>',
                iconSize: [20, 20],
                iconAnchor: [10, 10],
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const selectSite = (site) => {
                detailElement.innerHTML = `
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-lg font-black text-slate-950">${site.name}</div>
                        <a href="${site.client_url}" class="mt-1 block font-bold text-teal-700 hover:underline">${site.client}</a>
                        <div class="mt-2 text-xs font-semibold text-slate-500">${site.address || 'No address recorded'}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="${site.url}" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-800 transition hover:border-teal-300 hover:text-teal-700">View site</a>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="mb-2 font-black text-slate-950">Machines</div>
                        <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            ${site.machines.map(machine => `
                                <a href="${machine.url}" class="block rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm shadow-slate-100 transition hover:border-teal-300">
                                    <span class="font-bold text-slate-900">${machine.name || machine.serial}</span>
                                    <span class="mt-1 block text-xs font-semibold text-slate-500">${machine.model} / ${machine.serial} / ${machine.status}</span>
                                </a>
                            `).join('') || '<div class="rounded-xl border border-dashed border-slate-300 bg-white p-3 text-slate-500">No machines at this site.</div>'}
                        </div>
                    </div>
                `;
                markers.get(site.id)?.openPopup();
                map.setView([site.latitude, site.longitude], Math.max(map.getZoom(), 9), { animate: true });
                document.querySelectorAll('.site-map-list-item').forEach(item => {
                    const selected = Number(item.dataset.siteId) === site.id;
                    item.classList.toggle('border-teal-500', selected);
                    item.classList.toggle('bg-teal-50', selected);
                });
            };

            sites.forEach((site) => {
                const position = [site.latitude, site.longitude];
                bounds.push(position);
                const marker = L.marker(position, { icon: markerIcon }).addTo(map);
                marker.bindPopup(`<strong>${site.name}</strong><br>${site.client}<br>${site.machines_count} machines`);
                marker.on('click', () => selectSite(site));
                markers.set(site.id, marker);
            });

            map.fitBounds(bounds, { padding: [35, 35], maxZoom: 9 });
            selectSite(sites[0]);

            document.querySelectorAll('.site-map-list-item').forEach((button) => {
                button.addEventListener('click', () => {
                    const site = sites.find(item => item.id === Number(button.dataset.siteId));
                    if (site) {
                        selectSite(site);
                    }
                });
            });
        });
    </script>
</x-layouts.app>
