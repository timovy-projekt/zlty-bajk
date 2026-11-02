

<x-app-layout-layout-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Customer dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p>{{ __('Welcome, :name.', ['name' => auth()->user()->name]) }}</p>
                    <a href="{{ route('profile.edit') }}" class="inline-block mt-4 text-sm underline">
                        {{ __('Manage your profile') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout-layout-layout>
