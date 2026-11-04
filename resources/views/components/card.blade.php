@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg']) }}>
    @if ($title || $description || isset($actions))
        <header class="flex flex-col gap-3 border-b border-gray-100 dark:border-gray-700 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">
                        {{ $title }}
                    </h3>
                @endif

                @if ($description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="shrink-0">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    <div class="px-6 py-5">
        {{ $slot }}
    </div>
</section>
