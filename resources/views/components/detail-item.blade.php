@props(['label', 'value' => '—'])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1 border-b border-gray-100 py-3 first:pt-0 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6 dark:border-gray-700']) }}>
    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
        {{$label}}
    </dt>

    <dd class="text-sm text-gray-900 dark:text-gray-100 sm:text-end">
        @if ($slot->isEmpty())
        {{$value}}
        @else
            {{ $slot }}
        @endif
    </dd>
</div>
