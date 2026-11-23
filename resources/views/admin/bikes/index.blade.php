<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Bicykle') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Prehľad evidencie bicyklov.') }}
                </p>
            </div>

            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ __('Celkom: :count', ['count' => $bikes->total()]) }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card title="Filtre" description="Hľadajte podľa ID, evidenčného čísla, modelu, sériového čísla alebo statusu.">
                <form method="GET" action="{{ route('admin.bikes.index') }}">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                        <div class="md:col-span-6">
                            <x-input-label for="q" :value="__('Hľadať')" />
                            <x-text-input
                                id="q"
                                name="q"
                                type="search"
                                class="mt-1 block w-full"
                                :value="$search"
                                placeholder="ID, evidenčné číslo, model, sériové číslo..."
                            />
                        </div>

                        <div class="md:col-span-3">
                            <x-input-label for="status" :value="__('Status')" />
                            <select
                                id="status"
                                name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600"
                            >
                                <option value="">{{ __('Všetky statusy') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <x-input-label for="per_page" :value="__('Záznamov na stranu')" />
                            <select
                                id="per_page"
                                name="per_page"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-brand-600 dark:focus:ring-brand-600"
                                onchange="this.form.submit()"
                            >
                                @foreach ($perPageOptions as $option)
                                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-wrap gap-3 md:col-span-12">
                            <x-primary-button>
                                {{ __('Hľadať') }}
                            </x-primary-button>

                            <a
                                href="{{ route('admin.bikes.index') }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                {{ __('Zrušiť filtre') }}
                            </a>
                        </div>
                    </div>
                </form>
            </x-card>

            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if ($bikes->total() > 0)
                        {{ __('Zobrazuje sa :from–:to z :total záznamov.', [
                            'from' => $bikes->firstItem(),
                            'to' => $bikes->lastItem(),
                            'total' => $bikes->total(),
                        ]) }}
                    @else
                        {{ __('Žiadne záznamy.') }}
                    @endif
                </p>
            </div>

            <div class="space-y-4">
                @forelse ($bikes as $bike)
                    <x-admin.bike-card :bike="$bike" />
                @empty
                    <x-empty-state
                        title="Žiadne bicykle"
                        description="Pre zadané filtre sa nenašiel žiadny záznam."
                    />
                @endforelse
            </div>

            @if ($bikes->hasPages())
                <div>
                    {{ $bikes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
