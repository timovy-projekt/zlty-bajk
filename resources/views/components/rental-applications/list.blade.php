@props(['endpoint', 'admin' => false])

@php
    $statuses = [
        'submitted' => 'Podaná',
        'approved' => 'Schválená',
        'rejected' => 'Zamietnutá',
        'cancelled' => 'Zrušená',
    ];

    $selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600';
    $labelClass = 'text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $valueClass = 'mt-1 text-sm text-gray-900 dark:text-gray-100';
@endphp

<div x-data="rentalApplications($el.dataset.endpoint)" data-endpoint="{{ $endpoint }}" {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <x-card title="Filtre" description="Filtrujte žiadosti podľa stavu.">
        <form @submit.prevent="load(1)">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                <div class="md:col-span-6">
                    <x-input-label for="rental_applications_status" :value="__('Stav žiadosti')" />
                    <select id="rental_applications_status" x-model="status" @change="load(1)" class="{{ $selectClass }}">
                        <option value="">{{ __('Všetky stavy') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <x-input-label for="rental_applications_per_page" :value="__('Záznamov na stranu')" />
                    <select id="rental_applications_per_page" x-model.number="perPage" @change="load(1)" class="{{ $selectClass }}">
                        @foreach ([10, 20, 50, 100] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-3 md:col-span-3">
                    <button
                        type="button"
                        @click="resetFilters()"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        {{ __('Zrušiť filtre') }}
                    </button>
                </div>
            </div>
        </form>
    </x-card>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        <span x-show="loading">{{ __('Načítavam žiadosti…') }}</span>
        <span x-show="!loading && total > 0" x-text="`Zobrazuje sa ${meta?.from ?? 0}–${meta?.to ?? 0} z ${total} záznamov.`"></span>
        <span x-show="!loading && !error && total === 0">{{ __('Žiadne záznamy.') }}</span>
    </p>

    <div
        x-show="error"
        x-cloak
        class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
    >
        <span x-text="error"></span>
        <button type="button" @click="load(currentPage)" class="ms-2 font-semibold underline">{{ __('Skúsiť znova') }}</button>
    </div>

    <div class="space-y-4" :class="{ 'opacity-50': loading }">
        <template x-for="application in applications" :key="application.id">
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="{{ $labelClass }}" x-text="`Žiadosť #${application.id}`"></p>

                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="bikeLabel(application) === '—' ? 'Bicykel zatiaľ nepridelený' : bikeLabel(application)"></h3>

                        @if ($admin)
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="customerLabel(application)"></span>
                                <template x-if="application.customer?.email">
                                    <a :href="`mailto:${application.customer.email}`" class="ms-1 text-brand-600 hover:text-brand-500 dark:text-brand-500" x-text="`(${application.customer.email})`"></a>
                                </template>
                            </p>
                        @endif
                    </div>

                    <div>
                        <x-badge type="info" x-show="application.status === 'submitted'">Podaná</x-badge>
                        <x-badge type="success" x-show="application.status === 'approved'">Schválená</x-badge>
                        <x-badge type="danger" x-show="application.status === 'rejected'">Zamietnutá</x-badge>
                        <x-badge type="neutral" x-show="application.status === 'cancelled'">Zrušená</x-badge>
                    </div>
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="{{ $labelClass }}">{{ __('Podaná') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="formatDate(application.submitted_at, true)"></dd>
                    </div>

                    <div>
                        <dt class="{{ $labelClass }}">{{ __('Vybavená') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="formatDate(application.reviewed_at, true)"></dd>
                    </div>

                    <div>
                        <dt class="{{ $labelClass }}">{{ __('Termín prenájmu') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="termLabel(application)"></dd>
                    </div>

                    <div>
                        <dt class="{{ $labelClass }}">{{ __('Bicykel') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="bikeLabel(application)"></dd>
                    </div>

                    <div>
                        <dt class="{{ $labelClass }}">{{ __('Úhrada') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="paymentLabel(application)"></dd>
                    </div>

                    <div x-show="application.rental?.payment?.variable_symbol">
                        <dt class="{{ $labelClass }}">{{ __('Variabilný symbol') }}</dt>
                        <dd class="{{ $valueClass }}" x-text="application.rental?.payment?.variable_symbol ?? '—'"></dd>
                    </div>
                </dl>

                @if ($admin)
                    <div x-show="application.notes" class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <p class="{{ $labelClass }}">{{ __('Poznámka') }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-gray-100" x-text="application.notes"></p>
                    </div>
                @endif
            </x-card>
        </template>

        <div x-show="!loading && !error && applications.length === 0" x-cloak>
            <x-empty-state
                title="Žiadne žiadosti"
                :description="$admin ? 'Pre zadané filtre sa nenašla žiadna žiadosť.' : 'Zatiaľ ste nepodali žiadnu žiadosť o prenájom.'"
            />
        </div>
    </div>

    <nav x-show="lastPage > 1" x-cloak class="flex items-center justify-between" aria-label="Stránkovanie">
        <button
            type="button"
            @click="load(currentPage - 1)"
            :disabled="currentPage <= 1 || loading"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            {{ __('« Predchádzajúca') }}
        </button>

        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="`Strana ${currentPage} z ${lastPage}`"></span>

        <button
            type="button"
            @click="load(currentPage + 1)"
            :disabled="currentPage >= lastPage || loading"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            {{ __('Ďalšia »') }}
        </button>
    </nav>
</div>
