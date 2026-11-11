@props(['bike'])

@php
    $statusMeta = match ($bike->status) {
        'ready' => ['type' => 'success', 'label' => 'Pripravený'],
        'maintenance' => ['type' => 'warning', 'label' => 'V servise'],
        'retired' => ['type' => 'neutral', 'label' => 'Vyradený'],
        'lost' => ['type' => 'danger', 'label' => 'Stratený'],
        default => ['type' => 'neutral', 'label' => $bike->status],
    };
@endphp

<x-card>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('Bicykel #:id', ['id' => $bike->id]) }}
            </p>

            <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $bike->inventory_number }}
            </h3>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $bike->model ?: __('Model neuvedený') }}
            </p>
        </div>

        <x-badge :type="$statusMeta['type']">
            {{ $statusMeta['label'] }}
        </x-badge>
    </div>

    <dl class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 dark:border-gray-700 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ID') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">#{{ $bike->id }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Evidenčné číslo') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $bike->inventory_number }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Sériové číslo') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $bike->serial_number ?: '—' }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Model') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $bike->model ?: '—' }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Vytvorené') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ \Illuminate\Support\Carbon::parse($bike->created_at)->format('d.m.Y H:i') }}
            </dd>
        </div>

        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Aktualizované') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {{ \Illuminate\Support\Carbon::parse($bike->updated_at)->format('d.m.Y H:i') }}
            </dd>
        </div>
    </dl>

    @if (filled($bike->notes))
        <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Poznámka') }}</p>
            <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-gray-100">{{ $bike->notes }}</p>
        </div>
    @endif
</x-card>
