<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-brand-500 border border-transparent rounded-md font-semibold text-xs text-brand-500 dark:text-gray-900 uppercase tracking-widest hover:bg-black dark:hover:bg-brand-400 focus:bg-black dark:focus:bg-brand-400 active:bg-black dark:active:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
