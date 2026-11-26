<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Administrator dashboard') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Prehľad žiadostí o prenájom bicykla.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="text-sm underline">{{ __('Manage your profile') }}</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="rentalApplications(@js(route('api.v1.admin.rental-applications.index')))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card title="Žiadosti o prenájom" description="Vyberte stav žiadostí, ktoré chcete zobraziť.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="application_status" value="Stav žiadosti" />
                        <select id="application_status" x-model="status" @change="load()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="submitted">Nové</option>
                            <option value="">Všetky</option>
                            <option value="approved">Schválené</option>
                            <option value="rejected">Zamietnuté</option>
                            <option value="cancelled">Zrušené</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="application_per_page" value="Záznamov na stranu" />
                        <select id="application_per_page" x-model.number="perPage" @change="load()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </x-card>

            <noscript><p class="text-sm text-red-700 dark:text-red-400">Na zobrazenie žiadostí je potrebný JavaScript.</p></noscript>
            <p x-cloak x-show="loading" role="status" class="text-sm text-gray-600 dark:text-gray-400">Načítavajú sa žiadosti…</p>
            <div x-cloak x-show="error" role="alert" class="text-sm text-red-700 dark:text-red-400">
                <p x-text="error"></p>
                <button type="button" @click="load()" class="mt-2 underline">Skúsiť znova</button>
            </div>

            <div x-cloak x-show="!loading && !error && meta" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Celkom: <span x-text="meta?.total"></span>
                </p>
                <template x-if="applications.length === 0">
                    <x-empty-state title="Žiadne žiadosti" description="Pre zvolený stav sa nenašiel žiadny záznam." />
                </template>
                <template x-for="application in applications" :key="application.id">
                    <x-card>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Žiadosť č. <span x-text="application.id"></span>
                                </p>
                                <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="application.customer ? [application.customer.first_name, application.customer.last_name].filter(Boolean).join(' ') : 'Žiadateľ nedostupný'"></h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="application.customer?.email || 'E-mail nedostupný'"></p>
                            </div>
                            <x-badge><span x-text="statusLabel(application.status)"></span></x-badge>
                        </div>

                        <dl class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Podaná</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="formatDate(application.submitted_at)"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Obdobie prenájmu</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">Údaj nedostupný</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Bicykel</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">Údaj nedostupný</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Platba</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">Údaj nedostupný</dd>
                            </div>
                            <div x-show="application.notes">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Poznámka</dt>
                                <dd class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-gray-100" x-text="application.notes"></dd>
                            </div>
                        </dl>

                        <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <div class="flex flex-wrap gap-3">
                                <x-primary-button type="button" disabled class="cursor-not-allowed opacity-50">Schváliť</x-primary-button>
                                <x-secondary-button type="button" disabled class="cursor-not-allowed opacity-50">Zamietnuť</x-secondary-button>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Schválenie a zamietnutie zatiaľ nie je dostupné.</p>
                        </div>
                    </x-card>
                </template>
                <nav x-show="meta && meta.last_page > 1" aria-label="Stránkovanie žiadostí" class="flex items-center gap-4">
                    <x-secondary-button type="button" @click="load(meta.current_page - 1)" ::disabled="meta?.current_page <= 1">Predchádzajúca</x-secondary-button>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Strana <span x-text="meta?.current_page"></span> z <span x-text="meta?.last_page"></span>
                    </span>
                    <x-secondary-button type="button" @click="load(meta.current_page + 1)" ::disabled="meta?.current_page >= meta?.last_page">Ďalšia</x-secondary-button>
                </nav>
            </div>
        </div>
    </div>
</x-app-layout>
