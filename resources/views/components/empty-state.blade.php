@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-gray-300 px-6 py-10 text-center dark:border-gray-600']) }}>
    @isset($icon)
        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
            {{ $icon }}
        </div>
    @endisset

    <p @class(['mt-3' => isset($icon), 'text-sm font-medium text-gray-900 dark:text-gray-100'])>
        {{ $title }}
    </p>

    @if ($description)
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ $description }}
        </p>
    @endif

    {{ $slot }}
</div>
