@props(['columns' => 1])

<dl {{ $attributes->merge(['class' => "grid gap-x-8 sm:grid-cols-2"]) }}>
    {{ $slot }}
</dl>
