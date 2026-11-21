<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Žiadosti o prenájom') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Prehľad žiadostí od všetkých zákazníkov.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-rental-applications.list :endpoint="route('api.v1.admin.rental-applications.index')" :admin="true" />
        </div>
    </div>
</x-app-layout>
